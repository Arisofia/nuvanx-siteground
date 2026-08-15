import fs from 'node:fs/promises';
import path from 'node:path';
import { spawnSync } from 'node:child_process';

const SSH_BIN = '/usr/bin/ssh';
const ALLOWED_ALIASES = new Set(['production-siteground']);

function assertConfig(sha, alias) {
  if (!/^[0-9a-f]{40}$/.test(sha)) throw new Error('EXPECTED_SHA must be a full lowercase SHA');
  if (!ALLOWED_ALIASES.has(alias)) throw new Error(`Unsupported SSH alias: ${alias}`);
}

export async function verifyProductionIdentity(options = {}) {
  const expectedSha = String(options.expectedSha || process.env.EXPECTED_SHA || '').trim();
  const alias = options.originSshAlias || process.env.ORIGIN_SSH_ALIAS || 'production-siteground';
  const outputDir = path.resolve(options.outputDir || 'scripts/production/artifacts');
  
  assertConfig(expectedSha, alias);
  await fs.mkdir(outputDir, { recursive: true });

  // Fetch deploy stamp from production
  const remoteScript = [
    'set -Eeuo pipefail',
    'cd /home/customer/www/nuvanx-siteground/public_html',
    'wp eval "require \'wp-content/themes/nuvanx-medical/inc/nvx-deploy-stamp.php\';" --allow-root',
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

  // Extract deploy stamp from HTML output
  const stdout = result.stdout || '';
  const deployShaMatch = stdout.match(/<meta\s+name=["']nvx-deploy-sha["']\s+content=["']([^"']+)["']/i);
  const deployRunIdMatch = stdout.match(/<meta\s+name=["']nvx-deploy-run-id["']\s+content=["']([^"']+)["']/i);
  const deployTimestampMatch = stdout.match(/<meta\s+name=["']nvx-deploy-timestamp["']\s+content=["']([^"']+)["']/i);
  const releaseIdMatch = stdout.match(/<meta\s+name=["']nvx-release-id["']\s+content=["']([^"']+)["']/i);

  const deployStamp = {
    DEPLOY_SHA: deployShaMatch ? deployShaMatch[1] : '',
    DEPLOY_RUN_ID: deployRunIdMatch ? deployRunIdMatch[1] : '',
    DEPLOY_TIMESTAMP: deployTimestampMatch ? deployTimestampMatch[1] : '',
    RELEASE_ID: releaseIdMatch ? releaseIdMatch[1] : '',
  };

  // Verify chain of trust: master/release SHA = accepted staging SHA = production deployed SHA
  const issues = [];

  if (!deployStamp.DEPLOY_SHA) {
    issues.push('Production DEPLOY_SHA not found in deploy stamp');
  } else if (deployStamp.DEPLOY_SHA !== expectedSha) {
    issues.push(`Production DEPLOY_SHA mismatch: expected ${expectedSha}, found ${deployStamp.DEPLOY_SHA}`);
  }

  if (!deployStamp.DEPLOY_RUN_ID) {
    issues.push('Production DEPLOY_RUN_ID not found in deploy stamp');
  }

  if (!deployStamp.DEPLOY_TIMESTAMP) {
    issues.push('Production DEPLOY_TIMESTAMP not found in deploy stamp');
  }

  if (!deployStamp.RELEASE_ID) {
    issues.push('Production RELEASE_ID not found in deploy stamp');
  }

  const report = {
    schema: 'production-identity-verification',
    checkedAt: new Date().toISOString(),
    expectedSha,
    productionDeployStamp: deployStamp,
    validation: issues.length === 0 ? 'PASS' : 'FAIL',
    issues,
  };

  await fs.writeFile(path.join(outputDir, 'production-identity-verification.json'), `${JSON.stringify(report, null, 2)}\n`, 'utf8');

  if (issues.length > 0) {
    console.error('PRODUCTION_IDENTITY_VERIFICATION=FAIL');
    issues.forEach((issue) => console.error(`- ${issue}`));
    throw new Error(`Production identity verification failed with ${issues.length} issue(s). Chain of trust broken: master/release SHA ≠ accepted staging SHA ≠ production deployed SHA`);
  }

  console.log(`PRODUCTION_IDENTITY_VERIFICATION=PASS sha=${deployStamp.DEPLOY_SHA} run_id=${deployStamp.DEPLOY_RUN_ID} release_id=${deployStamp.RELEASE_ID}`);
  return report;
}