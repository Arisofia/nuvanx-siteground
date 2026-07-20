#!/usr/bin/env node
/** Contract test for the SEO production readiness gate workflow. */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const workflow = fs.readFileSync(path.join(root, '.github/workflows/seo-production-readiness-gate.yml'), 'utf8');

const requireMatch = (source, pattern, message) => {
  if (!pattern.test(source)) throw new Error(message);
};

const escapeRegExp = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

const checkoutStep = workflow.match(/- name: Check out repository[\s\S]*?(?=\n {6}- name:)/)?.[0] || '';
const syntaxStep = workflow.match(/- name: Validate PHP syntax[\s\S]*?(?=\n {6}- name:)/)?.[0] || '';

if (!checkoutStep) throw new Error('gate workflow is missing the repository checkout step');
if (!syntaxStep) throw new Error('gate workflow is missing the PHP syntax validation step');

requireMatch(
  checkoutStep,
  /persist-credentials: false/,
  'checkout step must disable credential persistence for this read-only CI job',
);
requireMatch(checkoutStep, /show-progress: false/, 'checkout step must keep checkout progress output disabled');

for (const file of [
  'wp-content/themes/nuvanx-medical/inc/nvx-seo-production-readiness.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-seo-metadata.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-page-hygiene.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-structured-data.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-integrations.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-aesthetic-treatment-schema.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-btl-detail-pages.php',
]) {
  requireMatch(
    syntaxStep,
    new RegExp(`php -l ${escapeRegExp(file)}`),
    `PHP syntax gate must lint ${file}`,
  );
}

requireMatch(
  workflow,
  /run: node scripts\/seo\/test-production-seo-contract\.mjs/,
  'gate must run the production SEO source contract check',
);
requireMatch(
  workflow,
  /run: php scripts\/seo\/test-production-seo-readiness\.php/,
  'gate must execute the Schema graph normalization regression script',
);

console.log('PASS: SEO production readiness gate workflow contract');