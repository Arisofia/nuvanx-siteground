#!/usr/bin/env node

import { chromium } from '@playwright/test';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const username = process.env.BASIC_USER || '';
const password = process.env.BASIC_PASSWORD || '';
const expectedSha = (process.env.EXPECTED_DEPLOY_SHA || '').trim().toLowerCase();
const attempts = Math.max(1, Number(process.env.VERIFY_RETRIES || 8));
const delayMs = Math.max(1000, Number(process.env.VERIFY_RETRY_DELAY_MS || 6000));

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function openCurrentDeployment(page) {
  let last = { status: 0, sha: '', title: '', url: '' };
  for (let attempt = 1; attempt <= attempts; attempt += 1) {
    const response = await page.goto(`${baseUrl}/contacto/`, {
      waitUntil: 'domcontentloaded',
      timeout: 60000,
    }).catch(() => null);
    await page.waitForLoadState('networkidle', { timeout: 8000 }).catch(() => {});

    const status = response?.status() || 0;
    const title = await page.title().catch(() => '');
    const sha = await page.locator('meta[name="nvx-deploy-sha"]').getAttribute('content').catch(() => '') || '';
    const html = await page.content().catch(() => '');
    const edge = status === 202 || /sgcaptcha|robot challenge|access denied/i.test(`${title} ${html}`);
    last = { status, sha: sha.toLowerCase(), title, url: page.url(), edge };

    if (!edge && status === 200 && (!expectedSha || last.sha === expectedSha)) return last;
    if (attempt < attempts) await sleep(delayMs * attempt);
  }
  return last;
}

function assert(condition, message) {
  if (!condition) throw new Error(message);
}

const browser = await chromium.launch({ headless: true });
const contextOptions = {
  locale: 'es-ES',
  timezoneId: 'Europe/Madrid',
  viewport: { width: 1440, height: 1000 },
  userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
};
if (username && password) contextOptions.httpCredentials = { username, password };
const context = await browser.newContext(contextOptions);
const page = await context.newPage();

try {
  const deployment = await openCurrentDeployment(page);
  assert(deployment.status === 200 && !deployment.edge, `staging document unavailable: ${JSON.stringify(deployment)}`);
  if (expectedSha) assert(deployment.sha === expectedSha, `expected deploy ${expectedSha}, received ${deployment.sha || 'missing'}`);

  const scriptCount = await page.locator('script[src*="nvx-conversion-events.js"]').count();
  assert(scriptCount === 1, `expected one conversion script, found ${scriptCount}`);

  const apiReady = await page.evaluate(() => Boolean(
    window.NUVANXConversionEvents
    && typeof window.NUVANXConversionEvents.emit === 'function'
    && typeof window.NUVANXConversionEvents.trackSuccessfulSubmission === 'function'
  ));
  assert(apiReady, 'window.NUVANXConversionEvents API is unavailable');

  const results = await page.evaluate(() => {
    const reset = () => {
      window.dataLayer = [];
      window.__nvxGtagCalls = [];
      window.gtag = (...args) => window.__nvxGtagCalls.push(args);
    };
    const snapshot = () => ({
      signals: window.dataLayer.filter((entry) => entry && entry.event === 'nvx_conversion_signal'),
      calls: window.__nvxGtagCalls.slice(),
    });
    const clickProbe = (attributes) => {
      reset();
      const link = document.createElement('a');
      Object.entries(attributes).forEach(([name, value]) => link.setAttribute(name, value));
      link.textContent = 'CI probe';
      link.addEventListener('click', (event) => event.preventDefault());
      document.body.appendChild(link);
      link.click();
      link.remove();
      return snapshot();
    };

    const reserve = clickProbe({ href: '#ci-reserve', 'data-gtag': 'click-reserve' });
    const whatsapp = clickProbe({ href: '#ci-whatsapp', 'data-gtag': 'click-whatsapp' });
    const phone = clickProbe({ href: 'tel:+34000000000' });

    reset();
    const modernEvent = new CustomEvent('hs-form-event:on-submission:success', {
      detail: { formId: '5042522a-0bc5-4381-ac3e-5aee8649b69c', instanceId: 'ci-modern' },
    });
    window.dispatchEvent(modernEvent);
    window.dispatchEvent(modernEvent);
    const modern = snapshot();

    reset();
    window.dispatchEvent(new MessageEvent('message', {
      origin: 'https://forms-eu1.hsforms.com',
      data: { type: 'hsFormCallback', eventName: 'onFormSubmitted', id: 'ci-legacy-form', data: {} },
    }));
    const legacy = snapshot();

    return { reserve, whatsapp, phone, modern, legacy };
  });

  const expectEvent = (probe, expectedName, label) => {
    assert(probe.signals.length === 1, `${label}: expected one diagnostic signal, found ${probe.signals.length}`);
    assert(probe.signals[0].nvx_event_name === expectedName, `${label}: unexpected signal ${probe.signals[0].nvx_event_name}`);
    assert(probe.calls.length === 1, `${label}: expected one gtag call, found ${probe.calls.length}`);
    assert(probe.calls[0][0] === 'event' && probe.calls[0][1] === expectedName, `${label}: malformed gtag call`);
  };

  expectEvent(results.reserve, 'reserve_click', 'reserve');
  expectEvent(results.whatsapp, 'whatsapp_click', 'whatsapp');
  expectEvent(results.phone, 'phone_click', 'phone');
  expectEvent(results.modern, 'generate_lead', 'modern HubSpot');
  expectEvent(results.legacy, 'generate_lead', 'legacy HubSpot');
  assert(results.modern.signals[0].form_context === 'valoracion', 'modern HubSpot form context is not valoracion');
  assert(results.legacy.signals[0].form_event_source === 'hubspot_legacy', 'legacy source marker is missing');

  const serialized = JSON.stringify(results).toLowerCase();
  for (const forbidden of ['submissionvalues', 'firstname', 'lastname', 'email_address']) {
    assert(!serialized.includes(forbidden), `PII field fragment leaked into analytics result: ${forbidden}`);
  }

  console.log(JSON.stringify({ deployment, headScriptCount, results }, null, 2));
  console.log('NUVANX staging conversion event verification passed.');
} finally {
  await context.close();
  await browser.close();
}
