import { spawn } from 'node:child_process';
import fs from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import { setTimeout as delay } from 'node:timers/promises';
import { fileURLToPath } from 'node:url';
import { EX_TEMPFAIL } from './siteground-transient-classifier.mjs';

const target = fileURLToPath(new URL('./governed-blog-head-contract.mjs', import.meta.url));
const maxCycles = 3;
const realGithubEnv = (process.env.GITHUB_ENV || '').trim();
const realSummary = (process.env.GITHUB_STEP_SUMMARY || '').trim();
const scratchDir = await fs.mkdtemp(path.join(os.tmpdir(), 'nvx-governed-head-'));

function runCycle(cycle) {
  const shadowEnv = path.join(scratchDir, `github-env-${cycle}.txt`);
  const shadowSummary = path.join(scratchDir, `summary-${cycle}.md`);

  return new Promise((resolve, reject) => {
    const child = spawn(process.execPath, [target], {
      env: {
        ...process.env,
        // The underlying contract deliberately disarms rollback on transient
        // exhaustion. Keep that side effect local to each bounded retry; only
        // the wrapper may propagate it after all retry cycles are exhausted.
        GITHUB_ENV: shadowEnv,
        GITHUB_STEP_SUMMARY: shadowSummary,
      },
      stdio: 'inherit',
    });

    child.once('error', reject);
    child.once('exit', (code, signal) => {
      if (signal) {
        reject(new Error(`Governed blog head cycle ${cycle} terminated by signal ${signal}`));
        return;
      }
      resolve(Number.isInteger(code) ? code : 1);
    });
  });
}

async function propagateFinalTransientDisarm() {
  if (realGithubEnv) {
    await fs.appendFile(realGithubEnv, 'STAGING_MUTATION_ARMED=0\n', 'utf8');
    console.error('GOVERNED_BLOG_HEAD_RESILIENT_ROLLBACK=DISARMED reason=transient-exhaustion-after-bounded-retries');
  } else {
    console.warn('GOVERNED_BLOG_HEAD_RESILIENT_ROLLBACK=NOT_DISARMED reason=GITHUB_ENV_unavailable');
  }

  if (realSummary) {
    await fs.appendFile(
      realSummary,
      '\n### Governed blog head contract transient exhaustion\n\nThe governed blog head contract was retried three times. Every failure was classified as SiteGround/transient infrastructure (`EX_TEMPFAIL`), with no real application defect established. Staging rollback was therefore disarmed only after bounded retries were exhausted; the run remains ineligible for Production acceptance.\n',
      'utf8'
    );
  }
}

let exitCode = 1;
try {
  for (let cycle = 1; cycle <= maxCycles; cycle += 1) {
    console.log(`GOVERNED_BLOG_HEAD_RESILIENT_CYCLE=${cycle}/${maxCycles}`);
    exitCode = await runCycle(cycle);

    if (exitCode === 0) {
      console.log(`GOVERNED_BLOG_HEAD_RESILIENT=PASS cycle=${cycle}`);
      process.exitCode = 0;
      break;
    }

    if (exitCode !== EX_TEMPFAIL) {
      console.error(`GOVERNED_BLOG_HEAD_RESILIENT=FAIL_REAL cycle=${cycle} exit=${exitCode}`);
      process.exitCode = exitCode || 1;
      break;
    }

    if (cycle === maxCycles) {
      await propagateFinalTransientDisarm();
      console.error(`GOVERNED_BLOG_HEAD_RESILIENT=FAIL_TRANSIENT_EXHAUSTED cycles=${maxCycles}`);
      process.exitCode = EX_TEMPFAIL;
      break;
    }

    const delayMs = 4000 * cycle;
    console.warn(`GOVERNED_BLOG_HEAD_RESILIENT=RETRY cycle=${cycle} delay_ms=${delayMs}`);
    await delay(delayMs);
  }
} catch (error) {
  console.error(`GOVERNED_BLOG_HEAD_RESILIENT=FAIL_REAL error=${error instanceof Error ? error.message : String(error)}`);
  process.exitCode = 1;
} finally {
  await fs.rm(scratchDir, { recursive: true, force: true }).catch(() => {});
}
