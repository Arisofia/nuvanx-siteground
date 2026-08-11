import fs from 'node:fs/promises';
import { spawn } from 'node:child_process';
import { setTimeout as delay } from 'node:timers/promises';
import { fileURLToPath } from 'node:url';
import { assertCanonicalPublishedPaths, loadPublishedPagesManifest } from './published-pages-contract.mjs';

const VIEWPORT_COUNT = 3;

const maxAttempts = 3;
const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const attemptScript = fileURLToPath(new URL('./block-c-matrix.mjs', import.meta.url));
const resultsUrl = new URL('./block-c-artifacts/block-c-results.json', import.meta.url);
const preloadDir = new URL('./block-c-artifacts/', import.meta.url);
const preloadUrl = new URL('./block-c-artifacts/trusted-pages-preload.mjs', import.meta.url);

async function prepareTrustedPagesPreload() {
  const pagesFile = (process.env.WORDPRESS_PAGES_FILE || '').trim();
  if (!pagesFile) return null;

  const pages = JSON.parse(await fs.readFile(pagesFile, 'utf8'));
  if (!Array.isArray(pages)) throw new TypeError('Trusted WordPress page inventory must be an array');

  const normalizedPages = pages.map((page) => ({
    id: Number(page.id),
    link: String(page.link || ''),
    slug: String(page.slug || ''),
    title: {
      rendered: typeof page.title === 'string' ? page.title : String(page.title?.rendered || ''),
    },
  }));

  for (const page of normalizedPages) {
    if (!page.link) {
      throw new Error(`Trusted WordPress page ${page.id} has empty link field`);
    }
    const url = new URL(page.link);
    if (url.hostname !== new URL(baseUrl).hostname) {
      throw new Error(`Trusted WordPress page ${page.id} points outside staging: ${page.link}`);
    }
  }

  const manifest = await loadPublishedPagesManifest();
  assertCanonicalPublishedPaths(
    normalizedPages.map((page) => new URL(page.link).pathname),
    manifest,
    'Trusted WordPress page inventory'
  );

  const payload = JSON.stringify(normalizedPages);
  const pagesEndpoint = `${baseUrl}/wp-json/wp/v2/pages`;
  const source = `const nativeFetch = globalThis.fetch.bind(globalThis);\nconst pagesEndpoint = ${JSON.stringify(pagesEndpoint)};\nconst pagesPayload = ${JSON.stringify(payload)};\nglobalThis.fetch = async (input, init) => {\n  const rawUrl = typeof input === 'string' ? input : (input && typeof input.url === 'string' ? input.url : String(input));\n  if (rawUrl.startsWith(pagesEndpoint)) {\n    return new Response(pagesPayload, { status: 200, headers: { 'content-type': 'application/json; charset=utf-8', 'x-nvx-inventory-source': 'trusted-wp-cli' } });\n  }\n  return nativeFetch(input, init);\n};\n`;

  await fs.mkdir(preloadDir, { recursive: true });
  await fs.writeFile(preloadUrl, source, 'utf8');
  console.log(`BLOCK_C_INVENTORY_SOURCE=trusted-wp-cli pages=${normalizedPages.length}`);
  return preloadUrl.href;
}

async function runAttempt(attempt) {
  console.log(`BLOCK_C_ATTEMPT=${attempt}/${maxAttempts}`);
  const preloadModule = await prepareTrustedPagesPreload();
  const args = preloadModule ? ['--import', preloadModule, attemptScript] : [attemptScript];

  return new Promise((resolve, reject) => {
    const child = spawn(process.execPath, args, {
      env: process.env,
      stdio: 'inherit',
    });
    child.once('error', reject);
    child.once('exit', (code, signal) => {
      if (signal) {
        reject(new Error(`Block C attempt ${attempt} terminated by signal ${signal}`));
        return;
      }
      resolve(Number.isInteger(code) ? code : 1);
    });
  });
}

function isTransientFailure(result) {
  if (!result || result.status === 'PASS') return true;

  const blockers = Array.isArray(result.blockers) ? result.blockers.map(String) : [];
  const issues = Array.isArray(result.issues) ? result.issues.map(String) : [];
  const networkErrors = Array.isArray(result.networkErrors) ? result.networkErrors.map(String) : [];
  const status = Number(result.httpStatus || 0);

  const antiBotOnly =
    result.status === 'BLOCKED' &&
    blockers.length > 0 &&
    blockers.every((message) => /SiteGround Antibot challenge prevented visual validation/i.test(message)) &&
    issues.length === 0 &&
    [202, 429, 503].includes(status);

  if (antiBotOnly) return true;

  const navigationNoResponseOnly =
    result.status === 'BLOCKED' &&
    status === 0 &&
    result.geometry == null &&
    blockers.length > 0 &&
    blockers.every((message) => /^Navigation returned no HTTP response$/i.test(message)) &&
    issues.length === 0 &&
    networkErrors.length === 0 &&
    typeof result.finalUrl === 'string' &&
    result.finalUrl.startsWith(`${baseUrl}/`);

  if (navigationNoResponseOnly) return true;

  const expectedDocumentUrl = `${baseUrl}${String(result.route || '')}`;
  const retryAbortOnly =
    result.status === 'FIX' &&
    blockers.length === 0 &&
    issues.length > 0 &&
    issues.every((message) => /^\d+ same-origin network error\(s\)$/i.test(message)) &&
    networkErrors.length > 0 &&
    networkErrors.every((message) => message === `${expectedDocumentUrl}: net::ERR_ABORTED`);

  return retryAbortOnly;
}

async function failedResultsAreTransient() {
  let results;
  let manifest;
  try {
    results = JSON.parse(await fs.readFile(resultsUrl, 'utf8'));
    manifest = await loadPublishedPagesManifest();
  } catch (error) {
    console.error(`BLOCK_C_RETRY_CLASSIFICATION=UNAVAILABLE reason=${error.message}`);
    return false;
  }

  const expectedResultsCount = manifest.length * VIEWPORT_COUNT;

  if (!Array.isArray(results) || results.length < expectedResultsCount) {
    console.error(`BLOCK_C_RETRY_CLASSIFICATION=INVALID_RESULTS count=${Array.isArray(results) ? results.length : 'non-array'} expected=${expectedResultsCount}`);
    return false;
  }

  const failed = results.filter((result) => result.status !== 'PASS');
  if (failed.length === 0) return false;

  const transient = failed.every(isTransientFailure);
  console.log(`BLOCK_C_RETRY_CLASSIFICATION=${transient ? 'TRANSIENT_ONLY' : 'REAL_FAILURE'} failed=${failed.length}`);
  if (transient) {
    for (const result of failed) {
      console.log(`BLOCK_C_TRANSIENT route=${result.route} viewport=${result.viewport?.key || 'unknown'} status=${result.status} http=${result.httpStatus || 0}`);
    }
  }
  return transient;
}

for (let attempt = 1; attempt <= maxAttempts; attempt += 1) {
  const code = await runAttempt(attempt);
  if (code === 0) {
    console.log(`BLOCK_C_RESILIENT=PASS attempt=${attempt}`);
    process.exit(0);
  }

  const transientOnly = await failedResultsAreTransient();
  if (!transientOnly) {
    console.error(`BLOCK_C_RESILIENT=FAIL_REAL attempt=${attempt}`);
    process.exit(code || 1);
  }

  if (attempt === maxAttempts) {
    console.error(`BLOCK_C_RESILIENT=FAIL_TRANSIENT_EXHAUSTED attempts=${maxAttempts}`);
    process.exit(code || 1);
  }

  const delayMs = 4000 * attempt;
  console.log(`BLOCK_C_RESILIENT=RETRY delay_ms=${delayMs}`);
  await delay(delayMs);
}

process.exit(1);
