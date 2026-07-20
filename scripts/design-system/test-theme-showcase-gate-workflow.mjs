#!/usr/bin/env node
/** Contract test for the theme showcase gate workflow's checkout step. */

import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const workflowPath = '.github/workflows/theme-showcase-gate.yml';
const workflow = fs.readFileSync(path.join(root, workflowPath), 'utf8');
const failures = [];

const requireMatch = (source, pattern, message) => {
  if (!pattern.test(source)) failures.push(message);
};

const requireAbsent = (source, pattern, message) => {
  if (pattern.test(source)) failures.push(message);
};

const checkoutStep = workflow.match(/- name: Check out repository[\s\S]*?(?=\n {6}- name:)/)?.[0] || '';
if (!checkoutStep) failures.push('theme showcase gate workflow is missing the repository checkout step');

requireMatch(
  checkoutStep,
  /uses: actions\/checkout@[0-9a-f]{40} # v4/,
  'checkout action must remain pinned to a commit SHA',
);

requireMatch(
  checkoutStep,
  /fetch-depth: 0/,
  'checkout step must keep full history so the runtime-theme diff check can compare against the PR base SHA',
);

requireMatch(
  checkoutStep,
  /persist-credentials: false/,
  'checkout step must disable credential persistence for this untrusted-diff PR gate',
);

// Both `with:` options must be configured together under the same checkout
// step, not scattered elsewhere in the file.
requireMatch(
  checkoutStep,
  /with:\s*\n\s+fetch-depth: 0\s*\n\s+persist-credentials: false/,
  'fetch-depth: 0 and persist-credentials: false must both live in the checkout step `with:` block',
);

const steps = workflow.split(/\n\s{6}-\s/);
const checkoutSteps = steps.filter(step => step.includes('uses: actions/checkout'));

if (checkoutSteps.length === 0) {
  failures.push('theme showcase gate workflow is missing the repository checkout step');
} else if (checkoutSteps.length > 1) {
  failures.push(`expected exactly one checkout step, found ${checkoutSteps.length}`);
}

for (const step of checkoutSteps) {
  if (!/persist-credentials:\s*false/.test(step)) {
    failures.push('checkout step must disable credential persistence');
  }
}

requireAbsent(
  workflow,
  /persist-credentials: true/,
  'workflow must never re-enable credential persistence',
);

if (failures.length) {
  console.error('Theme showcase gate workflow contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: theme showcase gate workflow disables checkout credential persistence');