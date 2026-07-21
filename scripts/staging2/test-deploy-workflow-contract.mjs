#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const workflowPath = path.join(root, '.github/workflows/deploy-staging2.yml');
const deployPath = path.join(root, 'tools/deploy/deploy-to-staging2.sh');
const smokePath = path.join(root, 'scripts/staging2/smoke-verify-staging2.sh');
const migrationPath = path.join(root, 'scripts/wp/nvx-production-readiness-command.php');
const errors = [];

function fail(message) {
  errors.push(message);
}

function readRequired(filePath) {
  if (!fs.existsSync(filePath)) {
    fail(`missing file: ${path.relative(root, filePath)}`);
    return '';
  }
  return fs.readFileSync(filePath, 'utf8');
}

const workflow = readRequired(workflowPath);
const deploy = readRequired(deployPath);
const smoke = readRequired(smokePath);
const migration = readRequired(migrationPath);

for (const marker of [
  'workflow_dispatch:',
  "github.ref == 'refs/heads/master'",
  "inputs.confirmation == 'DEPLOY_STAGING2'",
  'git merge-base --is-ancestor "$DEPLOY_SHA" origin/master',
  'persist-credentials: false',
  'StrictHostKeyChecking yes',
  'STAGING2_SSH_KNOWN_HOSTS',
  'scripts/staging2/smoke-verify-staging2.sh',
  'scripts/wp/nvx-production-readiness-command.php',
  'nvx production-readiness audit --allow-pending',
  'nvx production-readiness apply --confirm=retire-prototypes',
  'nvx production-readiness audit',
  'wp db export',
]) {
  if (!workflow.includes(marker)) fail(`deployment workflow missing contract: ${marker}`);
}

for (const forbidden of [
  'ssh-keyscan',
  '/home/customer/www/nuvanx.com/public_html',
  'StrictHostKeyChecking no',
  'persist-credentials: true',
]) {
  if (workflow.includes(forbidden)) fail(`deployment workflow contains forbidden marker: ${forbidden}`);
}

for (const marker of [
  "EXPECTED_ROOT='/home/customer/www/staging2.nuvanx.com/public_html'",
  "EXPECTED_URL='https://staging2.nuvanx.com'",
  "[[ \"$theme\" == 'nuvanx-medical' ]]",
  'rsync -a --delete',
  'pre-staging2-',
  '.nvx-deploy-sha',
  'DEPLOY_STAGING2_OK',
]) {
  if (!deploy.includes(marker)) fail(`remote deployment script missing contract: ${marker}`);
}

for (const forbidden of [
  '/home/customer/www/nuvanx.com/public_html',
  'ssh-keyscan',
]) {
  if (deploy.includes(forbidden)) fail(`remote deployment script contains forbidden marker: ${forbidden}`);
}

for (const marker of [
  "fetch_page '/tratamientos/' 'Portafolio Clínico'",
  "fetch_page '/protocolos-signature/' 'Protocolos Signature'",
  "fetch_page '/remodelacion-corporal-laser-madrid/' 'Couture Sculpt'",
  "check_redirect '/liposculpt-air/' '/remodelacion-corporal-laser-madrid/'",
  "check_redirect '/v-lift-awake/' '/papada-definicion-mandibular-madrid/'",
  "check_redirect '/tratamiento-postparto-abdomen-contorno-corporal-madrid/' '/protocolos-signature/'",
  'SMOKE_VERIFY_OK',
]) {
  if (!smoke.includes(marker)) fail(`staging2 smoke script missing contract: ${marker}`);
}

for (const marker of [
  '--allow-pending',
  '--allow-production',
  'retire-prototypes',
  'Another production-readiness migration is already running.',
  "WP_CLI::add_command( 'nvx production-readiness'",
]) {
  if (!migration.includes(marker)) fail(`migration command missing contract: ${marker}`);
}

if (errors.length) {
  console.error(`FAIL: ${errors.length} staging2 deployment contract finding(s)`);
  for (const error of errors) console.error(`- ${error}`);
  process.exit(1);
}

console.log('PASS: staging2 deployment workflow, migration and smoke contracts');
