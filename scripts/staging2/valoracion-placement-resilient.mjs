import { chromium } from 'playwright';
import fs from 'node:fs/promises';
import fsSync from 'node:fs';
import path from 'node:path';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedSha = (process.env.EXPECTED_SHA || '').trim();
const valuationUrl = `${baseUrl}/madrid/valoracion/`;
const expectedFormId = '5042522a-0bc5-4381-ac3e-5aee8649b69c';
const expectedPortalId = '147416356';
const transientStatuses = new Set([202, 429, 503]);
const captchaPath = '/.well-known/sgcaptcha/';
const transientExitCode = 75;
const maxAttempts = 5;
const viewports = [
  { key: 'desktop', width: 1440, height: 1100 },
  { key: 'tablet', width: 1024, height: 768 },
  { key: 'mobile', width: 390, height: 844 },
];

if (!/^[0-9a-f]{40}$/.test(expectedSha)) {
  throw new TypeError('EXPECTED_SHA must be a full lowercase 40-character SHA');
}

const outDir = path.resolve('scripts/staging2/valoracion-artifacts');
await fs.rm(outDir, { recursive: true, force: true });
await fs.mkdir(outDir, { recursive: true });

function isCaptchaInterruption(error, currentUrl) {
  const message = error instanceof Error ? error.message : String(error || '');
  return currentUrl.includes(captchaPath) || (/interrupted by another navigation/i.test(message) && message.includes(captchaPath));
}

async function visibleControlsInHubSpotFrame(page, embeddedSrc) {
  const deadline = Date.now() + 12000;
  while (Date.now() < deadline) {
    for (const frame of page.frames()) {
      if (frame === page.mainFrame()) continue;
      const frameUrl = frame.url() || '';
      if (!frameUrl.includes(expectedFormId) && (!embeddedSrc || frameUrl !== embeddedSrc)) continue;
      const controls = frame.locator('input:not([type="hidden"]), textarea, select, button, [role="button"]');
      const count = await controls.count().catch(() => 0);
      for (let index = 0; index < Math.min(count, 40); index += 1) {
        if (await controls.nth(index).isVisible().catch(() => false)) return { frameFound: true, visibleControls: 1 };
      }
      return { frameFound: true, visibleControls: 0 };
    }
    await page.waitForTimeout(600);
  }
  return { frameFound: false, visibleControls: 0 };
}

async function validateAttempt(browser, viewport, attempt) {
  const context = await browser.newContext({
    viewport: { width: viewport.width, height: viewport.height },
    ignoreHTTPSErrors: true,
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/151 Safari/537.36 NUVANX-Valoracion-QA/2.0',
  });
  const page = await context.newPage();
  const issues = [];
  let response = null;

  try {
    try {
      response = await page.goto(valuationUrl, { waitUntil: 'domcontentloaded', timeout: 40000 });
    } catch (error) {
      if (isCaptchaInterruption(error, page.url())) {
        return { transient: true, reason: error.message, status: 0, currentUrl: page.url() };
      }
      throw error;
    }

    const headers = response ? await response.allHeaders() : {};
    const status = response?.status() || 0;
    if (transientStatuses.has(status) || headers['sg-captcha'] || page.url().includes(captchaPath)) {
      return { transient: true, reason: `SiteGround challenge HTTP ${status}`, status, currentUrl: page.url() };
    }
    if (!response) issues.push('Valuation navigation returned no HTTP response');
    else if (status !== 200) issues.push(`Expected HTTP 200, got ${status}`);

    const metaSha = (await page.locator('meta[name="nvx-deploy-sha"]').getAttribute('content').catch(() => '')) || '';
    if (metaSha !== expectedSha) issues.push(`SHA mismatch ${metaSha || 'missing'} != ${expectedSha}`);

    const placement = await page.evaluate(() => {
      const visible = (element) => {
        if (!element) return false;
        const style = getComputedStyle(element);
        const rect = element.getBoundingClientRect();
        return style.display !== 'none' && style.visibility !== 'hidden' && rect.width > 1 && rect.height > 1;
      };
      const header = document.querySelector('header, .nvx-header, .nvx-site-header');
      const root = document.getElementById('nvx-valoracion-main');
      const hero = root?.querySelector(':scope > .nvx-valoracion-hero, :scope > .nvx-brand-hero');
      const form = document.getElementById('nvx-hubspot-form');
      const frame = form?.querySelector('.hs-form-frame[data-nvx-hubspot-lazy="1"]');
      const heroRect = hero?.getBoundingClientRect();
      const formRect = form?.getBoundingClientRect();
      return {
        headerVisible: visible(header),
        heroVisible: visible(hero),
        formVisible: visible(form),
        frameExists: Boolean(frame),
        adjacent: Boolean(hero && form && hero.nextElementSibling === form),
        heroBottom: heroRect ? Math.round(heroRect.bottom) : null,
        formTop: formRect ? Math.round(formRect.top) : null,
      };
    });

    if (!placement.headerVisible) issues.push('Header/menu is not visible');
    if (!placement.heroVisible) issues.push('Valuation heading block is not visible');
    if (!placement.formVisible) issues.push('Form section is not visible');
    if (!placement.frameExists) issues.push('HubSpot page frame is missing');
    if (!placement.adjacent) issues.push('Form section is not the immediate sibling after the page heading');
    if (placement.formTop !== null && placement.heroBottom !== null && placement.formTop < placement.heroBottom - 2) {
      issues.push('Form overlaps the page heading');
    }

    await page.waitForLoadState('load').catch(() => {});
    await page.locator('#nvx-hubspot-form').dispatchEvent('focusin').catch(() => {});

    const mountedSelector = [
      '#nvx-hubspot-form .hs-form-frame[data-hs-forms-root="true"] iframe[data-test-id^="embedded-form-"]',
      '#nvx-hubspot-form .hbspt-form',
      '#nvx-hubspot-form form.hs-form',
    ].join(', ');
    const mounted = await page.locator(mountedSelector).first().waitFor({ state: 'attached', timeout: 12000 }).then(() => true).catch(() => false);
    if (!mounted) issues.push('HubSpot form did not mount inside #nvx-hubspot-form within 12s');

    const mountState = await page.evaluate(({ formId, portalId }) => {
      const section = document.getElementById('nvx-hubspot-form');
      const embedded = section?.querySelector('.hs-form-frame[data-hs-forms-root="true"] iframe[data-test-id^="embedded-form-"]') || null;
      const src = embedded?.getAttribute('src') || '';
      const testId = embedded?.getAttribute('data-test-id') || '';
      const rogueLegacy = Array.from(document.querySelectorAll('.hbspt-form, form.hs-form')).filter((el) => !section?.contains(el)).length;
      const rogueIframes = Array.from(document.querySelectorAll('iframe[data-test-id^="embedded-form-"]')).filter((el) => !section?.contains(el)).length;
      return {
        embedded: Boolean(embedded),
        src,
        expectedIdentity: Boolean(embedded && src.includes(`_hsPortalId=${portalId}`) && src.includes(`_hsFormId=${formId}`) && testId.includes(formId)),
        rogueMounts: rogueLegacy + rogueIframes,
      };
    }, { formId: expectedFormId, portalId: expectedPortalId });

    if (mountState.embedded && !mountState.expectedIdentity) issues.push('HubSpot iframe mounted with an unexpected portal/form identity');
    if (mountState.rogueMounts > 0) issues.push(`Found ${mountState.rogueMounts} HubSpot form mount(s) outside #nvx-hubspot-form`);

    if (mountState.embedded) {
      const interactive = await visibleControlsInHubSpotFrame(page, mountState.src);
      if (!interactive.frameFound) issues.push('HubSpot iframe element exists but its document frame was not reachable');
      else if (interactive.visibleControls < 1) issues.push('HubSpot iframe has no visible interactive controls');
    } else if (mounted) {
      const visibleLegacy = await page.locator('#nvx-hubspot-form input:not([type="hidden"]):visible, #nvx-hubspot-form textarea:visible, #nvx-hubspot-form select:visible, #nvx-hubspot-form button:visible').count().catch(() => 0);
      if (visibleLegacy < 1) issues.push('Legacy HubSpot form mounted without visible interactive controls');
    }

    await page.screenshot({ path: path.join(outDir, `valoracion-${viewport.key}-attempt-${attempt}.jpg`), type: 'jpeg', quality: 78, fullPage: true });
    return { transient: false, status, currentUrl: page.url(), placement, mountState, issues };
  } finally {
    await context.close();
  }
}

const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });
const results = [];
let realFailure = false;
let transientExhausted = false;

try {
  for (const viewport of viewports) {
    let finalResult = null;
    for (let attempt = 1; attempt <= maxAttempts; attempt += 1) {
      console.log(`VALORACION_ATTEMPT viewport=${viewport.key} attempt=${attempt}/${maxAttempts}`);
      const result = await validateAttempt(browser, viewport, attempt);
      finalResult = { viewport, attempt, ...result };
      if (!result.transient) break;
      console.warn(`VALORACION_TRANSIENT viewport=${viewport.key} attempt=${attempt} reason=${result.reason}`);
      if (attempt < maxAttempts) await new Promise((resolve) => setTimeout(resolve, 2500 * attempt));
    }

    results.push(finalResult);
    if (finalResult.transient) {
      transientExhausted = true;
      console.error(`VALORACION_PLACEMENT=TRANSIENT_EXHAUSTED viewport=${viewport.key} attempts=${maxAttempts}`);
    } else if (finalResult.issues.length > 0) {
      realFailure = true;
      console.error(`FIX /madrid/valoracion/ ${viewport.width}x${viewport.height}`);
      finalResult.issues.forEach((issue) => console.error(`  ${issue}`));
    } else {
      console.log(`PASS /madrid/valoracion/ ${viewport.width}x${viewport.height}`);
    }
  }
} finally {
  await browser.close();
}

await fs.writeFile(path.join(outDir, 'results.json'), `${JSON.stringify(results, null, 2)}\n`, 'utf8');

if (realFailure) {
  console.error('VALORACION_PLACEMENT=FAIL_REAL');
  process.exit(1);
}
if (transientExhausted) {
  if (process.env.GITHUB_ENV) {
    fsSync.appendFileSync(process.env.GITHUB_ENV, 'STAGING_MUTATION_ARMED=0\nSTAGING_ACCEPTANCE_TRANSIENT=1\n', 'utf8');
  }
  console.error('VALORACION_PLACEMENT=TRANSIENT_ONLY');
  process.exit(transientExitCode);
}

console.log('VALORACION_INTERACTIVITY=PASS');
console.log('VALORACION_PLACEMENT=PASS');
