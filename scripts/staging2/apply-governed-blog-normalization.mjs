import fs from 'node:fs/promises';
import path from 'node:path';
import { spawnSync } from 'node:child_process';

const SSH_BIN = '/usr/bin/ssh';
const ALLOWED_ALIASES = new Set(['nvx-staging2', 'nvx-staging2-pr']);
const STAGING_ROOT = '/home/customer/www/staging2.nuvanx.com/public_html';

function resolveReleaseMigration() {
  const release = String(process.env.REMOTE_RELEASE || '').trim();
  const prefix = `${STAGING_ROOT}/wp-content/.nuvanx-deployments/`;
  if (!release.startsWith(prefix) || !/^[A-Za-z0-9_./-]+$/.test(release)) {
    throw new Error('REMOTE_RELEASE is missing or outside the canonical Staging2 deployment area');
  }
  return `${release}/tools/migrations/migrate-recent-posts-to-blocks.php`;
}

export async function runGovernedBlogNormalization(options = {}) {
  const alias = options.originSshAlias || process.env.ORIGIN_SSH_ALIAS || 'nvx-staging2';
  const outputDir = path.resolve(options.outputDir || 'scripts/staging2/artifacts');
  if (!ALLOWED_ALIASES.has(alias)) throw new Error(`Unsupported ORIGIN_SSH_ALIAS: ${alias}`);

  const migration = resolveReleaseMigration();
  const remoteScript = [
    'set -Eeuo pipefail',
    `cd '${STAGING_ROOT}'`,
    `NVX_MIGRATION_APPLY=yes wp eval-file '${migration}' --allow-root`,
  ].join('\n');

  const result = spawnSync(
    SSH_BIN,
    ['-o', 'BatchMode=yes', '-o', 'ConnectTimeout=10', '-o', 'ConnectionAttempts=1', '--', alias, remoteScript],
    { encoding: 'utf8', timeout: 120000, maxBuffer: 16 * 1024 * 1024 },
  );

  if (result.error) throw new Error(`Governed blog normalization transport failed: ${result.error.message}`);
  if (result.status !== 0) {
    const reason = String(result.stderr || `exit ${result.status}`).trim().replace(/\s+/g, ' ').slice(0, 2000);
    throw new Error(`Governed blog normalization failed: ${reason}`);
  }

  let report;
  try {
    report = JSON.parse(String(result.stdout || '').trim());
  } catch (error) {
    throw new Error(`Governed blog normalization returned invalid JSON: ${error.message}`);
  }

  if (report.schema !== 'governed-blog-content-migration' || report.apply !== true) {
    throw new Error(`Unexpected governed blog normalization report: schema=${report.schema} apply=${report.apply}`);
  }
  if (Number(report.summary?.validation_failed || 0) !== 0) {
    throw new Error(`Governed blog normalization reported validation failures: ${report.summary.validation_failed}`);
  }

  await fs.mkdir(outputDir, { recursive: true });
  await fs.writeFile(
    path.join(outputDir, 'governed-blog-normalization.json'),
    `${JSON.stringify(report, null, 2)}\n`,
    'utf8',
  );

  console.log(
    `GOVERNED_BLOG_NORMALIZATION=PASS checked=${report.summary?.published_checked ?? 0} migrated=${report.summary?.migrated ?? 0}`,
  );
  return report;
}
