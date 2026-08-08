import { chromium } from 'playwright';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const formId = (process.env.FORM_ID || '5042522a-0bc5-4381-ac3e-5aee8649b69c').trim().toLowerCase();
const expectedSha = (process.env.EXPECTED_SHA || '').trim();
const runId = String(process.env.QA_RUN_ID || Date.now()).replace(/[^A-Za-z0-9-]/g, '').slice(0, 40);
if (expectedSha && !/^[0-9a-f]{40}$/.test(expectedSha)) throw new Error('EXPECTED_SHA must be a full lowercase SHA');

const allowGclid = `NVXALLOW-${runId}`;
const denyGclid = `NVXDENY-${runId}`;
const allowEmail = `qa-google-allow-${runId}@example.com`;
const denyEmail = `qa-google-deny-${runId}@example.com`;
const edgeNeedle = '/functions/v1/google-click-attribution';

function sleep(ms) { return new Promise((resolve) => setTimeout(resolve, ms)); }

async function gotoCanonical(page, gclid) {
  const target = `${baseUrl}/madrid/valoracion/?gclid=${encodeURIComponent(gclid)}`;
  let last = '';
  for (let attempt = 1; attempt <= 8; attempt += 1) {
    try {
      const response = await page.goto(target, { waitUntil: 'domcontentloaded', timeout: 45000 });
      const status = response?.status() || 0;
      const path = new URL(page.url()).pathname;
      last = `${status} ${page.url()}`;
      console.log(`NAV attempt=${attempt} status=${status} path=${path}`);
      if (status === 200 && path === '/madrid/valoracion/') return;
    } catch (error) {
      last = error.message;
      console.log(`NAV attempt=${attempt} error=${error.message}`);
    }
    await sleep(2500);
  }
  throw new Error(`Unable to reach canonical valoración route: ${last}`);
}

async function verifySha(page) {
  if (!expectedSha) return;
  const actual = await page.evaluate(() => document.querySelector('meta[name="nvx-deploy-sha"]')?.getAttribute('content') || '');
  if (actual !== expectedSha) throw new Error(`Staging SHA mismatch: expected=${expectedSha} actual=${actual}`);
}

async function findHubSpotFrame(page) {
  await page.locator(`#nvx-hubspot-form iframe[data-test-id*="${formId}"]`).first().waitFor({ state: 'attached', timeout: 30000 });
  for (let attempt = 0; attempt < 60; attempt += 1) {
    for (const frame of page.frames()) {
      if ((await frame.locator('input[name="email"]').count().catch(() => 0)) > 0) return frame;
    }
    await sleep(500);
  }
  throw new Error('HubSpot form iframe exists but email field never became available');
}

async function setMarketing(page, allowed) {
  const state = await page.evaluate(async (allowed) => {
    if (typeof window.wp_has_consent !== 'function') throw new Error('wp_has_consent unavailable');
    if (typeof window.wp_set_consent !== 'function') throw new Error('wp_set_consent unavailable');
    window.wp_set_consent('marketing', allowed ? 'allow' : 'deny');
    document.dispatchEvent(new Event('wp_listen_for_consent_change'));
    document.dispatchEvent(new Event('wp_consent_type_defined'));
    await new Promise((resolve) => setTimeout(resolve, 1000));
    return window.wp_has_consent('marketing');
  }, allowed);
  if (state !== allowed) throw new Error(`Marketing consent mismatch: expected=${allowed} actual=${state}`);
}

async function fieldValue(frame, name) {
  const input = frame.locator(`input[name="${name}"]`).first();
  if ((await input.count()) === 0) return null;
  return input.inputValue();
}

async function waitField(frame, name, predicate, label) {
  for (let attempt = 0; attempt < 40; attempt += 1) {
    const value = await fieldValue(frame, name);
    if (predicate(value)) return value;
    await sleep(250);
  }
  throw new Error(`${label}: field ${name} final=${JSON.stringify(await fieldValue(frame, name))}`);
}

async function inspectFields(frame) {
  return frame.locator('input, textarea, select').evaluateAll((nodes) => nodes.map((node) => ({
    tag: node.tagName.toLowerCase(),
    name: node.getAttribute('name') || '',
    type: node.getAttribute('type') || '',
    required: node.required || node.getAttribute('aria-required') === 'true',
    hidden: node.type === 'hidden' || node.hidden,
    value: node.type === 'password' ? '[redacted]' : node.value,
  })));
}

function fillValue(name, type, email) {
  const n = String(name || '').toLowerCase();
  if (type === 'email' || n.includes('email') || n.includes('correo')) return email;
  if (type === 'tel' || n.includes('phone') || n.includes('telefono') || n.includes('teléfono') || n.includes('mobile')) return '600000000';
  if (n.includes('firstname') || n === 'nombre' || n.includes('first_name')) return 'QA';
  if (n.includes('lastname') || n.includes('apellido') || n.includes('last_name')) return 'Release';
  if (n.includes('postal') || n.includes('zip')) return '28010';
  if (n.includes('city') || n.includes('ciudad')) return 'Madrid';
  if (n.includes('message') || n.includes('mensaje') || n.includes('coment')) return 'QA release attribution. Registro técnico, no paciente real.';
  if (type === 'number') return '1';
  if (type === 'date') return '2026-08-08';
  return 'QA';
}

async function fillForm(frame, email) {
  const controls = frame.locator('form input, form textarea, form select');
  const count = await controls.count();
  console.log(`FORM_CONTROL_COUNT=${count}`);
  const radioDone = new Set();
  for (let i = 0; i < count; i += 1) {
    const el = controls.nth(i);
    const tag = await el.evaluate((node) => node.tagName.toLowerCase());
    const type = String((await el.getAttribute('type')) || '').toLowerCase();
    const name = String((await el.getAttribute('name')) || '');
    const visible = await el.isVisible().catch(() => false);
    const required = (await el.getAttribute('required')) !== null || (await el.getAttribute('aria-required')) === 'true';
    if (type === 'hidden' || type === 'submit' || type === 'button' || /google|gclid|gbraid|wbraid|gclsrc|utm|attribution/i.test(name)) continue;
    try {
      if (type === 'checkbox') {
        if (required && !(await el.isChecked())) await el.check({ force: true });
      } else if (type === 'radio') {
        if (required && !radioDone.has(name)) {
          await el.check({ force: true });
          radioDone.add(name);
        }
      } else if (tag === 'select') {
        if (!visible && !required) continue;
        const options = await el.locator('option').evaluateAll((opts) => opts.map((o) => ({ value: o.value, disabled: o.disabled })).filter((o) => o.value && !o.disabled));
        if (options.length) await el.selectOption(options[0].value);
      } else {
        if (!visible && !required) continue;
        const current = await el.inputValue().catch(() => '');
        if (!current) await el.fill(fillValue(name, type, email));
      }
    } catch (error) {
      console.log(`FILL_WARN name=${name} type=${type} required=${required} error=${error.message}`);
    }
  }
}

async function submitAndAssert(page, frame, scenario, rawEmail, auditRequests, auditResponses) {
  await page.evaluate((formId) => {
    window.__nvxQaSuccess = 0;
    window.__nvxQaGenerateBefore = (window.dataLayer || []).filter((item) => item && item.event === 'nvx_conversion_signal' && item.nvx_event_name === 'generate_lead').length;
    window.addEventListener('message', (event) => {
      let data = event.data || {};
      if (typeof data === 'string') { try { data = JSON.parse(data); } catch (_) { return; } }
      if (data.type === 'hsFormCallback' && data.eventName === 'onFormSubmitted' && String(data.id || '').toLowerCase() === formId) {
        window.__nvxQaSuccess += 1;
      }
    });
  }, formId);

  await fillForm(frame, rawEmail);
  console.log(`FORM_FIELDS_${scenario}=${JSON.stringify(await inspectFields(frame))}`);
  const submit = frame.locator('form input[type="submit"], form button[type="submit"], input[type="submit"], button[type="submit"]').first();
  if ((await submit.count()) === 0) throw new Error('No submit control found');
  await submit.click({ force: true });

  await page.waitForFunction(() => Number(window.__nvxQaSuccess || 0) >= 1, null, { timeout: 30000 });
  await sleep(5000);
  const result = await page.evaluate(() => {
    const after = (window.dataLayer || []).filter((item) => item && item.event === 'nvx_conversion_signal' && item.nvx_event_name === 'generate_lead').length;
    return { successMessages: Number(window.__nvxQaSuccess || 0), generateLeadDelta: after - Number(window.__nvxQaGenerateBefore || 0) };
  });
  if (result.generateLeadDelta !== 1) throw new Error(`${scenario}: generate_lead delta=${result.generateLeadDelta}, expected=1`);

  if (scenario === 'ALLOW') {
    if (auditRequests.length !== 1) throw new Error(`ALLOW: audit request count=${auditRequests.length}, expected=1`);
    if (auditResponses.length !== 1 || auditResponses[0] < 200 || auditResponses[0] >= 300) throw new Error(`ALLOW: audit response statuses=${JSON.stringify(auditResponses)}`);
    const payload = JSON.parse(auditRequests[0]);
    if (JSON.stringify(payload).includes(rawEmail)) throw new Error('ALLOW: raw email leaked in audit payload');
    if (!/^[0-9a-f]{64}$/.test(String(payload.email_hash || ''))) throw new Error('ALLOW: missing SHA-256 email_hash');
    console.log(`ALLOW_AUDIT_PAYLOAD=${JSON.stringify(payload)}`);
  } else {
    if (auditRequests.length !== 0) throw new Error(`DENY: audit request count=${auditRequests.length}, expected=0`);
  }
  console.log(`SCENARIO_${scenario}=PASS successMessages=${result.successMessages} generateLeadDelta=${result.generateLeadDelta} auditRequests=${auditRequests.length}`);
}

async function runScenario(browser, { scenario, gclid, email, allowed }) {
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  const auditRequests = [];
  const auditResponses = [];
  try {
    const page = await context.newPage();

    await page.route('**/*', async (route) => {
      const url = new URL(route.request().url());
      const googleAdHost = /(^|\.)(googleadservices\.com|googlesyndication\.com|doubleclick\.net|google\.com)$/.test(url.hostname);
      const conversionPath = /conversion|viewthroughconversion|pagead\/1p-conversion|pagead\/gen_204/i.test(url.pathname);
      if (googleAdHost && conversionPath) return route.abort();
      return route.continue();
    });

    page.on('request', (request) => {
      if (request.url().includes(edgeNeedle) && request.method() === 'POST') auditRequests.push(request.postData() || '');
    });
    page.on('response', (response) => {
      if (response.url().includes(edgeNeedle) && response.request().method() === 'POST') auditResponses.push(response.status());
    });

    await gotoCanonical(page, gclid);
    await verifySha(page);
    const frame = await findHubSpotFrame(page);

    const initialConsent = await page.evaluate(() => typeof window.wp_has_consent === 'function' ? window.wp_has_consent('marketing') : 'missing');
    console.log(`SCENARIO_${scenario}_INITIAL_MARKETING=${initialConsent}`);
    if (initialConsent === true) await setMarketing(page, false);

    await waitField(frame, 'nvx_google_click_id', (value) => value !== gclid, `${scenario} pre-consent leak`);
    const nativeBefore = await fieldValue(frame, 'hs_google_click_id');
    if (nativeBefore === gclid) throw new Error(`${scenario}: native hs_google_click_id leaked before consent`);

    await setMarketing(page, allowed);
    if (allowed) {
      await waitField(frame, 'nvx_google_click_id', (value) => value === gclid, 'ALLOW custom GCLID population');
      const native = await fieldValue(frame, 'hs_google_click_id');
      if (native !== null && native !== gclid) throw new Error(`ALLOW native hs_google_click_id exists but value=${JSON.stringify(native)}`);

      await setMarketing(page, false);
      await waitField(frame, 'nvx_google_click_id', (value) => !value, 'ALLOW revoke custom GCLID clear');
      const nativeRevoked = await fieldValue(frame, 'hs_google_click_id');
      if (nativeRevoked === gclid) throw new Error('ALLOW revoke: adapter-written native GCLID remained after consent withdrawal');

      await setMarketing(page, true);
      await waitField(frame, 'nvx_google_click_id', (value) => value === gclid, 'ALLOW regrant GCLID population');
    } else {
      await waitField(frame, 'nvx_google_click_id', (value) => !value, 'DENY custom GCLID must remain empty');
      const native = await fieldValue(frame, 'hs_google_click_id');
      if (native === gclid) throw new Error('DENY: native GCLID equals synthetic click id');
    }

    await submitAndAssert(page, frame, scenario, email, auditRequests, auditResponses);
  } finally {
    await context.close();
  }
}

const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });
try {
  await runScenario(browser, { scenario: 'ALLOW', gclid: allowGclid, email: allowEmail, allowed: true });
  await runScenario(browser, { scenario: 'DENY', gclid: denyGclid, email: denyEmail, allowed: false });
  console.log(`ALLOW_GCLID=${allowGclid}`);
  console.log(`ALLOW_EMAIL=${allowEmail}`);
  console.log(`DENY_GCLID=${denyGclid}`);
  console.log(`DENY_EMAIL=${denyEmail}`);
  console.log('GOOGLE_LEGACY_ATTRIBUTION_E2E=PASS');
} finally {
  await browser.close();
}
