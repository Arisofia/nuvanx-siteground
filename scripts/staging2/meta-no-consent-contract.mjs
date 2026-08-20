import fs from 'node:fs/promises';
import path from 'node:path';
import { EX_TEMPFAIL, isSiteGroundTransientResponse } from './siteground-transient-classifier.mjs';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedHost = (process.env.EXPECTED_HOST || new URL(baseUrl).hostname).trim().toLowerCase();
const expectedSha = (process.env.EXPECTED_SHA || '').trim();
const requestTimeoutMs = Number.parseInt(process.env.META_NO_CONSENT_REQUEST_TIMEOUT_MS || '15000', 10);
const routes = [
  '/',
  '/clinicas-de-medicina-estetica-nuvanx/',
  '/medicina-estetica-chamberi/',
  '/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/',
];
const forbiddenHtml = [
  ['dedupe_marker', 'NVX_META_EVENT_DEDUPE_ACTIVE'],
  ['dedupe_prefix', 'nvx-meta-event-dedupe-'],
  ['legacy_pixel_id', '1497940655079106'],
  ['facebook_connect', 'connect.facebook.net'],
  ['facebook_events', 'fbevents.js'],
];

if (!Number.isInteger(requestTimeoutMs) || requestTimeoutMs < 1000 || requestTimeoutMs > 60000) {
  console.error('META_NO_CONSENT=FAIL reason=invalid_timeout');
  process.exit(1);
}
if (!/^[a-z0-9.-]+$/.test(expectedHost)) {
  console.error('META_NO_CONSENT=FAIL reason=invalid_expected_host');
  process.exit(1);
}
if (expectedSha && !/^[0-9a-f]{40}$/.test(expectedSha)) {
  console.error('META_NO_CONSENT=FAIL reason=invalid_expected_sha');
  process.exit(1);
}

function extractDeploySha(html) {
  const tag = (html.match(/<meta\b[^>]*\bname=["']nvx-deploy-sha["'][^>]*>/i) || [])[0] || '';
  const match = tag.match(/\bcontent=["']([0-9a-f]{40})["']/i);
  return match ? match[1].toLowerCase() : '';
}

function setCookieValues(headers) {
  if (typeof headers.getSetCookie === 'function') return headers.getSetCookie();
  const value = headers.get('set-cookie');
  return value ? [value] : [];
}

function metaCookiePresent(values) {
  return values.some((value) => /(?:^|,\s*|;\s*)(?:_fbp|_fbc)=/i.test(value));
}

const outputDir = path.resolve('scripts/staging2/meta-no-consent-artifacts');
await fs.rm(outputDir, { recursive: true, force: true });
await fs.mkdir(outputDir, { recursive: true });

const report = {
  baseUrl,
  expectedHost,
  expectedSha: expectedSha || null,
  checkedAt: new Date().toISOString(),
  routes: [],
  pass: false,
};
let transient = false;

for (const route of routes) {
  const url = new URL(route, `${baseUrl}/`);
  url.searchParams.set('nvx_meta_no_consent', `${Date.now()}-${report.routes.length + 1}`);
  const row = { route, url: url.toString(), issues: [] };

  try {
    if (url.protocol !== 'https:' || url.hostname !== expectedHost) {
      throw new Error(`refusing host ${url.hostname}`);
    }

    const response = await fetch(url, {
      redirect: 'follow',
      signal: AbortSignal.timeout(requestTimeoutMs),
      headers: {
        'user-agent': 'NUVANX-Meta-No-Consent-Contract/1.0',
        accept: 'text/html,application/xhtml+xml',
        'cache-control': 'no-cache',
        pragma: 'no-cache',
      },
    });

    if (isSiteGroundTransientResponse(response.status, Object.fromEntries(response.headers.entries()))) {
      transient = true;
      row.issues.push(`siteground_transient status=${response.status} sg-captcha=${response.headers.get('sg-captcha') || ''}`);
      report.routes.push(row);
      continue;
    }

    const html = await response.text();
    const cookies = setCookieValues(response.headers);
    row.status = response.status;
    row.finalUrl = response.url;
    row.bytes = Buffer.byteLength(html);
    row.setCookieCount = cookies.length;
    row.deploySha = extractDeploySha(html);

    if (response.status !== 200) row.issues.push(`http_${response.status}`);
    if (new URL(response.url).hostname !== expectedHost) row.issues.push(`cross_host:${new URL(response.url).hostname}`);
    if (metaCookiePresent(cookies)) row.issues.push('pre_consent_meta_cookie');
    if (/\bfbq\s*\(/i.test(html)) row.issues.push('browser_fbq_present');
    if (/(?:document\.cookie|cookie\s*=)[\s\S]{0,500}(?:_fbp|_fbc)/i.test(html)) row.issues.push('browser_meta_cookie_writer_present');
    for (const [name, marker] of forbiddenHtml) {
      if (html.toLowerCase().includes(marker.toLowerCase())) row.issues.push(`${name}_present`);
    }
    if (expectedSha && row.deploySha !== expectedSha) row.issues.push(`sha_mismatch:${row.deploySha || 'missing'}`);
  } catch (error) {
    row.issues.push(error instanceof Error ? error.message : String(error));
  }

  row.pass = row.issues.length === 0;
  report.routes.push(row);
}

report.pass = !transient && report.routes.length === routes.length && report.routes.every((row) => row.pass);
await fs.writeFile(path.join(outputDir, 'results.json'), `${JSON.stringify(report, null, 2)}\n`, 'utf8');

if (transient) {
  console.error('META_NO_CONSENT=TRANSIENT siteground_challenge=1');
  process.exit(EX_TEMPFAIL);
}
if (!report.pass) {
  for (const row of report.routes.filter((item) => !item.pass)) {
    console.error(`META_NO_CONSENT_ROUTE=FAIL route=${row.route} issues=${row.issues.join(',')}`);
  }
  console.error('META_NO_CONSENT=FAIL');
  process.exit(1);
}

console.log(`META_NO_CONSENT=PASS routes=${routes.length} meta_cookie=0 browser_pixel=0 dedupe=0`);
