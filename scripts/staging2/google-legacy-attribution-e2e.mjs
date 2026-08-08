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

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function pathnameOf(value) {
  try {
    return new URL(value).pathname;
  } catch (_) {
    return '';
  }
}

async function gotoCanonical(page, gclid) {
  const canonicalPath = '/madrid/valoracion/';
  const target = `${baseUrl}${canonicalPath}?gclid=${encodeURIComponent(gclid)}`;
  let last = '';
  let lastMainDocumentStatus = 0;

  const rememberMainDocumentStatus = (response) => {
    try {
      const request = response.request();
      if (request.isNavigationRequest() && request.frame() === page.mainFrame()) {
        lastMainDocumentStatus = response.status();
      }
    } catch (_) {
      // Ignore non-document responses that cannot expose a frame.
    }
  };
  page.on('response', rememberMainDocumentStatus);

  try {
    for (let attempt = 1; attempt <= 6; attempt += 1) {
      try {
        const response = await page.goto(target, { waitUntil: 'domcontentloaded', timeout: 45000 });
        const status = response?.status() || lastMainDocumentStatus || 0;
        const path = pathnameOf(page.url());
        last = `${status} ${page.url()}`;
        console.log(`NAV attempt=${attempt} status=${status} path=${path}`);
        if (status === 200 && path === canonicalPath) return;

        if (status === 202) {
          const captchaHeader = String((await response?.headerValue('sg-captcha').catch(() => '')) || '').toLowerCase();
          const classification = captchaHeader === 'challenge' ? 'challenge' : 'suspected_challenge';
          console.log(`NAV attempt=${attempt} siteground_antibot=${classification} settle_ms=30000`);

          // Do not immediately navigate away from a SiteGround 202 response. Its browser
          // challenge needs time to run and can reload the canonical URL after setting the
          // short-lived verification state for this runner/IP. We still require a real 200.
          for (let second = 0; second < 30; second += 1) {
            await sleep(1000);
            const settledPath = pathnameOf(page.url());
            if (lastMainDocumentStatus === 200 && settledPath === canonicalPath) {
              console.log(`NAV attempt=${attempt} challenge_resolved status=200 path=${settledPath}`);
              return;
            }
          }

          const settledPath = pathnameOf(page.url());
          last = `${lastMainDocumentStatus || status} ${page.url()}`;
          console.log(
            `NAV attempt=${attempt} challenge_unresolved status=${lastMainDocumentStatus || status} path=${settledPath}`
          );
          await sleep(5000);
          continue;
        }
      } catch (error) {
        last = error.message;
        console.log(`NAV attempt=${attempt} error=${error.message}`);
      }
      await sleep(5000);
    }
  } finally {
    page.off('response', rememberMainDocumentStatus);
  }

  throw new Error(`Unable to reach canonical valoración route with HTTP 200: ${last}`);
}

async function verifySha(page) {
  if (!expectedSha) return;
  const actual = await page.evaluate(() => document.querySelector('meta[name="nvx-deploy-sha"]')?.getAttribute('content') || '');
  if (actual !== expectedSha) throw new Error(`Staging SHA mismatch: expected=${expectedSha} actual=${actual}`);
}

function fieldSelector(name) {
  const escaped = name.replaceAll('"', String.raw`\"`);
  return `input[name="${escaped}"], input[name="0-1/${escaped}"]`;
}

async function findHubSpotFrame(page) {
  const iframe = page.locator(`#nvx-hubspot-form iframe[data-test-id*="${formId}"]`).first();
  await iframe.waitFor({ state: 'attached', timeout: 30000 });
  for (let attempt = 0; attempt < 60; attempt += 1) {
    const handle = await iframe.elementHandle().catch(() => null);
    try {
      const frame = handle ? await handle.contentFrame().catch(() => null) : null;
      if (frame && (await frame.locator(fieldSelector('email')).count().catch(() => 0)) > 0) return frame;
    } finally {
      await handle?.dispose().catch(() => {});
    }
    await sleep(500);
  }
  throw new Error('HubSpot form iframe exists but email field never became available');
}

async function setMarketing(page, allowed) {
  const state = await page.evaluate(async (nextAllowed) => {
    if (typeof window.wp_has_consent !== 'function') throw new Error('wp_has_consent unavailable');
    if (typeof window.wp_set_consent !== 'function') throw new Error('wp_set_consent unavailable');
    window.wp_set_consent('marketing', nextAllowed ? 'allow' : 'deny');
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

async function assertFieldNever(frame, name, forbidden, label, samples = 20) {
  if ((await fieldValue(frame, name)) === null) {
    throw new Error(`${label}: field ${name} not present, cannot assert absence of leak`);
  }
  for (let attempt = 0; attempt < samples; attempt += 1) {
    const value = await fieldValue(frame, name);
    if (value === forbidden) throw new Error(`${label}: field ${name} leaked forbidden value`);
    await sleep(250);
  }
}

async function inspectFields(frame) {
  return frame.locator('input, textarea, select').evaluateAll((nodes) => nodes.map((node) => {
    const name = node.getAttribute('name') || '';
    const isAttribution = /nvx_google|hs_google_click_id|gclid|gbraid|wbraid|gclsrc/i.test(name);
    return {
      tag: node.tagName.toLowerCase(),
      name,
      type: node.getAttribute('type') || '',
      required: node.required || node.getAttribute('aria-required') === 'true',
      hidden: node.type === 'hidden' || node.hidden,
      checked: node.type === 'checkbox' || node.type === 'radio' ? Boolean(node.checked) : undefined,
      value: isAttribution ? node.value : '[redacted]',
    };
  }));
}

function fillValue(name, type, email) {
  const normalized = String(name || '').toLowerCase();
  if (type === 'email' || normalized.includes('email') || normalized.includes('correo')) return email;
  if (type === 'tel' || normalized.includes('phone') || normalized.includes('telefono') || normalized.includes('teléfono') || normalized.includes('mobile')) return '600000000';
  if (normalized.includes('firstname') || normalized === 'nombre' || normalized.includes('first_name')) return 'QA';
  if (normalized.includes('lastname') || normalized.includes('apellido') || normalized.includes('last_name')) return 'Release';
  if (normalized.includes('postal') || normalized.includes('zip')) return '28010';
  if (normalized.includes('city') || normalized.includes('ciudad')) return 'Madrid';
  if (normalized.includes('message') || normalized.includes('mensaje') || normalized.includes('coment')) return 'QA release attribution. Registro técnico, no paciente real.';
  if (type === 'number') return '1';
  if (type === 'date') return '2026-08-08';
  return 'QA';
}

async function checkHubSpotBox(frame, element) {
  if (await element.isChecked().catch(() => false)) return;

  await element.check({ force: true }).catch(() => {});
  if (await element.isChecked().catch(() => false)) return;

  const labelId = await element.getAttribute('aria-labelledby');
  if (labelId) {
    const safeLabelId = labelId.replaceAll('"', String.raw`\"`);
    await frame.locator(`[id="${safeLabelId}"]`).click({ force: true }).catch(() => {});
    if (await element.isChecked().catch(() => false)) return;
  }

  await element.evaluate((node) => {
    const descriptor = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'checked');
    if (descriptor && typeof descriptor.set === 'function') descriptor.set.call(node, true);
    else node.checked = true;
    node.dispatchEvent(new Event('input', { bubbles: true }));
    node.dispatchEvent(new Event('change', { bubbles: true }));
    node.dispatchEvent(new Event('click', { bubbles: true }));
  }).catch(() => {});

  if (!(await element.isChecked().catch(() => false))) {
    throw new Error('Required HubSpot checkbox could not be checked');
  }
}

async function fillForm(frame, email) {
  const controls = frame.locator('form input, form textarea, form select');
  const count = await controls.count();
  console.log(`FORM_CONTROL_COUNT=${count}`);
  const radioDone = new Set();

  for (let index = 0; index < count; index += 1) {
    const element = controls.nth(index);
    let tag = '';
    let type = '';
    let name = '';
    let visible = false;
    let required = false;

    try {
      tag = await element.evaluate((node) => node.tagName.toLowerCase());
      type = String((await element.getAttribute('type')) || '').toLowerCase();
      name = String((await element.getAttribute('name')) || '');
      visible = await element.isVisible().catch(() => false);
      required = (await element.getAttribute('required')) !== null || (await element.getAttribute('aria-required')) === 'true';
    } catch (error) {
      console.log(`FILL_SKIP index=${index} error=${error.message}`);
      continue;
    }

    if (type === 'hidden' || type === 'submit' || type === 'button' || /google|gclid|gbraid|wbraid|gclsrc|utm|attribution/i.test(name)) continue;

    try {
      if (type === 'checkbox') {
        if (required) await checkHubSpotBox(frame, element);
      } else if (type === 'radio') {
        if (required && !radioDone.has(name)) {
          await element.check({ force: true });
          radioDone.add(name);
        }
      } else if (tag === 'select') {
        if (!visible && !required) continue;
        const options = await element.locator('option').evaluateAll((nodes) => nodes
          .map((option) => ({ value: option.value, disabled: option.disabled }))
          .filter((option) => option.value && !option.disabled));
        if (options.length) await element.selectOption(options[0].value);
      } else {
        if (!visible && !required) continue;
        const current = await element.inputValue().catch(() => '');
        if (!current) await element.fill(fillValue(name, type, email));
      }
    } catch (error) {
      console.log(`FILL_WARN name=${name} type=${type} required=${required} error=${error.message}`);
      if (required) throw error;
    }
  }

  const emailInput = frame.locator(fieldSelector('email')).first();
  if ((await emailInput.count()) === 0) throw new Error('HubSpot email field disappeared before submit');
  await emailInput.fill(email);
}

async function formDiagnostics(frame) {
  const form = frame.locator('form').first();
  if ((await form.count()) === 0) return { present: false, valid: false, invalid: [], submitDisabled: null };
  return form.evaluate((node) => {
    const invalid = Array.from(node.querySelectorAll(':invalid')).slice(0, 20).map((field) => ({
      tag: field.tagName.toLowerCase(),
      name: field.getAttribute('name') || '',
      type: field.getAttribute('type') || '',
      required: Boolean(field.required || field.getAttribute('aria-required') === 'true'),
      checked: field.type === 'checkbox' || field.type === 'radio' ? Boolean(field.checked) : undefined,
    }));
    const submit = node.querySelector('input[type="submit"], button[type="submit"]');
    return {
      present: true,
      valid: typeof node.checkValidity === 'function' ? node.checkValidity() : null,
      invalid,
      submitDisabled: submit ? Boolean(submit.disabled || submit.getAttribute('aria-disabled') === 'true') : null,
    };
  });
}

async function submissionState(page) {
  return page.evaluate(() => {
    const after = (window.dataLayer || []).filter((item) => item?.event === 'nvx_conversion_signal' && item.nvx_event_name === 'generate_lead').length;
    return {
      successMessages: Number(window.__nvxQaSuccess || 0),
      successSources: Array.isArray(window.__nvxQaSuccessSources) ? window.__nvxQaSuccessSources.slice() : [],
      generateLeadDelta: after - Number(window.__nvxQaGenerateBefore || 0),
    };
  });
}

async function installSubmissionObservers(page) {
  await page.evaluate((canonicalFormId) => {
    window.__nvxQaSuccess = 0;
    window.__nvxQaSuccessSources = [];
    window.__nvxQaGenerateBefore = (window.dataLayer || []).filter((item) => item?.event === 'nvx_conversion_signal' && item.nvx_event_name === 'generate_lead').length;

    const recordSuccess = (source, id) => {
      if (String(id || '').toLowerCase() !== canonicalFormId) return;
      window.__nvxQaSuccess += 1;
      window.__nvxQaSuccessSources.push(source);
    };

    const isAllowedHubSpotOrigin = (origin) => {
      if (!origin || origin === 'null') return false;
      try {
        return /(^|\.)(hubspot\.com|hsforms\.com|hsforms\.net)$/.test(new URL(origin).hostname.toLowerCase());
      } catch {
        return false;
      }
    };

    window.addEventListener('hs-form-event:on-submission:success', (event) => {
      const detail = event?.detail ?? {};
      recordSuccess('hubspot_form_event', detail.formId || '');
    });

    window.addEventListener('message', (event) => {
      if (!isAllowedHubSpotOrigin(event.origin)) return;
      const hubspotIframe = document.querySelector(`#nvx-hubspot-form iframe[data-test-id*="${canonicalFormId}"]`);
      if (!hubspotIframe || event.source !== hubspotIframe.contentWindow) return;
      let data = event.data || {};
      if (typeof data === 'string') {
        try {
          data = JSON.parse(data);
        } catch {
          return;
        }
      }
      if (data.type === 'hsFormCallback' && data.eventName === 'onFormSubmitted') {
        recordSuccess('hubspot_post_message', data.id || '');
      }
    });
  }, formId);
}

async function submitAndAssert(page, frame, scenario, rawEmail, auditRequests, auditResponses) {
  await installSubmissionObservers(page);
  await fillForm(frame, rawEmail);

  const fields = await inspectFields(frame);
  console.log(`FORM_FIELDS_${scenario}=${JSON.stringify(fields)}`);

  const preSubmit = await formDiagnostics(frame);
  console.log(`FORM_VALIDITY_${scenario}=${JSON.stringify(preSubmit)}`);
  if (!preSubmit.present) throw new Error(`${scenario}: HubSpot form disappeared before submit`);
  if (preSubmit.valid === false) throw new Error(`${scenario}: HubSpot form invalid before submit: ${JSON.stringify(preSubmit.invalid)}`);
  if (preSubmit.submitDisabled === true) throw new Error(`${scenario}: HubSpot submit control is disabled`);

  const submit = frame.locator('form input[type="submit"], form button[type="submit"], input[type="submit"], button[type="submit"]').first();
  if ((await submit.count()) === 0) throw new Error('No submit control found');
  await submit.click({ force: true });

  try {
    await page.waitForFunction(() => {
      const after = (window.dataLayer || []).filter((item) => item?.event === 'nvx_conversion_signal' && item.nvx_event_name === 'generate_lead').length;
      const delta = after - Number(window.__nvxQaGenerateBefore || 0);
      return Number(window.__nvxQaSuccess || 0) >= 1 || delta >= 1;
    }, null, { timeout: 30000 });
  } catch (error) {
    const [state, validity] = await Promise.all([
      submissionState(page).catch(() => ({ successMessages: 0, successSources: [], generateLeadDelta: 0 })),
      formDiagnostics(frame).catch(() => ({ present: false, valid: null, invalid: [], submitDisabled: null })),
    ]);
    throw new Error(`${scenario}: no HubSpot submission success within 30s; state=${JSON.stringify(state)} form=${JSON.stringify(validity)} cause=${error.name}`);
  }

  await sleep(5000);
  const result = await submissionState(page);
  if (result.generateLeadDelta !== 1) {
    throw new Error(`${scenario}: generate_lead delta=${result.generateLeadDelta}, expected=1 sources=${JSON.stringify(result.successSources)}`);
  }

  if (scenario === 'ALLOW') {
    for (let attempt = 0; attempt < 40 && auditRequests.length < 1; attempt += 1) await sleep(250);
    await sleep(3000);
    if (auditRequests.length !== 1) {
      // The theme fires the audit POST only from the hs-form-event:on-submission:success
      // CustomEvent (nvx-conversion-events.js). If success was detected solely via the HubSpot
      // postMessage, that CustomEvent never ran and no audit request can appear — surface that
      // explicitly instead of a bare count mismatch.
      const missingCustomEvent = auditRequests.length === 0 && !result.successSources.includes('hubspot_form_event');
      const hint = missingCustomEvent
        ? ' (no hs-form-event:on-submission:success CustomEvent observed; success came from postMessage only)'
        : '';
      throw new Error(`ALLOW: audit request count=${auditRequests.length}, expected=1 sources=${JSON.stringify(result.successSources)}${hint}`);
    }
    for (let attempt = 0; attempt < 40 && auditResponses.length < 1; attempt += 1) await sleep(250);
    if (auditResponses.length !== 1 || auditResponses[0] < 200 || auditResponses[0] >= 300) {
      throw new Error(`ALLOW: audit response statuses=${JSON.stringify(auditResponses)}`);
    }
    if (!auditRequests[0]) throw new Error('ALLOW: audit payload body unavailable');
    const payload = JSON.parse(auditRequests[0]);
    if (JSON.stringify(payload).includes(rawEmail)) throw new Error('ALLOW: raw email leaked in audit payload');
    const expectedHash = createHash('sha256').update(rawEmail.trim().toLowerCase()).digest('hex');
    if (String(payload.email_hash || '') !== expectedHash) throw new Error('ALLOW: email_hash mismatch, expected SHA-256 of submitted email');
    console.log(`ALLOW_AUDIT_PAYLOAD=${JSON.stringify(payload)}`);
  } else {
    for (let attempt = 0; attempt < 20; attempt += 1) {
      if (auditRequests.length > 0) break;
      await sleep(250);
    }
    if (auditRequests.length !== 0) throw new Error(`DENY: audit request count=${auditRequests.length}, expected=0`);
  }

  console.log(`SCENARIO_${scenario}=PASS successMessages=${result.successMessages} successSources=${JSON.stringify(result.successSources)} generateLeadDelta=${result.generateLeadDelta} auditRequests=${auditRequests.length}`);
}

async function runScenario(browser, { scenario, gclid, email, allowed }) {
  const context = await browser.newContext();
  const auditRequests = [];
  const auditResponses = [];

  try {
    const page = await context.newPage();

    await page.route('**/*', async (route) => {
      let shouldBlock = false;
      try {
        const url = new URL(route.request().url());
        const googleAdHost = /(^|\.)(googleadservices\.com|googlesyndication\.com|doubleclick\.net|googletagmanager\.com|google\.[a-z.]+)$/.test(url.hostname);
        const conversionPath = /conversion|viewthroughconversion|pagead\/1p-conversion|pagead\/gen_204/i.test(url.pathname);
        shouldBlock = googleAdHost && conversionPath;
      } catch {
        // Parsing failure is not a reason to stall an unrelated request.
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
      if ((await fieldValue(frame, 'nvx_google_click_id')) === null) throw new Error(`${scenario} pre-consent clear: nvx_google_click_id field disappeared`);
      await waitField(frame, 'nvx_google_click_id', (value) => !value, `${scenario} pre-consent clear`);
    }

    await assertFieldNever(frame, 'nvx_google_click_id', gclid, `${scenario} pre-consent leak`);
    const nativeBefore = await fieldValue(frame, 'hs_google_click_id');
    if (nativeBefore === gclid) throw new Error(`${scenario}: native hs_google_click_id leaked before consent`);

    await setMarketing(page, allowed);
    if (allowed) {
      await waitField(frame, 'nvx_google_click_id', (value) => value === gclid, 'ALLOW custom GCLID population');
      const native = await fieldValue(frame, 'hs_google_click_id');
      if (native && native !== gclid) throw new Error(`ALLOW native hs_google_click_id exists but value=${JSON.stringify(native)}`);

      await setMarketing(page, false);
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

const browser = await chromium.launch({ headless: true });
try {
  await runScenario(browser, { scenario: 'ALLOW', gclid: allowGclid, email: allowEmail, allowed: true });
  await runScenario(browser, { scenario: 'DENY', gclid: denyGclid, email: denyEmail, allowed: false });
  console.log(`ALLOW_GCLID=${allowGclid}`);
  console.log(`DENY_GCLID=${denyGclid}`);
  console.log('GOOGLE_LEGACY_ATTRIBUTION_E2E=PASS');
} finally {
  await browser.close();
}
