import { chromium } from 'playwright';
import { createHash } from 'node:crypto';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const formId = (process.env.FORM_ID || '5042522a-0bc5-4381-ac3e-5aee8649b69c').trim().toLowerCase();
const expectedSha = (process.env.EXPECTED_SHA || '').trim();
const runId = String(process.env.QA_RUN_ID || Date.now()).replace(/[^A-Za-z0-9-]/g, '').slice(0, 40);
if (expectedSha && !/^[0-9a-f]{40}$/.test(expectedSha)) throw new Error('EXPECTED_SHA must be a full lowercase SHA');

const edgeNeedle = '/functions/v1/google-click-attribution';
const phoneValue = '+34600000000';
const scenarios = [
  { name: 'ALLOW', allowed: true, gclid: `NVXALLOW-${runId}`, email: `qa-google-allow-${runId}@example.com` },
  { name: 'DENY', allowed: false, gclid: `NVXDENY-${runId}`, email: `qa-google-deny-${runId}@example.com` },
];

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

function safePath(value) {
  try { return new URL(value).pathname; } catch { return ''; }
}

function sanitizeUrl(value) {
  try {
    const url = new URL(value);
    return /^https?:$/.test(url.protocol) ? `${url.origin}${url.pathname}` : '';
  } catch {
    return '';
  }
}

function isHubSpotHost(value) {
  try {
    return /(^|\.)(hubspot\.com|hsforms\.com|hsforms\.net|forms-eu1\.com)$/.test(new URL(value).hostname.toLowerCase());
  } catch {
    return false;
  }
}

function selector(name) {
  const escaped = name.replaceAll('"', String.raw`\"`);
  return `input[name="${escaped}"],input[name="0-1/${escaped}"]`;
}

async function gotoCanonical(page, gclid) {
  const canonicalPath = '/madrid/valoracion/';
  const target = `${baseUrl}${canonicalPath}?gclid=${encodeURIComponent(gclid)}`;
  let documentStatus = 0;
  let last = '';
  const onResponse = (response) => {
    try {
      const request = response.request();
      if (request.isNavigationRequest() && request.frame() === page.mainFrame()) documentStatus = response.status();
    } catch {}
  };
  page.on('response', onResponse);
  try {
    for (let attempt = 1; attempt <= 6; attempt += 1) {
      try {
        const response = await page.goto(target, { waitUntil: 'domcontentloaded', timeout: 45000 });
        const status = response?.status() || documentStatus || 0;
        const path = safePath(page.url());
        last = `${status} ${path}`;
        console.log(`NAV attempt=${attempt} status=${status} path=${path}`);
        if (status === 200 && path === canonicalPath) return;
        if (status === 202) {
          console.log(`NAV attempt=${attempt} siteground_antibot=challenge settle_ms=30000`);
          for (let second = 0; second < 30; second += 1) {
            await sleep(1000);
            if (documentStatus === 200 && safePath(page.url()) === canonicalPath) {
              console.log(`NAV attempt=${attempt} challenge_resolved status=200 path=${canonicalPath}`);
              return;
            }
          }
        }
      } catch (error) {
        last = error.message;
        console.log(`NAV attempt=${attempt} error=${error.message}`);
      }
      await sleep(5000);
    }
  } finally {
    page.off('response', onResponse);
  }
  throw new Error(`Unable to reach canonical valoración route with HTTP 200: ${last}`);
}

async function verifySha(page) {
  if (!expectedSha) return;
  const actual = await page.locator('meta[name="nvx-deploy-sha"]').getAttribute('content').catch(() => '');
  if (actual !== expectedSha) throw new Error(`Staging SHA mismatch: expected=${expectedSha} actual=${actual}`);
}

async function hubSpotFrame(page) {
  const iframe = page.locator(`#nvx-hubspot-form iframe[data-test-id*="${formId}"]`).first();
  await iframe.waitFor({ state: 'attached', timeout: 30000 });
  for (let attempt = 0; attempt < 60; attempt += 1) {
    const handle = await iframe.elementHandle().catch(() => null);
    try {
      const frame = handle ? await handle.contentFrame().catch(() => null) : null;
      if (frame && await frame.locator(selector('email')).count()) return frame;
    } finally {
      await handle?.dispose().catch(() => {});
    }
    await sleep(500);
  }
  throw new Error('HubSpot form iframe exists but email field never became available');
}

async function setMarketing(page, allowed) {
  const state = await page.evaluate(async (next) => {
    if (typeof window.wp_has_consent !== 'function' || typeof window.wp_set_consent !== 'function') {
      throw new Error('WordPress consent API unavailable');
    }
    window.wp_set_consent('marketing', next ? 'allow' : 'deny');
    document.dispatchEvent(new Event('wp_listen_for_consent_change'));
    document.dispatchEvent(new Event('wp_consent_type_defined'));
    await new Promise((resolve) => setTimeout(resolve, 1000));
    return window.wp_has_consent('marketing');
  }, allowed);
  if (state !== allowed) throw new Error(`Marketing consent mismatch: expected=${allowed} actual=${state}`);
}

async function valueOf(frame, name) {
  const input = frame.locator(selector(name)).first();
  return await input.count() ? input.inputValue() : null;
}

async function waitValue(frame, name, predicate, label) {
  for (let attempt = 0; attempt < 40; attempt += 1) {
    const value = await valueOf(frame, name);
    if (predicate(value)) return value;
    await sleep(250);
  }
  throw new Error(`${label}: ${name}=${JSON.stringify(await valueOf(frame, name))}`);
}

async function assertNever(frame, name, forbidden, label) {
  if (await valueOf(frame, name) === null) throw new Error(`${label}: ${name} field missing`);
  for (let attempt = 0; attempt < 20; attempt += 1) {
    if (await valueOf(frame, name) === forbidden) throw new Error(`${label}: forbidden value leaked`);
    await sleep(250);
  }
}

async function fillVisibleForm(frame, email) {
  const fillNamed = async (name, value) => {
    const input = frame.locator(selector(name)).first();
    if (!await input.count()) throw new Error(`Required HubSpot field missing: ${name}`);
    await input.fill(value);
    await input.press('Tab').catch(() => {});
  };
  await fillNamed('firstname', 'QA');
  await fillNamed('lastname', 'Release');
  await fillNamed('email', email);

  const phone = frame.locator('form input[type="tel"]').first();
  if (!await phone.count()) throw new Error('Required HubSpot phone input missing');
  await phone.fill(phoneValue);
  await phone.press('Tab').catch(() => {});

  const requiredChecks = frame.locator('form input[type="checkbox"][required], form input[type="checkbox"][aria-required="true"]');
  const count = await requiredChecks.count();
  for (let index = 0; index < count; index += 1) {
    const checkbox = requiredChecks.nth(index);
    if (!await checkbox.isChecked().catch(() => false)) await checkbox.check().catch(() => checkbox.check({ force: true }));
    if (!await checkbox.isChecked().catch(() => false)) throw new Error(`Required HubSpot checkbox ${index} remained unchecked`);
  }
  console.log(`FORM_REQUIRED_CHECKBOXES=${count}`);
}

async function syncV4State(page, email) {
  const result = await page.evaluate(async ({ canonicalFormId, emailValue, phone }) => {
    const api = window.HubSpotFormsV4;
    if (!api || typeof api.getForms !== 'function') throw new Error('HubSpotFormsV4.getForms unavailable');
    const forms = Array.from(api.getForms() || []);
    const form = forms.find((candidate) => {
      try { return String(candidate?.getFormId?.() || '').toLowerCase() === canonicalFormId; } catch { return false; }
    });
    if (!form || typeof form.getFormFieldValues !== 'function' || typeof form.setFieldValue !== 'function') {
      throw new Error('Canonical HubSpot Forms V4 instance unavailable');
    }
    const before = await form.getFormFieldValues();
    const available = new Set((Array.isArray(before) ? before : []).map((field) => field?.name || '').filter(Boolean));
    const setFirst = (candidates, value) => {
      for (const name of candidates) {
        if (!available.has(name)) continue;
        form.setFieldValue(name, value);
        return name;
      }
      return '';
    };
    const applied = {
      firstname: setFirst(['0-1/firstname', 'firstname'], 'QA'),
      lastname: setFirst(['0-1/lastname', 'lastname'], 'Release'),
      email: setFirst(['0-1/email', 'email'], emailValue),
      phone: setFirst(['0-1/phone', 'phone'], phone),
    };
    await new Promise((resolve) => setTimeout(resolve, 500));
    const after = await form.getFormFieldValues();
    const values = new Map((Array.isArray(after) ? after : []).map((field) => [field?.name || '', String(field?.value ?? '')]));
    return {
      applied,
      emailMatches: Boolean(applied.email) && values.get(applied.email) === emailValue,
      phoneReady: Boolean(applied.phone) && values.get(applied.phone) === phone,
      fieldCount: available.size,
    };
  }, { canonicalFormId: formId, emailValue: email, phone: phoneValue });

  if (!result.applied.firstname || !result.applied.lastname || !result.emailMatches || !result.phoneReady) {
    throw new Error(`HubSpot V4 state mismatch: ${JSON.stringify(result)}`);
  }
  console.log(`HUBSPOT_V4_STATE=${JSON.stringify(result)}`);
}

async function formState(frame) {
  return frame.locator('form').first().evaluate((form) => ({
    valid: form.checkValidity(),
    invalid: Array.from(form.querySelectorAll(':invalid')).map((field) => ({
      name: field.getAttribute('name') || '',
      type: field.getAttribute('type') || '',
    })).slice(0, 20),
    submitDisabled: Boolean(form.querySelector('[type="submit"]')?.disabled),
  }));
}

async function installObservers(page) {
  await page.evaluate((canonicalFormId) => {
    window.__nvxQaSuccess = 0;
    window.__nvxQaSuccessSources = [];
    window.__nvxQaGenerateBefore = (window.dataLayer || []).filter((item) => item?.event === 'nvx_conversion_signal' && item.nvx_event_name === 'generate_lead').length;
    const record = (source, id) => {
      if (String(id || '').toLowerCase() !== canonicalFormId) return;
      window.__nvxQaSuccess += 1;
      window.__nvxQaSuccessSources.push(source);
    };
    window.addEventListener('hs-form-event:on-submission:success', (event) => record('hubspot_form_event', event?.detail?.formId));
    window.addEventListener('message', (event) => {
      try {
        if (!/(^|\.)(hubspot\.com|hsforms\.com|hsforms\.net)$/.test(new URL(event.origin).hostname.toLowerCase())) return;
      } catch { return; }
      let data = event.data || {};
      if (typeof data === 'string') { try { data = JSON.parse(data); } catch { return; } }
      if (data.type === 'hsFormCallback' && data.eventName === 'onFormSubmitted') record('hubspot_post_message', data.id);
    });
  }, formId);
}

async function submissionState(page) {
  return page.evaluate(() => ({
    successMessages: Number(window.__nvxQaSuccess || 0),
    successSources: Array.isArray(window.__nvxQaSuccessSources) ? window.__nvxQaSuccessSources.slice() : [],
    generateLeadDelta: (window.dataLayer || []).filter((item) => item?.event === 'nvx_conversion_signal' && item.nvx_event_name === 'generate_lead').length - Number(window.__nvxQaGenerateBefore || 0),
  }));
}

async function submit(page, frame, scenario, email, auditRequests, auditResponses) {
  await installObservers(page);
  await fillVisibleForm(frame, email);
  await syncV4State(page, email);

  const pre = await formState(frame);
  console.log(`FORM_VALIDITY_${scenario}=${JSON.stringify(pre)}`);
  if (!pre.valid || pre.submitDisabled) throw new Error(`${scenario}: form not submit-ready: ${JSON.stringify(pre)}`);

  const network = { requests: [], responses: [], failures: [] };
  const onRequest = (request) => {
    if (request.method() === 'POST' && isHubSpotHost(request.url())) network.requests.push(sanitizeUrl(request.url()));
  };
  const onResponse = (response) => {
    const request = response.request();
    if (request.method() === 'POST' && isHubSpotHost(response.url())) network.responses.push({ status: response.status(), url: sanitizeUrl(response.url()) });
  };
  const onFailed = (request) => {
    if (request.method() === 'POST' && isHubSpotHost(request.url())) network.failures.push({ url: sanitizeUrl(request.url()), error: String(request.failure()?.errorText || 'unknown').slice(0, 120) });
  };
  page.on('request', onRequest);
  page.on('response', onResponse);
  page.on('requestfailed', onFailed);

  const button = frame.locator('form button[type="submit"],form input[type="submit"]').first();
  if (!await button.count()) throw new Error('HubSpot submit control missing');
  await button.scrollIntoViewIfNeeded();
  await button.click();

  try {
    await page.waitForFunction(() => {
      const generated = (window.dataLayer || []).filter((item) => item?.event === 'nvx_conversion_signal' && item.nvx_event_name === 'generate_lead').length - Number(window.__nvxQaGenerateBefore || 0);
      return Number(window.__nvxQaSuccess || 0) >= 1 || generated >= 1;
    }, null, { timeout: 30000 });
  } catch (error) {
    const state = await submissionState(page).catch(() => ({ successMessages: 0, successSources: [], generateLeadDelta: 0 }));
    const currentForm = await formState(frame).catch(() => ({ valid: null, invalid: [], submitDisabled: null }));
    throw new Error(`${scenario}: HubSpot did not accept submission; state=${JSON.stringify(state)} form=${JSON.stringify(currentForm)} network=${JSON.stringify(network)} cause=${error.name}`);
  } finally {
    page.off('request', onRequest);
    page.off('response', onResponse);
    page.off('requestfailed', onFailed);
  }

  console.log(`HUBSPOT_NETWORK_${scenario}=${JSON.stringify(network)}`);
  await sleep(5000);
  const state = await submissionState(page);
  if (state.generateLeadDelta !== 1) throw new Error(`${scenario}: generate_lead delta=${state.generateLeadDelta}, expected=1`);

  if (scenario === 'ALLOW') {
    for (let attempt = 0; attempt < 40 && auditRequests.length < 1; attempt += 1) await sleep(250);
    await sleep(3000);
    if (auditRequests.length !== 1) throw new Error(`ALLOW: audit request count=${auditRequests.length}, expected=1`);
    for (let attempt = 0; attempt < 40 && auditResponses.length < 1; attempt += 1) await sleep(250);
    if (auditResponses.length !== 1 || auditResponses[0] < 200 || auditResponses[0] >= 300) throw new Error(`ALLOW: audit response statuses=${JSON.stringify(auditResponses)}`);
    const payload = JSON.parse(auditRequests[0] || '{}');
    if (JSON.stringify(payload).includes(email)) throw new Error('ALLOW: raw email leaked in audit payload');
    const expectedHash = createHash('sha256').update(email.trim().toLowerCase()).digest('hex');
    if (payload.email_hash !== expectedHash) throw new Error('ALLOW: email_hash mismatch');
  } else if (auditRequests.length !== 0) {
    throw new Error(`DENY: audit request count=${auditRequests.length}, expected=0`);
  }
  console.log(`SCENARIO_${scenario}=PASS successSources=${JSON.stringify(state.successSources)} generateLeadDelta=1 auditRequests=${auditRequests.length}`);
}

async function runScenario(browser, scenario) {
  const context = await browser.newContext();
  const auditRequests = [];
  const auditResponses = [];
  try {
    const page = await context.newPage();
    await page.route('**/*', async (route) => {
      let block = false;
      try {
        const url = new URL(route.request().url());
        const googleHost = /(^|\.)(googleadservices\.com|googlesyndication\.com|doubleclick\.net|googletagmanager\.com|google-analytics\.com|google\.[a-z.]+)$/.test(url.hostname);
        const measurement = /conversion|viewthroughconversion|pagead\/1p-conversion|pagead\/gen_204|\/ccm\/collect|\/g\/collect|\/collect/i.test(url.pathname);
        block = googleHost && measurement;
      } catch {}
      return block ? route.abort() : route.continue();
    });
    page.on('request', (request) => {
      if (request.method() === 'POST' && request.url().includes(edgeNeedle)) auditRequests.push(request.postData() || '');
    });
    page.on('response', (response) => {
      if (response.request().method() === 'POST' && response.url().includes(edgeNeedle)) auditResponses.push(response.status());
    });

    await gotoCanonical(page, scenario.gclid);
    await verifySha(page);
    const frame = await hubSpotFrame(page);
    const initial = await page.evaluate(() => typeof window.wp_has_consent === 'function' ? window.wp_has_consent('marketing') : 'missing');
    console.log(`SCENARIO_${scenario.name}_INITIAL_MARKETING=${initial}`);
    if (initial === true) {
      await setMarketing(page, false);
      await waitValue(frame, 'nvx_google_click_id', (value) => !value, `${scenario.name} pre-consent clear`);
    }
    await assertNever(frame, 'nvx_google_click_id', scenario.gclid, `${scenario.name} pre-consent leak`);

    await setMarketing(page, scenario.allowed);
    if (scenario.allowed) {
      await waitValue(frame, 'nvx_google_click_id', (value) => value === scenario.gclid, 'ALLOW GCLID population');
      await setMarketing(page, false);
      await waitValue(frame, 'nvx_google_click_id', (value) => !value, 'ALLOW revoke');
      await setMarketing(page, true);
      await waitValue(frame, 'nvx_google_click_id', (value) => value === scenario.gclid, 'ALLOW regrant');
    } else {
      await assertNever(frame, 'nvx_google_click_id', scenario.gclid, 'DENY GCLID must remain empty');
    }

    await submit(page, frame, scenario.name, scenario.email, auditRequests, auditResponses);
  } finally {
    await context.close();
  }
}

const browser = await chromium.launch({ headless: true });
try {
  for (const scenario of scenarios) await runScenario(browser, scenario);
  console.log(`ALLOW_GCLID=${scenarios[0].gclid}`);
  console.log(`DENY_GCLID=${scenarios[1].gclid}`);
  console.log('GOOGLE_LEGACY_ATTRIBUTION_E2E=PASS');
} finally {
  await browser.close();
}
