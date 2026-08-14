import './test-siteground-transient-classifier.mjs';
import { spawn } from 'node:child_process';
import { setTimeout as delay } from 'node:timers/promises';
import { fileURLToPath } from 'node:url';

const resilientScript = fileURLToPath(new URL('./valoracion-placement-resilient.mjs', import.meta.url));
const maxQaCycles = 3;
let lastExitCode = 1;

function runQaCycle(cycle) {
  console.log(`VALORACION_QA_CYCLE=${cycle}/${maxQaCycles}`);
  return new Promise((resolve, reject) => {
    const child = spawn(process.execPath, [resilientScript], {
      env: process.env,
      stdio: 'inherit',
    });

    child.once('error', reject);
    child.once('exit', (code, signal) => {
      if (signal) {
        reject(new Error(`Valoración QA cycle ${cycle} terminated by signal ${signal}`));
        return;
      }
      resolve(Number.isInteger(code) ? code : 1);
    });
  });
}

for (let cycle = 1; cycle <= maxQaCycles; cycle += 1) {
  lastExitCode = await runQaCycle(cycle);
  if (lastExitCode === 0) {
    console.log(`VALORACION_QA_RESILIENT=PASS cycle=${cycle}`);
    process.exit(0);
  }

  if (cycle < maxQaCycles) {
    const delayMs = 3500 * cycle;
    console.warn(`VALORACION_QA_RESILIENT=RETRY cycle=${cycle} exit=${lastExitCode} delay_ms=${delayMs}`);
    await delay(delayMs);
  }
}

console.error(`VALORACION_QA_RESILIENT=FAIL cycles=${maxQaCycles} exit=${lastExitCode}`);
process.exit(lastExitCode || 1);
