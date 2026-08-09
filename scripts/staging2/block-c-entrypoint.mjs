import fs from 'node:fs/promises';
import { spawn } from 'node:child_process';
import { setTimeout as delay } from 'node:timers/promises';
import { fileURLToPath } from 'node:url';

const maxAttempts = 3;
const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const attemptScript = fileURLToPath(new URL('./block-c-52x3.mjs', import.meta.url));
const resultsUrl = new URL('./block-c-artifacts/block-c-results.json', import.meta.url);

function runAttempt(attempt) {
  console.log(`BLOCK_C_ATTEMPT=${attempt}/${maxAttempts}`);
  return new Promise((resolve, reject) => {
    const child = spawn(process.execPath, [attemptScript], {
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
  try {
    results = JSON.parse(await fs.readFile(resultsUrl, 'utf8'));
  } catch (error) {
    console.error(`BLOCK_C_RETRY_CLASSIFICATION=UNAVAILABLE reason=${error.message}`);
    return false;
  }

  if (!Array.isArray(results) || results.length !== 156) {
    console.error(`BLOCK_C_RETRY_CLASSIFICATION=INVALID_RESULTS count=${Array.isArray(results) ? results.length : 'non-array'}`);
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
