import { chromium } from 'playwright';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const formId = (process.env.FORM_ID || '5042522a-0bc5-4381-ac3e-5aee8649b69c').trim().toLowerCase();
const expectedSha = (process.env.EXPECTED_SHA || '').trim();
const qaRunId = String(process.env.QA_RUN_ID || Date.now()).replace(/[^0-9A-Za-z-]/g, '').slice(0, 48);
const proxyServer = (process.env.SOCKS_PROXY || '').trim();
if (expectedSha && !/^[0-9a-f]{40}$/.test(expectedSha)) throw new Error('EXPECTED_SHA must be a full lowercase SHA');

const allowGclid = `NVXRELALLOW-${qaRunId}`;
const denyGclid = `NVXRELDENY-${qaRunId}`;
const allowEmail = `qa-google-allow-${qaRunId}@example.com`;
const denyEmail = `qa-google-deny-${qaRunId}@example.com`;

function sleep(ms) { return new Promise((resolve) => setTimeout(resolve, ms)); }

async function gotoTarget(page, gclid) {
  const target = `${baseUrl}/madrid/valoracion/?gclid=${encodeURIComponent(gclid)}`;
  let last = 'none';
  for (let attempt = 1; attempt <= 4; attempt += 1) {
    try {
      const response = await page.goto(target, { waitUntil: 'domcontentloaded', timeout: 30000 });
      const status = response?.status() || 0;
      const current = page.url();
      last = `${status} ${current}`;
      console.log(`NAV scenario=${gclid} attempt=${attempt} status=${status} url=${current}`);
      if (status === 200 && new URL(current).pathname === '/madrid/valoracion/') return;
    } catch (error) {
      last = error.message;
      console.log(`NAV scenario=${gclid} attempt=${attempt} error=${error.message}`);
    }
    await sleep(2000);
  }
  throw new Error(`Unable to reach canonical valoración route: ${last}`);
}

async function assertSha(page) {
  if (!expectedSha) return;
  const pageSha = await page.evaluate(() => document.querySelector('meta[name="nvx-deploy-sha"]')?.getAttribute('content') || '');
  if (pageSha !== expectedSha) throw new Error(`SHA mismatch expected=${expectedSha} actual=${pageSha}`);
}

async function waitCanonicalForm(page) {
  await page.waitForFunction((id) => {
    if (!window.HubSpotFormsV4 || typeof window.HubSpotFormsV4.getForms !== 'function') return false;
    return (window.HubSpotFormsV4.getForms() || []).some((form) => String(form?.getFormId?.() || '').toLowerCase() === id);
  }, formId, { timeout: 30000 });
}

async function setMarketingConsent(page, allowed) {
  const result = await page.evaluate(async ({ allowed }) => {
    if (typeof window.wp_has_consent !== 'function') throw new Error('wp_has_consent unavailable');
    if (typeof window.wp_set_consent !== 'function') throw new Error('wp_set_consent unavailable');
    window.wp_set_consent('marketing', allowed ? 'allow' : 'deny');
    document.dispatchEvent(new Event('wp_listen_for_consent_change'));
    document.dispatchEvent(new Event('wp_consent_type_defined'));
    await new Promise((resolve) => setTimeout(resolve, 1200));
    return window.wp_has_consent('marketing');
  }, { allowed });
  if (result !== allowed) throw new Error(`Marketing consent mismatch expected=${allowed} actual=${result}`);
}

async function googleFields(page) {
  return page.evaluate(async ({ formId }) => {
    const forms = window.HubSpotFormsV4?.getForms?.() || [];
    const form = forms.find((candidate) => String(candidate?.getFormId?.() || '').toLowerCase() === formId);
    if (!form || typeof form.getFormFieldValues !== 'function') return [];
    const fields = await form.getFormFieldValues();
    return (fields || [])
      .filter((field) => /google|gclid|gbraid|wbraid|gclsrc/i.test(String(field?.name || '')))
      .map((field) => ({ name: String(field.name || ''), value: field.value }));
  }, { formId });
}

async function waitGclidState(page, gclid, shouldExist) {
  for (let i = 0; i < 20; i += 1) {
    const fields = await googleFields(page);
    const values = fields.flatMap((field) => Array.isArray(field.value) ? field.value : [field.value]).map(String);
    if (values.includes(gclid) === shouldExist) return fields;
    await sleep(250);
  }
  const finalFields = await googleFields(page);
  throw new Error(`GCLID state mismatch shouldExist=${shouldExist} fields=${JSON.stringify(finalFields)}`);
}

function valueFor(name, type, email) {
  const n = String(name || '').toLowerCase();
  if (type === 'email' || n.includes('email') || n.includes('correo')) return email;
  if (type === 'tel' || n.includes('phone') || n.includes('telefono') || n.includes('teléfono') || n.includes('mobile')) return '600000000';
  if (n.includes('first') || n === 'firstname' || n.includes('nombre')) return 'QA';
  if (n.includes('last') || n === 'lastname' || n.includes('apellido')) return 'Release';
  if (n.includes('postal') || n.includes('zip')) return '28010';
  if (n.includes('city') || n.includes('ciudad')) return 'Madrid';
  if (n.includes('message') || n.includes('mensaje') || n.includes('coment')) return 'QA release Google attribution. No es un paciente real.';
  if (type === 'number') return '1';
  if (type === 'date') return '2026-08-08';
  return 'QA';
}

async function fillAndSubmit(page, email) {
  await page.evaluate(({ formId }) => {
    window.__nvxQaSuccess = null;
    window.__nvxQaGenerateBefore = (window.dataLayer || []).filter((item) => item && item.event === 'nvx_conversion_signal' && item.nvx_event_name === 'generate_lead').length;
    window.addEventListener('hs-form-event:on-submission:success', (event) => {
      const detail = event?.detail || {};
      if (String(detail.formId || '').toLowerCase() === formId) window.__nvxQaSuccess = { formId: detail.formId || '' };
    });
  }, { formId });

  let submit = null;
  let fieldCount = 0;
  for (const frame of page.frames()) {
    const controls = frame.locator('form input, form textarea, form select');
    const count = await controls.count().catch(() => 0);
    if (!count) continue;
    fieldCount += count;
    const radioDone = new Set();
    for (let i = 0; i < count; i += 1) {
      const el = controls.nth(i);
      const type = String((await el.getAttribute('type').catch(() => '')) || '').toLowerCase();
      const name = String((await el.getAttribute('name').catch(() => '')) || '');
      if (type === 'hidden' || type === 'submit' || type === 'button' || /google|gclid|gbraid|wbraid|gclsrc|utm|attribution/i.test(name)) continue;
      const required = (await el.getAttribute('required').catch(() => null)) !== null || (await el.getAttribute('aria-required').catch(() => null)) === 'true';
      try {
        if (type === 'checkbox') {
          if (required && !(await el.isChecked())) await el.check({ force: true });
        } else if (type === 'radio') {
          if (required && !radioDone.has(name)) {
            await el.check({ force: true });
            radioDone.add(name);
          }
        } else if ((await el.evaluate((node) => node.tagName.toLowerCase())) === 'select') {
          const options = await el.locator('option').evaluateAll((opts) => opts.map((o) => ({ value: o.value, disabled: o.disabled })).filter((o) => o.value && !o.disabled));
          if (options.length) await el.selectOption(options[0].value);
        } else {
          const current = await el.inputValue().catch(() => '');
          if (!current) await el.fill(valueFor(name, type, email));
        }
      } catch (error) {
        console.log(`FILL_WARN name=${name} type=${type} error=${error.message}`);
      }
    }
    const candidate = frame.locator('form button[type="submit"], form input[type="submit"], button[type="submit"], input[type="submit"]').first();
    if ((await candidate.count().catch(() => 0)) > 0) submit = candidate;
  }
  if (!submit) throw new Error(`No HubSpot submit control found; controls=${fieldCount}`);
  await submit.click({ force: true });
  await page.waitForFunction(() => Boolean(window.__nvxQaSuccess), null, { timeout: 30000 });
  await sleep(3000);
  const result = await page.evaluate(() => {
    const after = (window.dataLayer || []).filter((item) => item && item.event === 'nvx_conversion_signal' && item.nvx_event_name === 'generate_lead').length;
    return { success: window.__nvxQaSuccess, generateLeadDelta: after - Number(window.__nvxQaGenerateBefore || 0) };
  });
  if (result.generateLeadDelta !== 1) throw new Error(`generate_lead delta expected 1 got ${result.generateLeadDelta}`);
  return result;
}

async function runScenario(browser, { gclid, email, allow }) {
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  try {
    const page = await context.newPage();
    await gotoTarget(page, gclid);
    await assertSha(page);
    await waitCanonicalForm(page);
    await setMarketingConsent(page, allow);
    const fields = await waitGclidState(page, gclid, allow);
    const result = await fillAndSubmit(page, email);
    console.log(`SCENARIO=${allow ? 'ALLOW' : 'DENY'} PASS gclid=${gclid} email=${email} fields=${JSON.stringify(fields)} generateLeadDelta=${result.generateLeadDelta}`);
  } finally {
    await context.close();
  }
}

const launchOptions = { headless: true, args: ['--no-sandbox'] };
if (proxyServer) launchOptions.proxy = { server: proxyServer };
const browser = await chromium.launch(launchOptions);
try {
  await runScenario(browser, { gclid: allowGclid, email: allowEmail, allow: true });
  await runScenario(browser, { gclid: denyGclid, email: denyEmail, allow: false });
  console.log(`ALLOW_GCLID=${allowGclid}`);
  console.log(`ALLOW_EMAIL=${allowEmail}`);
  console.log(`DENY_GCLID=${denyGclid}`);
  console.log(`DENY_EMAIL=${denyEmail}`);
  console.log('GOOGLE_ATTRIBUTION_RELEASE_E2E=PASS');
} finally {
  await browser.close();
}
