#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium } from '@playwright/test';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const baseUrl = (process.env.BASE_URL || 'https://nuvanx.com').replace(/\/$/, '');
const expectedSha = (process.env.EXPECTED_DEPLOY_SHA || '').trim().toLowerCase();
const outputPath = process.env.HOME_MEDIA_OUTPUT_PATH || path.join(root, 'home-media-performance.json');
const retries = Math.max(1, Number(process.env.VERIFY_RETRIES || 4));
const delayMs = Math.max(1000, Number(process.env.VERIFY_RETRY_DELAY_MS || 4000));
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const assert = (condition, message) => { if (!condition) throw new Error(message); };

const report = {
  generatedAt: new Date().toISOString(),
  baseUrl,
  expectedSha,
  document: null,
  cardinality: null,
  attributes: null,
  resources: null,
  fatal: [],
};

const options = {
  locale: 'es-ES',
  timezoneId: 'Europe/Madrid',
  viewport: { width: 1440, height: 1000 },
};
if (process.env.BASIC_USER && process.env.BASIC_PASSWORD) {
  options.httpCredentials = { username: process.env.BASIC_USER, password: process.env.BASIC_PASSWORD };
}

let browser = null;
let context = null;
let failure = null;

try {
  browser = await chromium.launch({ headless: true });
  context = await browser.newContext(options);
  const page = await context.newPage();

  for (let attempt = 1; attempt <= retries; attempt += 1) {
    const response = await page.goto(`${baseUrl}/`, { waitUntil: 'domcontentloaded', timeout: 60000 }).catch(() => null);
    await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {});
    const title = await page.title().catch(() => '');
    const html = await page.content().catch(() => '');
    const sha = ((await page.locator('meta[name="nvx-deploy-sha"]').getAttribute('content').catch(() => '')) || '').toLowerCase();
    report.document = {
      status: response?.status() || 0,
      sha,
      edgeChallenge: /sgcaptcha|robot challenge|access denied/i.test(`${title} ${html}`),
      attempt,
    };
    if (report.document.status === 200 && !report.document.edgeChallenge && (!expectedSha || sha === expectedSha)) break;
    if (attempt < retries) await sleep(delayMs * attempt);
  }

  assert(report.document?.status === 200 && !report.document.edgeChallenge, `home unavailable: ${JSON.stringify(report.document)}`);
  if (expectedSha) assert(report.document.sha === expectedSha, `deploy SHA mismatch: expected ${expectedSha}, found ${report.document.sha || 'missing'}`);

  const idMatches = page.locator('#nvx-home-hero-video');
  const idCount = await idMatches.count();
  report.cardinality = { idCount, canonicalShape: null, sourceCount: 0 };
  assert(idCount === 1, `expected one #nvx-home-hero-video ID, found ${idCount}`);

  const video = idMatches.first();
  const canonicalShape = await video.evaluate((element) => ({
    tagName: element.tagName,
    hasCanonicalClass: element.classList.contains('nvx-home-hero-video'),
  }));
  report.cardinality.canonicalShape = canonicalShape;
  assert(canonicalShape.tagName === 'VIDEO' && canonicalShape.hasCanonicalClass, 'sole hero ID is not the canonical video element');

  const source = video.locator('source[type="video/mp4"]');
  report.cardinality.sourceCount = await source.count();
  assert(report.cardinality.sourceCount === 1, 'canonical MP4 source is missing or duplicated');

  const attributes = await video.evaluate((element) => ({
    preload: element.getAttribute('preload') || '',
    fetchpriority: element.getAttribute('fetchpriority') || '',
    poster: element.getAttribute('poster') || '',
    autoplay: element.autoplay,
    muted: element.muted,
    loop: element.loop,
    playsInline: element.playsInline,
    ariaLabel: element.getAttribute('aria-label') || '',
    readyState: element.readyState,
    networkState: element.networkState,
  }));
  const sourceUrl = await source.getAttribute('src') || '';
  report.attributes = { ...attributes, sourceUrl, metadataLoaded: false };

  assert(attributes.preload === 'metadata', `home video preload must be metadata, found ${attributes.preload || 'missing'}`);
  assert(attributes.fetchpriority.toLowerCase() !== 'high', 'home video must not use fetchpriority=high');
  assert(attributes.poster !== '', 'home video poster is missing');
  assert(attributes.autoplay && attributes.muted && attributes.loop && attributes.playsInline, 'home video autoplay/mobile attributes are incomplete');
  assert(attributes.ariaLabel !== '', 'home video accessible label is missing');
  assert(sourceUrl !== '', 'home video MP4 URL is missing');

  const metadataLoaded = await video.evaluate((element) => {
    if (element.readyState >= 1) return Promise.resolve(true);
    return new Promise((resolve) => {
      const finish = (value) => {
        clearTimeout(timer);
        element.removeEventListener('loadedmetadata', onLoaded);
        element.removeEventListener('error', onError);
        resolve(value);
      };
      const onLoaded = () => finish(true);
      const onError = () => finish(false);
      const timer = setTimeout(() => finish(false), 10000);
      element.addEventListener('loadedmetadata', onLoaded, { once: true });
      element.addEventListener('error', onError, { once: true });
    });
  });
  report.attributes.metadataLoaded = metadataLoaded;
  assert(metadataLoaded, 'home video did not decode metadata');

  const origin = new URL(baseUrl).origin;
  const posterUrl = new URL(attributes.poster, baseUrl).href;
  const mp4Url = new URL(sourceUrl, baseUrl).href;
  assert(new URL(posterUrl).origin === origin, 'home poster must be first-party');
  assert(new URL(mp4Url).origin === origin, 'home MP4 must be first-party');

  const posterResponse = await context.request.get(posterUrl, { timeout: 30000 }).catch(() => null);
  const mp4Response = await context.request.get(mp4Url, {
    headers: { Range: 'bytes=0-1023' },
    timeout: 30000,
  }).catch(() => null);
  const posterStatus = posterResponse?.status() || 0;
  const mp4Status = mp4Response?.status() || 0;
  const posterContentType = posterResponse?.headers()['content-type'] || '';
  const mp4ContentType = mp4Response?.headers()['content-type'] || '';
  report.resources = {
    posterUrl,
    posterStatus,
    posterContentType,
    mp4Url,
    mp4Status,
    mp4ContentType,
  };

  assert(posterStatus >= 200 && posterStatus < 400, `home poster failed with HTTP ${posterStatus}`);
  assert(/^image\//i.test(posterContentType), `home poster returned non-image content type: ${posterContentType || 'missing'}`);
  assert(mp4Status === 200 || mp4Status === 206, `home MP4 failed with HTTP ${mp4Status}`);
  assert(/^video\/mp4(?:;|$)/i.test(mp4ContentType), `home MP4 returned invalid content type: ${mp4ContentType || 'missing'}`);
} catch (error) {
  failure = error instanceof Error ? error : new Error(String(error));
  report.fatal.push(failure.message);
} finally {
  fs.mkdirSync(path.dirname(outputPath), { recursive: true });
  fs.writeFileSync(outputPath, `${JSON.stringify(report, null, 2)}\n`);
  console.log(JSON.stringify(report, null, 2));
  await context?.close().catch(() => {});
  await browser?.close().catch(() => {});
}

if (failure) {
  console.error(`FAIL: ${failure.message}`);
  process.exit(1);
}
console.log('PASS: rendered home hero video performance contract');
