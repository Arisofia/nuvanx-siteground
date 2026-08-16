import fs from 'node:fs/promises';
import path from 'node:path';
import { spawnSync } from 'node:child_process';

const SSH_BIN = '/usr/bin/ssh';
const CANONICAL_PROD_ROOT = '/home/customer/www/nuvanx.com/public_html';
const ALLOWED_ALIASES = new Set(['nvx-prod', 'nvx-prod-audit', 'production-siteground']);

function assertConfig(sha, alias, prodRoot) {
  if (!/^[0-9a-f]{40}$/.test(sha)) throw new Error('EXPECTED_SHA must be a full lowercase SHA');
  if (!ALLOWED_ALIASES.has(alias)) throw new Error(`Unsupported SSH alias: ${alias}`);
  if (prodRoot !== CANONICAL_PROD_ROOT) throw new Error(`Refusing unexpected production root: ${prodRoot}`);
}

export async function verifyProductionIdentity(options = {}) {
  const expectedSha = String(options.expectedSha || process.env.EXPECTED_SHA || '').trim();
  const alias = options.originSshAlias || process.env.ORIGIN_SSH_ALIAS || 'nvx-prod';
  const prodRoot = String(options.prodRoot || process.env.PROD_ROOT || CANONICAL_PROD_ROOT).trim();
  const outputDir = path.resolve(options.outputDir || 'scripts/production/artifacts');

  assertConfig(expectedSha, alias, prodRoot);
  await fs.mkdir(outputDir, { recursive: true });

  // Fetch deploy identity from the canonical production WordPress root. The
  // function may already be loaded by the active theme, so require it only when
  // necessary to avoid redeclaration in WP-CLI.
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
  const extractMeta = (name) => {
    const tags = stdout.match(/<meta\b[^>]*>/gi) || [];
    for (const tag of tags) {
      const nameMatch = tag.match(/\bname\s*=\s*["']([^"']+)["']/i);
      if (!nameMatch || nameMatch[1].toLowerCase() !== name.toLowerCase()) continue;
      const contentMatch = tag.match(/\bcontent\s*=\s*["']([^"']*)["']/i);
      return contentMatch ? contentMatch[1].trim() : '';
    }
    return '';
  };

  const deployStamp = {
    DEPLOY_SHA: extractMeta('nvx-deploy-sha'),
    DEPLOY_RUN_ID: extractMeta('nvx-deploy-run-id'),
    DEPLOY_TIMESTAMP: extractMeta('nvx-deploy-timestamp'),
    RELEASE_ID: extractMeta('nvx-release-id'),
  };

  const issues = [];

  if (!deployStamp.DEPLOY_SHA) {
    issues.push('Production DEPLOY_SHA not found in deploy stamp');
  } else if (deployStamp.DEPLOY_SHA !== expectedSha) {
    issues.push(`Production DEPLOY_SHA mismatch: expected ${expectedSha}, found ${deployStamp.DEPLOY_SHA}`);
  }

  if (!/^\d+$/.test(deployStamp.DEPLOY_RUN_ID)) {
    issues.push(`Production DEPLOY_RUN_ID missing or invalid: ${deployStamp.DEPLOY_RUN_ID || '(missing)'}`);
  }

  if (!deployStamp.DEPLOY_TIMESTAMP || Number.isNaN(Date.parse(deployStamp.DEPLOY_TIMESTAMP))) {
    issues.push(`Production DEPLOY_TIMESTAMP missing or invalid: ${deployStamp.DEPLOY_TIMESTAMP || '(missing)'}`);
  }

  if (!deployStamp.RELEASE_ID) {
    issues.push('Production RELEASE_ID not found in deploy stamp');
  }

  const report = {
    schema: 'production-identity-verification',
    checkedAt: new Date().toISOString(),
    expectedSha,
    productionRoot: prodRoot,
    productionDeployStamp: deployStamp,
    validation: issues.length === 0 ? 'PASS' : 'FAIL',
    issues,
  };

  await fs.writeFile(path.join(outputDir, 'production-identity-verification.json'), `${JSON.stringify(report, null, 2)}\n`, 'utf8');

  if (issues.length > 0) {
    console.error('PRODUCTION_IDENTITY_VERIFICATION=FAIL');
    issues.forEach((issue) => console.error(`- ${issue}`));
    throw new Error(`Production identity verification failed with ${issues.length} issue(s). Chain of trust is incomplete.`);
  }

  console.log(`PRODUCTION_IDENTITY_VERIFICATION=PASS sha=${deployStamp.DEPLOY_SHA} run_id=${deployStamp.DEPLOY_RUN_ID} release_id=${deployStamp.RELEASE_ID}`);
  return report;
}