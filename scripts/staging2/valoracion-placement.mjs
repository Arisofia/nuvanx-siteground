import { spawn } from 'node:child_process';
import fs from 'node:fs/promises';
import path from 'node:path';
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
      if (signal) return reject(new Error(`Terminated by signal ${signal}`));
      resolve(Number.isInteger(code) ? code : 1);
    });
  });
}

function blockA11yEvidencePaths() {
  return {
    source: path.resolve('scripts/staging2/block-a11y-artifacts/results.json'),
    destinationDir: path.resolve('scripts/staging2/valoracion-artifacts'),
    destination: path.resolve('scripts/staging2/valoracion-artifacts/block-a11y-results.json'),
  };
}

async function prepareBlockA11yEvidence() {
  const { source, destination } = blockA11yEvidencePaths();
  await fs.rm(source, { force: true });
  await fs.rm(destination, { force: true });
}

async function preserveStageEvidence(component) {
  if (component !== 'block-a11y') return true;
  const { source, destinationDir, destination } = blockA11yEvidencePaths();
  try {
    await fs.access(source);
    await fs.mkdir(destinationDir, { recursive: true });
    await fs.copyFile(source, destination);
    console.log(`STAGING_ACCEPTANCE_EVIDENCE=PRESERVED component=${component} path=${destination}`);
    return true;
  } catch (error) {
    console.warn(`STAGING_ACCEPTANCE_EVIDENCE=UNAVAILABLE component=${component} error=${error instanceof Error ? error.message : String(error)}`);
    return false;
  }
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

async function appendTransientSummary(component, reason, detail) {
  const summary = (process.env.GITHUB_STEP_SUMMARY || '').trim();
  if (!summary) return;
  try {
    await fs.appendFile(summary, `\n### Staging acceptance transient\n\nComponent \`${component}\` could not produce production-eligible evidence (${reason}). ${detail} No deterministic site defect was established, so rollback was disarmed and the same immutable SHA must be retried on a fresh runner.\n`, 'utf8');
  } catch (error) {
    console.warn(`STAGING_ACCEPTANCE_SUMMARY=WRITE_FAILED component=${component} error=${error instanceof Error ? error.message : String(error)}`);
  }
}

async function disarmRollbackAfterTransientExhaustion(component) {
  await writeRollbackState('0', component, 'transient-exhaustion');
  await appendTransientSummary(component, 'transient-exhaustion', 'The component exhausted its bounded runtime attempts.');
}

async function failTransientForMissingEvidence(component) {
  await writeRollbackState('0', component, 'required-evidence-unavailable');
  await appendTransientSummary(component, 'required-evidence-unavailable', 'The gate itself passed, but its required evidence file could not be preserved.');
  console.error(`STAGING_ACCEPTANCE_COMPONENT=FAIL_TRANSIENT component=${component} reason=required_evidence_unavailable exit=${EX_TEMPFAIL}`);
  process.exit(EX_TEMPFAIL);
}

async function runStage(name, moduleUrl, maxCycles = 1, backoffMs = 3500) {
  let lastExitCode = 1;
  let sawTransient = false;

  for (let cycle = 1; cycle <= maxCycles; cycle += 1) {
    if (maxCycles > 1) console.log(`STAGING_ACCEPTANCE_CYCLE component=${name} cycle=${cycle}/${maxCycles}`);

    let processError = null;
    let evidencePreserved = true;
    try {
      lastExitCode = await runProcess(moduleUrl);
    } catch (err) {
      processError = err;
    } finally {
      evidencePreserved = await preserveStageEvidence(name);
    }

    if (processError) {
      console.error(`STAGING_ACCEPTANCE_COMPONENT=FAIL component=${name} reason=${processError instanceof Error ? processError.message : String(processError)}`);
      process.exit(1);
    }
    if (lastExitCode === 0 && !evidencePreserved) await failTransientForMissingEvidence(name);
    if (lastExitCode === 0) {
      if (sawTransient) await writeRollbackState('1', name, 'transient-recovered');
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

// Network-aware children own their bounded internal retries. Any remaining
// transient is escalated after one cycle to the next of the three fresh GitHub
// runners, preserving coverage without repeating the same workload/network ID.
const stages = [
  { name: 'siteground-transient-classifier', url: new URL('./test-siteground-transient-classifier.mjs', import.meta.url), maxCycles: 1 },
  { name: 'hubspot-submission-classifier', url: new URL('./test-hubspot-submission-classifier.mjs', import.meta.url), maxCycles: 1 },
  { name: 'governed-blog-head-contract', url: new URL('./governed-blog-head-resilient.mjs', import.meta.url), maxCycles: 1 },
  { name: 'valoracion-placement', url: new URL('./valoracion-placement-resilient.mjs', import.meta.url), maxCycles: 1 },
  { name: 'hubspot-a11y', url: new URL('./h1-hubspot-a11y.mjs', import.meta.url), maxCycles: 1 },
  { name: 'block-a11y', url: new URL('./block-a11y.mjs', import.meta.url), maxCycles: 1 },
];

// Remove both locations before any earlier stage can fail, preventing stale
// evidence from a reused workspace from entering the diagnostic artifact.
await prepareBlockA11yEvidence();
for (const stage of stages) await runStage(stage.name, stage.url, stage.maxCycles, stage.backoffMs);
