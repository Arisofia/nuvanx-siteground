#!/usr/bin/env node

import { chromium } from '@playwright/test';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedSha = (process.env.EXPECTED_DEPLOY_SHA || '').trim().toLowerCase();
const attempts = Math.max(1, Number(process.env.VERIFY_RETRIES || 8));
const delayMs = Math.max(1000, Number(process.env.VERIFY_RETRY_DELAY_MS || 5000));
const assessmentId = '5042522a-0bc5-4381-ac3e-5aee8649b69c';
const uuid = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/;
const routes = [
  { path: '/contacto/', context: 'contacto' },
  { path: '/madrid/valoracion/', context: 'valoracion', expectedId: assessmentId },
];

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const assert = (condition, message) => { if (!condition) throw new Error(message); };

async function open(page, path) {
  let result = {};
  for (let attempt = 1; attempt <= attempts; attempt += 1) {
    const response = await page.goto(`${baseUrl}${path}`, { waitUntil: 'domcontentloaded', timeout: 60000 }).catch(() => null);
    await page.waitForLoadState('networkidle', { timeout: 8000 }).catch(() => {});
    const title = await page.title().catch(() => '');
    const html = await page.content().catch(() => '');
    result = {
      path,
      status: response?.status() || 0,
      sha: ((await page.locator('meta[name="nvx-deploy-sha"]').getAttribute('content').catch(() => '')) || '').toLowerCase(),
      edge: /sgcaptcha|robot challenge|access denied/i.test(`${title} ${html}`),
    };
    if (result.status === 200 && !result.edge && (!expectedSha || result.sha === expectedSha)) return result;
    if (attempt < attempts) await sleep(delayMs * attempt);
  }
  return result;
}

async function inspect(page, route) {
  const deployment = await open(page, route.path);
  assert(deployment.status === 200 && !deployment.edge, `${route.path}: unavailable ${JSON.stringify(deployment)}`);
  if (expectedSha) assert(deployment.sha === expectedSha, `${route.path}: expected SHA ${expectedSha}, found ${deployment.sha || 'missing'}`);

  const frame = page.locator('.hs-form-frame[data-form-id][data-portal-id][data-region]');
  assert(await frame.count() === 1, `${route.path}: expected one HubSpot frame`);
  const formId = ((await frame.getAttribute('data-form-id')) || '').toLowerCase();
  const portalId = (await frame.getAttribute('data-portal-id')) || '';
  const region = ((await frame.getAttribute('data-region')) || '').toLowerCase();
  assert(uuid.test(formId), `${route.path}: invalid form UUID ${formId || 'missing'}`);
  assert(portalId === '147416356', `${route.path}: unexpected portal ${portalId || 'missing'}`);
  assert(region === 'eu1', `${route.path}: unexpected region ${region || 'missing'}`);
  if (route.expectedId) assert(formId === route.expectedId, `${route.path}: unexpected valoración form ${formId}`);
  else assert(formId !== assessmentId, `${route.path}: contacto reuses valoración form`);

  const embeds = await page.locator('script[src="https://js-eu1.hsforms.net/forms/embed/147416356.js"]').count();
  assert(embeds === 1, `${route.path}: expected one canonical embed script, found ${embeds}`);
  const privacy = page.locator('a[href$="/politica-privacidad/"]');
  assert(await privacy.count() > 0, `${route.path}: privacy link missing`);
  assert(await privacy.first().isVisible(), `${route.path}: privacy link hidden`);

  if (route.context === 'contacto') {
    assert(await page.getByText('El formulario de contacto no está disponible temporalmente.', { exact: false }).count() === 0, `${route.path}: contact form configuration missing`);
  }

  const config = await page.evaluate(() => window.nvxConversionEvents || {});
  assert(config?.forms?.valoracion === assessmentId, `${route.path}: valoración context missing`);
  assert(String(config?.forms?.[route.context] || '').toLowerCase() === formId, `${route.path}: analytics form context mismatch`);

  const probe = await page.evaluate(({ id }) => {
    window.dataLayer = [];
    window.__nvxGtagCalls = [];
    window.gtag = (...args) => window.__nvxGtagCalls.push(args);
    const event = new CustomEvent('hs-form-event:on-submission:success', { detail: { formId: id, instanceId: `ci-${id}` } });
    window.dispatchEvent(event);
    window.dispatchEvent(event);
    return {
      signals: window.dataLayer.filter((entry) => entry?.event === 'nvx_conversion_signal'),
      calls: window.__nvxGtagCalls,
    };
  }, { id: formId });

  assert(probe.signals.length === 1, `${route.path}: lead event is not deduplicated`);
  assert(probe.signals[0].nvx_event_name === 'generate_lead', `${route.path}: generate_lead missing`);
  assert(probe.signals[0].form_context === route.context, `${route.path}: wrong form context`);
  assert(probe.signals[0].form_id === formId, `${route.path}: wrong form ID in event`);
  assert(probe.calls.length === 1 && probe.calls[0][0] === 'event' && probe.calls[0][1] === 'generate_lead', `${route.path}: malformed gtag event`);

  return { deployment, formId, portalId, region, privacyLinks: await privacy.count(), probe };
}

const options = { locale: 'es-ES', timezoneId: 'Europe/Madrid', viewport: { width: 1440, height: 1000 } };
if (process.env.BASIC_USER && process.env.BASIC_PASSWORD) options.httpCredentials = { username: process.env.BASIC_USER, password: process.env.BASIC_PASSWORD };
const browser = await chromium.launch({ headless: true });
const context = await browser.newContext(options);
const page = await context.newPage();

try {
  const results = [];
  for (const route of routes) results.push(await inspect(page, route));
  assert(results[0].formId !== results[1].formId, 'contacto and valoración must use different form IDs');
  const evidence = JSON.stringify(results).toLowerCase();
  for (const forbidden of ['submissionvalues', 'firstname', 'lastname', 'email_address', 'phone_number']) {
    assert(!evidence.includes(forbidden), `PII fragment leaked into evidence: ${forbidden}`);
  }
  console.log(JSON.stringify({ expectedSha, results }, null, 2));
  console.log('PASS: rendered HubSpot form runtime contract');
} finally {
  await context.close();
  await browser.close();
}
