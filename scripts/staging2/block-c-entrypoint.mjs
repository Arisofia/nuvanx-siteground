import fs from 'node:fs/promises';
import { spawn } from 'node:child_process';
import { setTimeout as delay } from 'node:timers/promises';
import { fileURLToPath } from 'node:url';
import { assertCanonicalPublishedPaths, loadPublishedPagesManifest, VIEWPORTS } from './published-pages-contract.mjs';
import {
  SITEGROUND_CAPTCHA_PATH,
  SITEGROUND_TRANSIENT_HTTP_STATUSES,
  EX_TEMPFAIL,
  isSiteGroundTransientResponse,
} from './siteground-transient-classifier.mjs';
import './governed-blog-head-contract.mjs';

import {
  SITEGROUND_CAPTCHA_PATH,
  SITEGROUND_TRANSIENT_HTTP_STATUSES,
  EX_TEMPFAIL,
  isSiteGroundTransientResponse,
} from './siteground-transient-classifier.mjs';

const VIEWPORT_COUNT = VIEWPORTS.length;

const maxAttempts = 3;
const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedSha = (process.env.EXPECTED_SHA || '').trim();
const attemptScript = fileURLToPath(new URL('./block-c-matrix.mjs', import.meta.url));
const resultsUrl = new URL('./block-c-artifacts/block-c-results.json', import.meta.url);
const preloadDir = new URL('./block-c-artifacts/', import.meta.url);
const preloadUrl = new URL('./block-c-artifacts/trusted-pages-preload.mjs', import.meta.url);

async function prepareTrustedPagesPreload() {
  const pagesFile = (process.env.WORDPRESS_PAGES_FILE || '').trim();
  if (!pagesFile) {
    console.log('BLOCK_C_INVENTORY_SOURCE=public-rest (no WORDPRESS_PAGES_FILE provided)');
    return null;
  }

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

function isAllowedSiteGroundAbort(networkErrors, route) {
  const expectedDocumentUrl = `${baseUrl}${String(route || '')}`;
  const captchaPrefix = `${baseUrl}${SITEGROUND_CAPTCHA_PATH}`;
  return (
    networkErrors.length === 0 ||
    networkErrors.every((msg) => {
      const message = String(msg || '').trim();
      if (!/net::ERR_ABORTED/i.test(message)) return false;
      return message.startsWith(expectedDocumentUrl) || message.startsWith(captchaPrefix);
    })
  );
}

// Helper functions for transient failure detection
function isAntiBotOnly(result, blockers, issues, status) {
  return (
    result.status === 'BLOCKED' &&
    blockers.length > 0 &&
    blockers.every((message) => /SiteGround Antibot challenge prevented visual validation/i.test(message)) &&
    issues.length === 0 &&
    SITEGROUND_TRANSIENT_HTTP_STATUSES.has(status)
  );
}

function isNavigationNoResponseOnly(result, blockers, issues, networkErrors, status) {
  return (
    result.status === 'BLOCKED' &&
    status === 0 &&
    result.geometry == null &&
    blockers.length > 0 &&
    blockers.every((message) => /^Navigation returned no HTTP response$/i.test(message)) &&
    issues.length === 0 &&
    isAllowedSiteGroundAbort(networkErrors, result.route) &&
    typeof result.finalUrl === 'string' &&
    result.finalUrl.startsWith(`${baseUrl}/`)
  );
}

function isNetworkIssueOnly(result, blockers, issues, networkErrors) {
  return (
    result.status === 'FIX' &&
    blockers.length === 0 &&
    issues.length > 0 &&
    issues.every((message) => /^\d+ same-origin network error\(s\)$/i.test(message)) &&
    networkErrors.length > 0
  );
}

function isRetryAbortOnly(networkErrors, expectedDocumentUrl) {
  return (
    networkErrors.length > 0 &&
    networkErrors.every((msg) => {
      const message = String(msg || '').trim();
      return /net::ERR_ABORTED/i.test(message) && message.startsWith(expectedDocumentUrl);
    })
  );
}

function isSiteGroundCaptchaRequestAbortOnly(networkErrors) {
  const captchaPrefix = `${baseUrl}${SITEGROUND_CAPTCHA_PATH}`;
  return (
    networkErrors.length > 0 &&
    networkErrors.every((msg) => {
      const message = String(msg || '').trim();
      return /net::ERR_ABORTED/i.test(message) && message.startsWith(captchaPrefix);
    })
  );
}

function isTransientFailure(result) {
  if (!result || result.status === 'PASS') return true;

  const blockers = Array.isArray(result.blockers) ? result.blockers.map(String) : [];
  const issues = Array.isArray(result.issues) ? result.issues.map(String) : [];
  const networkErrors = Array.isArray(result.networkErrors) ? result.networkErrors.map(String) : [];
  const status = Number(result.edgeHttpStatus ?? result.httpStatus ?? 0);

  // Check 1: SiteGround Antibot challenge only
  if (isAntiBotOnly(result, blockers, issues, status)) return true;

  // Check 2: Navigation with no HTTP response due to SiteGround challenge
  if (isNavigationNoResponseOnly(result, blockers, issues, networkErrors, status)) return true;

  // Check 3: Network issues with abort errors (either retry or captcha-related)
  const expectedDocumentUrl = `${baseUrl}${String(result.route || '')}`;
  const networkIssueOnly = isNetworkIssueOnly(result, blockers, issues, networkErrors);

  if (networkIssueOnly) {
    const retryAbortOnly = isRetryAbortOnly(networkErrors, expectedDocumentUrl);
    const siteGroundCaptchaRequestAbortOnly = isSiteGroundCaptchaRequestAbortOnly(networkErrors);

    return retryAbortOnly || siteGroundCaptchaRequestAbortOnly;
  }

  return false;
}

function isOriginVerifiedVisualInconclusive(result) {
  if (!result || result.status !== 'PASS') return false;
  if (result.externalInconclusive !== true || result.originVerified !== true) return false;
  if (result.visualValidation !== 'inconclusive-siteground-antibot' || result.geometry != null) return false;
  if (Number(result.originStatus || 0) !== 200) return false;
  if (expectedSha && String(result.originDeploySha || '') !== expectedSha) return false;

  const edgeStatus = Number(result.edgeHttpStatus ?? 0);
  const finalUrl = String(result.finalUrl || '');
  const networkErrors = Array.isArray(result.networkErrors) ? result.networkErrors.map(String) : [];

  if (isSiteGroundTransientResponse(edgeStatus, result.edgeHeaders || {}, finalUrl)) return true;
  if (edgeStatus === 0 && isAllowedSiteGroundAbort(networkErrors, result.route)) return true;

  return false;
}

async function readValidatedResults() {
  let results;
  try {
    results = JSON.parse(await fs.readFile(resultsUrl, 'utf8'));
  } catch (error) {
    console.error(`BLOCK_C_RESULTS_VALIDATION=RESULTS_UNAVAILABLE reason=${error.message}`);
    return null;
  }

  let manifest;
  try {
    manifest = await loadPublishedPagesManifest();
  } catch (error) {
    console.error(`BLOCK_C_RESULTS_VALIDATION=MANIFEST_INVALID reason=${error.message}`);
    return null;
  }

  const expectedResultsCount = manifest.length * VIEWPORT_COUNT;
  if (!Array.isArray(results) || results.length < expectedResultsCount || results.length % VIEWPORT_COUNT !== 0) {
    console.error(
      `BLOCK_C_RESULTS_VALIDATION=INVALID_RESULTS count=${Array.isArray(results) ? results.length : 'non-array'} min_expected=${expectedResultsCount}`
    );
    return null;
  }

  return results;
}

async function successfulResultsAreComplete() {
  const results = await readValidatedResults();
  if (!results) return { valid: false, complete: false, transientOnly: false };

  const nonPass = results.filter((result) => result.status !== 'PASS');
  if (nonPass.length > 0) {
    console.error(`BLOCK_C_PRODUCTION_ELIGIBILITY=INVALID_SUCCESS non_pass=${nonPass.length}`);
    return { valid: false, complete: false, transientOnly: false };
  }

  const inconclusive = results.filter((result) => result.externalInconclusive === true);
  if (inconclusive.length === 0) {
    return { valid: true, complete: true, transientOnly: false };
  }

  const transientOnly = inconclusive.every(isOriginVerifiedVisualInconclusive);
  console.log(`BLOCK_C_PRODUCTION_ELIGIBILITY=${transientOnly ? 'TRANSIENT_INCONCLUSIVE' : 'INVALID_INCONCLUSIVE'} cases=${inconclusive.length}`);
  for (const result of inconclusive) {
    console.log(`BLOCK_C_INCONCLUSIVE route=${result.route} viewport=${result.viewport?.key || 'unknown'} edge_http=${result.edgeHttpStatus ?? 0} origin_http=${result.originStatus ?? 0}`);
  }

  return { valid: transientOnly, complete: false, transientOnly };
}

async function failedResultsAreTransient() {
  const results = await readValidatedResults();
  if (!results) return false;

  const failed = results.filter((result) => result.status !== 'PASS');
  if (failed.length === 0) return false;

  const transient = failed.every(isTransientFailure);
  console.log(`BLOCK_C_RETRY_CLASSIFICATION=${transient ? 'TRANSIENT_ONLY' : 'REAL_FAILURE'} failed=${failed.length}`);
  if (transient) {
    for (const result of failed) {
      console.log(`BLOCK_C_TRANSIENT route=${result.route} viewport=${result.viewport?.key || 'unknown'} status=${result.status} edge_http=${result.edgeHttpStatus ?? 0} effective_http=${result.httpStatus ?? 0}`);
    }
  }
  return transient;
}

async function disarmRollbackAfterTransientExhaustion(reason = 'transient-only-exhaustion') {
  const envFile = (process.env.GITHUB_ENV || '').trim();
  if (envFile) {
    try {
      await fs.appendFile(envFile, 'STAGING_MUTATION_ARMED=0\n', 'utf8');
      console.error(`BLOCK_C_STAGING_ROLLBACK=DISARMED reason=${reason}`);
    } catch (err) {
      console.warn(`BLOCK_C_STAGING_ROLLBACK=NOT_DISARMED reason=GITHUB_ENV_write_failed error=${err instanceof Error ? err.message : String(err)}`);
    }
  } else {
    console.warn('BLOCK_C_STAGING_ROLLBACK=NOT_DISARMED reason=GITHUB_ENV_unavailable');
  }

  const summaryFile = (process.env.GITHUB_STEP_SUMMARY || '').trim();
  if (summaryFile) {
    try {
      await fs.appendFile(
        summaryFile,
        `\n### Block C transient exhaustion\n\nSiteGround Antibot or transient infrastructure challenge prevented complete browser validation after all bounded retries (${reason}). No real application defect was established, so the Staging rollback was disarmed. This run remains ineligible for Production acceptance because browser geometry, H1 visibility, responsive layout and images were not completely validated.\n`,
        'utf8'
      );
    } catch (err) {
      console.warn(`Failed to write GITHUB_STEP_SUMMARY: ${err instanceof Error ? err.message : String(err)}`);
    }
  }
}

for (let attempt = 1; attempt <= maxAttempts; attempt += 1) {
  let code;
  try {
    code = await runAttempt(attempt);
  } catch (error) {
    console.error(`BLOCK_C_RESILIENT=FAIL_REAL attempt=${attempt} reason=${error.message}`);
    console.error(`BLOCK_C_RETRY_CLASSIFICATION=PRELOAD_ERROR reason=${error.message}`);
    process.exit(1);
  }

  let transientOnly = false;
  let transientReason = 'transient-only-exhaustion';

  if (code === 0) {
    const completion = await successfulResultsAreComplete();
    if (completion.valid && completion.complete) {
      console.log(`BLOCK_C_RESILIENT=PASS attempt=${attempt}`);
      process.exit(0);
    }
    if (!completion.valid || !completion.transientOnly) {
      console.error(`BLOCK_C_RESILIENT=FAIL_REAL attempt=${attempt} reason=incomplete-or-invalid-success-evidence`);
      process.exit(1);
    }
    transientOnly = true;
    transientReason = 'siteground-antibot-visual-inconclusive';
  } else {
    transientOnly = await failedResultsAreTransient();
    if (!transientOnly) {
      console.error(`BLOCK_C_RESILIENT=FAIL_REAL attempt=${attempt}`);
      process.exit(code || 1);
    }
    transientReason = 'transient-network-or-challenge-failure';
  }

  if (attempt === maxAttempts) {
    await disarmRollbackAfterTransientExhaustion(transientReason);
    console.error(`BLOCK_C_RESILIENT=FAIL_TRANSIENT_EXHAUSTED attempts=${maxAttempts} reason=${transientReason}`);
    process.exit(EX_TEMPFAIL);
  }

  const delayMs = 4000 * attempt;
  console.log(`BLOCK_C_RESILIENT=RETRY_TRANSIENT_INCONCLUSIVE attempt=${attempt} delay_ms=${delayMs}`);
  await delay(delayMs);
}

process.exit(1);

