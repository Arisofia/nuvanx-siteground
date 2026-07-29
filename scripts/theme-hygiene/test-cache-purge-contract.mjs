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
    failures.push(`expected only the documented fail-loud "wp sg purge" command; found ${JSON.stringify(siteGroundLines)}`);
  }

  if (!executable.includes('echo "sg_purge=ok"')) {
    failures.push('purge helper must emit sg_purge=ok only after the canonical command succeeds');
  }

  for (const marker of ['sgo-cache', 'supercache', 'sg-cachepress', 'opcache_reset']) {
    if (!source.includes(marker)) {
      failures.push(`purge helper missing cache cleanup marker: ${marker}`);
    }
  }

  const purgeIndex = executable.indexOf('wp sg purge');
  const successIndex = executable.indexOf('echo "sg_purge=ok"');
  if (purgeIndex < 0 || successIndex !== purgeIndex + 1) {
    failures.push('sg_purge=ok must immediately follow a successful wp sg purge');
  }
}

if (failures.length > 0) {
  console.error(`CACHE_PURGE_CONTRACT_FAILED findings=${failures.length}`);
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('CACHE_PURGE_CONTRACT_OK siteground=canonical fail_mode=strict');
