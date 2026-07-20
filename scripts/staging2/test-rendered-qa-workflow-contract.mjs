#!/usr/bin/env node

// Contract test for .github/workflows/staging2-rendered-qa.yml, following the
// same text-based contract convention as test-deploy-workflow-contract.mjs.

import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const workflowPath = '.github/workflows/staging2-rendered-qa.yml';
const workflow = fs.readFileSync(path.join(root, workflowPath), 'utf8');
const failures = [];

function requireMatch(source, pattern, message) {
  if (!pattern.test(source)) failures.push(message);
}

function requireAbsent(source, pattern, message) {
  if (pattern.test(source)) failures.push(message);
}

// Triggers: PR on the audit's own files, plus a manual dispatch with a required base_url/expected_sha.
requireMatch(workflow, /^\s{2}pull_request:/m, 'workflow must run on pull requests');
requireMatch(workflow, /paths:\s*\n\s+- '\.github\/workflows\/staging2-rendered-qa\.yml'/, 'PR trigger must watch the workflow file itself');
requireMatch(workflow, /- 'scripts\/staging2\/rendered-qa\.mjs'/, 'PR trigger must watch the audit script');
requireMatch(workflow, /- 'scripts\/staging2\/rendered-qa-routes\.json'/, 'PR trigger must watch the routes config');
requireMatch(workflow, /^\s{2}workflow_dispatch:/m, 'workflow must expose workflow_dispatch for manual runs');
requireMatch(workflow, /base_url:\s*\n\s+description: 'Public Staging2 URL'\s*\n\s+required: true/, 'workflow_dispatch must require a base_url input');
requireMatch(workflow, /expected_sha:\s*\n\s+description: 'SHA expected to be deployed in Staging2'\s*\n\s+required: true/, 'workflow_dispatch must require an expected_sha input');
requireAbsent(workflow, /^\s{2}push:/m, 'workflow must not run on push');
requireAbsent(workflow, /^\s{2}schedule:/m, 'workflow must not run on a schedule');

// Least-privilege permissions and non-overlapping concurrency.
requireMatch(workflow, /permissions:\s*\n\s+contents: read/, 'workflow must declare read-only contents permission');
requireMatch(workflow, /concurrency:\s*\n\s+group: staging2-rendered-qa-\$\{\{ github\.ref \}\}\s*\n\s+cancel-in-progress: true/, 'workflow must cancel superseded runs per-ref');

// Job shape.
requireMatch(workflow, /runs-on: ubuntu-latest/, 'job must run on ubuntu-latest');
requireMatch(workflow, /timeout-minutes: 30/, 'job must have a bounded timeout');
requireMatch(workflow, /BASE_URL: \$\{\{ inputs\.base_url \|\| 'https:\/\/staging2\.nuvanx\.com' \}\}/, 'BASE_URL env must fall back to the staging2 URL for PR runs');
requireMatch(workflow, /EXPECTED_DEPLOY_SHA: \$\{\{ inputs\.expected_sha \|\| '[0-9a-f]{40}' \}\}/, 'EXPECTED_DEPLOY_SHA env must fall back to a pinned 40-char SHA');
requireMatch(workflow, /EXPECTED_CANONICAL_HOST: nuvanx\.com/, 'EXPECTED_CANONICAL_HOST must be pinned to the production apex domain');
requireMatch(workflow, /QA_OUTPUT_DIR: qa-artifacts\/staging2-rendered/, 'QA_OUTPUT_DIR must be the artifacts directory used by rendered-qa.mjs');

// Actions must be pinned to full commit SHAs, not floating tags.
requireMatch(workflow, /uses: actions\/checkout@[0-9a-f]{40}/, 'checkout action must be pinned to a commit SHA');
requireMatch(workflow, /uses: actions\/setup-node@[0-9a-f]{40}/, 'setup-node action must be pinned to a commit SHA');
requireMatch(workflow, /node-version: '22'/, 'workflow must pin the Node.js major version');

// Source is validated before any browser dependency is installed or run.
requireMatch(workflow, /node --check scripts\/staging2\/rendered-qa\.mjs/, 'workflow must syntax-check the audit script');
requireMatch(
  workflow,
  /node -e "JSON\.parse\(require\('fs'\)\.readFileSync\('scripts\/staging2\/rendered-qa-routes\.json','utf8'\)\)"/,
  'workflow must JSON-validate the routes config',
);

// Playwright is installed as an unpinned-in-lockfile, pinned-in-command temporary dependency.
requireMatch(workflow, /npm install playwright@1\.54\.1 --no-save/, 'workflow must install a pinned Playwright version without persisting it');
requireMatch(workflow, /npx playwright install --with-deps chromium/, 'workflow must install the Chromium browser binary with its OS deps');

// The audit step must capture its exit code without failing the job outright,
// so that the report/artifact steps below still run on failure.
const auditStep = workflow.match(/- name: Run rendered QA[\s\S]*?(?=\n {6}- name:)/)?.[0] || '';
if (!auditStep) failures.push('workflow is missing the "Run rendered QA" step');
requireMatch(auditStep, /id: audit/, 'audit step must expose an id so its outputs can be referenced');
requireMatch(auditStep, /set \+e/, 'audit step must disable errexit so it can capture the real exit code');
requireMatch(auditStep, /node scripts\/staging2\/rendered-qa\.mjs/, 'audit step must run the rendered QA script');
requireMatch(auditStep, /echo "exit_code=\$exit_code" >> "\$GITHUB_OUTPUT"/, 'audit step must publish its exit code as a step output');
requireMatch(auditStep, /\n\s*exit 0\s*$/, 'audit step must itself exit 0 so downstream always-run steps execute');

// Reporting and artifact upload must run even when the audit step failed.
requireMatch(
  workflow,
  /- name: Publish QA report in job summary\s*\n\s+if: always\(\)\s*\n\s+run: cat qa-artifacts\/staging2-rendered\/report\.md >> "\$GITHUB_STEP_SUMMARY"/,
  'job summary step must always publish report.md',
);
const uploadStep = workflow.match(/- name: Upload screenshots and reports[\s\S]*?(?=\n {6}- name:|$)/)?.[0] || '';
if (!uploadStep) failures.push('workflow is missing the "Upload screenshots and reports" step');
requireMatch(uploadStep, /if: always\(\)/, 'artifact upload must run even if the audit step failed');
requireMatch(uploadStep, /uses: actions\/upload-artifact@v4/, 'workflow must use upload-artifact v4');
requireMatch(uploadStep, /path: qa-artifacts\/staging2-rendered/, 'artifact upload must include the QA output directory');
requireMatch(uploadStep, /if-no-files-found: error/, 'artifact upload must fail loudly if no artifacts were produced');
requireMatch(uploadStep, /retention-days: 14/, 'artifact upload must set an explicit retention period');

// The job must still fail overall when the audit reported critical findings.
const enforceStep = workflow.match(/- name: Enforce critical findings[\s\S]*$/)?.[0] || '';
if (!enforceStep) failures.push('workflow is missing the "Enforce critical findings" step');
requireMatch(enforceStep, /if: always\(\)/, 'enforcement step must run even after earlier failures');
requireMatch(
  enforceStep,
  /test "\$\{\{ steps\.audit\.outputs\.exit_code \}\}" = "0" \|\| \{/,
  'enforcement step must check the captured audit exit code',
);
requireMatch(enforceStep, /exit 1/, 'enforcement step must fail the job when critical findings were found');

if (failures.length) {
  console.error('staging2-rendered-qa.yml workflow contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: staging2-rendered-qa.yml workflow contract');