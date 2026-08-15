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
  return `${release}/tools/migrations/generate-publication-manifest.php`;
}

function manifestHeaderErrors(manifest) {
  const errors = [];
  if (manifest.schema !== 'nuvanx-publication-manifest') errors.push('Invalid manifest schema identifier');
  if (!/^\d+\.\d+\.\d+$/.test(String(manifest.version || ''))) errors.push('Invalid manifest version format');
  if (!manifest.generated_at || Number.isNaN(Date.parse(manifest.generated_at))) errors.push('Invalid generated_at timestamp');
  if (!String(manifest.source || '').startsWith('https://')) errors.push('Invalid source URL');
  if (!manifest.routes || typeof manifest.routes !== 'object' || Array.isArray(manifest.routes)) errors.push('Invalid routes structure');
  return errors;
}

function routeConfigErrors(route, config) {
  const errors = [];
  if (!route.startsWith('/')) errors.push(`${route}: route must start with /`);
  if (!Number.isInteger(config.post_id) || config.post_id < 1) errors.push(`${route}: invalid post_id`);
  if (!['page', 'post'].includes(config.post_type)) errors.push(`${route}: invalid post_type`);
  if (typeof config.slug !== 'string') errors.push(`${route}: invalid slug`);
  if (config.status !== 'publish') errors.push(`${route}: expected manifest status must be publish`);
  if (!config.robots || typeof config.robots.index !== 'boolean' || typeof config.robots.follow !== 'boolean') {
    errors.push(`${route}: invalid robots configuration`);
  }
  return errors;
}

function validateSchema(manifest) {
  const errors = manifestHeaderErrors(manifest);
  if (errors.length) throw new Error(errors.join('\n'));

  for (const [route, config] of Object.entries(manifest.routes)) {
    errors.push(...routeConfigErrors(route, config));
  }
  if (errors.length) throw new Error(`Route validation failed:\n${errors.join('\n')}`);
}

function parseManifest(stdout) {
  const trimmed = String(stdout || '').trim();
  if (!trimmed) throw new Error('Publication validator produced no JSON report');
  try {
    return JSON.parse(trimmed);
  } catch (error) {
    throw new Error(`Publication validator returned invalid JSON: ${error.message}`);
  }
}

function buildContractReport(manifest, result, host, sha) {
  const validation = manifest.validation || {};
  const issues = Array.isArray(validation.errors) ? validation.errors : [];
  return {
    schema: 'publication-manifest-contract',
    checkedAt: new Date().toISOString(),
    host,
    sha,
    manifestVersion: manifest.version,
    manifestSource: manifest.source,
    routeCount: Object.keys(manifest.routes).length,
    validation: validation.pass === true && result.status === 0 ? 'PASS' : 'FAIL',
    validatorExitCode: result.status,
    issues,
    missing: Array.isArray(validation.missing) ? validation.missing : [],
    surplus: Array.isArray(validation.surplus) ? validation.surplus : [],
    changed: Array.isArray(validation.changed) ? validation.changed : [],
  };
}

function printContractFailure(report, validation) {
  console.error(
    `PUBLICATION_MANIFEST_CONTRACT=FAIL expected=${validation.expected_count ?? report.routeCount} actual=${validation.actual_count ?? 'unknown'} missing=${report.missing.length} surplus=${report.surplus.length} changed=${report.changed.length}`,
  );
  for (const issue of report.issues.slice(0, 50)) console.error(`- ${issue}`);
}

export async function runPublicationManifestContract(options = {}) {
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
    `wp eval-file '${migrationPath}' --allow-root`,
  ].join('\n');

  const result = spawnSync(
    SSH_BIN,
    ['-o', 'BatchMode=yes', '-o', 'ConnectTimeout=10', '-o', 'ConnectionAttempts=1', '--', alias, remoteScript],
    { encoding: 'utf8', timeout: 90000, maxBuffer: 16 * 1024 * 1024 },
  );
  if (result.error) throw new Error(`Publication validator transport failed: ${result.error.message}`);

  const manifest = parseManifest(result.stdout);
  validateSchema(manifest);
  await fs.writeFile(
    path.join(outputDir, 'publication-manifest-runtime.json'),
    `${JSON.stringify(manifest, null, 2)}\n`,
    'utf8',
  );

  const report = buildContractReport(manifest, result, host, sha);
  await fs.writeFile(
    path.join(outputDir, 'publication-manifest-contract.json'),
    `${JSON.stringify(report, null, 2)}\n`,
    'utf8',
  );

  if (report.validation !== 'PASS') {
    printContractFailure(report, manifest.validation || {});
    throw new Error('Publication manifest contract failed: EXPECTED_PUBLIC_URLS !== ACTUAL_PUBLIC_URLS');
  }

  console.log(`PUBLICATION_MANIFEST_CONTRACT=PASS routes=${report.routeCount} version=${manifest.version}`);
  return report;
}
