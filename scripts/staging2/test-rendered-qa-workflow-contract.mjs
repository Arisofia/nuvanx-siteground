#!/usr/bin/env node
/** Contract test for .github/workflows/staging2-rendered-qa.yml. */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const workflowPath = '.github/workflows/staging2-rendered-qa.yml';
const workflow = fs.readFileSync(path.join(root, workflowPath), 'utf8');
const failures = [];

function requireMatch(pattern, message) {
  if (!pattern.test(workflow)) failures.push(message);
}

function requireAbsent(pattern, message) {
  if (pattern.test(workflow)) failures.push(message);
}

// --- trigger: manual-only QA, never a mutating/automatic trigger -------------

requireMatch(/^on:\s*\n\s+workflow_dispatch:/m, 'workflow must be triggerable via workflow_dispatch');
requireAbsent(/^\s{2}push:/m, 'a rendered QA run against a public staging URL must not run on push');
requireAbsent(/^\s{2}pull_request:/m, 'a rendered QA run against a public staging URL must not run on pull_request');
requireAbsent(/^\s{2}schedule:/m, 'this workflow must not run on a schedule (manual dispatch only)');

requireMatch(
  /base_url:\s*\n\s+description: 'Public Staging2 URL'\s*\n\s+required: true\s*\n\s+default: 'https:\/\/staging2\.nuvanx\.com'\s*\n\s+type: string/,
  'workflow must expose a required base_url input defaulting to the public Staging2 URL',
);
requireMatch(
  /expected_sha:\s*\n\s+description: 'SHA expected to be deployed in Staging2'\s*\n\s+required: true\s*\n\s+default: '[0-9a-f]{40}'\s*\n\s+type: string/,
  'workflow must expose a required expected_sha input defaulting to a 40-character commit SHA',
);

// --- permissions & concurrency -------------------------------------------------

requireMatch(/^permissions:\s*\n\s+contents: read/m, 'workflow must request only read-only contents permission');
requireMatch(
  /^concurrency:\s*\n\s+group: staging2-rendered-qa-\$\{\{ github\.ref \}\}\s*\n\s+cancel-in-progress: true/m,
  'workflow must define a concurrency group keyed on the ref and cancel superseded runs',
);

// --- job configuration ---------------------------------------------------------

requireMatch(/runs-on: ubuntu-latest/, 'job must run on ubuntu-latest');
requireMatch(/timeout-minutes: 30/, 'job must have a bounded timeout');

// --- env wiring: inputs must drive the script's env vars with matching fallbacks -

requireMatch(
  /BASE_URL: \$\{\{ inputs\.base_url \|\| 'https:\/\/staging2\.nuvanx\.com' \}\}/,
  'BASE_URL env must be sourced from the base_url input with the same default as the input',
);
requireMatch(
  /EXPECTED_DEPLOY_SHA: \$\{\{ inputs\.expected_sha \|\| '[0-9a-f]{40}' \}\}/,
  'EXPECTED_DEPLOY_SHA env must be sourced from the expected_sha input with the same default as the input',
);
requireMatch(
  /EXPECTED_CANONICAL_HOST: nuvanx\.com/,
  'EXPECTED_CANONICAL_HOST env must pin the production canonical host used by the canonical-host check',
);
requireMatch(
  /QA_OUTPUT_DIR: qa-artifacts\/staging2-rendered/,
  'QA_OUTPUT_DIR env must match the directory the audit script and later steps read/write',
);

// The workflow_dispatch input default and the job env fallback default must
// never drift, otherwise triggering without inputs silently audits/blames
// the wrong SHA.
const inputShaDefault = workflow.match(/expected_sha:[\s\S]*?default: '([0-9a-f]{40})'/)?.[1];
const envShaDefault = workflow.match(/EXPECTED_DEPLOY_SHA: \$\{\{ inputs\.expected_sha \|\| '([0-9a-f]{40})' \}\}/)?.[1];
if (!inputShaDefault || !envShaDefault || inputShaDefault !== envShaDefault) {
  failures.push('the expected_sha input default and the EXPECTED_DEPLOY_SHA env fallback default must be identical');
}

// --- checkout & node setup, pinned to full commit SHAs -------------------------

requireMatch(/uses: actions\/checkout@[0-9a-f]{40} # v4/, 'checkout action must be pinned to a full commit SHA');
requireMatch(/persist-credentials: false/, 'checkout must not persist credentials for this read-only job');
requireMatch(/uses: actions\/setup-node@[0-9a-f]{40} # v4/, 'setup-node action must be pinned to a full commit SHA');
requireMatch(/node-version: '22'/, 'workflow must use Node.js 22');

// --- source validation before executing anything --------------------------------

requireMatch(
  /node --check scripts\/staging2\/rendered-qa\.mjs/,
  'workflow must syntax-check the audit script before running it',
);
requireMatch(
  /node -e "JSON\.parse\(require\('fs'\)\.readFileSync\('scripts\/staging2\/rendered-qa-routes\.json','utf8'\)\)"/,
  'workflow must validate the routes JSON before running the audit',
);

// --- browser dependency is installed as a temporary, unpinned-to-repo dep ---------

requireMatch(
  /npm install playwright@1\.54\.1 --no-save/,
  'workflow must install a pinned Playwright version without persisting it to a manifest',
);
requireMatch(
  /npx playwright install --with-deps chromium/,
  'workflow must install the Chromium browser (and OS deps) Playwright needs in CI',
);

// --- audit execution must not let a critical failure abort the job early --------

const auditStep = workflow.match(/- name: Run rendered QA[\s\S]*?(?=\n {6}- name:)/)?.[0] || '';
if (!auditStep) {
  failures.push('workflow is missing the "Run rendered QA" step');
} else {
  if (!/id: audit/.test(auditStep)) failures.push('the audit step must expose its outcome via "id: audit"');
  if (!/set \+e/.test(auditStep)) failures.push('the audit step must disable errexit so later steps can still run/report');
  if (!/node scripts\/staging2\/rendered-qa\.mjs/.test(auditStep)) failures.push('the audit step must run rendered-qa.mjs');
  if (!/echo "exit_code=\$exit_code" >> "\$GITHUB_OUTPUT"/.test(auditStep)) {
    failures.push('the audit step must capture the script exit code into a step output');
  }
  if (!/\n\s*exit 0\s*$/.test(auditStep)) {
    failures.push('the audit step must itself exit 0 so artifact upload/summary steps still run on audit failure');
  }
}

// --- reporting and artifacts must run even when the audit step failed -----------

requireMatch(
  /- name: Publish QA report in job summary\s*\n\s+if: always\(\)\s*\n\s+run: cat qa-artifacts\/staging2-rendered\/report\.md >> "\$GITHUB_STEP_SUMMARY"/,
  'the job summary step must run unconditionally (if: always()) and publish report.md',
);

const uploadStep = workflow.match(/- name: Upload screenshots and reports[\s\S]*?(?=\n {6}- name:|\n$)/)?.[0] || '';
if (!uploadStep) {
  failures.push('workflow is missing the "Upload screenshots and reports" step');
} else {
  if (!/if: always\(\)/.test(uploadStep)) failures.push('artifact upload must run unconditionally (if: always())');
  if (!/uses: actions\/upload-artifact@[0-9a-f]{40} # v4\.6\.2/.test(uploadStep)) {
    failures.push('upload-artifact action must be pinned to a full commit SHA');
  }
  if (!/path: qa-artifacts\/staging2-rendered/.test(uploadStep)) failures.push('upload must include the QA output directory');
  if (!/if-no-files-found: error/.test(uploadStep)) failures.push('upload must fail loudly if no artifacts were produced');
  if (!/retention-days: 14/.test(uploadStep)) failures.push('upload must define an explicit, bounded retention period');
}

// --- the job must actually fail when the audit found critical findings ----------

const enforceStep = workflow.match(/- name: Enforce critical findings[\s\S]*$/)?.[0] || '';
if (!enforceStep) {
  failures.push('workflow is missing the "Enforce critical findings" step');
} else {
  if (!/if: always\(\)/.test(enforceStep)) failures.push('the enforcement step must run unconditionally (if: always())');
  if (!/AUDIT_EXIT_CODE: \$\{\{ steps\.audit\.outputs\.exit_code \}\}/.test(enforceStep)) {
    failures.push('the enforcement step must read the captured audit exit code from the "audit" step output');
  }
  if (!/test "\$AUDIT_EXIT_CODE" = "0"/.test(enforceStep)) {
    failures.push('the enforcement step must fail the job when the captured audit exit code is non-zero');
  }
  if (!/exit 1/.test(enforceStep)) failures.push('the enforcement step must exit non-zero to fail the job on critical findings');
}

if (failures.length) {
  console.error(`Staging2 rendered QA workflow contract failed (${workflowPath}):`);
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: staging2-rendered-qa.yml workflow contract');