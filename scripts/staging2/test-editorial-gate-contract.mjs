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

export async function runEditorialGateContract(options = {}) {
  const host = options.expectedHost || process.env.EXPECTED_HOST || 'staging2.nuvanx.com';
  const sha = String(options.expectedSha || process.env.EXPECTED_SHA || '').trim();
  const alias = options.originSshAlias || process.env.ORIGIN_SSH_ALIAS || 'nvx-staging2';
  const outputDir = path.resolve(options.outputDir || 'scripts/staging2/artifacts');
  
  assertConfig(host, sha, alias);
  await fs.mkdir(outputDir, { recursive: true });

  // Run editorial gate validation via SSH
  const remoteScript = [
    'set -Eeuo pipefail',
    'cd /home/customer/www/nuvanx-siteground/public_html',
    'wp eval "require \'tools/migrations/editorial-gate-validation.php\';" --allow-root',
  ].join('\n');

  const result = spawnSync(
    SSH_BIN,
    ['-o', 'BatchMode=yes', '-o', 'ConnectTimeout=10', '-o', 'ConnectionAttempts=1', '--', alias, remoteScript],
    { encoding: 'utf8', timeout: 120000, maxBuffer: 8 * 1024 * 1024 },
  );

  if (result.error || result.status !== 0) {
    const reason = result.error?.message || result.stderr?.trim() || `exit ${result.status}`;
    throw new Error(`Editorial gate validation failed: ${reason}`);
  }

  // Parse validation results
  const stdout = result.stdout || '';
  const validationMatch = stdout.match(/EDITORIAL_GATE_VALIDATION=(PASS|FAIL)/);
  const validationStatus = validationMatch ? validationMatch[1] : 'UNKNOWN';

  const report = {
    schema: 'editorial-gate-contract',
    checkedAt: new Date().toISOString(),
    host,
    sha,
    validation: validationStatus,
    issues: [],
  };

  if (validationStatus === 'FAIL') {
    const errorsMatch = stdout.match(/errors=(\d+)/);
    const errorCount = errorsMatch ? parseInt(errorsMatch[1], 10) : 0;
    report.issues.push(`Editorial gate failed with ${errorCount} error(s)`);
    
    await fs.writeFile(path.join(outputDir, 'editorial-gate-contract.json'), `${JSON.stringify(report, null, 2)}\n`, 'utf8');
    
    console.error('EDITORIAL_GATE_CONTRACT=FAIL');
    report.issues.forEach((issue) => console.error(`- ${issue}`));
    
    throw new Error(`Editorial gate contract failed with ${errorCount} editorial violation(s). Publication blocked.`);
  }

  await fs.writeFile(path.join(outputDir, 'editorial-gate-contract.json'), `${JSON.stringify(report, null, 2)}\n`, 'utf8');
  
  console.log(`EDITORIAL_GATE_CONTRACT=PASS sha=${sha}`);
  return report;
}