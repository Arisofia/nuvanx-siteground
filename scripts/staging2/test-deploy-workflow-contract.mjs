#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const failures = [];

function requireMatch(source, pattern, message) {
  if (!pattern.test(source)) failures.push(message);
}

function requireAbsent(source, pattern, message) {
  if (pattern.test(source)) failures.push(message);
}

const workflowPath = '.github/workflows/deploy-staging2.yml';
const deployScriptPath = 'tools/deploy/deploy-to-staging2.sh';
const docsPath = 'docs/operations/deployment.md';

for (const requiredPath of [workflowPath, deployScriptPath, docsPath]) {
  if (!fs.existsSync(path.join(root, requiredPath))) {
    failures.push(`missing required deployment contract file: ${requiredPath}`);
  }
}

if (failures.length) {
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

const workflow = read(workflowPath);
const deployScript = read(deployScriptPath);
const docs = read(docsPath);

// The workflow may validate itself on pull requests, but deployment must never
// happen from push, pull_request, schedule or an unprotected feature branch.
requireMatch(workflow, /^\s{2}workflow_dispatch:/m, 'workflow must expose workflow_dispatch');
requireMatch(workflow, /^\s{2}pull_request:/m, 'workflow contract must run on pull requests');
requireAbsent(workflow, /^\s{2}push:/m, 'workflow must not deploy or trigger from push');
requireAbsent(workflow, /^\s{2}schedule:/m, 'workflow must not deploy from a schedule');
requireMatch(
  workflow,
  /github\.event_name == 'workflow_dispatch'[\s\S]*github\.ref == 'refs\/heads\/master'[\s\S]*inputs\.confirmation == 'DEPLOY_STAGING2'/,
  'deploy job must require manual dispatch from master and explicit DEPLOY_STAGING2 confirmation',
);
requireMatch(workflow, /environment:\s*\n\s+name: staging2/m, 'deploy job must use the protected staging2 environment');
requireMatch(workflow, /cancel-in-progress:\s*false/, 'concurrent staging deploys must not cancel a running mutation');
requireMatch(workflow, /git_sha:[\s\S]*Full 40-character SHA/, 'workflow must require a full immutable SHA input');
requireMatch(workflow, /\^\[0-9a-f\]\{40\}\$/, 'workflow must validate the SHA shape');
requireMatch(
  workflow,
  /git merge-base --is-ancestor "\$DEPLOY_SHA" origin\/master/,
  'workflow must reject commits that are not contained in master',
);
requireMatch(workflow, /git checkout --detach "\$DEPLOY_SHA"/, 'workflow must check out the exact immutable SHA');

for (const secret of [
  'STAGING2_SSH_HOST',
  'STAGING2_SSH_PORT',
  'STAGING2_SSH_USER',
  'STAGING2_SSH_PRIVATE_KEY',
  'STAGING2_SSH_KNOWN_HOSTS',
]) {
  requireMatch(workflow, new RegExp(`secrets\\.${secret}`), `workflow must use ${secret}`);
}

requireMatch(workflow, /StrictHostKeyChecking yes/, 'SSH host verification must be strict');
requireMatch(workflow, /BatchMode yes/, 'SSH must not fall back to interactive password prompts');
requireMatch(workflow, /UserKnownHostsFile/, 'SSH must use the protected known_hosts file');
requireAbsent(workflow, /StrictHostKeyChecking[= ]no/i, 'workflow must never disable SSH host verification');
requireAbsent(workflow, /ssh-keyscan/i, 'workflow must not trust a runtime ssh-keyscan result');
requireAbsent(workflow, /password/i, 'workflow must not contain password-based deployment logic');
requireMatch(
  workflow,
  /uses: actions\/checkout@[0-9a-f]{40}/,
  'checkout action must be pinned to a commit SHA',
);
requireMatch(
  workflow,
  /uses: actions\/setup-node@[0-9a-f]{40}/,
  'setup-node action must be pinned to a commit SHA',
);

const stagingRoot = '/home/customer/www/staging2.nuvanx.com/public_html';
const productionRoot = '/home/customer/www/nuvanx.com/public_html';
requireMatch(workflow, new RegExp(stagingRoot.replaceAll('/', '\\/')), 'workflow must use the exact staging2 root');
requireAbsent(workflow, new RegExp(productionRoot.replaceAll('/', '\\/')), 'workflow must not contain the production root');
requireMatch(workflow, /wp-content\/themes\/nuvanx-medical\//, 'workflow must upload only the NUVANX theme payload');
requireMatch(workflow, /rsync -az --delete/, 'release upload must delete stale files in its isolated payload');
requireMatch(workflow, /deploy-to-staging2\.sh/, 'workflow must invoke the guarded remote deploy script');
requireMatch(workflow, /smoke-verify-staging2\.sh/, 'workflow must run the staging2 smoke verification');
requireMatch(workflow, /\.nvx-deploy-sha/, 'workflow must verify the deployed SHA marker');

// Remote mutation safety and rollback contract.
requireMatch(deployScript, /EXPECTED_ROOT='\/home\/customer\/www\/staging2\.nuvanx\.com\/public_html'/, 'script must pin the staging2 root');
requireMatch(deployScript, /EXPECTED_URL='https:\/\/staging2\.nuvanx\.com'/, 'script must pin the staging2 URL');
requireAbsent(deployScript, new RegExp(productionRoot.replaceAll('/', '\\/')), 'script must not contain the production root');
requireMatch(deployScript, /explicit confirmation is required/, 'script must require explicit confirmation');
requireMatch(deployScript, /source theme must be inside the staging2 deployment area/, 'script must restrict the release source path');
requireMatch(deployScript, /wp option get siteurl/, 'script must guard the staging2 siteurl');
requireMatch(deployScript, /wp option get home/, 'script must guard the staging2 home URL');
requireMatch(deployScript, /wp theme list --status=active --field=name/, 'script must guard the active theme');
requireMatch(deployScript, /tar -czf "\$BACKUP_DIR\/theme\.tgz"/, 'script must back up the live theme before mutation');
requireMatch(deployScript, /ROLLBACK: restoring the pre-deploy staging2 theme/, 'script must implement automatic rollback');
requireMatch(deployScript, /rsync -a --delete/, 'script must remove obsolete live theme files');
requireMatch(deployScript, /php -l/, 'script must lint PHP before replacing the live theme');
requireMatch(deployScript, /\.nvx-deploy-sha/, 'script must stamp the immutable source SHA');
requireMatch(deployScript, /wp cache flush/, 'script must flush WordPress cache');
requireMatch(deployScript, /wp sg purge/, 'script must purge SiteGround cache');
requireMatch(deployScript, /DEPLOY_STAGING2_OK/, 'script must emit an explicit success marker');

// Documentation must make the security and operational boundary explicit.
requireMatch(docs, /Deploy Staging2 \(manual\)/, 'deployment documentation must name the manual workflow');
requireMatch(docs, /workflow_dispatch/, 'deployment documentation must state the manual trigger');
requireMatch(docs, /STAGING2_SSH_PRIVATE_KEY/, 'deployment documentation must list required SSH secrets');
requireMatch(docs, /DEPLOY_STAGING2/, 'deployment documentation must describe explicit confirmation');
requireMatch(docs, /does not deploy to production/i, 'deployment documentation must state that the workflow cannot deploy production');

if (failures.length) {
  console.error('Staging2 deployment workflow contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: protected manual staging2 deployment contract');
