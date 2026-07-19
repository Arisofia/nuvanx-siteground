#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium } from '@playwright/test';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const baseUrl = (process.env.BASE_URL || 'https://nuvanx.com').replace(/\/$/, '');
const baseHost = new URL(baseUrl).hostname;
const expectedSha = (process.env.EXPECTED_DEPLOY_SHA || '').trim().toLowerCase();
const outputPath = process.env.OUTPUT_PATH || path.join(root, 'runtime-health.json');
const retries = Math.max(1, Number(process.env.VERIFY_RETRIES || 4));
const delayMs = Math.max(1000, Number(process.env.VERIFY_RETRY_DELAY_MS || 4000));
const routes = (process.env.RUNTIME_ROUTES || '/,/contacto/,/madrid/valoracion/,/endolift-facial-papada-mandibula/,/endolaser-corporal-grasa-localizada/,/laser-co2-fraccionado-madrid-textura-cicatrices-poro/,/exion-btl/')
  .split(',').map((value) => value.trim()).filter(Boolean);
const known = JSON.parse(fs.readFileSync(path.join(root, 'qa/runtime/known-third-party-findings.json'), 'utf8'));
const expired = new Date(`${known.expiresOn}T23:59:59Z`).getTime() < Date.now();
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

function isMetaUrl(raw) {
  try {
    const url = new URL(raw);
    return /(^|\.)(facebook\.com|facebook\.net)$/.test(url.hostname);
  } catch {
    return false;
  }
}

function isFirstParty(raw) {
  try {
    return new URL(raw).hostname === baseHost;
  } catch {
    return false;
  }
}

async function openRoute(page, route, resetCapture) {
  let state = null;
  for (let attempt = 1; attempt <= retries; attempt += 1) {
    resetCapture();
    const response = await page.goto(`${baseUrl}${route}`, { waitUntil: 'domcontentloaded', timeout: 60000 }).catch(() => null);
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
    const title = await page.title().catch(() => '');
    const html = await page.content().catch(() => '');
    const sha = ((await page.locator('meta[name="nvx-deploy-sha"]').getAttribute('content').catch(() => '')) || '').toLowerCase();
    state = {
      status: response?.status() || 0,
      sha,
      edgeChallenge: /sgcaptcha|robot challenge|access denied/i.test(`${title} ${html}`),
    };
    if (state.status === 200 && !state.edgeChallenge && (!expectedSha || sha === expectedSha)) return state;
    if (attempt < retries) await sleep(delayMs * attempt);
  }
  return state;
}

const browser = await chromium.launch({ headless: true });
const contextOptions = {
  locale: 'es-ES',
  timezoneId: 'Europe/Madrid',
  viewport: { width: 1440, height: 1000 },
};
if (process.env.BASIC_USER && process.env.BASIC_PASSWORD) {
  contextOptions.httpCredentials = { username: process.env.BASIC_USER, password: process.env.BASIC_PASSWORD };
}

const results = [];
try {
  for (const route of routes) {
    const context = await browser.newContext(contextOptions);
    const page = await context.newPage();
    const pageErrors = [];
    const consoleErrors = [];
    const responses = [];
    const requests = [];
    const resetCapture = () => {
      pageErrors.length = 0;
      consoleErrors.length = 0;
      responses.length = 0;
      requests.length = 0;
    };

    page.on('pageerror', (error) => pageErrors.push(String(error?.message || error)));
    page.on('console', (message) => {
      if (message.type() !== 'error') return;
      const location = message.location();
      consoleErrors.push({ text: message.text(), url: location?.url || '' });
    });
    page.on('request', (request) => requests.push(request.url()));
    page.on('response', (response) => responses.push({ url: response.url(), status: response.status() }));

    const document = await openRoute(page, route, resetCapture);
    const firstPartyFailures = responses.filter((entry) => isFirstParty(entry.url) && entry.status >= 400);
    const pluginSignalAsset = requests.some((url) => url.includes('/siteground-optimizer-assets/facebook-signal'));
    const gtmMetaPixel = requests.some((raw) => {
      try {
        const url = new URL(raw);
        return /(^|\.)facebook\.com$/.test(url.hostname)
          && url.pathname.includes('/tr')
          && /googletagmanager/i.test(url.searchParams.get('a') || '');
      } catch {
        return false;
      }
    });
    const metaRequestsBeforeConsent = requests.filter(isMetaUrl);
    const signals = [];
    if (pluginSignalAsset && gtmMetaPixel) signals.push('multiple_meta_pixel_owners');
    if (metaRequestsBeforeConsent.length) signals.push('meta_request_before_marketing_consent');

    const allowedPageErrors = pageErrors.filter((message) => message.includes(known.allowedPageError));
    const allowedConsoleErrors = consoleErrors.filter((entry) => entry.text.includes(known.allowedPageError));
    const knownErrorMessages = new Set([
      ...allowedPageErrors,
      ...allowedConsoleErrors.map((entry) => entry.text),
    ]);
    const unexpectedPageErrors = pageErrors.filter((message) => !message.includes(known.allowedPageError));
    const firstPartyConsoleErrors = consoleErrors.filter(
      (entry) => isFirstParty(entry.url) && !entry.text.includes(known.allowedPageError)
    );
    const unexpectedSignals = signals.filter((signal) => !known.allowedSignals.includes(signal));
    const fatal = [];

    if (!document || document.status !== 200 || document.edgeChallenge) fatal.push(`document unavailable: ${JSON.stringify(document)}`);
    if (expectedSha && document?.sha !== expectedSha) fatal.push(`deploy SHA mismatch: expected ${expectedSha}, found ${document?.sha || 'missing'}`);
    if (firstPartyFailures.length) fatal.push(`first-party HTTP failures: ${JSON.stringify(firstPartyFailures)}`);
    if (unexpectedPageErrors.length) fatal.push(`unexpected page errors: ${JSON.stringify(unexpectedPageErrors)}`);
    if (firstPartyConsoleErrors.length) fatal.push(`first-party console errors: ${JSON.stringify(firstPartyConsoleErrors)}`);
    if (unexpectedSignals.length) fatal.push(`unexpected runtime signals: ${unexpectedSignals.join(', ')}`);
    if (knownErrorMessages.size > Number(known.maxPerRoute || 0)) fatal.push(`known page error exceeded baseline: ${knownErrorMessages.size}`);
    if (expired && (knownErrorMessages.size || signals.length)) fatal.push(`known runtime exception expired on ${known.expiresOn}; issue #${known.issue}`);

    results.push({
      route,
      document,
      firstPartyFailures,
      pageErrors,
      consoleErrors,
      signals,
      evidence: {
        pluginSignalAsset,
        gtmMetaPixel,
        metaRequestCountBeforeConsent: metaRequestsBeforeConsent.length,
      },
      fatal,
    });
    await context.close();
  }
} finally {
  await browser.close();
}

const report = {
  generatedAt: new Date().toISOString(),
  baseUrl,
  expectedSha,
  knownException: { issue: known.issue, expiresOn: known.expiresOn, expired },
  results,
  fatalCount: results.reduce((total, result) => total + result.fatal.length, 0),
};
fs.mkdirSync(path.dirname(outputPath), { recursive: true });
fs.writeFileSync(outputPath, `${JSON.stringify(report, null, 2)}\n`);
console.log(JSON.stringify(report, null, 2));
if (report.fatalCount) process.exit(1);
