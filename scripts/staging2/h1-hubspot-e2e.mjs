import { chromium } from 'playwright';

const base = 'https://nuvanx.com';
const formId = '5042522a-0bc5-4381-ac3e-5aee8649b69c';

// Generate unique per-run QA credentials based on run ID, run attempt or timestamp.
const runAttempt = process.env.GITHUB_RUN_ATTEMPT || '1';
const runId = `${process.env.GITHUB_RUN_ID || Date.now().toString()}-${runAttempt}`;
const gclid = `NUVANX_QA_H1_${runId}`;
const email = `nvxqa-h1-${runId}@example.com`;
const phone = '+34600000000';
const target = `${base}/madrid/valoracion/?gclid=${encodeURIComponent(gclid)}`;
const ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36';
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const sel = (name) => `input[name="${name}"],input[name="0-1/${name}"]`;

const expectedSha = (process.env.EXPECTED_SHA || '').trim();

const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });
try {
  const context = await browser.newContext({ userAgent: ua });
  const page = await context.newPage();
  let lastHttpStatus = 0;
  let reached = false;
  let lastError = null;

  for (let attempt = 1; attempt <= 6; attempt++) {
    try {
      const response = await page.goto(target, { waitUntil: 'domcontentloaded', timeout: 45000 });
      const currentStatus = response?.status() || 0;
      if (currentStatus > 0) {
        lastHttpStatus = currentStatus;
      }
      const currentPath = new URL(page.url()).pathname;
      console.log(`NAV attempt=${attempt} status=${currentStatus} path=${currentPath}`);
      if (currentStatus === 200 && currentPath === '/madrid/valoracion/') {
        reached = true;
        break;
      }
      if (currentStatus === 202) {
        console.log(`Attempt ${attempt}: Antibot challenge (HTTP 202); waiting 10s...`);
        await sleep(10000);
      } else if (currentStatus >= 500 || currentStatus === 429) {
        console.log(`Attempt ${attempt}: Transient server response (HTTP ${currentStatus}); backing off ${3000 * attempt}ms...`);
        await sleep(3000 * attempt);
      } else {
        await sleep(2500);
      }
    } catch (e) {
      lastError = e;
      console.log(`NAV attempt=${attempt} error=${e.message}`);
      await sleep(2500);
    }
  }

  if (!reached) {
    const finalPath = new URL(page.url()).pathname;
    const errDetails = lastError ? `; lastError=${lastError.message}` : '';
    throw new Error(`Production valoración route not reachable at /madrid/valoracion/; lastStatus=${lastHttpStatus}, finalPath=${finalPath}${errDetails}`);
  }

  // Fast-check for deploy SHA meta tag with short 2s timeout to avoid 30s silent stall.
  const metaLocator = page.locator('meta[name="nvx-deploy-sha"]').first();
  const metaCount = await metaLocator.count().catch(() => 0);
  const sha = metaCount > 0 ? (await metaLocator.getAttribute('content', { timeout: 2000 }).catch(() => '')) || '' : '';
  console.log(`PRODUCTION_SHA=${sha || '(none)'}`);
  if (expectedSha && sha !== expectedSha) {
    console.warn(`WARNING: Production SHA mismatch: current=${sha}, expected=${expectedSha}`);
  }

  await page.evaluate(() => {
    if (typeof window.wp_set_consent !== 'function' || typeof window.wp_has_consent !== 'function') {
      throw new TypeError('WordPress consent API unavailable');
    }
    window.wp_set_consent('marketing', 'allow');
    document.dispatchEvent(new Event('wp_listen_for_consent_change'));
    document.dispatchEvent(new Event('wp_consent_type_defined'));
  });
  await sleep(1000);
  const consent = await page.evaluate(() => window.wp_has_consent('marketing'));
  console.log(`MARKETING_CONSENT=${consent}`);
  if (consent !== true) {
    throw new Error('Marketing consent was not granted');
  }

  const host = page.locator('#nvx-hubspot-form');
  await host.scrollIntoViewIfNeeded().catch(() => {});
  await host.dispatchEvent('focusin').catch(() => {});
  await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight / 2)).catch(() => {});

  const getActiveFrame = async () => {
    const iframe = page.locator(`iframe[data-test-id*="${formId}"], #nvx-hubspot-form iframe`).first();
    await iframe.waitFor({ state: 'attached', timeout: 45000 });
    const handle = await iframe.elementHandle();
    const frame = handle ? await handle.contentFrame() : null;
    if (!frame) throw new Error('HubSpot iframe content frame unavailable');
    return frame;
  };

  let frame = await getActiveFrame();
  await frame.locator(sel('email')).first().waitFor({ state: 'attached', timeout: 30000 });

  const names = await frame.locator('input').evaluateAll((nodes) => nodes.map((n) => n.getAttribute('name') || '').filter(Boolean));
  const hasCustom = names.includes('nvx_google_click_id') || names.includes('0-1/nvx_google_click_id');
  const hasNative = names.includes('hs_google_click_id') || names.includes('0-1/hs_google_click_id');
  console.log(`FIELD_CUSTOM_PRESENT=${hasCustom}`);
  console.log(`FIELD_NATIVE_PRESENT=${hasNative}`);
  if (!hasCustom) throw new Error('nvx_google_click_id is not present in published HubSpot form');

  // Re-fire consent lifecycle after the legacy form is registered by onFormReady.
  await page.evaluate(() => {
    document.dispatchEvent(new Event('wp_listen_for_consent_change'));
    document.dispatchEvent(new Event('wp_consent_type_defined'));
  });
  await sleep(500);

  // Re-resolve the frame handle after consent lifecycle re-fire to prevent stale execution context.
  frame = await getActiveFrame();

  async function waitForValue(name, maxPoll = 40) {
    const input = frame.locator(sel(name)).first();
    for (let i = 0; i < maxPoll; i++) {
      const value = await input.inputValue().catch(() => '');
      if (value === gclid) return value;
      await sleep(250);
    }
    return await input.inputValue().catch(() => '');
  }

  const customValue = await waitForValue('nvx_google_click_id', 40);
  // Native field is informational only (never asserted), so use a short budget.
  const nativeValue = hasNative ? await waitForValue('hs_google_click_id', 4) : '';
  console.log(`CUSTOM_GCLID_MATCH=${customValue === gclid}`);
  console.log(`NATIVE_GCLID_MATCH=${nativeValue === gclid}`);
  if (customValue !== gclid) throw new Error(`Custom GCLID not populated; value=${JSON.stringify(customValue)}`);

  const fill = async (name, value) => {
    const input = frame.locator(sel(name)).first();
    if (!await input.count()) throw new Error(`Required HubSpot field missing: ${name}`);
    await input.fill(value);
    await input.press('Tab').catch(() => {});
  };
  await fill('firstname', 'QA');
  await fill('lastname', 'H1 Attribution');
  await fill('email', email);
  const phoneInput = frame.locator('input[type="tel"]').first();
  if (!await phoneInput.count()) throw new Error('Required phone field missing');
  await phoneInput.fill(phone);
  await phoneInput.press('Tab').catch(() => {});

  const checks = frame.locator('input[type="checkbox"][required],input[type="checkbox"][aria-required="true"]');
  for (let i = 0; i < await checks.count(); i++) {
    const box = checks.nth(i);
    const checked = await box.isChecked().catch(() => false);
    if (!checked) {
      // Prefer Playwright's real user interaction so HubSpot's React state
      // registers the change through its own value setter.
      await box.check({ force: true }).catch(async () => {
        await box.click({ force: true }).catch(() => {});
      });
      const nowChecked = await box.isChecked().catch(() => false);
      if (!nowChecked) {
        // Last-resort DOM fallback if the framework interaction did not stick.
        await box.evaluate((el) => {
          el.checked = true;
          el.dispatchEvent(new Event('change', { bubbles: true }));
          el.dispatchEvent(new Event('input', { bubbles: true }));
        });
      }
    }
  }

  const form = frame.locator('form').first();
  const valid = await form.evaluate((f) => f.checkValidity());
  console.log(`FORM_VALID=${valid}`);
  if (!valid) {
    const invalid = await frame.locator(':invalid').evaluateAll((nodes) => nodes.map((n) => ({ name: n.getAttribute('name'), type: n.getAttribute('type') })));
    throw new Error(`Form invalid: ${JSON.stringify(invalid)}`);
  }

  let submitted = false;
  page.on('response', (response) => {
    try {
      const request = response.request();
      const targetUrl = response.url();
      // Match only the concrete HubSpot form-submission endpoint for this form
      // (e.g. api.hsforms.com/submissions/v3/integration/submit/<portalId>/<formId>)
      // to avoid false positives from HubSpot analytics/telemetry traffic.
      const isSubmissionPost = request.method() === 'POST' &&
        /\/submissions\/v3\/integration\/submit\//i.test(targetUrl) &&
        targetUrl.includes(formId);
      if (isSubmissionPost && response.status() >= 200 && response.status() < 400) {
        submitted = true;
      }
    } catch {}
  });

  const button = frame.locator('button[type="submit"],input[type="submit"]').first();
  await button.click();

  for (let i = 0; i < 60; i++) {
    if (submitted) break;
    await sleep(250);
  }
  console.log(`HUBSPOT_POST_SUCCESS=${submitted}`);
  if (!submitted) throw new Error('No successful HubSpot submission POST observed after submit');

  console.log(`QA_EMAIL=${email}`);
  console.log(`QA_GCLID=${gclid}`);
  console.log('H1_BROWSER_E2E=PASS');
} finally {
  await browser.close();
}
