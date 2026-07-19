#!/usr/bin/env node

import { execFileSync } from 'node:child_process';

const searchRef = process.argv[2];
if (!searchRef) throw new Error('usage: resolve-staging-deploy-sha.mjs <git-ref>');

const sha = execFileSync('git', [
  'log',
  '--first-parent',
  '-1',
  '--format=%H',
  searchRef,
  '--',
  'wp-content/themes/nuvanx-medical',
  'wp-content/mu-plugins',
  'scripts/staging2',
  '.github/workflows/deploy-theme-staging2.yml',
], { encoding: 'utf8' }).trim();

if (!/^[0-9a-f]{40}$/.test(sha)) {
  throw new Error(`unable to resolve a staging deployment SHA from ${searchRef}`);
}

process.stdout.write(`${sha}\n`);
