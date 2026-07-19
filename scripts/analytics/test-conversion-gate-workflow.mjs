#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const workflow = fs.readFileSync(path.join(root, '.github/workflows/conversion-events-gate.yml'), 'utf8');
const resolverSource = fs.readFileSync(path.join(root, 'scripts/analytics/resolve-staging-deploy-sha.mjs'), 'utf8');

const resolver = workflow.match(/- name: Resolve latest deploy-triggering commit[\s\S]*?(?=\n      - name:)/)?.[0] || '';

if (!/node scripts\/analytics\/resolve-staging-deploy-sha\.mjs "\$SEARCH_REF"/.test(resolver)) {
  throw new Error('conversion gate must use the audited staging SHA resolver');
}

if (!/'--first-parent'/.test(resolverSource)) {
  throw new Error('staging SHA resolver must follow first-parent history');
}

if (!/EXPECTED_DEPLOY_SHA: \$\{\{ steps\.deploy\.outputs\.sha \}\}/.test(workflow)) {
  throw new Error('rendered conversion verification must consume the resolved deployed SHA');
}

console.log('PASS: conversion gate deploy identity contract');
