import { chromium } from 'playwright';

const base = 'https://nuvanx.com';
const gclid = 'NUVANX_QA_H1_20260809_1510';
const email = 'nvxqa-h1-20260809-1510@example.com';
const phone = '+34600000000';
const target = `${base}/madrid/valoracion/?gclid=${encodeURIComponent(gclid)}`;
const ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36';
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const sel = (name) => `input[name="${name}"],input[name="0-1/${name}"]`;

function safeUrl(value) {
  try {
    const u = new URL(value);
    return `${u.origin}${u.pathname}`;
  } catch {
    return '';
  }
}

const browser = await chromium.launch({ headless: true });
try {
  const context = await browser.newContext({ userAgent: ua });
  const page = await context.newPage();
  let status = 0;
  let reached = false;

  for (let attempt = 1; attempt <= 6; attempt++) {
    try {
      const response = await page.goto(target, { waitUntil: 'domcontentloaded', timeout: 45000 });
      status = response?.status() || 0;
      console.log(`NAV attempt=${attempt} status=${status} path=${new URL(page.url()).pathname}`);
      if (status === 200 && new URL(page.url()).pathname === '/madrid/valoracion/') {
        reached = true;
        break;
      }
      if (status === 202) await sleep(10000);
    } catch (e) {
      console.log(`NAV attempt=${attempt} error=${e.message}`);
    }
    await sleep(3000);
  }
  if (!reached) throw new Error(`Production valoración route not reachable with HTTP 200; last=${status}`);

  const sha = await page.locator('meta[name="nvx-deploy-sha"]').getAttribute('content').catch(() => '');
  console.log(`PRODUCTION_SHA=${sha}`);
  if (sha !== '1bfd42caa839503c475d10458fc4bdbc0b391d2f') throw new Error(`Unexpected production SHA: ${sha}`);

  await page.evaluate(() => {
    if (typeof window.wp_set_consent !== 'function' || typeof window.wp_has_consent !== 'function') {
      throw new Error('WordPress consent API unavailable');
    }
    window.wp_set_consent('marketing', 'allow');
    document.dispatchEvent(new Event('wp_listen_for_consent_change'));
    document.dispatchEvent(new Event('wp_consent_type_defined'));
  });
  await sleep(1000);
  const consent = await page.evaluate(() => window.wp_has_consent('marketing'));
  console.log(`MARKETING_CONSENT=${consent}`);
  if (consent !== true) throw new Error('Marketing consent was not granted');

  const host = page.locator('#nvx-hubspot-form');
  if (!await host.count()) throw new Error('#nvx-hubspot-form is missing');
  await host.dispatchEvent('focusin').catch(() => {});
  await sleep(3000);

  let formFrame = null;
  for (let attempt = 0; attempt < 90 && !formFrame; attempt++) {
    for (const candidate of page.frames()) {
      try {
        if (await candidate.locator(sel('email')).count()) {
          formFrame = candidate;
          break;
        }
      } catch {}
    }
    if (!formFrame) await sleep(500);
  }

  const iframeMeta = await page.locator('iframe').evaluateAll((nodes) => nodes.map((node) => ({
    src: node.getAttribute('src') || '',
    title: node.getAttribute('title') || '',
    testId: node.getAttribute('data-test-id') || '',
    id: node.id || '',
    name: node.getAttribute('name') || '',
    insideValoracion: Boolean(node.closest('#nvx-hubspot-form')),
  })));
  console.log(`IFRAME_COUNT=${iframeMeta.length}`);
  console.log(`IFRAME_META=${JSON.stringify(iframeMeta.map((m) => ({ ...m, src: safeUrl(m.src) })))}`);
  console.log(`FRAME_URLS=${JSON.stringify(page.frames().map((f) => safeUrl(f.url())))}`);

  if (!formFrame) throw new Error('No live frame containing HubSpot email field was found');
  console.log(`FORM_FRAME_URL=${safeUrl(formFrame.url())}`);

  const names = await formFrame.locator('input').evaluateAll((nodes) => nodes.map((n) => n.getAttribute('name') || '').filter(Boolean));
  console.log(`FORM_INPUT_NAMES=${JSON.stringify(names)}`);
  const hasCustom = names.includes('nvx_google_click_id') || names.includes('0-1/nvx_google_click_id');
  const hasNative = names.includes('hs_google_click_id') || names.includes('0-1/hs_google_click_id');
  console.log(`FIELD_CUSTOM_PRESENT=${hasCustom}`);
  console.log(`FIELD_NATIVE_PRESENT=${hasNative}`);
  if (!hasCustom) throw new Error('nvx_google_click_id is not present in published HubSpot form');
  if (!hasNative) throw new Error('hs_google_click_id is not present in published HubSpot form');

  // Re-fire consent lifecycle after the legacy form has registered its onFormReady hook.
  await page.evaluate(() => {
    document.dispatchEvent(new Event('wp_listen_for_consent_change'));
    document.dispatchEvent(new Event('wp_consent_type_defined'));
  });

  async function waitForValue(name) {
    const input = formFrame.locator(sel(name)).first();
    for (let i = 0; i < 40; i++) {
      const value = await input.inputValue().catch(() => '');
      if (value === gclid) return value;
      await sleep(250);
    }
    return await input.inputValue().catch(() => '');
  }

  const customValue = await waitForValue('nvx_google_click_id');
  const nativeValue = await waitForValue('hs_google_click_id');
  console.log(`CUSTOM_GCLID_MATCH=${customValue === gclid}`);
  console.log(`NATIVE_GCLID_MATCH=${nativeValue === gclid}`);
  if (customValue !== gclid) throw new Error(`Custom GCLID not populated; field exists but value did not match`);
  if (nativeValue !== gclid) throw new Error(`Native GCLID not populated; field exists but value did not match`);

  const fill = async (name, value) => {
    const input = formFrame.locator(sel(name)).first();
    if (!await input.count()) throw new Error(`Required HubSpot field missing: ${name}`);
    await input.fill(value);
    await input.press('Tab').catch(() => {});
  };
  await fill('firstname', 'QA');
  await fill('lastname', 'H1 Attribution');
  await fill('email', email);
  const phoneInput = formFrame.locator('input[type="tel"]').first();
  if (!await phoneInput.count()) throw new Error('Required phone field missing');
  await phoneInput.fill(phone);
  await phoneInput.press('Tab').catch(() => {});

  const checks = formFrame.locator('input[type="checkbox"][required],input[type="checkbox"][aria-required="true"]');
  for (let i = 0; i < await checks.count(); i++) {
    if (!await checks.nth(i).isChecked().catch(() => false)) await checks.nth(i).check({ force: true });
  }

  const form = formFrame.locator('form').first();
  const valid = await form.evaluate((f) => f.checkValidity());
  console.log(`FORM_VALID=${valid}`);
  if (!valid) {
    const invalid = await formFrame.locator(':invalid').evaluateAll((nodes) => nodes.map((n) => ({ name: n.getAttribute('name'), type: n.getAttribute('type') })));
    throw new Error(`Form invalid: ${JSON.stringify(invalid)}`);
  }

  let submitted = false;
  page.on('response', (response) => {
    try {
      if (response.request().method() === 'POST' && /hubspot|hsforms|forms-eu1/i.test(response.url()) && response.status() >= 200 && response.status() < 400) submitted = true;
    } catch {}
  });

  const button = formFrame.locator('button[type="submit"],input[type="submit"]').first();
  await button.click();
  for (let i = 0; i < 60 && !submitted; i++) await sleep(250);
  console.log(`HUBSPOT_POST_SUCCESS=${submitted}`);
  if (!submitted) throw new Error('No successful HubSpot POST observed after submit');

  console.log(`QA_EMAIL=${email}`);
  console.log(`QA_GCLID=${gclid}`);
  console.log('H1_BROWSER_E2E=PASS');
} finally {
  await browser.close();
}
