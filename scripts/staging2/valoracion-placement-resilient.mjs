import { chromium } from 'playwright';
import fs from 'node:fs/promises';
import path from 'node:path';
import {
  SITEGROUND_CAPTCHA_PATH,
  SITEGROUND_TRANSIENT_HTTP_STATUSES,
  EX_TEMPFAIL,
  isSiteGroundCaptchaInterruption,
  isSiteGroundTransientResponse,
} from './siteground-transient-classifier.mjs';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedSha = (process.env.EXPECTED_SHA || '').trim();
const valuationUrl = `${baseUrl}/madrid/valoracion/`;
const expectedFormId = '5042522a-0bc5-4381-ac3e-5aee8649b69c';
const expectedPortalId = '147416356';
const transientExitCode = EX_TEMPFAIL;
const maxAttempts = 5;

if (!/^[0-9a-f]{40}$/.test(expectedSha)) {
  throw new TypeError('EXPECTED_SHA must be a full lowercase 40-character SHA');
}

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

const legacyControlsSelector = [
  '#nvx-hubspot-form .hbspt-form input:not([type="hidden"])',
  '#nvx-hubspot-form .hbspt-form textarea',
  '#nvx-hubspot-form .hbspt-form select',
  '#nvx-hubspot-form .hbspt-form button',
  '#nvx-hubspot-form form.hs-form input:not([type="hidden"])',
  '#nvx-hubspot-form form.hs-form textarea',
  '#nvx-hubspot-form form.hs-form select',
  '#nvx-hubspot-form form.hs-form button',
].join(', ');

const outDir = path.resolve('scripts/staging2/valoracion-artifacts');
await fs.rm(outDir, { recursive: true, force: true });
await fs.mkdir(outDir, { recursive: true });

function formatTransientReason(status, headers, currentUrl) {
  const reasons = [];
  if (SITEGROUND_TRANSIENT_HTTP_STATUSES.has(Number(status || 0))) {
    reasons.push(`HTTP status ${status}`);
  }
  if (headers && headers['sg-captcha']) {
    reasons.push(`sg-captcha header (${headers['sg-captcha']})`);
  }
  if (String(currentUrl).includes(SITEGROUND_CAPTCHA_PATH)) {
    reasons.push(`captcha URL path (${SITEGROUND_CAPTCHA_PATH})`);
  }
  return reasons.length > 0 ? `SiteGround challenge detected via: ${reasons.join(', ')}` : `SiteGround challenge HTTP ${status}`;
}

function isMatchingHubSpotFrame(frame, page, embeddedSrc) {
  if (frame === page.mainFrame()) return false;
  const frameUrl = frame.url() || '';
  return frameUrl.includes(expectedFormId) || Boolean(embeddedSrc && frameUrl === embeddedSrc);
}

async function inspectHubSpotInteractivity(page, embeddedSrc) {
  const state = {
    frameFound: false,
    frameUrl: '',
    controls: 0,
    visibleControls: 0,
  };

  const deadline = Date.now() + 12000;
  while (Date.now() < deadline) {
    const candidateFrames = page.frames().filter((frame) => isMatchingHubSpotFrame(frame, page, embeddedSrc));

    for (const frame of candidateFrames) {
      state.frameFound = true;
      state.frameUrl = frame.url() || '';

      const controls = frame.locator('input:not([type="hidden"]), textarea, select, button, [role="button"]');
      const count = await controls.count().catch(() => 0);
      let visibleControls = 0;
      for (let index = 0; index < Math.min(count, 40); index += 1) {
        if (await controls.nth(index).isVisible().catch(() => false)) visibleControls += 1;
      }

      state.controls = Math.max(state.controls, count);
      state.visibleControls = Math.max(state.visibleControls, visibleControls);

      if (visibleControls > 0) return state;
    }

    await page.waitForTimeout(600).catch(() => {});
  }

  return state;
}

async function inspectLegacyControls(page) {
  const controls = page.locator(legacyControlsSelector);
  const count = await controls.count().catch(() => 0);
  let visibleControls = 0;
  for (let index = 0; index < Math.min(count, 40); index += 1) {
    if (await controls.nth(index).isVisible().catch(() => false)) visibleControls += 1;
  }
  return { controls: count, visibleControls };
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
    const embeddedSrc = embedded?.getAttribute('src') || '';
    const embeddedTestId = embedded?.getAttribute('data-test-id') || '';
    const embeddedRect = embedded?.getBoundingClientRect();
    const rogueLegacy = Array.from(document.querySelectorAll('.hbspt-form, form.hs-form')).filter((el) => !section?.contains(el)).length;
    const rogueIframes = Array.from(document.querySelectorAll('iframe[data-test-id^="embedded-form-"]')).filter((el) => !section?.contains(el)).length;
    return {
      embedded: Boolean(embedded),
      embeddedSrc,
      embeddedTestId,
      embeddedWidth: embeddedRect ? Math.round(embeddedRect.width) : 0,
      embeddedHeight: embeddedRect ? Math.round(embeddedRect.height) : 0,
      expectedIdentity: Boolean(
        embedded &&
        embeddedSrc.includes(`_hsPortalId=${portalId}`) &&
        embeddedSrc.includes(`_hsFormId=${formId}`) &&
        embeddedTestId.includes(formId)
      ),
      rogueMounts: rogueLegacy + rogueIframes,
    };
  }, { formId: expectedFormId, portalId: expectedPortalId });
}

async function validateHubSpotMount(page, mounted, mountState) {
  const issues = [];
  let interactiveState = {
    frameFound: false,
    frameUrl: '',
    controls: 0,
    visibleControls: 0,
  };

  if (!mounted) issues.push('HubSpot form did not mount inside #nvx-hubspot-form within 12s');
  if (mountState.embedded && !mountState.expectedIdentity) issues.push('HubSpot iframe mounted with an unexpected portal/form identity');
  if (mountState.rogueMounts > 0) issues.push(`Found ${mountState.rogueMounts} HubSpot form mount(s) outside #nvx-hubspot-form`);

  if (mountState.embedded) {
    interactiveState = await inspectHubSpotInteractivity(page, mountState.embeddedSrc);
    if (!interactiveState.frameFound) {
      issues.push('HubSpot iframe element exists but its document frame was not reachable');
    } else if (interactiveState.visibleControls < 1) {
      issues.push(`HubSpot iframe has no visible interactive controls (controls=${interactiveState.controls})`);
    }
  } else if (mounted) {
    interactiveState = await inspectLegacyControls(page);
    if (interactiveState.visibleControls < 1) {
      issues.push(`Legacy HubSpot form mounted without visible interactive controls (controls=${interactiveState.controls})`);
    }
  }

  return { issues, interactiveState };
}

async function saveScreenshot(page, viewportKey, attempt, isTransient = false) {
  const filename = isTransient
    ? `valoracion-${viewportKey}-attempt-${attempt}-transient.jpg`
    : `valoracion-${viewportKey}-attempt-${attempt}.jpg`;
  const options = {
    path: path.join(outDir, filename),
    type: 'jpeg',
    quality: 78,
    fullPage: true,
  };
  if (isTransient) {
    await page.screenshot(options).catch(() => {});
  } else {
    await page.screenshot(options);
  }
}

function createTransientResult(status, currentUrl, reason, placement = null, mounted = false, mountState = null, interactiveState = null) {
  return {
    transient: true,
    status,
    currentUrl,
    reason,
    placement,
    mounted,
    mountState,
    interactiveState,
    issues: [reason],
  };
}

async function validateAttempt(context, viewport, attempt) {
  const page = await context.newPage();

  try {
    let response = null;
    let navError = null;

    try {
      response = await page.goto(valuationUrl, { waitUntil: 'domcontentloaded', timeout: 40000 });
    } catch (error) {
      navError = error;
    }

    const currentUrl = page.url() || '';

    if (navError) {
      const navMessage = navError instanceof Error ? navError.message : String(navError);
      if (isSiteGroundCaptchaInterruption(navError, currentUrl)) {
        await saveScreenshot(page, viewport.key, attempt, true);
        return createTransientResult(0, currentUrl, `Captcha interruption: ${navMessage}`);
      }

      await saveScreenshot(page, viewport.key, attempt, false);
      return {
        transient: false,
        status: 0,
        currentUrl,
        reason: navMessage,
        placement: null,
        mounted: false,
        mountState: null,
        interactiveState: null,
        issues: [`Valuation navigation failed: ${navMessage}`],
      };
    }

    const headers = response ? await response.allHeaders() : {};
    const status = response?.status() || 0;
    const isTransientStatus = isSiteGroundTransientResponse(status, headers, currentUrl);

    if (currentUrl.includes(SITEGROUND_CAPTCHA_PATH)) {
      await saveScreenshot(page, viewport.key, attempt, true);
      return createTransientResult(status, currentUrl, `SiteGround captcha challenge URL: ${currentUrl}`);
    }

    const issues = [];
    if (!response) issues.push('Valuation navigation returned no HTTP response');
    else if (status !== 200 && !isTransientStatus) issues.push(`Expected HTTP 200, got ${status}`);

    try {
      const metaSha = (await page.locator('meta[name="nvx-deploy-sha"]').getAttribute('content').catch(() => '')) || '';
      if (metaSha !== expectedSha) issues.push(`SHA mismatch ${metaSha || 'missing'} != ${expectedSha}`);

      await page.evaluate(async () => { if (document.fonts) await document.fonts.ready; }).catch(() => {});
      await page.waitForTimeout(350).catch(() => {});

      const placement = await collectPlacement(page);
      issues.push(...validatePlacement(placement));

      await page.waitForLoadState('load').catch(() => {});
      await page.locator('#nvx-hubspot-form').dispatchEvent('focusin').catch(() => {});
      const mounted = await page.locator(mountedSelector).first().waitFor({ state: 'attached', timeout: 12000 }).then(() => true).catch(() => false);
      const mountState = await collectMountState(page);
      const hubSpotValidation = await validateHubSpotMount(page, mounted, mountState);
      issues.push(...hubSpotValidation.issues);

      const recoveredTransientHttp = Boolean(isTransientStatus && issues.length === 0);

      if (isTransientStatus && !recoveredTransientHttp) {
        await saveScreenshot(page, viewport.key, attempt, true);
        const reason = formatTransientReason(status, headers, page.url());
        return createTransientResult(status, page.url(), reason, placement, mounted, mountState, hubSpotValidation.interactiveState);
      }

      await saveScreenshot(page, viewport.key, attempt, false);

      if (recoveredTransientHttp) {
        console.log(`RECOVERED /madrid/valoracion/ ${viewport.width}x${viewport.height} attempt=${attempt} HTTP ${status} -> exact interactive page`);
      }

      return {
        transient: false,
        status,
        recoveredTransientHttp,
        currentUrl: page.url(),
        reason: '',
        placement,
        mounted,
        mountState,
        interactiveState: hubSpotValidation.interactiveState,
        issues,
      };
    } catch (evalError) {
      if (isSiteGroundCaptchaInterruption(evalError, page.url())) {
        await saveScreenshot(page, viewport.key, attempt, true);
        const evalMessage = evalError instanceof Error ? evalError.message : String(evalError);
        return createTransientResult(status, page.url(), `Captcha redirection during inspection: ${evalMessage}`);
      }
      throw evalError;
    }
  } finally {
    await page.close().catch(() => {});
  }
}

async function runViewport(browser, viewport) {
  const context = await browser.newContext({
    viewport: { width: viewport.width, height: viewport.height },
    ignoreHTTPSErrors: true,
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/151 Safari/537.36 NUVANX-Valoracion-QA/2.0',
  });

  try {
    const attempts = [];
    let finalResult = null;

    for (let attempt = 1; attempt <= maxAttempts; attempt += 1) {
      console.log(`VALORACION_ATTEMPT viewport=${viewport.key} attempt=${attempt}/${maxAttempts}`);
      const result = await validateAttempt(context, viewport, attempt);
      attempts.push({ attempt, ...result });
      finalResult = { viewport, attempt, attempts, ...result };

      if (result.transient) {
        console.warn(`VALORACION_TRANSIENT viewport=${viewport.key} attempt=${attempt} reason=${result.reason}`);
        if (attempt < maxAttempts) {
          const backoff = calculateBackoff(attempt);
          console.log(`VALORACION_BACKOFF viewport=${viewport.key} delay_ms=${backoff}`);
          await delay(backoff);
          continue;
        }
      }

      return finalResult;
    }

    return finalResult;
  } finally {
    await context.close();
  }
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
  await browser.close().catch(() => {});
  try {
    await fs.writeFile(path.join(outDir, 'results.json'), `${JSON.stringify(results, null, 2)}\n`, 'utf8');
  } catch (writeErr) {
    console.error(`Failed to write results.json: ${writeErr instanceof Error ? writeErr.message : String(writeErr)}`);
  }
}

if (realFailure) {
  if (process.env.GITHUB_STEP_SUMMARY) {
    const summary = [
      '',
      '### ❌ Staging Valoración QA — Real Failure',
      '> **One or more viewports failed valuation placement assertions:**',
      ...results
        .filter((r) => r.issues?.length > 0 && !r.transient)
        .flatMap((r) => [
          `- **Viewport:** \`${r.viewport.key}\` (${r.viewport.width}x${r.viewport.height})`,
          ...r.issues.map((issue) => `  - 🔴 ${issue}`),
        ]),
      '',
    ].join('\n');
    await fs.appendFile(process.env.GITHUB_STEP_SUMMARY, summary, 'utf8').catch((err) => {
      const message = err instanceof Error ? err.message : String(err);
      console.warn(`Failed to write GITHUB_STEP_SUMMARY: ${message}`);
    });
  }
  console.error('VALORACION_PLACEMENT=FAIL_REAL');
  process.exit(1);
}

if (transientExhausted) {
  if (process.env.GITHUB_ENV) {
    await fs.appendFile(process.env.GITHUB_ENV, 'STAGING_ACCEPTANCE_TRANSIENT=1\n', 'utf8').catch((err) => {
      const message = err instanceof Error ? err.message : String(err);
      console.warn(`Failed to append to GITHUB_ENV: ${message}`);
    });
  }
  if (process.env.GITHUB_STEP_SUMMARY) {
    const summary = [
      '',
      '### ⚠️ Staging Valoración QA — Transient Exhausted',
      '> **SiteGround challenge / antibot / transient navigation interruptions prevented complete valuation placement verification.**',
      `- **Exit code:** \`${transientExitCode}\``,
      `- **Max attempts:** \`${maxAttempts}\``,
      '- Automatic Staging2 rollback executes and artifacts are preserved.',
      '',
    ].join('\n');
    await fs.appendFile(process.env.GITHUB_STEP_SUMMARY, summary, 'utf8').catch((err) => {
      const message = err instanceof Error ? err.message : String(err);
      console.warn(`Failed to write GITHUB_STEP_SUMMARY: ${message}`);
    });
  }
  console.error('VALORACION_PLACEMENT=TRANSIENT_ONLY');
  process.exit(transientExitCode);
}

console.log('VALORACION_INTERACTIVITY=PASS');
console.log('VALORACION_PLACEMENT=PASS');