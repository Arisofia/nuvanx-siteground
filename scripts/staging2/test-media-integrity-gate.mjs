import fs from 'node:fs/promises';
import path from 'node:path';
import { spawnSync } from 'node:child_process';

const SSH_BIN = '/usr/bin/ssh';
const ALLOWED_ALIASES = new Set(['nvx-staging2', 'nvx-staging2-pr']);

function assertConfig(host, sha, alias) {
  if (!/^[a-z0-9.-]+$/.test(host)) throw new Error('EXPECTED_HOST contains unsupported characters');
  if (!/^[0-9a-f]{40}$/.test(sha)) throw new Error('EXPECTED_SHA must be a full lowercase SHA');
  if (!ALLOWED_ALIASES.has(alias)) throw new Error(`Unsupported ORIGIN_SSH_ALIAS: ${alias}`);
}

export async function runMediaIntegrityGate(options = {}) {
  const host = options.expectedHost || process.env.EXPECTED_HOST || 'staging2.nuvanx.com';
  const sha = String(options.expectedSha || process.env.EXPECTED_SHA || '').trim();
  const alias = options.originSshAlias || process.env.ORIGIN_SSH_ALIAS || 'nvx-staging2';
  const outputDir = path.resolve(options.outputDir || 'scripts/staging2/artifacts');
  
  assertConfig(host, sha, alias);
  await fs.mkdir(outputDir, { recursive: true });

  // Run asset cleanup via SSH to get media integrity data
  const remoteScript = [
    'set -Eeuo pipefail',
    'cd /home/customer/www/nuvanx-siteground/public_html',
    'wp eval "require \'tools/migrations/asset-cleanup.php\';" --allow-root',
  ].join('\n');

  const result = spawnSync(
    SSH_BIN,
    ['-o', 'BatchMode=yes', '-o', 'ConnectTimeout=10', '-o', 'ConnectionAttempts=1', '--', alias, remoteScript],
    { encoding: 'utf8', timeout: 120000, maxBuffer: 8 * 1024 * 1024 },
  );

  if (result.error || result.status !== 0) {
    const reason = result.error?.message || result.stderr?.trim() || `exit ${result.status}`;
    throw new Error(`Media integrity gate failed: ${reason}`);
  }

  // Parse asset cleanup report
  const stdout = result.stdout || '';
  const report = JSON.parse(stdout);

  // Validate media integrity
  const issues = [];
  
  if (!report.integrity || Object.keys(report.integrity).length === 0) {
    issues.push('No media integrity hashes generated');
  }

  // Check for orphans that are publicly consumed
  const publiclyConsumedOrphans = report.consumption?.filter(c => c.publicly_consumed) || [];
  if (publiclyConsumedOrphans.length > 0) {
    issues.push(`${publiclyConsumedOrphans.length} orphaned assets are still publicly consumed`);
  }

  const validationReport = {
    schema: 'media-integrity-gate',
    checkedAt: new Date().toISOString(),
    host,
    sha,
    validation: issues.length === 0 ? 'PASS' : 'FAIL',
    issues,
    inventory: report.inventory,
    orphans: report.orphans,
    integrity: report.integrity,
  };

  await fs.writeFile(path.join(outputDir, 'media-integrity-gate.json'), `${JSON.stringify(validationReport, null, 2)}\n`, 'utf8');

  if (issues.length > 0) {
    console.error('MEDIA_INTEGRITY_GATE=FAIL');
    issues.forEach((issue) => console.error(`- ${issue}`));
    throw new Error(`Media integrity gate failed with ${issues.length} issue(s).`);
  }

  console.log(`MEDIA_INTEGRITY_GATE=PASS sha=${sha} assets=${Object.keys(report.integrity).length}`);
  return validationReport;
}