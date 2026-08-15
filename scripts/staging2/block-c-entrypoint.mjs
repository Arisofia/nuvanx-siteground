import fs from 'node:fs/promises';
import path from 'node:path';
import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { EX_TEMPFAIL } from './siteground-transient-classifier.mjs';
import { createSiteGroundOriginVerifier } from './siteground-origin-verifier.mjs';

const coreScript = fileURLToPath(new URL('./block-c-entrypoint-core.mjs', import.meta.url));
const resultsUrl = new URL('./block-c-artifacts/block-c-results.json', import.meta.url);
const runnerTemp = process.env.RUNNER_TEMP || '/tmp';
const realGithubEnv = process.env.GITHUB_ENV || '';
const realStepSummary = process.env.GITHUB_STEP_SUMMARY || '';
const shadowGithubEnv = path.join(runnerTemp, `nvx-block-c-core-env-${process.pid}.txt`);
const shadowStepSummary = path.join(runnerTemp, `nvx-block-c-core-summary-${process.pid}.md`);
const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedHost = new URL(baseUrl).hostname;
const expectedSha = (process.env.EXPECTED_SHA || '').trim();

function runCore() {
  return new Promise((resolve, reject) => {
    const env = {
      ...process.env,
      GITHUB_ENV: shadowGithubEnv,
      GITHUB_STEP_SUMMARY: shadowStepSummary,
    };
    const child = spawn(process.execPath, [coreScript], { env, stdio: 'inherit' });
    child.once('error', reject);
    child.once('exit', (code, signal) => {
      if (signal) reject(new Error(`Block C core terminated by signal ${signal}`));
      else resolve(Number.isInteger(code) ? code : 1);
    });
  });
}

function isRecoverableCompletedVisualTransient(result) {
  if (!result || result.status !== 'FIX') return false;
  if (result.geometry == null || Number(result.httpStatus || 0) !== 200) return false;
  if (result.externalInconclusive === true) return false;
  const blockers = Array.isArray(result.blockers) ? result.blockers.map(String) : [];
  const issues = Array.isArray(result.issues) ? result.issues.map(String) : [];
  const networkErrors = Array.isArray(result.networkErrors) ? result.networkErrors.map(String) : [];
  return blockers.length === 0
    && issues.length > 0
    && issues.every((message) => /^\d+ same-origin network error\(s\)$/i.test(message))
    && networkErrors.length > 0;
}

async function tryExactOriginNetworkRecovery() {
  let results;
  try {
    results = JSON.parse(await fs.readFile(resultsUrl, 'utf8'));
  } catch (error) {
    console.error(`BLOCK_C_ORIGIN_NETWORK_RECOVERY=UNAVAILABLE reason=results_unreadable error=${error instanceof Error ? error.message : String(error)}`);
    return false;
  }
  if (!Array.isArray(results) || results.length === 0) return false;

  const failed = results.filter((result) => result?.status !== 'PASS');
  if (failed.length === 0 || !failed.every(isRecoverableCompletedVisualTransient)) {
    console.error(`BLOCK_C_ORIGIN_NETWORK_RECOVERY=NOT_APPLICABLE failed=${failed.length}`);
    return false;
  }
  if (expectedHost !== 'staging2.nuvanx.com' || !/^[0-9a-f]{40}$/.test(expectedSha)) {
    console.error(`BLOCK_C_ORIGIN_NETWORK_RECOVERY=REFUSED host=${expectedHost} sha=${expectedSha || 'missing'}`);
    return false;
  }

  const verifier = createSiteGroundOriginVerifier({ expectedHost, expectedSha });
  if (!verifier.isAvailable()) {
    console.error('BLOCK_C_ORIGIN_NETWORK_RECOVERY=UNAVAILABLE reason=origin_ssh');
    return false;
  }

  const verificationByRoute = new Map();
  for (const result of failed) {
    const route = String(result.route || '');
    if (!verificationByRoute.has(route)) verificationByRoute.set(route, verifier.verify(route));
    const verification = verificationByRoute.get(route);
    if (!verification?.pass || verification.originStatus !== 200 || verification.originDeploySha !== expectedSha) {
      console.error(`BLOCK_C_ORIGIN_NETWORK_RECOVERY=FAIL route=${route} origin_http=${verification?.originStatus ?? 0} origin_sha=${verification?.originDeploySha || 'missing'}`);
      return false;
    }
  }

  const recovered = results.map((result) => {
    if (result?.status === 'PASS') return result;
    const verification = verificationByRoute.get(String(result.route || ''));
    return {
      ...result,
      status: 'PASS',
      recoveredIssues: Array.isArray(result.issues) ? result.issues : [],
      issues: [],
      originVerified: true,
      originStatus: verification.originStatus,
      originDeploySha: verification.originDeploySha,
      validationTransport: 'public-browser+siteground-origin-network-verification',
      transientNetworkEvidencePreserved: true,
    };
  });

  await fs.writeFile(resultsUrl, `${JSON.stringify(recovered, null, 2)}\n`, 'utf8');
  console.log(`BLOCK_C_ORIGIN_NETWORK_RECOVERY=PASS cases=${failed.length} sha=${expectedSha}`);
  return true;
}

async function propagateTransientFailureState() {
  if (realGithubEnv) {
    await fs.appendFile(realGithubEnv, 'STAGING_MUTATION_ARMED=0\n', 'utf8');
    console.error('BLOCK_C_STAGING_ROLLBACK=DISARMED reason=transient-exhausted-after-origin-verification');
  }
  if (realStepSummary) {
    await fs.appendFile(
      realStepSummary,
      '\n### Block C transient exhaustion\n\nThe public browser could not complete the visual contract and exact-SHA origin verification could not safely recover the case. No production-eligible completion marker is allowed.\n',
      'utf8'
    );
  }
}

async function cleanupShadowFiles() {
  await fs.rm(shadowGithubEnv, { force: true }).catch(() => {});
  await fs.rm(shadowStepSummary, { force: true }).catch(() => {});
}

let coreCode = 1;
try {
  coreCode = await runCore();
  if (coreCode !== EX_TEMPFAIL) {
    process.exitCode = coreCode;
  } else {
    const recovered = await tryExactOriginNetworkRecovery();
    if (recovered) {
      console.log('BLOCK_C_RESILIENT=PASS_EXACT_ORIGIN_NETWORK_RECOVERY visual_contract=complete');
      process.exitCode = 0;
    } else {
      await propagateTransientFailureState();
      console.error('BLOCK_C_RESILIENT=FAIL_TRANSIENT_EXHAUSTED fallback=exact-origin-verification-unavailable-or-inapplicable');
      process.exitCode = EX_TEMPFAIL;
    }
  }
} catch (error) {
  console.error(`BLOCK_C_WRAPPER=FAIL_REAL reason=${error instanceof Error ? error.message : String(error)}`);
  process.exitCode = 1;
} finally {
  await cleanupShadowFiles();
}
