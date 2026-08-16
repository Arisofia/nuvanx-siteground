import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
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
  // Prevent path traversal attacks by checking for .. segments after the prefix
  const relativePath = release.slice(prefix.length);
  if (relativePath.includes('..')) {
    throw new Error('REMOTE_RELEASE contains path traversal sequences');
  }
  return release;
}

async function getExpectedManifestInfo() {
  // Use fileURLToPath to anchor the manifest path relative to the module location
  // rather than process cwd, making the reader location-independent
  const moduleDir = path.dirname(fileURLToPath(import.meta.url));
  const manifestPath = path.resolve(moduleDir, '../../wp-content/themes/nuvanx-medical/inc/data/publication-manifest.json');
  
  try {
    const manifestContent = await fs.readFile(manifestPath, 'utf8');
    const manifest = JSON.parse(manifestContent);
    
    if (!manifest.routes || typeof manifest.routes !== 'object') {
      throw new Error('Publication manifest missing or invalid routes object');
    }
    
    const routeCount = Object.keys(manifest.routes).length;
    
    // Explicit non-empty routes assertion to prevent silent pass on empty manifests
    if (routeCount === 0) {
      throw new Error('Publication manifest routes object is empty');
    }
    
    return {
      routeCount,
      version: manifest.version || null,
    };
  } catch (error) {
    // Provide specific error context for read/parse failures vs structural issues
    if (error.code === 'ENOENT') {
      throw new Error(`Publication manifest file not found at ${manifestPath}`);
    }
    if (error instanceof SyntaxError) {
      throw new Error(`Publication manifest contains invalid JSON at ${manifestPath}`);
    }
    throw new Error(`Failed to read publication manifest at ${manifestPath}: ${error.message}`);
  }
}

export async function runStagingPublicationParitySync(options = {}) {
  const alias = options.originSshAlias || process.env.ORIGIN_SSH_ALIAS || 'nvx-staging2';
  const outputDir = path.resolve(options.outputDir || 'scripts/staging2/artifacts');
  if (!ALLOWED_ALIASES.has(alias)) throw new Error(`Unsupported ORIGIN_SSH_ALIAS: ${alias}`);

  const expectedManifest = await getExpectedManifestInfo();

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
    // Validate snapshot is valid JSON before proceeding
    // This catches wp-cli notices, PHP deprecations, or plugin output that would corrupt the JSON
    'if ! jq empty < "$snapshot" 2>/dev/null; then',
    '  echo "ERROR: Snapshot is not valid JSON. First 500 bytes:" >&2',
    '  head -c 500 "$snapshot" >&2',
    '  exit 1',
    'fi',
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
