import { chromium } from 'playwright';

const base = 'https://staging2.nuvanx.com';
const formId = '5042522a-0bc5-4381-ac3e-5aee8649b69c';
const runId = Date.now();
const target = `${base}/madrid/valoracion/?gclid=NVXDIAG-${runId}`;
const diagnosticEmail = `qa-hubspot-diag-${runId}@example.com`;
const diagnosticPhone = '+34600000000';

function sanitizeUrl(value) {
  try {
    const url = new URL(value);
    if (!/^https?:$/.test(url.protocol)) return url.protocol === 'about:' ? value : '';
    return `${url.origin}${url.pathname}`;
  } catch {
    return '';
  }
}

function isHubSpotUrl(value) {
  try {
    return /(^|\.)(hubspot\.com|hsforms\.com|hsforms\.net|forms-eu1\.com)$/.test(new URL(value).hostname.toLowerCase());
  } catch {
    return false;
  }
}

function redact(text) {
  return String(text || '')
    .replaceAll(diagnosticEmail, '[email]')
    .replaceAll(diagnosticPhone, '[phone]')
    .replace(/NVXDIAG-\d+/g, 'NVXDIAG-[run]')
    .slice(0, 300);
}

// Desktop Chrome UA to clear SiteGround's anti-bot layer, matching the established
// scripts/staging2/google-attribution-audit.mjs harness (default headless UA is flakier here).
const desktopUserAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36';

const browser = await chromium.launch({ headless: true });
try {
  const context = await browser.newContext({ userAgent: desktopUserAgent });
  const page = await context.newPage();
  const failures = [];
  const hubSpotPosts = [];
  const hubSpotResponses = [];
  const pageErrors = [];
  const consoleErrors = [];

  page.on('requestfailed', (request) => {
    const url = request.url();
    if (isHubSpotUrl(url)) failures.push({ url: sanitizeUrl(url), error: redact(request.failure()?.errorText || '') });
  });
  page.on('request', (request) => {
    if (request.method() === 'POST' && isHubSpotUrl(request.url())) hubSpotPosts.push(sanitizeUrl(request.url()));
  });
  page.on('response', (response) => {
    if (response.request().method() === 'POST' && isHubSpotUrl(response.url())) {
      hubSpotResponses.push({ url: sanitizeUrl(response.url()), status: response.status() });
    }
  });
  page.on('pageerror', (error) => pageErrors.push(redact(error.message)));
  page.on('console', (message) => {
    if (message.type() === 'error' || message.type() === 'warning') consoleErrors.push({ type: message.type(), text: redact(message.text()) });
  });

  let response = null;
  let observedStatus = 0;
  let observedUrl = '';
  for (let attempt = 1; attempt <= 8; attempt += 1) {
    response = null;
    try {
      response = await page.goto(target, { waitUntil: 'domcontentloaded', timeout: 45000 });
      const status = response?.status() || 0;
      if (status) {
        observedStatus = status;
        observedUrl = page.url();
      }
      console.log(`NAV attempt=${attempt} status=${status} url=${sanitizeUrl(page.url())}`);
      if (status === 200 && new URL(page.url()).pathname === '/madrid/valoracion/') break;
    } catch (error) {
      console.log(`NAV attempt=${attempt} error=${redact(error.message)}`);
    }
    await page.waitForTimeout(2500);
  }

  const finalStatus = response?.status() || observedStatus || 0;
  const finalUrl = response ? page.url() : (observedUrl || page.url());
  const finalPathname = (() => {
    try { return new URL(finalUrl).pathname; } catch { return ''; }
  })();

  if (!response || finalStatus !== 200 || finalPathname !== '/madrid/valoracion/') {
    throw new Error(`Navigation failed: status=${finalStatus} pathname=${finalPathname}`);
  }

  async function collectDiagnostics() {
    const iframeMeta = await page.locator('#nvx-hubspot-form iframe').evaluateAll((nodes) => nodes.map((node) => ({
      src: node.getAttribute('src'),
      title: node.getAttribute('title'),
      dataTestId: node.dataset.testId,
      name: node.getAttribute('name'),
      id: node.id,
    })));
    console.log(`IFRAMES=${JSON.stringify(iframeMeta.map((meta) => ({ ...meta, src: sanitizeUrl(meta.src) })))}`);
    console.log(`FRAME_COUNT=${page.frames().length}`);

    const frames = page.frames();
    for (let i = 0; i < frames.length; i += 1) {
      const frame = frames[i];
      let fields = [];
      let forms = 0;
      let inspectError = '';
      try {
        forms = await frame.locator('form').count();
        fields = await frame.locator('input,textarea,select,button').evaluateAll((nodes) => nodes.map((node) => ({
          tag: node.tagName.toLowerCase(),
          name: node.getAttribute('name') || '',
          type: node.getAttribute('type') || '',
          id: node.id || '',
          required: Boolean(node.required || node.getAttribute('aria-required') === 'true'),
        })).slice(0, 100));
      } catch {
        inspectError = 'FRAME_INSPECT_ERROR';
      }
      console.log(`FRAME_${i}=${JSON.stringify({ url: sanitizeUrl(frame.url()), forms, fields, inspectError })}`);
    }
    console.log(`HUBSPOT_REQUEST_FAILURES=${JSON.stringify(failures)}`);
  }

  try {
    await page.locator(`#nvx-hubspot-form iframe[data-test-id*="${formId}"]`).first().waitFor({ state: 'attached', timeout: 30000 });
  } catch (error) {
    // The iframe never attached — collect the diagnostics we do have (this is the case the
    // script exists to investigate) before surfacing the failure.
    await collectDiagnostics().catch(() => {});
    throw error;
  }
  await page.waitForTimeout(5000);
  await collectDiagnostics();

  const iframe = page.locator(`#nvx-hubspot-form iframe[data-test-id*="${formId}"]`).first();
  const handle = await iframe.elementHandle();
  const frame = handle ? await handle.contentFrame() : null;
  if (!frame) throw new Error('HubSpot content frame unavailable');

  await page.evaluate((canonicalFormId) => {
    window.__nvxDiagFormEvents = [];
    for (const name of ['hs-form-event:on-submission:success', 'hs-form-event:on-submission:failed']) {
      window.addEventListener(name, (event) => {
        window.__nvxDiagFormEvents.push({ name, formId: String(event?.detail?.formId || '').toLowerCase() === canonicalFormId ? canonicalFormId : 'other' });
      });
    }
  }, formId);

  await frame.evaluate(() => {
    window.__nvxDiagDomSubmit = [];
    const form = document.querySelector('form');
    if (!form) throw new Error('form missing');
    form.addEventListener('submit', (event) => {
      window.__nvxDiagDomSubmit.push({ phase: 'capture', defaultPrevented: event.defaultPrevented });
      queueMicrotask(() => window.__nvxDiagDomSubmit.push({ phase: 'after-listeners', defaultPrevented: event.defaultPrevented }));
    }, true);
    form.addEventListener('submit', (event) => {
      window.__nvxDiagDomSubmit.push({ phase: 'bubble', defaultPrevented: event.defaultPrevented });
    });
  });

  const fill = async (name, value) => {
    const input = frame.locator(`input[name="0-1/${name}"],input[name="${name}"]`).first();
    if (!await input.count()) throw new Error(`missing ${name}`);
    await input.fill(value);
    await input.press('Tab').catch(() => {});
  };
  await fill('firstname', 'QA');
  await fill('lastname', 'Diagnostic');
  await fill('email', diagnosticEmail);
  const phone = frame.locator('input[type="tel"]').first();
  await phone.fill(diagnosticPhone);
  await phone.press('Tab').catch(() => {});

  const checks = frame.locator('input[type="checkbox"][required],input[type="checkbox"][aria-required="true"]');
  for (let index = 0; index < await checks.count(); index += 1) await checks.nth(index).check();

  const v4 = await page.evaluate(async ({ canonicalFormId, email, phoneValue }) => {
    const api = window.HubSpotFormsV4;
    const forms = Array.from(api?.getForms?.() || []);
    const form = forms.find((item) => String(item?.getFormId?.() || '').toLowerCase() === canonicalFormId);
    if (!form) return { available: false };
    const values = await form.getFormFieldValues();
    const names = new Set(values.map((field) => field.name));
    const set = (candidates, value) => {
      const name = candidates.find((candidate) => names.has(candidate));
      if (name) form.setFieldValue(name, value);
      return name || '';
    };
    const applied = {
      firstname: set(['0-1/firstname', 'firstname'], 'QA'),
      lastname: set(['0-1/lastname', 'lastname'], 'Diagnostic'),
      email: set(['0-1/email', 'email'], email),
      phone: set(['0-1/phone', 'phone'], phoneValue),
    };
    await new Promise((resolve) => setTimeout(resolve, 500));
    return { available: true, applied, fieldCount: names.size };
  }, { canonicalFormId: formId, email: diagnosticEmail, phoneValue: diagnosticPhone });
  console.log(`V4=${JSON.stringify(v4)}`);

  const button = frame.locator('button[type="submit"],input[type="submit"]').first();
  const before = await frame.locator('form').first().evaluate((form) => ({ valid: form.checkValidity(), submitDisabled: Boolean(form.querySelector('[type="submit"]')?.disabled) }));
  console.log(`BEFORE_SUBMIT=${JSON.stringify(before)}`);
  // Baseline the POST count so only requests produced by THIS click are considered; hubSpotPosts
  // accumulates from before page.goto, so a pre-existing POST would otherwise mask this attempt.
  const postCountBeforeSubmit = hubSpotPosts.length;
  await button.click();
  // Wait up to ~15s for the first submission's POST before considering a fallback, so a slow
  // HubSpot v4 submission is not double-submitted (which would create a duplicate CRM lead).
  for (let attempt = 0; attempt < 60 && hubSpotPosts.length === postCountBeforeSubmit; attempt += 1) await page.waitForTimeout(250);

  let after = await frame.evaluate(() => ({
    domSubmit: window.__nvxDiagDomSubmit || [],
    validity: document.querySelector('form')?.checkValidity() ?? null,
    invalid: Array.from(document.querySelectorAll(':invalid')).map((node) => ({ name: node.getAttribute('name') || '', type: node.getAttribute('type') || '', ariaInvalid: node.getAttribute('aria-invalid') || '' })).slice(0, 20),
    messages: Array.from(document.querySelectorAll('[role="alert"],[aria-live],.hs-error-msg,.hs-error-msgs,[class*="error"]'))
      .map((node) => ({ tag: node.tagName.toLowerCase(), className: String(node.className || '').slice(0, 120), text: String(node.textContent || '').trim().slice(0, 200) }))
      .filter((item) => item.text)
      .slice(0, 20),
  }));
  after.messages = after.messages.map((item) => ({ ...item, text: redact(item.text) }));
  console.log(`AFTER_CLICK=${JSON.stringify(after)}`);
  console.log(`PARENT_EVENTS_AFTER_CLICK=${JSON.stringify(await page.evaluate(() => window.__nvxDiagFormEvents || []))}`);
  console.log(`HUBSPOT_POSTS_AFTER_CLICK=${JSON.stringify(hubSpotPosts.slice(postCountBeforeSubmit))}`);

  if (hubSpotPosts.length === postCountBeforeSubmit) {
    await button.evaluate((node) => node.form?.requestSubmit(node));
    await page.waitForTimeout(3000);
    after = await frame.evaluate(() => ({
      domSubmit: window.__nvxDiagDomSubmit || [],
      validity: document.querySelector('form')?.checkValidity() ?? null,
      invalid: Array.from(document.querySelectorAll(':invalid')).map((node) => ({ name: node.getAttribute('name') || '', type: node.getAttribute('type') || '', ariaInvalid: node.getAttribute('aria-invalid') || '' })).slice(0, 20),
      messages: Array.from(document.querySelectorAll('[role="alert"],[aria-live],.hs-error-msg,.hs-error-msgs,[class*="error"]'))
        .map((node) => ({ tag: node.tagName.toLowerCase(), className: String(node.className || '').slice(0, 120), text: String(node.textContent || '').trim().slice(0, 200) }))
        .filter((item) => item.text)
        .slice(0, 20),
    }));
    after.messages = after.messages.map((item) => ({ ...item, text: redact(item.text) }));
    console.log(`AFTER_REQUEST_SUBMIT=${JSON.stringify(after)}`);
  }

  console.log(`PARENT_FORM_EVENTS=${JSON.stringify(await page.evaluate(() => window.__nvxDiagFormEvents || []))}`);
  console.log(`HUBSPOT_POSTS=${JSON.stringify(hubSpotPosts)}`);
  console.log(`HUBSPOT_RESPONSES=${JSON.stringify(hubSpotResponses)}`);
  console.log(`PAGE_ERRORS=${JSON.stringify(pageErrors)}`);
  console.log(`CONSOLE_ERRORS=${JSON.stringify(consoleErrors.slice(-20))}`);
} finally {
  await browser.close();
}
