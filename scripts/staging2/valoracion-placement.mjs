import { spawn } from 'node:child_process';
import { setTimeout as delay } from 'node:timers/promises';
import { fileURLToPath } from 'node:url';

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

    if (cycle < maxCycles) {
      const delayMs = backoffMs * cycle;
      console.warn(`STAGING_ACCEPTANCE_COMPONENT=RETRY component=${name} cycle=${cycle} exit=${lastExitCode} delay_ms=${delayMs}`);
      await delay(delayMs);
    }
  }

  console.error(`STAGING_ACCEPTANCE_COMPONENT=FAIL component=${name} cycles=${maxCycles} exit=${lastExitCode}`);
  process.exit(lastExitCode || 1);
}

const stages = [
  { name: 'siteground-transient-classifier', url: new URL('./test-siteground-transient-classifier.mjs', import.meta.url), maxCycles: 1 },
  { name: 'governed-blog-head-contract', url: new URL('./governed-blog-head-contract.mjs', import.meta.url), maxCycles: 1 },
  { name: 'valoracion-placement', url: new URL('./valoracion-placement-resilient.mjs', import.meta.url), maxCycles: 3 },
];

for (const stage of stages) {
  await runStage(stage.name, stage.url, stage.maxCycles);
}


