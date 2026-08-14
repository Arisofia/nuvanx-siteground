import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

function runStage(name, moduleUrl) {
  const result = spawnSync(process.execPath, [fileURLToPath(moduleUrl)], {
    env: process.env,
    stdio: 'inherit',
  });

  if (result.error) {
    console.error(`STAGING_ACCEPTANCE_COMPONENT=FAIL component=${name} reason=${result.error.message}`);
    process.exit(1);
  }
  if (result.signal) {
    console.error(`STAGING_ACCEPTANCE_COMPONENT=FAIL component=${name} signal=${result.signal}`);
    process.exit(1);
  }
  if (result.status !== 0) {
    const exitCode = Number.isInteger(result.status) ? result.status : 1;
    console.error(`STAGING_ACCEPTANCE_COMPONENT=FAIL component=${name} exit=${exitCode}`);
    process.exit(exitCode);
  }

  console.log(`STAGING_ACCEPTANCE_COMPONENT=PASS component=${name}`);
}

const stages = [
  ['siteground-transient-classifier', new URL('./test-siteground-transient-classifier.mjs', import.meta.url)],
  ['governed-blog-head-contract', new URL('./governed-blog-head-contract.mjs', import.meta.url)],
  ['valoracion-placement', new URL('./valoracion-placement-resilient.mjs', import.meta.url)],
];

for (const [name, moduleUrl] of stages) {
  runStage(name, moduleUrl);
}

