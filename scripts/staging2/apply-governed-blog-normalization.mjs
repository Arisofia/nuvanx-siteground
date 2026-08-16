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
  return `${release}/tools/migrations/content-hygiene-shared.php`;
}

/**
 * Run the full shared content migration on Staging2.
 *
 * NOTE: This executes content-hygiene-shared.php in its entirety (Blocks A-E),
 * not just the new Block C2 (governed-blog-markdown-hygiene.php). The function
 * name is legacy; it was added when Block C2 was introduced but runs the complete
 * migration for consistency with the production deployment workflow.
 *
 * Side effects:
 * - Blocks are idempotent as written (retired slugs are already trashed,
 *   normalized posts no longer match nvxNeedsMarkdownNormalization)
 * - Retired slugs are not part of the manifest, so the manifest contract that
 *   runs afterwards is unaffected
 * - MIGRATION_WRITE_MARKER is not set for this invocation, so the durable
 *   rollback marker referenced in the Block C2 comment is not armed here
 *
 * This broader scope is intentional to ensure Staging2 acceptance validates the
 * complete content hygiene state that production will receive.
 */
export async function runGovernedBlogNormalization(options = {}) {
  const alias = options.originSshAlias || process.env.ORIGIN_SSH_ALIAS || 'nvx-staging2';
  const outputDir = path.resolve(options.outputDir || 'scripts/staging2/artifacts');
  if (!ALLOWED_ALIASES.has(alias)) throw new Error(`Unsupported ORIGIN_SSH_ALIAS: ${alias}`);

  const migration = resolveReleaseMigration();
  const remoteScript = [
    'set -Eeuo pipefail',
    `cd '${STAGING_ROOT}'`,
    `wp eval-file '${migration}' --allow-root`,
  ].join('\n');

  const result = spawnSync(
    SSH_BIN,
    ['-o', 'BatchMode=yes', '-o', 'ConnectTimeout=10', '-o', 'ConnectionAttempts=1', '--', alias, remoteScript],
    { encoding: 'utf8', timeout: 120000, maxBuffer: 16 * 1024 * 1024 },
  );

  if (result.error) throw new Error(`Governed blog normalization transport failed: ${result.error.message}`);
  const stdout = String(result.stdout || '');
  const stderr = String(result.stderr || '');
  if (result.status !== 0 || !/Status:\s+MIGRATION_OK\b/.test(stdout)) {
    const fallbackReason = `exit ${result.status}`;
    const reason = (stderr || stdout || fallbackReason).trim().replace(/\s+/g, ' ').slice(0, 2000);
    throw new Error(`Governed blog normalization failed: ${reason}`);
  }

  const migrated = (stdout.match(/\[NORMALIZED JOURNAL\]/g) || []).length;
  await fs.mkdir(outputDir, { recursive: true });
  const logSuffix = stderr ? `\n--- STDERR ---\n${stderr}` : '';
  await fs.writeFile(
    path.join(outputDir, 'governed-blog-normalization.log'),
    `${stdout}${logSuffix}`,
    'utf8',
  );

  const report = {
    schema: 'governed-blog-normalization-contract',
    checkedAt: new Date().toISOString(),
    migrated,
    validation: 'PASS',
  };
  await fs.writeFile(
    path.join(outputDir, 'governed-blog-normalization.json'),
    `${JSON.stringify(report, null, 2)}\n`,
    'utf8',
  );

  console.log(`GOVERNED_BLOG_NORMALIZATION=PASS migrated=${migrated}`);
  return report;
}
