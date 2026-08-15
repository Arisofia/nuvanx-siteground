import fs from 'node:fs/promises';
import path from 'node:path';
import { spawnSync } from 'node:child_process';

const SSH_BIN = '/usr/bin/ssh';
const ALLOWED_ALIASES = new Set(['nvx-staging2', 'nvx-staging2-pr']);
const CANONICAL_STAGING_ROOT = '/home/customer/www/staging2.nuvanx.com/public_html';

function assertConfig(host, sha, alias) {
  if (!/^[a-z0-9.-]+$/.test(host)) throw new Error('EXPECTED_HOST contains unsupported characters');
  if (!/^[0-9a-f]{40}$/.test(sha)) throw new Error('EXPECTED_SHA must be a full lowercase SHA');
  if (!ALLOWED_ALIASES.has(alias)) throw new Error(`Unsupported ORIGIN_SSH_ALIAS: ${alias}`);
}

function resolveRemoteMigrationPath() {
  const release = String(process.env.REMOTE_RELEASE || '').trim();
  const prefix = `${CANONICAL_STAGING_ROOT}/wp-content/.nuvanx-deployments/`;
  if (!release.startsWith(prefix) || !/^[A-Za-z0-9_./-]+$/.test(release)) {
    throw new Error('REMOTE_RELEASE is missing or outside the canonical Staging2 deployment area');
  }
  return `${release}/tools/migrations/editorial-gate-validation.php`;
}

function parseReport(stdout) {
  const raw = String(stdout || '').trim();
  if (!raw) throw new Error('Editorial validator produced no JSON report');
  try {
    return JSON.parse(raw);
  } catch (error) {
    throw new Error(`Editorial validator returned invalid JSON: ${error.message}`);
  }
}

export async function runEditorialGateContract(options = {}) {
  const host = options.expectedHost || process.env.EXPECTED_HOST || 'staging2.nuvanx.com';
  const sha = String(options.expectedSha || process.env.EXPECTED_SHA || '').trim();
  const alias = options.originSshAlias || process.env.ORIGIN_SSH_ALIAS || 'nvx-staging2';
  const outputDir = path.resolve(options.outputDir || 'scripts/staging2/artifacts');

  assertConfig(host, sha, alias);
  await fs.mkdir(outputDir, { recursive: true });

  const migrationPath = resolveRemoteMigrationPath();
  const remoteScript = [
    'set -Eeuo pipefail',
    `cd '${CANONICAL_STAGING_ROOT}'`,
    `wp eval "require '${migrationPath}';" --allow-root`,
  ].join('\n');

  const result = spawnSync(
    SSH_BIN,
    ['-o', 'BatchMode=yes', '-o', 'ConnectTimeout=10', '-o', 'ConnectionAttempts=1', '--', alias, remoteScript],
    { encoding: 'utf8', timeout: 120000, maxBuffer: 16 * 1024 * 1024 },
  );

  if (result.error) throw new Error(`Editorial gate transport failed: ${result.error.message}`);

  const validation = parseReport(result.stdout);
  const violations = Array.isArray(validation.violations) ? validation.violations : [];
  const report = {
    schema: 'editorial-gate-contract',
    checkedAt: new Date().toISOString(),
    host,
    sha,
    validation: result.status === 0 && violations.length === 0 ? 'PASS' : 'FAIL',
    validatorExitCode: result.status,
    summary: validation.summary || {},
    violations,
  };

  await fs.writeFile(
    path.join(outputDir, 'editorial-gate-contract.json'),
    `${JSON.stringify(report, null, 2)}\n`,
    'utf8',
  );

  if (report.validation !== 'PASS') {
    console.error(`EDITORIAL_GATE_CONTRACT=FAIL posts=${violations.length}`);
    for (const item of violations.slice(0, 30)) {
      const rules = (item.errors || []).map((error) => error.rule).join(',');
      console.error(`- id=${item.post_id} slug=${item.slug} rules=${rules}`);
    }
    throw new Error(`Editorial gate contract failed with ${violations.length} published content violation(s)`);
  }

  console.log(`EDITORIAL_GATE_CONTRACT=PASS posts=${report.summary.total_checked ?? 'unknown'} sha=${sha}`);
  return report;
}
