#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const workflowPath = path.join(root, '.github/workflows/deploy-staging2.yml');
const deployPath = path.join(root, 'tools/deploy/deploy-to-staging2.sh');
const diagnosticsPath = path.join(root, 'scripts/staging2/collect-staging2-diagnostics.sh');
const migrationPath = path.join(root, 'scripts/wp/nvx-production-readiness-command.php');
const smokePath = path.join(root, 'scripts/staging2/smoke-verify-staging2.sh');
const failures = [];

function fail(message) {
  failures.push(message);
}

function read(file) {
  if (!fs.existsSync(file)) {
    fail(`missing ${path.relative(root, file)}`);
    return '';
  }
  return fs.readFileSync(file, 'utf8');
}

const workflow = read(workflowPath);
const deploy = read(deployPath);
const diagnostics = read(diagnosticsPath);
const migration = read(migrationPath);
const smoke = read(smokePath);

for (const marker of [
  'workflow_dispatch:',
  "inputs.confirmation == 'DEPLOY_STAGING2'",
  'PREFLIGHT_ONLY',
  'DEPLOY_AND_MIGRATE',
  'SMOKE_ONLY',
  'environment:',
  'name: staging2',
  'persist-credentials: false',
  'StrictHostKeyChecking yes',
  'STAGING2_SSH_KNOWN_HOSTS',
  'git_sha must equal the selected workflow ref HEAD',
  'ref: ${{ github.sha }}',
  'scripts/staging2/collect-staging2-diagnostics.sh',
  '< scripts/staging2/collect-staging2-diagnostics.sh',
  'scripts/wp/nvx-production-readiness-command.php',
  'scripts/staging2/smoke-verify-staging2.sh',
  'staging2-deployment-evidence',
  'actions/upload-artifact@',
  'ssh-debug.log',
  'preflight.log',
  'remote-deploy.log',
  'independent-smoke.log',
  '--migration-script',
  '--smoke-script',
]) {
  if (!workflow.includes(marker)) fail(`workflow missing contract marker: ${marker}`);
}

for (const forbidden of [
  'ssh-keyscan',
  'StrictHostKeyChecking no',
  'persist-credentials: true',
  'NUVANX_CONFIRM=yes bash tools/deploy/deploy-to-prod.sh',
  '/home/customer/www/nuvanx.com/public_html',
  "github.ref == 'refs/heads/master'",
  'git merge-base --is-ancestor "$DEPLOY_SHA" origin/master',
  'bash scripts/staging2/collect-staging2-diagnostics.sh --wp-root "$WP_ROOT" \\\n            | ssh',
]) {
  if (workflow.includes(forbidden)) fail(`workflow contains forbidden marker: ${forbidden}`);
}

for (const marker of [
  "EXPECTED_ROOT='/home/customer/www/staging2.nuvanx.com/public_html'",
  "EXPECTED_URL='https://staging2.nuvanx.com'",
  "BACKUP_ROOT='/home/customer/backups-nuvanx/staging2'",
  '--migration-script',
  '--smoke-script',
  'wp db export',
  'wp db import',
  'nvx production-readiness audit --allow-pending',
  'nvx production-readiness apply --confirm=retire-prototypes',
  'nvx production-readiness audit',
  'SMOKE_VERIFY_OK',
  'ROLLBACK_COMPLETE',
  'DEPLOY_STAGING2_OK',
]) {
  if (!deploy.includes(marker)) fail(`deploy script missing contract marker: ${marker}`);
}

for (const forbidden of [
  '/home/customer/www/nuvanx.com/public_html',
  'ssh-keyscan',
  'BACKUP_DIR="$WP_ROOT/wp-content/',
]) {
  if (deploy.includes(forbidden)) fail(`deploy script contains forbidden marker: ${forbidden}`);
}

for (const marker of [
  "EXPECTED_ROOT='/home/customer/www/staging2.nuvanx.com/public_html'",
  "EXPECTED_URL='https://staging2.nuvanx.com'",
  "BACKUP_ROOT='/home/customer/backups-nuvanx/staging2'",
  'command.wp=available',
  'wordpress.siteurl=',
  'wordpress.home=',
  'wordpress.active_theme=',
  'wordpress.empty_trash_days=',
  'wordpress.db_check=',
  'path.backup_root_writable=yes',
  'path.deployment_root_writable=yes',
  'http.home_status=',
  'STAGING2_PREFLIGHT_OK',
]) {
  if (!diagnostics.includes(marker)) fail(`diagnostics missing contract marker: ${marker}`);
}
if (/\brm\s+-rf\b/.test(diagnostics)) fail('diagnostics must remain read-only');
if (/\bwp\s+(post|db import|option update|rewrite flush)\b/.test(diagnostics)) fail('diagnostics contains a mutating WP-CLI command');

for (const marker of [
  'retire-prototypes',
  '--allow-pending',
  '--allow-production',
  'LOCK_OPTION',
  'staging2.nuvanx.com',
  'nvx_production_readiness_governed_pages',
  'validate_invocation',
  'apply_approved_pages',
  'apply_governed_pages',
  'EMPTY_TRASH_DAYS',
  'wp_trash_post',
  "WP_CLI::add_command( 'nvx production-readiness'",
]) {
  if (!migration.includes(marker)) fail(`migration missing contract marker: ${marker}`);
}
if (/['"]post_status['"]\s*=>\s*['"]trash['"]/.test(migration)) fail('migration uses direct trash status update');

for (const marker of [
  "fetch_page '/tratamientos/'",
  "fetch_page '/protocolos-signature/'",
  "fetch_page '/remodelacion-corporal-laser-madrid/'",
  "fetch_page '/por-que-nuvanx/'",
  "fetch_page '/inversion-medicina-estetica/'",
  "check_redirect '/liposculpt-air/'",
  "check_redirect '/v-lift-awake/'",
  "check_redirect '/tratamiento-postparto-abdomen-contorno-corporal-madrid/'",
  'SMOKE_VERIFY_OK',
]) {
  if (!smoke.includes(marker)) fail(`smoke script missing contract marker: ${marker}`);
}

if (failures.length) {
  console.error(`FAIL: ${failures.length} staging2 deployment contract finding(s)`);
  for (const finding of failures) console.error(`- ${finding}`);
  process.exit(1);
}

console.log('DEPLOY_WORKFLOW_CONTRACT_OK');
