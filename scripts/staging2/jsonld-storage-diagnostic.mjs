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
  const remoteCommand = `cd '${stagingRoot}' && wp eval-file '${diagnosticScript}' --allow-root`;

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

  const line = stdout.split(/\r?\n/).find((entry) => entry.startsWith('JSONLD_STORAGE_DIAGNOSTIC_JSON='));
  let report = null;
  if (line) {
    try {
      report = JSON.parse(line.slice('JSONLD_STORAGE_DIAGNOSTIC_JSON='.length));
      await fs.writeFile(path.join(outputDir, 'jsonld-storage-diagnostic.json'), `${JSON.stringify(report, null, 2)}\n`, 'utf8');
    } catch (error) {
      console.error(`JSONLD_STORAGE_DIAGNOSTIC_PARSE=FAIL reason=${String(error.message || error).replace(/\s+/g, '_')}`);
    }
  }

  const pass = stdout.includes('JSONLD_STORAGE_DIAGNOSTIC=PASS');
  return { pass, report, reason: pass ? '' : 'missing PASS marker' };
}
