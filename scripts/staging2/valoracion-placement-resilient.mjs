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
const mountedSelector = [
  '#nvx-hubspot-form .hs-form-frame[data-hs-forms-root="true"] iframe[data-test-id^="embedded-form-"]',
  '#nvx-hubspot-form .hbspt-form',
  '#nvx-hubspot-form form.hs-form',
].join(', ');

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

function isMatchingHubSpotFrame(frame, page, embeddedSrc) {
  if (frame === page.mainFrame()) return false;
  const frameUrl = frame.url() || '';
  return frameUrl.includes(expectedFormId) || Boolean(embeddedSrc && frameUrl === embeddedSrc);
}

async function frameHasVisibleControl(frame) {
  const controls = frame.locator('input:not([type="hidden"]), textarea, select, button, [role="button"]');
  const count = Math.min(await controls.count().catch(() => 0), 40);
  for (let index = 0; index < count; index += 1) {
    if (await controls.nth(index).isVisible().catch(() => false)) return true;
  }
  return false;
}

async function visibleControlsInHubSpotFrame(page, embeddedSrc) {
  const deadline = Date.now() + 12000;
  while (Date.now() < deadline) {
    const frames = page.frames().filter((frame) => isMatchingHubSpotFrame(frame, page, embeddedSrc));
    for (const frame of frames) {
      if (await frameHasVisibleControl(frame)) return { frameFound: true, visibleControls: 1 };
    }
    if (frames.length > 0) return { frameFound: true, visibleControls: 0 };
    await page.waitForTimeout(600);
  }
  return { frameFound: false, visibleControls: 0 };
}

async function navigateValuation(page) {
  try {
    const response = await page.goto(valuationUrl, { waitUntil: 'domcontentloaded', timeout: 40000 });
    const headers = response ? await response.allHeaders() : {};
    const status = response?.status() || 0;
    const transient = transientStatuses.has(status) || Boolean(headers['sg-captcha']) || page.url().includes(captchaPath);
    return {
      response,
      status,
      transient,
      reason: transient ? `SiteGround challenge HTTP ${status}` : '',
      currentUrl: page.url(),
    };
  } catch (error) {
    if (!isCaptchaInterruption(error, page.url())) throw error;
    return {
      response: null,
      status: 0,
      transient: true,
      reason: error instanceof Error ? error.message : String(error),
      currentUrl: page.url(),
    };
  }
}

async function collectPlacement(page) {
  return page.evaluate(() => {
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
}

function validatePlacement(placement) {
  const issues = [];
  if (!placement.headerVisible) issues.push('Header/menu is not visible');
  if (!placement.heroVisible) issues.push('Valuation heading block is not visible');
  if (!placement.formVisible) issues.push('Form section is not visible');
  if (!placement.frameExists) issues.push('HubSpot page frame is missing');
  if (!placement.adjacent) issues.push('Form section is not the immediate sibling after the page heading');
  if (placement.formTop !== null && placement.heroBottom !== null && placement.formTop < placement.heroBottom - 2) {
    issues.push('Form overlaps the page heading');
  }
  return issues;
}

async function collectMountState(page) {
  return page.evaluate(({ formId, portalId }) => {
    const section = document.getElementById('nvx-hubspot-form');
    const embedded = section?.querySelector('.hs-form-frame[data-hs-forms-root="true"] iframe[data-test-id^="embedded-form-"]') || null;
    const src = embedded?.src || '';
    const testId = embedded?.dataset.testId || '';
    const rogueLegacy = Array.from(document.querySelectorAll('.hbspt-form, form.hs-form')).filter((element) => !section?.contains(element)).length;
    const rogueIframes = Array.from(document.querySelectorAll('iframe[data-test-id^="embedded-form-"]')).filter((element) => !section?.contains(element)).length;
    return {
      embedded: Boolean(embedded),
      src,
      expectedIdentity: Boolean(embedded && src.includes(`_hsPortalId=${portalId}`) && src.includes(`_hsFormId=${formId}`) && testId.includes(formId)),
      rogueMounts: rogueLegacy + rogueIframes,
    };
  }, { formId: expectedFormId, portalId: expectedPortalId });
}

async function validateHubSpotMount(page, mounted, mountState) {
  const issues = [];
  if (!mounted) issues.push('HubSpot form did not mount inside #nvx-hubspot-form within 12s');
  if (mountState.embedded && !mountState.expectedIdentity) issues.push('HubSpot iframe mounted with an unexpected portal/form identity');
  if (mountState.rogueMounts > 0) issues.push(`Found ${mountState.rogueMounts} HubSpot form mount(s) outside #nvx-hubspot-form`);

  if (mountState.embedded) {
    const interactive = await visibleControlsInHubSpotFrame(page, mountState.src);
    if (!interactive.frameFound) issues.push('HubSpot iframe element exists but its document frame was not reachable');
    else if (interactive.visibleControls < 1) issues.push('HubSpot iframe has no visible interactive controls');
    return issues;
  }

  if (mounted) {
    const visibleLegacy = await page.locator('#nvx-hubspot-form input:not([type="hidden"]):visible, #nvx-hubspot-form textarea:visible, #nvx-hubspot-form select:visible, #nvx-hubspot-form button:visible').count().catch(() => 0);
    if (visibleLegacy < 1) issues.push('Legacy HubSpot form mounted without visible interactive controls');
  }
  return issues;
}

async function validateAttempt(browser, viewport, attempt) {
  const context = await browser.newContext({
    viewport: { width: viewport.width, height: viewport.height },
    ignoreHTTPSErrors: true,
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/151 Safari/537.36 NUVANX-Valoracion-QA/2.0',
  });
  const page = await context.newPage();

  try {
    const navigation = await navigateValuation(page);
    if (navigation.transient) return navigation;

    const issues = [];
    if (!navigation.response) issues.push('Valuation navigation returned no HTTP response');
    else if (navigation.status !== 200) issues.push(`Expected HTTP 200, got ${navigation.status}`);

    const metaSha = (await page.locator('meta[name="nvx-deploy-sha"]').getAttribute('content').catch(() => '')) || '';
    if (metaSha !== expectedSha) issues.push(`SHA mismatch ${metaSha || 'missing'} != ${expectedSha}`);

    const placement = await collectPlacement(page);
    issues.push(...validatePlacement(placement));

    await page.waitForLoadState('load').catch(() => {});
    await page.locator('#nvx-hubspot-form').dispatchEvent('focusin').catch(() => {});
    const mounted = await page.locator(mountedSelector).first().waitFor({ state: 'attached', timeout: 12000 }).then(() => true).catch(() => false);
    const mountState = await collectMountState(page);
    issues.push(...await validateHubSpotMount(page, mounted, mountState));

    await page.screenshot({ path: path.join(outDir, `valoracion-${viewport.key}-attempt-${attempt}.jpg`), type: 'jpeg', quality: 78, fullPage: true });
    return {
      transient: false,
      status: navigation.status,
      currentUrl: navigation.currentUrl,
      placement,
      mountState,
      issues,
    };
  } finally {
    await context.close();
  }
}

async function runViewport(browser, viewport) {
  let finalResult = null;
  for (let attempt = 1; attempt <= maxAttempts; attempt += 1) {
    console.log(`VALORACION_ATTEMPT viewport=${viewport.key} attempt=${attempt}/${maxAttempts}`);
    const result = await validateAttempt(browser, viewport, attempt);
    finalResult = { viewport, attempt, ...result };
    if (!result.transient) break;
    console.warn(`VALORACION_TRANSIENT viewport=${viewport.key} attempt=${attempt} reason=${result.reason}`);
    if (attempt < maxAttempts) await new Promise((resolve) => setTimeout(resolve, 2500 * attempt));
  }
  return finalResult;
}

function reportViewport(result) {
  if (result.transient) {
    console.error(`VALORACION_PLACEMENT=TRANSIENT_EXHAUSTED viewport=${result.viewport.key} attempts=${maxAttempts}`);
    return 'transient';
  }
  if (result.issues.length > 0) {
    console.error(`FIX /madrid/valoracion/ ${result.viewport.width}x${result.viewport.height}`);
    result.issues.forEach((issue) => console.error(`  ${issue}`));
    return 'real';
  }
  console.log(`PASS /madrid/valoracion/ ${result.viewport.width}x${result.viewport.height}`);
  return 'pass';
}

const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });
const results = [];
let realFailure = false;
let transientExhausted = false;

try {
  for (const viewport of viewports) {
    const result = await runViewport(browser, viewport);
    results.push(result);
    const classification = reportViewport(result);
    realFailure ||= classification === 'real';
    transientExhausted ||= classification === 'transient';
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