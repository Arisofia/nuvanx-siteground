#!/usr/bin/env node

import { chromium } from '@playwright/test';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedSha = (process.env.EXPECTED_DEPLOY_SHA || '').trim().toLowerCase();
const attempts = Math.max(1, Number(process.env.VERIFY_RETRIES || 8));
const delayMs = Math.max(1000, Number(process.env.VERIFY_RETRY_DELAY_MS || 5000));
const assessmentId = '5042522a-0bc5-4381-ac3e-5aee8649b69c';
const uuid = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/;

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const assert = (condition, message) => { if (!condition) throw new Error(message); };

async function open(page, path) {
  let result = {};
  for (let attempt = 1; attempt <= attempts; attempt += 1) {
    const response = await page.goto(`${baseUrl}${path}`, { waitUntil: 'domcontentloaded', timeout: 60000 }).catch(() => null);
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
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

async function inspectContacto(page) {
  const deployment = await open(page, '/contacto/');
  assert(deployment.status === 200 && !deployment.edge, `/contacto/: unavailable ${JSON.stringify(deployment)}`);
  if (expectedSha) assert(deployment.sha === expectedSha, `/contacto/: expected SHA ${expectedSha}, found ${deployment.sha || 'missing'}`);

  assert(await page.locator('.hs-form-frame, .hbspt-form').count() === 0, '/contacto/: HubSpot form container must be absent');
  assert(await page.locator('iframe[src*="hsforms"], iframe[src*="hubspot"]').count() === 0, '/contacto/: HubSpot iframe must be absent');
  assert(await page.locator('script[src*="hsforms.net"], script[src*="hubspot"]').count() === 0, '/contacto/: HubSpot embed script must be absent');
  assert(await page.locator('#nvx-contacto-hubspot-form').count() === 0, '/contacto/: legacy contact mount must be absent');
  assert(await page.locator('#nvx-valoracion-modal').count() === 0, '/contacto/: valoración modal must not be rendered');

  assert(await page.locator('.nvx-clinic-card').count() === 2, '/contacto/: expected two clinic cards');
  assert(await page.locator('.nvx-clinic-card__map iframe').count() === 2, '/contacto/: expected two clinic maps');
  assert(await page.locator('a[href^="tel:"]').count() >= 2, '/contacto/: clinic phone links missing');
  assert(await page.locator('a[href*="wa.me"]').count() >= 2, '/contacto/: WhatsApp links missing');

  const valuationLink = page.locator('a[href$="/madrid/valoracion/"]').first();
  assert(await valuationLink.count() === 1 && await valuationLink.isVisible(), '/contacto/: direct valoración route missing');

  return {
    deployment,
    clinicCards: await page.locator('.nvx-clinic-card').count(),
    maps: await page.locator('.nvx-clinic-card__map iframe').count(),
    valuationHref: await valuationLink.getAttribute('href'),
  };
}

async function inspectValoracion(page) {
  const deployment = await open(page, '/madrid/valoracion/');
  assert(deployment.status === 200 && !deployment.edge, `/madrid/valoracion/: unavailable ${JSON.stringify(deployment)}`);
  if (expectedSha) assert(deployment.sha === expectedSha, `/madrid/valoracion/: expected SHA ${expectedSha}, found ${deployment.sha || 'missing'}`);

  const mount = page.locator('#nvx-hubspot-native-form');
  assert(await mount.count() === 1, '/madrid/valoracion/: expected one canonical mount');

  const frame = mount.locator('.hs-form-frame[data-form-id][data-portal-id][data-region]');
  assert(await frame.count() === 1, '/madrid/valoracion/: expected one HubSpot frame');

  const formId = ((await frame.getAttribute('data-form-id')) || '').toLowerCase();
  const portalId = (await frame.getAttribute('data-portal-id')) || '';
  const region = ((await frame.getAttribute('data-region')) || '').toLowerCase();

  assert(uuid.test(formId), `/madrid/valoracion/: invalid form UUID ${formId || 'missing'}`);
  assert(formId === assessmentId, `/madrid/valoracion/: unexpected valoración form ${formId}`);
  assert(portalId === '147416356', `/madrid/valoracion/: unexpected portal ${portalId || 'missing'}`);
  assert(region === 'eu1', `/madrid/valoracion/: unexpected region ${region || 'missing'}`);

  const embeds = await page.locator('script[src="https://js-eu1.hsforms.net/forms/embed/147416356.js"]').count();
  assert(embeds === 1, `/madrid/valoracion/: expected one canonical embed script, found ${embeds}`);

  const privacy = mount.locator('a[href$="/politica-privacidad/"]');
  assert(await privacy.count() === 1, '/madrid/valoracion/: primary form privacy link is missing or duplicated');
  assert(await privacy.isVisible(), '/madrid/valoracion/: primary privacy link hidden');

  await page.waitForTimeout(1500);
  const initializedIframes = await mount.locator('iframe').count();
  assert(initializedIframes === 1, `/madrid/valoracion/: HubSpot did not initialize one iframe, found ${initializedIframes}`);

  const config = await page.evaluate(() => window.nvxConversionEvents || {});
  assert(config?.forms?.valoracion === assessmentId, '/madrid/valoracion/: valoración analytics context missing');
  assert(!config?.forms?.contacto, '/madrid/valoracion/: obsolete contacto form context remains');

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

  assert(probe.signals.length === 1, '/madrid/valoracion/: lead event is not deduplicated');
  assert(probe.signals[0].nvx_event_name === 'generate_lead', '/madrid/valoracion/: generate_lead missing');
  assert(probe.signals[0].form_context === 'valoracion', '/madrid/valoracion/: wrong form context');
  assert(probe.signals[0].form_id === formId, '/madrid/valoracion/: wrong form ID in event');
  assert(probe.calls.length === 1 && probe.calls[0][0] === 'event' && probe.calls[0][1] === 'generate_lead', '/madrid/valoracion/: malformed gtag event');

  return { deployment, formId, portalId, region, initializedIframes, probe };
}

const options = { locale: 'es-ES', timezoneId: 'Europe/Madrid', viewport: { width: 1440, height: 1000 } };
if (process.env.BASIC_USER && process.env.BASIC_PASSWORD) options.httpCredentials = { username: process.env.BASIC_USER, password: process.env.BASIC_PASSWORD };

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext(options);
const page = await context.newPage();

try {
  const results = {
    contacto: await inspectContacto(page),
    valoracion: await inspectValoracion(page),
  };
  const evidence = JSON.stringify(results).toLowerCase();
  for (const forbidden of ['submissionvalues', 'firstname', 'lastname', 'email_address', 'phone_number']) {
    assert(!evidence.includes(forbidden), `PII fragment leaked into evidence: ${forbidden}`);
  }
  console.log(JSON.stringify({ expectedSha, results }, null, 2));
  console.log('PASS: contacto is form-free and valoración has one canonical HubSpot form');
} finally {
  await context.close();
  await browser.close();
}
