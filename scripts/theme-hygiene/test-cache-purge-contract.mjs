#!/usr/bin/env node
/**
 * Contract for the shared WordPress/SiteGround cache purge helper.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const helperPath = path.join(root, 'tools/deploy/nvx-purge-wp-caches.sh');
const failures = [];

if (!fs.existsSync(helperPath)) {
  failures.push('missing tools/deploy/nvx-purge-wp-caches.sh');
} else {
  const source = fs.readFileSync(helperPath, 'utf8');
  const executable = source
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter((line) => line !== '' && !line.startsWith('#'));

  if (!source.includes('set -Eeuo pipefail')) {
    failures.push('purge helper must enable strict shell error handling');
  }

  const cacheFlushLines = executable.filter((line) => /^wp cache flush(?:\s|$)/.test(line));
  if (cacheFlushLines.length !== 1 || cacheFlushLines[0] !== 'wp cache flush') {
    failures.push(`expected one fail-loud "wp cache flush" command; found ${JSON.stringify(cacheFlushLines)}`);
  }

  const siteGroundLines = executable.filter((line) => /^wp sg purge(?:\s|$)/.test(line));
  if (siteGroundLines.length !== 1 || siteGroundLines[0] !== 'wp sg purge') {
    failures.push(`expected only the canonical fail-loud "wp sg purge" command; found ${JSON.stringify(siteGroundLines)}`);
  }

  for (const marker of [
    'echo "wp_cache_flush=ok"',
    'echo "sg_purge=ok"',
    'shopt -s nullglob dotglob',
    'rm -rf -- "${cache_targets[@]}"',
    'disk_cache_cleanup=ok',
    'optimizer_assets_cleanup=ok',
    'opcache_reset()',
    'opcache.enable_cli',
    'FILTER_VALIDATE_BOOLEAN',
    'opcache=unavailable',
    'opcache=failed',
    'exit(1)',
    'opcache=ok',
    'sgo-cache',
    'supercache',
    'sg-cachepress',
  ]) {
    if (!source.includes(marker)) {
      failures.push(`purge helper missing strict cleanup marker: ${marker}`);
    }
  }

  for (const forbidden of [
    'nvx_run_optional_wp_command',
    'wp cache flush ||',
    'wp sg purge ||',
    '|| true',
    '&& true ||',
    'wp sg purge dynamic',
    'wp sg purge memcached',
  ]) {
    if (source.includes(forbidden)) {
      failures.push(`purge helper contains forbidden marker: ${forbidden}`);
    }
  }

  const cacheIndex = executable.indexOf('wp cache flush');
  const cacheSuccessIndex = executable.indexOf('echo "wp_cache_flush=ok"');
  if (cacheIndex < 0 || cacheSuccessIndex !== cacheIndex + 1) {
    failures.push('wp_cache_flush=ok must immediately follow a successful wp cache flush');
  }

  const purgeIndex = executable.indexOf('wp sg purge');
  const purgeSuccessIndex = executable.indexOf('echo "sg_purge=ok"');
  if (purgeIndex < 0 || purgeSuccessIndex !== purgeIndex + 1) {
    failures.push('sg_purge=ok must immediately follow a successful wp sg purge');
  }

  const opcacheEval = executable.find((line) => line.startsWith("wp eval 'if (!function_exists(\"opcache_reset\")"));
  if (
    !opcacheEval
    || !opcacheEval.includes('ini_get("opcache.enable_cli")')
    || !opcacheEval.includes('FILTER_VALIDATE_BOOLEAN')
    || !opcacheEval.includes('elseif (!opcache_reset())')
    || !opcacheEval.includes('exit(1)')
  ) {
    failures.push('OpCache must distinguish unavailable CLI support from an active reset failure');
  }
}

if (failures.length > 0) {
  console.error(`CACHE_PURGE_CONTRACT_FAILED findings=${failures.length}`);
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('CACHE_PURGE_CONTRACT_OK siteground=canonical filesystem=strict opcache=classified');
