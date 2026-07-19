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

const options = {
  locale: 'es-ES',
  timezoneId: 'Europe/Madrid',
  viewport: { width: 1440, height: 1000 },
};
if (process.env.BASIC_USER && process.env.BASIC_PASSWORD) {
  options.httpCredentials = { username: process.env.BASIC_USER, password: process.env.BASIC_PASSWORD };
}

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext(options);
const page = await context.newPage();
const responses = new Map();
page.on('response', (response) => responses.set(response.url(), response.status()));

let documentState = null;
try {
  for (let attempt = 1; attempt <= retries; attempt += 1) {
    responses.clear();
    const response = await page.goto(`${baseUrl}/`, { waitUntil: 'domcontentloaded', timeout: 60000 }).catch(() => null);
    await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {});
    const title = await page.title().catch(() => '');
    const html = await page.content().catch(() => '');
    const sha = ((await page.locator('meta[name="nvx-deploy-sha"]').getAttribute('content').catch(() => '')) || '').toLowerCase();
    documentState = {
      status: response?.status() || 0,
      sha,
      edgeChallenge: /sgcaptcha|robot challenge|access denied/i.test(`${title} ${html}`),
    };
    if (documentState.status === 200 && !documentState.edgeChallenge && (!expectedSha || sha === expectedSha)) break;
    if (attempt < retries) await sleep(delayMs * attempt);
  }

  assert(documentState?.status === 200 && !documentState.edgeChallenge, `home unavailable: ${JSON.stringify(documentState)}`);
  if (expectedSha) assert(documentState.sha === expectedSha, `deploy SHA mismatch: expected ${expectedSha}, found ${documentState.sha || 'missing'}`);

  const videos = page.locator('video#nvx-home-hero-video.nvx-home-hero-video');
  assert(await videos.count() === 1, `expected one canonical home hero video, found ${await videos.count()}`);
  const video = videos.first();
  const source = video.locator('source[type="video/mp4"]');
  assert(await source.count() === 1, 'canonical MP4 source is missing or duplicated');

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

  assert(attributes.preload === 'metadata', `home video preload must be metadata, found ${attributes.preload || 'missing'}`);
  assert(attributes.fetchpriority.toLowerCase() !== 'high', 'home video must not use fetchpriority=high');
  assert(attributes.poster !== '', 'home video poster is missing');
  assert(attributes.autoplay && attributes.muted && attributes.loop && attributes.playsInline, 'home video autoplay/mobile attributes are incomplete');
  assert(attributes.ariaLabel !== '', 'home video accessible label is missing');
  assert(sourceUrl !== '', 'home video MP4 URL is missing');

  const origin = new URL(baseUrl).origin;
  const posterUrl = new URL(attributes.poster, baseUrl).href;
  const mp4Url = new URL(sourceUrl, baseUrl).href;
  assert(new URL(posterUrl).origin === origin, 'home poster must be first-party');
  assert(new URL(mp4Url).origin === origin, 'home MP4 must be first-party');

  if (!responses.has(posterUrl)) {
    const response = await context.request.get(posterUrl, { timeout: 30000 }).catch(() => null);
    responses.set(posterUrl, response?.status() || 0);
  }
  if (!responses.has(mp4Url)) {
    const response = await context.request.get(mp4Url, { headers: { Range: 'bytes=0-1023' }, timeout: 30000 }).catch(() => null);
    responses.set(mp4Url, response?.status() || 0);
  }

  const posterStatus = responses.get(posterUrl) || 0;
  const mp4Status = responses.get(mp4Url) || 0;
  assert(posterStatus >= 200 && posterStatus < 400, `home poster failed with HTTP ${posterStatus}`);
  assert(mp4Status === 200 || mp4Status === 206, `home MP4 failed with HTTP ${mp4Status}`);

  const report = {
    generatedAt: new Date().toISOString(),
    baseUrl,
    expectedSha,
    document: documentState,
    attributes,
    resources: {
      posterUrl,
      posterStatus,
      mp4Url,
      mp4Status,
    },
  };
  fs.mkdirSync(path.dirname(outputPath), { recursive: true });
  fs.writeFileSync(outputPath, `${JSON.stringify(report, null, 2)}\n`);
  console.log(JSON.stringify(report, null, 2));
  console.log('PASS: rendered home hero video performance contract');
} finally {
  await context.close();
  await browser.close();
}
