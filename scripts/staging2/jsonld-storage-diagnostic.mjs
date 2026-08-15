import fs from 'node:fs/promises';
import path from 'node:path';
import { spawnSync } from 'node:child_process';

const SSH_BIN = '/usr/bin/ssh';
const ALLOWED_ALIASES = new Set(['nvx-staging2', 'nvx-staging2-pr']);

function safeRemotePath(value, name) {
  const normalized = String(value || '').trim();
  if (!normalized.startsWith('/home/customer/www/') || !/^[A-Za-z0-9_./-]+$/.test(normalized)) {
    throw new Error(`${name} is missing or unsafe.`);
  }
  return normalized;
}

export async function runJsonLdStorageDiagnostic(options = {}) {
  const originSshAlias = options.originSshAlias || process.env.ORIGIN_SSH_ALIAS || 'nvx-staging2';
  if (!ALLOWED_ALIASES.has(originSshAlias)) {
    throw new Error(`Unsupported ORIGIN_SSH_ALIAS: ${originSshAlias}`);
  }

  const stagingRoot = safeRemotePath(options.stagingRoot || process.env.STAGING_ROOT, 'STAGING_ROOT');
  const remoteRelease = safeRemotePath(options.remoteRelease || process.env.REMOTE_RELEASE, 'REMOTE_RELEASE');
  const diagnosticScript = `${remoteRelease}/tools/migrations/diagnose-jsonld-storage.php`;

  // wp eval-file evaluates the file body and therefore makes a top-level
  // declare(strict_types=1) illegal. wp eval + require compiles the diagnostic
  // as a normal PHP file, preserving its strict-types contract.
  //
  // NOTE: This premise should be verified against the installed WP-CLI version.
  // Depending on WP-CLI version, eval-file may include real file paths (only
  // using eval() for stdin), in which case strict types would have been legal.
  // The captured scripts/staging2/artifacts/jsonld-storage-diagnostic.log from
  // the next staging run should be checked for the actual PHP error text to
  // confirm this was the root cause of the Staging #1010 abort.
  //
  // SECURITY: The double-quoted require argument is safe from remote shell
  // expansion only because safeRemotePath restricts the path to [A-Za-z0-9_./-].
  const remoteCommand = `cd '${stagingRoot}' && wp eval "require '${diagnosticScript}';" --allow-root`;

  const result = spawnSync(
    SSH_BIN,
    ['-o', 'BatchMode=yes', '-o', 'ConnectTimeout=10', '-o', 'ConnectionAttempts=1', '--', originSshAlias, remoteCommand],
    { encoding: 'utf8', timeout: 90000, maxBuffer: 8 * 1024 * 1024 },
  );

  const stdout = result.stdout || '';
  const stderr = result.stderr || '';
  const outputDir = path.resolve(options.outputDir || 'scripts/staging2/artifacts');
  await fs.mkdir(outputDir, { recursive: true });
  await fs.writeFile(path.join(outputDir, 'jsonld-storage-diagnostic.log'), `${stdout}${stderr ? `\nSTDERR:\n${stderr}` : ''}`, 'utf8');

  if (result.error || result.status !== 0) {
    const reason = result.error?.message || stderr.trim() || `exit ${result.status}`;
    console.error(`JSONLD_STORAGE_DIAGNOSTIC=UNAVAILABLE reason=${reason.replace(/\s+/g, '_').slice(0, 500)}`);
    return { pass: false, reason, report: null };
  }

  process.stdout.write(stdout);
  if (stderr) process.stderr.write(stderr);

  const line = stdout.split(/\r?\n).find((entry) => entry.startsWith('JSONLD_STORAGE_DIAGNOSTIC_JSON='));
  let report = null;
  let parseError = null;
  if (line) {
    try {
      report = JSON.parse(line.slice('JSONLD_STORAGE_DIAGNOSTIC_JSON='.length));
      await fs.writeFile(path.join(outputDir, 'jsonld-storage-diagnostic.json'), `${JSON.stringify(report, null, 2)}\n`, 'utf8');
    } catch (error) {
      const errorMessage = String(error.message || error).replace(/\s+/g, '_');
      console.error(`JSONLD_STORAGE_DIAGNOSTIC_PARSE=FAIL reason=${errorMessage}`);
      parseError = errorMessage;
    }
  }

  const pass = stdout.includes('JSONLD_STORAGE_DIAGNOSTIC=PASS');
  const reason = parseError ? `parse_error_${parseError}` : (pass ? '' : 'missing PASS marker');
  return { pass, report, reason, parseError };
}
