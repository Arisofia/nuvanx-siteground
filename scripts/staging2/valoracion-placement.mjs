import { spawn } from 'node:child_process';
import fs from 'node:fs/promises';
import { setTimeout as delay } from 'node:timers/promises';
import { fileURLToPath } from 'node:url';
import { EX_TEMPFAIL } from './siteground-transient-classifier.mjs';

function runProcess(moduleUrl) {
  return new Promise((resolve, reject) => {
    const child = spawn(process.execPath, [fileURLToPath(moduleUrl)], {
      env: process.env,
      stdio: 'inherit',
    });

    child.once('error', reject);
    child.once('exit', (code, signal) => {
      if (signal) {
        reject(new Error(`Terminated by signal ${signal}`));
        return;
      }
      resolve(Number.isInteger(code) ? code : 1);
    });
  });
}

async function writeRollbackState(value, component, reason) {
  const envFile = (process.env.GITHUB_ENV || '').trim();
  if (!envFile) return;
  try {
    await fs.appendFile(envFile, `STAGING_MUTATION_ARMED=${value}\n`, 'utf8');
    console.log(`STAGING_ACCEPTANCE_ROLLBACK=${value === '1' ? 'REARMED' : 'DISARMED'} component=${component} reason=${reason}`);
  } catch (error) {
    console.warn(`STAGING_ACCEPTANCE_ROLLBACK=WRITE_FAILED component=${component} error=${error instanceof Error ? error.message : String(error)}`);
  }
}

async function disarmRollbackAfterTransientExhaustion(component) {
  const summary = (process.env.GITHUB_STEP_SUMMARY || '').trim();
  if (!summary) return;
  try {
    await fs.appendFile(
      summary,
      `\n### Staging acceptance transient exhaustion\n\nComponent \`${component}\` remained inconclusive after all bounded retry cycles. No deterministic defect was established; this run is not eligible for Production acceptance.\n`,
      'utf8'
    );
  } catch (error) {
    console.warn(`STAGING_ACCEPTANCE_SUMMARY=WRITE_FAILED component=${component} error=${error instanceof Error ? error.message : String(error)}`);
  }
}

async function runStage(name, moduleUrl, maxCycles = 1, backoffMs = 3500) {
  let lastExitCode = 1;
  let sawTransient = false;

  for (let cycle = 1; cycle <= maxCycles; cycle += 1) {
    if (maxCycles > 1) {
      console.log(`STAGING_ACCEPTANCE_CYCLE component=${name} cycle=${cycle}/${maxCycles}`);
    }

    try {
      lastExitCode = await runProcess(moduleUrl);
    } catch (err) {
      console.error(`STAGING_ACCEPTANCE_COMPONENT=FAIL component=${name} reason=${err instanceof Error ? err.message : String(err)}`);
      process.exit(1);
    }

    if (lastExitCode === 0) {
      if (sawTransient) {
        await writeRollbackState('1', name, 'transient-recovered');
        const envFile = (process.env.GITHUB_ENV || '').trim();
        if (envFile) {
          try {
            await fs.appendFile(envFile, 'STAGING_ACCEPTANCE_TRANSIENT=0\n', 'utf8');
            console.log(`STAGING_ACCEPTANCE_TRANSIENT=RESET component=${name} reason=transient-recovered`);
          } catch (err) {
            console.warn(`STAGING_ACCEPTANCE_TRANSIENT=RESET_FAILED component=${name} error=${err instanceof Error ? err.message : String(err)}`);
          }
        }
      }
      console.log(`STAGING_ACCEPTANCE_COMPONENT=PASS component=${name}${maxCycles > 1 ? ` cycle=${cycle}` : ''}`);
      return;
    }

    if (lastExitCode !== EX_TEMPFAIL) {
      console.error(`STAGING_ACCEPTANCE_COMPONENT=FAIL component=${name} exit=${lastExitCode}`);
      process.exit(lastExitCode);
    }

    sawTransient = true;
    if (cycle < maxCycles) {
      await writeRollbackState('1', name, 'outer-transient-retry');
      const delayMs = backoffMs * cycle;
      console.warn(`STAGING_ACCEPTANCE_COMPONENT=RETRY component=${name} cycle=${cycle} exit=${lastExitCode} delay_ms=${delayMs}`);
      await delay(delayMs);
    }
  }

  await disarmRollbackAfterTransientExhaustion(name);
  console.error(`STAGING_ACCEPTANCE_COMPONENT=FAIL component=${name} cycles=${maxCycles} exit=${lastExitCode}`);
  process.exit(lastExitCode || 1);
}

const stages = [
  { name: 'siteground-transient-classifier', url: new URL('./test-siteground-transient-classifier.mjs', import.meta.url), maxCycles: 1 },
  { name: 'hubspot-submission-classifier', url: new URL('./test-hubspot-submission-classifier.mjs', import.meta.url), maxCycles: 1 },
  { name: 'hubspot-a11y-safe-unit', url: new URL('./test-hubspot-a11y-safe.mjs', import.meta.url), maxCycles: 1 },
  { name: 'governed-blog-head-contract', url: new URL('./governed-blog-head-resilient.mjs', import.meta.url), maxCycles: 1 },
  { name: 'valoracion-placement', url: new URL('./valoracion-placement-resilient.mjs', import.meta.url), maxCycles: 3 },
  // The strict HubSpot probe remains unchanged. The safe-scope wrapper may only
  // convert the exact zero-submit/server-validation boundary into PASS after it
  // verifies all observable client-side semantics and all other required fields.
  // Three outer cycles provide bounded resilience for third-party transient behavior.
  { name: 'hubspot-a11y', url: new URL('./h1-hubspot-a11y-safe.mjs', import.meta.url), maxCycles: 3, backoffMs: 7000 },
  { name: 'block-a11y', url: new URL('./block-a11y.mjs', import.meta.url), maxCycles: 1 },
];

for (const stage of stages) {
  await runStage(stage.name, stage.url, stage.maxCycles, stage.backoffMs);
}
