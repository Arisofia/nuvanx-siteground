import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawnSync } from 'node:child_process';
import {
  extractMetaContent,
  validGitHubRunId,
  validateDeployIdentity,
} from './deploy-identity-contract.mjs';

const SSH_BIN = '/usr/bin/ssh';
const CANONICAL_PROD_ROOT = '/home/customer/www/nuvanx.com/public_html';
const ALLOWED_ALIASES = new Set(['nvx-prod', 'nvx-prod-audit', 'nvx-prod-hubspot', 'production-siteground']);

function assertConfig(sha, runId, alias, prodRoot) {
  if (!/^[0-9a-f]{40}$/.test(sha)) throw new Error('EXPECTED_SHA must be a full lowercase SHA');
  if (runId && !validGitHubRunId(runId)) throw new Error('EXPECTED_RUN_ID must be numeric when supplied');
  if (!ALLOWED_ALIASES.has(alias)) throw new Error(`Unsupported SSH alias: ${alias}`);
  if (prodRoot !== CANONICAL_PROD_ROOT) throw new Error(`Refusing unexpected production root: ${prodRoot}`);
}

export async function verifyProductionIdentity(options = {}) {
  const expectedSha = String(options.expectedSha || process.env.EXPECTED_SHA || '').trim();
  const expectedRunId = String(options.expectedRunId || process.env.EXPECTED_RUN_ID || '').trim();
  const alias = String(options.originSshAlias || process.env.ORIGIN_SSH_ALIAS || 'nvx-prod').trim().toLowerCase();
  const prodRoot = String(options.prodRoot || process.env.PROD_ROOT || CANONICAL_PROD_ROOT).trim();
  const outputDir = path.resolve(options.outputDir || 'scripts/production/artifacts');

  assertConfig(expectedSha, expectedRunId, alias, prodRoot);
  await fs.mkdir(outputDir, { recursive: true });

  const remoteScript = [
    'set -Eeuo pipefail',
    `cd '${prodRoot}'`,
    'wp eval "if ( ! function_exists( \'nvx_render_deploy_stamp_meta\' ) ) { require \'wp-content/themes/nuvanx-medical/inc/nvx-deploy-stamp.php\'; } nvx_render_deploy_stamp_meta();" --allow-root',
  ].join('\n');

  const result = spawnSync(
    SSH_BIN,
    ['-o', 'BatchMode=yes', '-o', 'ConnectTimeout=10', '-o', 'ConnectionAttempts=1', '--', alias, remoteScript],
    { encoding: 'utf8', timeout: 60000, maxBuffer: 8 * 1024 * 1024 },
  );

  if (result.error || result.status !== 0) {
    const reason = result.error?.message || result.stderr?.trim() || `exit ${result.status}`;
    throw new Error(`Production deploy stamp fetch failed: ${reason}`);
  }

  const stdout = result.stdout || '';
  const deployStamp = {
    DEPLOY_SHA: extractMetaContent(stdout, 'nvx-deploy-sha'),
    DEPLOY_RUN_ID: extractMetaContent(stdout, 'nvx-deploy-run-id'),
    DEPLOY_TIMESTAMP: extractMetaContent(stdout, 'nvx-deploy-timestamp'),
    RELEASE_ID: extractMetaContent(stdout, 'nvx-release-id'),
  };
  const issues = validateDeployIdentity(deployStamp, { expectedSha, expectedRunId });

  const report = {
    schema: 'production-identity-verification',
    checkedAt: new Date().toISOString(),
    expectedSha,
    expectedRunId: expectedRunId || null,
    originSshAlias: alias,
    productionRoot: prodRoot,
    productionDeployStamp: deployStamp,
    validation: issues.length === 0 ? 'PASS' : 'FAIL',
    issues,
  };

  await fs.writeFile(
    path.join(outputDir, 'production-identity-verification.json'),
    `${JSON.stringify(report, null, 2)}\n`,
    'utf8',
  );

  if (issues.length > 0) {
    console.error('PRODUCTION_IDENTITY_VERIFICATION=FAIL');
    issues.forEach((issue) => console.error(`- ${issue}`));
    throw new Error(`Production identity verification failed with ${issues.length} issue(s). Chain of trust is incomplete.`);
  }

  console.log(
    `PRODUCTION_IDENTITY_VERIFICATION=PASS sha=${deployStamp.DEPLOY_SHA} run_id=${deployStamp.DEPLOY_RUN_ID} timestamp=${deployStamp.DEPLOY_TIMESTAMP} release_id=${deployStamp.RELEASE_ID}`,
  );
  return report;
}

if (process.argv[1] && fileURLToPath(import.meta.url) === path.resolve(process.argv[1])) {
  verifyProductionIdentity().catch((err) => {
    console.error(err);
    process.exit(1);
  });
}
