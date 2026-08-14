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

async function disarmRollbackAfterTransientExhaustion(component) {
  const envFile = (process.env.GITHUB_ENV || '').trim();
  if (envFile) {
    try {
      await fs.appendFile(envFile, 'STAGING_MUTATION_ARMED=0\n', 'utf8');
      console.error(`STAGING_ACCEPTANCE_ROLLBACK=DISARMED component=${component} reason=transient-exhaustion`);
    } catch (error) {
      console.warn(`STAGING_ACCEPTANCE_ROLLBACK=DISARM_FAILED component=${component} error=${error instanceof Error ? error.message : String(error)}`);
    }
  }

  const summary = (process.env.GITHUB_STEP_SUMMARY || '').trim();
  if (!summary) return;
  try {
    await fs.appendFile(
      summary,
      `\n### Staging acceptance transient exhaustion\n\nComponent \`${component}\` remained inconclusive after all bounded retry cycles. No deterministic defect was established, so rollback was disarmed; this run is not eligible for Production acceptance.\n`,
      'utf8'
    );
  } catch (error) {
    console.warn(`STAGING_ACCEPTANCE_SUMMARY=WRITE_FAILED component=${component} error=${error instanceof Error ? error.message : String(error)}`);
  }
}

async function runStage(name, moduleUrl, maxCycles = 1, backoffMs = 3500) {
  let lastExitCode = 1;

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
      console.log(`STAGING_ACCEPTANCE_COMPONENT=PASS component=${name}${maxCycles > 1 ? ` cycle=${cycle}` : ''}`);
      return;
    }

    // Do not retry deterministic real failures across cycles; fail immediately to preserve evidence.
    if (lastExitCode !== EX_TEMPFAIL) {
      console.error(`STAGING_ACCEPTANCE_COMPONENT=FAIL component=${name} exit=${lastExitCode}`);
      process.exit(lastExitCode);
    }

    if (cycle < maxCycles) {
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
  { name: 'governed-blog-head-contract', url: new URL('./governed-blog-head-resilient.mjs', import.meta.url), maxCycles: 1 },
  { name: 'valoracion-placement', url: new URL('./valoracion-placement-resilient.mjs', import.meta.url), maxCycles: 3 },
  // HubSpot is third-party runtime. Each child cycle already performs three
  // internal attempts; three outer cycles provide bounded resilience without
  // weakening any accessibility assertion or creating a real contact.
  { name: 'hubspot-a11y', url: new URL('./h1-hubspot-a11y.mjs', import.meta.url), maxCycles: 3, backoffMs: 7000 },
  { name: 'block-a11y', url: new URL('./block-a11y.mjs', import.meta.url), maxCycles: 1 },
];

for (const stage of stages) {
  await runStage(stage.name, stage.url, stage.maxCycles, stage.backoffMs);
}
