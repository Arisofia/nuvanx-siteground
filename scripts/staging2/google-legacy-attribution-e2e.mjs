import { chromium } from 'playwright';
import { createHash } from 'node:crypto';

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

function fieldSelector(name) {
  // HubSpot v4 exposes fields either by their bare name or a "0-1/" prefixed variant.
  const escaped = name.replace(/"/g, '\\"');
  return `input[name="${escaped}"], input[name="0-1/${escaped}"]`;
}

async function findHubSpotFrame(page) {
  const iframe = page.locator(`#nvx-hubspot-form iframe[data-test-id*="${formId}"]`).first();
  await iframe.waitFor({ state: 'attached', timeout: 30000 });
  for (let attempt = 0; attempt < 60; attempt += 1) {
    // Tie the frame to the canonical HubSpot iframe element so we never bind to an
    // unrelated form elsewhere on the page that happens to expose an email input.
    const frame = await iframe.elementHandle().then((handle) => handle?.contentFrame()).catch(() => null);
    if (frame && (await frame.locator(fieldSelector('email')).count().catch(() => 0)) > 0) return frame;
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
  const input = frame.locator(fieldSelector(name)).first();
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

// Negative assertion: the field must exist and never equal `forbidden` across the whole window.
async function assertFieldNever(frame, name, forbidden, label, samples = 20) {
  if ((await fieldValue(frame, name)) === null) throw new Error(`${label}: field ${name} not present, cannot assert absence of leak`);
  for (let attempt = 0; attempt < samples; attempt += 1) {
    const value = await fieldValue(frame, name);
    if (value === forbidden) throw new Error(`${label}: field ${name} leaked forbidden value`);
    await sleep(250);
  }
}

async function inspectFields(frame) {
  return frame.locator('input, textarea, select').evaluateAll((nodes) => nodes.map((node) => {
    const name = node.getAttribute('name') || '';
    // Only surface values for attribution fields; redact everything else to avoid
    // leaking submitted PII or HubSpot tracking tokens into public CI logs.
    const isAttribution = /nvx_google|hs_google_click_id|gclid|gbraid|wbraid|gclsrc/i.test(name);
    return {
      tag: node.tagName.toLowerCase(),
      name,
      type: node.getAttribute('type') || '',
      required: node.required || node.getAttribute('aria-required') === 'true',
      hidden: node.type === 'hidden' || node.hidden,
      value: isAttribution ? node.value : '[redacted]',
    };
  }));
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

// HubSpot v4 renders consent checkboxes with the real <input> visually hidden behind a
// styled label, so element.check() reports "did not change its state". Fall back to
// clicking the associated label (aria-labelledby) so the checkbox actually toggles.
async function checkHubSpotBox(frame, el) {
  if (await el.isChecked().catch(() => false)) return;
  try {
    await el.check({ force: true });
    if (await el.isChecked().catch(() => false)) return;
  } catch (_) {
    // Styled checkbox: the input itself is not clickable, fall through to the label.
  }
  const labelId = await el.getAttribute('aria-labelledby');
  if (labelId) {
    // Escape via attribute selector rather than #id so unusual label ids never break the query,
    // and because CSS.escape is a DOM API unavailable in this Node/Playwright context.
    await frame.locator(`[id="${labelId.replace(/"/g, '\\"')}"]`).click({ force: true }).catch(() => {});
    if (await el.isChecked().catch(() => false)) return;
  }
  // Last resort: toggle the checked state directly and dispatch the events HubSpot listens to.
  await el.evaluate((node) => {
    node.checked = true;
    node.dispatchEvent(new Event('input', { bubbles: true }));
    node.dispatchEvent(new Event('change', { bubbles: true }));
  }).catch(() => {});
}

async function fillForm(frame, email) {
  const controls = frame.locator('form input, form textarea, form select');
  const count = await controls.count();
  console.log(`FORM_CONTROL_COUNT=${count}`);
  const radioDone = new Set();
  for (let i = 0; i < count; i += 1) {
    const el = controls.nth(i);
    let tag = '';
    let type = '';
    let name = '';
    let visible = false;
    let required = false;
    try {
      tag = await el.evaluate((node) => node.tagName.toLowerCase());
      type = String((await el.getAttribute('type')) || '').toLowerCase();
      name = String((await el.getAttribute('name')) || '');
      visible = await el.isVisible().catch(() => false);
      required = (await el.getAttribute('required')) !== null || (await el.getAttribute('aria-required')) === 'true';
    } catch (error) {
      console.log(`FILL_SKIP index=${i} error=${error.message}`);
      continue;
    }
    if (type === 'hidden' || type === 'submit' || type === 'button' || /google|gclid|gbraid|wbraid|gclsrc|utm|attribution/i.test(name)) continue;
    try {
      if (type === 'checkbox') {
        if (required) await checkHubSpotBox(frame, el);
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
    const isAllowedHubSpotOrigin = (origin) => {
      if (!origin || origin === 'null') return false;
      try {
        return /(^|\.)(hubspot\.com|hsforms\.com|hsforms\.net)$/.test(new URL(origin).hostname.toLowerCase());
      } catch (_) {
        return false;
      }
    };
    window.addEventListener('message', (event) => {
      if (!isAllowedHubSpotOrigin(event.origin)) return;
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
    // The audit POST is fired asynchronously (keepalive fetch) after the submit postMessage,
    // so poll for it instead of relying on a fixed sleep before asserting exactly one request.
    for (let attempt = 0; attempt < 40 && auditRequests.length < 1; attempt += 1) await sleep(250);
    await sleep(3000); // Grace window so a duplicate audit POST is detected instead of missed.
    if (auditRequests.length !== 1) throw new Error(`ALLOW: audit request count=${auditRequests.length}, expected=1`);
    for (let attempt = 0; attempt < 40 && auditResponses.length < 1; attempt += 1) await sleep(250);
    if (auditResponses.length !== 1 || auditResponses[0] < 200 || auditResponses[0] >= 300) throw new Error(`ALLOW: audit response statuses=${JSON.stringify(auditResponses)}`);
    if (!auditRequests[0]) throw new Error('ALLOW: audit payload body unavailable');
    const payload = JSON.parse(auditRequests[0]);
    if (JSON.stringify(payload).includes(rawEmail)) throw new Error('ALLOW: raw email leaked in audit payload');
    const expectedHash = createHash('sha256').update(rawEmail.trim().toLowerCase()).digest('hex');
    if (String(payload.email_hash || '') !== expectedHash) throw new Error(`ALLOW: email_hash mismatch, expected SHA-256 of submitted email`);
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
      let shouldBlock = false;
      try {
        const url = new URL(route.request().url());
        // Regional Google ad hosts (google.es, google.co.uk, ...) plus googletagmanager are
        // included so real conversion pings never leave the browser during the ALLOW scenario.
        const googleAdHost = /(^|\.)(googleadservices\.com|googlesyndication\.com|doubleclick\.net|googletagmanager\.com|google\.[a-z.]+)$/.test(url.hostname);
        const conversionPath = /conversion|viewthroughconversion|pagead\/1p-conversion|pagead\/gen_204/i.test(url.pathname);
        shouldBlock = googleAdHost && conversionPath;
      } catch (_) {
        // Fall through to continue so a parsing error never stalls page loading.
      }
      if (shouldBlock) return route.abort();
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
    if (initialConsent === true) {
      await setMarketing(page, false);
      // The adapter clears the field asynchronously; wait for the clear before asserting absence.
      await waitField(frame, 'nvx_google_click_id', (value) => !value, `${scenario} pre-consent clear`);
    }

    await assertFieldNever(frame, 'nvx_google_click_id', gclid, `${scenario} pre-consent leak`);
    const nativeBefore = await fieldValue(frame, 'hs_google_click_id');
    if (nativeBefore === gclid) throw new Error(`${scenario}: native hs_google_click_id leaked before consent`);

    await setMarketing(page, allowed);
    if (allowed) {
      await waitField(frame, 'nvx_google_click_id', (value) => value === gclid, 'ALLOW custom GCLID population');
      const native = await fieldValue(frame, 'hs_google_click_id');
      // An empty native value is acceptable: the adapter only writes it when HubSpot surfaces the field.
      if (native && native !== gclid) throw new Error(`ALLOW native hs_google_click_id exists but value=${JSON.stringify(native)}`);

      await setMarketing(page, false);
      // Fail-closed policy: the adapter clears the custom nvx_ field on revoke but
      // deliberately leaves native HubSpot fields untouched, so we only assert the custom field here.
      // Presence guard first so a removed/renamed field is a failure, not a vacuous pass.
      if ((await fieldValue(frame, 'nvx_google_click_id')) === null) throw new Error('ALLOW revoke: nvx_google_click_id field disappeared');
      await waitField(frame, 'nvx_google_click_id', (value) => !value, 'ALLOW revoke custom GCLID clear');

      await setMarketing(page, true);
      await waitField(frame, 'nvx_google_click_id', (value) => value === gclid, 'ALLOW regrant GCLID population');
    } else {
      await assertFieldNever(frame, 'nvx_google_click_id', gclid, 'DENY custom GCLID must remain empty');
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
