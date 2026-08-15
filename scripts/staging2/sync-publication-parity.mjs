import fs from 'node:fs/promises';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';

const SSH_BIN = '/usr/bin/ssh';
const ALLOWED_ALIASES = new Set(['nvx-staging2', 'nvx-staging2-pr']);
const STAGING_ROOT = '/home/customer/www/staging2.nuvanx.com/public_html';
const PROD_ROOT = '/home/customer/www/nuvanx.com/public_html';

function resolveRelease() {
  const release = String(process.env.REMOTE_RELEASE || '').trim();
  const prefix = `${STAGING_ROOT}/wp-content/.nuvanx-deployments/`;
  if (!release.startsWith(prefix) || !/^[A-Za-z0-9_./-]+$/.test(release)) {
    throw new Error('REMOTE_RELEASE is missing or outside the canonical Staging2 deployment area');
  }
  return release;
}

function getExpectedManifestInfo() {
  const manifestPath = path.resolve('wp-content/themes/nuvanx-medical/inc/data/publication-manifest.json');
  try {
    const manifest = JSON.parse(readFileSync(manifestPath, 'utf8'));
    if (manifest.routes && typeof manifest.routes === 'object') {
      return {
        routeCount: Object.keys(manifest.routes).length,
        version: manifest.version || null,
      };
    }
  } catch (error) {
    throw new Error(`Failed to read publication manifest at ${manifestPath}: ${error.message}`);
  }
  throw new Error('Publication manifest missing or invalid routes object');
}

export async function runStagingPublicationParitySync(options = {}) {
  const alias = options.originSshAlias || process.env.ORIGIN_SSH_ALIAS || 'nvx-staging2';
  const outputDir = path.resolve(options.outputDir || 'scripts/staging2/artifacts');
  if (!ALLOWED_ALIASES.has(alias)) throw new Error(`Unsupported ORIGIN_SSH_ALIAS: ${alias}`);

  const expectedManifest = getExpectedManifestInfo();

  const release = resolveRelease();
  const manifest = `${release}/theme/inc/data/publication-manifest.json`;
  const exporter = `${release}/tools/migrations/export-production-publication-snapshot.php`;
  const collisionPrep = `${release}/tools/migrations/prepare-staging-publication-collisions.php`;
  const synchronizer = `${release}/tools/migrations/sync-staging-publication-parity.php`;

  const remoteScript = [
    'set -Eeuo pipefail',
    'snapshot="$(mktemp)"',
    'cleanup(){ rm -f "$snapshot"; }',
    'trap cleanup EXIT',
    `cd '${PROD_ROOT}'`,
    `PUBLICATION_MANIFEST_FILE='${manifest}' wp eval-file '${exporter}' --allow-root > "$snapshot"`,
    'test -s "$snapshot"',
    `cd '${STAGING_ROOT}'`,
    `PUBLICATION_SNAPSHOT_FILE="$snapshot" wp eval-file '${collisionPrep}' --allow-root >&2`,
    `PUBLICATION_SNAPSHOT_FILE="$snapshot" wp eval-file '${synchronizer}' --allow-root`,
  ].join('\n');

  const result = spawnSync(
    SSH_BIN,
    ['-o', 'BatchMode=yes', '-o', 'ConnectTimeout=10', '-o', 'ConnectionAttempts=1', '--', alias, remoteScript],
    { encoding: 'utf8', timeout: 180000, maxBuffer: 32 * 1024 * 1024 },
  );

  if (result.error) throw new Error(`Publication parity transport failed: ${result.error.message}`);
  if (result.status !== 0) {
    const reason = String(result.stderr || `exit ${result.status}`).trim().replace(/\s+/g, ' ').slice(0, 2000);
    throw new Error(`Publication parity synchronization failed: ${reason}`);
  }

  let report;
  try {
    report = JSON.parse(String(result.stdout || '').trim());
  } catch (error) {
    throw new Error(`Publication parity synchronizer returned invalid JSON: ${error.message}`);
  }

  if (report.schema !== 'nuvanx-staging-publication-parity') {
    throw new Error(`Unexpected publication parity report schema: ${report.schema}`);
  }

  if (report.route_count !== expectedManifest.routeCount) {
    throw new Error(`Publication parity route count mismatch: reported=${report.route_count} expected=${expectedManifest.routeCount}`);
  }

  if (expectedManifest.version && report.manifest_version !== expectedManifest.version) {
    throw new Error(`Publication parity manifest version mismatch: reported=${report.manifest_version} expected=${expectedManifest.version}`);
  }

  await fs.mkdir(outputDir, { recursive: true });
  await fs.writeFile(
    path.join(outputDir, 'staging-publication-parity.json'),
    `${JSON.stringify(report, null, 2)}\n`,
    'utf8',
  );

  console.log(
    `STAGING_PUBLICATION_PARITY_SYNC=PASS routes=${report.route_count} created=${report.created} updated=${report.updated} drafted=${report.drafted_surplus}`,
  );
  return report;
}
