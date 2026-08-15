import fs from 'node:fs/promises';
import path from 'node:path';
import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { EX_TEMPFAIL } from './siteground-transient-classifier.mjs';
import { runOriginBrowserFallback } from './block-c-origin-browser-fallback.mjs';

const coreScript = fileURLToPath(new URL('./block-c-entrypoint-core.mjs', import.meta.url));
const runnerTemp = process.env.RUNNER_TEMP || '/tmp';
const realGithubEnv = process.env.GITHUB_ENV || '';
const realStepSummary = process.env.GITHUB_STEP_SUMMARY || '';
const shadowGithubEnv = path.join(runnerTemp, `nvx-block-c-core-env-${process.pid}.txt`);
const shadowStepSummary = path.join(runnerTemp, `nvx-block-c-core-summary-${process.pid}.md`);

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

async function propagateTransientFailureState() {
  if (realGithubEnv) {
    await fs.appendFile(realGithubEnv, 'STAGING_MUTATION_ARMED=0\n', 'utf8');
    console.error('BLOCK_C_STAGING_ROLLBACK=DISARMED reason=transient-exhausted-after-origin-browser-fallback');
  }
  if (realStepSummary) {
    await fs.appendFile(
      realStepSummary,
      '\n### Block C transient exhaustion\n\nThe public edge remained blocked by SiteGround Antibot and the full-browser SiteGround-origin SSH-tunnel fallback could not produce a complete visual PASS. No production-eligible completion marker is allowed.\n',
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
    process.env.BLOCK_C_FALLBACK_REASON = 'public-edge-transient-exhausted';
    const fallbackPass = await runOriginBrowserFallback();
    if (fallbackPass) {
      console.log('BLOCK_C_RESILIENT=PASS_ORIGIN_BROWSER_FALLBACK visual_contract=complete');
      process.exitCode = 0;
    } else {
      await propagateTransientFailureState();
      console.error('BLOCK_C_RESILIENT=FAIL_TRANSIENT_EXHAUSTED fallback=origin-browser-unavailable-or-incomplete');
      process.exitCode = EX_TEMPFAIL;
    }
  }
} catch (error) {
  console.error(`BLOCK_C_WRAPPER=FAIL_REAL reason=${error instanceof Error ? error.message : String(error)}`);
  process.exitCode = 1;
} finally {
  await cleanupShadowFiles();
}
