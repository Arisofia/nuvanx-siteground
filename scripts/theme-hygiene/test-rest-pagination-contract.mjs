#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const auditPath = path.join(root, 'scripts/staging2/audit-full-site-ui.mjs');
const source = fs.readFileSync(auditPath, 'utf8');
const failures = [];
const fail = (message) => failures.push(message);

const start = source.indexOf('async function fetchWpCollectionBrowser(endpoint)');
const end = source.indexOf('\n    function addDiscoveredLink', start);
if (start < 0 || end < 0) {
  fail('REST collection fetch block is missing');
}
const block = start >= 0 && end > start ? source.slice(start, end) : '';

for (const marker of [
  'function readCollectionTotalPages(header, endpoint)',
  'while (page <= maxPages + 1)',
  "response.headers.get('X-WP-TotalPages')",
  'REST collection exceeds pagination cap:',
  'REST collection exceeds inferred pagination cap:',
  "payload?.code === 'rest_post_invalid_page_number'",
]) {
  if (!source.includes(marker)) fail(`missing pagination contract marker: ${marker}`);
}

if (source.includes('Math.min(totalPages, maxPages)')) {
  fail('pagination must not truncate reported pages with Math.min');
}

const totalPagesRead = block.indexOf('const totalPages = readCollectionTotalPages(');
const payloadRead = block.indexOf('const items = await response.json()');
const emptyBreak = block.indexOf('if (items.length === 0) break');
const probeGuard = block.indexOf('if (page > maxPages)');
const collectedPush = block.indexOf('collected.push(...items)');

if (totalPagesRead < 0 || payloadRead < 0 || totalPagesRead > payloadRead) {
  fail('X-WP-TotalPages must be validated before reading or accepting the payload');
}
if (payloadRead < 0 || emptyBreak < 0 || payloadRead > emptyBreak) {
  fail('payload shape must be validated before the empty-page termination');
}
if (probeGuard < 0 || collectedPush < 0 || probeGuard > collectedPush) {
  fail('probe-page overflow must fail before probe elements can be collected');
}

if (failures.length > 0) {
  console.error(`FAIL: ${failures.length} REST pagination contract finding(s)`);
  for (const finding of failures) console.error(`- ${finding}`);
  process.exit(1);
}

console.log('REST_PAGINATION_CONTRACT_OK');
