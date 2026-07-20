#!/usr/bin/env node
/** Contract test for the security artifact cleanup workflow. */

import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const workflowPath = '.github/workflows/security-artifact-cleanup.yml';
const workflow = fs.readFileSync(path.join(root, workflowPath), 'utf8');
const failures = [];

const requireMatch = (source, pattern, message) => {
  if (!pattern.test(source)) failures.push(message);
};

const requireAbsent = (source, pattern, message) => {
  if (pattern.test(source)) failures.push(message);
};

const checkoutStep = workflow.match(/- name: Checkout[\s\S]*?(?=\n {6}- name:)/)?.[0] || '';
if (!checkoutStep) failures.push('artifact cleanup workflow is missing the repository checkout step');

requireMatch(
  checkoutStep,
  /uses: actions\/checkout@[0-9a-f]{40} # v4/,
  'checkout action must remain pinned to a commit SHA',
);

requireMatch(
  checkoutStep,
  /persist-credentials: false/,
  'checkout step must disable credential persistence; this job only deletes workflow runs via the GitHub CLI/API and must not leave a git credential on the runner',
);

// The guard must live inside the checkout step's own `with:` block rather
// than merely appearing anywhere else in the file.
requireMatch(
  checkoutStep,
  /with:\s*\n\s+persist-credentials: false/,
  'persist-credentials: false must be configured via the checkout step `with:` block',
);

// This is a same-repository cleanup job with no other checkout steps, so the
// guard should appear exactly once.
const persistCredentialsMatches = workflow.match(/persist-credentials:/g) || [];
if (persistCredentialsMatches.length !== 1) {
  failures.push(`expected exactly one persist-credentials setting, found ${persistCredentialsMatches.length}`);
}

requireAbsent(
  workflow,
  /persist-credentials: true/,
  'workflow must never re-enable credential persistence',
);

if (failures.length) {
  console.error('Security artifact cleanup workflow contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: security artifact cleanup workflow disables checkout credential persistence');