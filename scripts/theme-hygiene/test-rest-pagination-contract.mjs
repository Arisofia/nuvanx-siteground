#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const auditPath = path.join(root, 'scripts/staging2/audit-full-site-ui.mjs');
const source = fs.readFileSync(auditPath, 'utf8');
const failures = [];
const fail = (message) => failures.push(message);

const helperStart = source.indexOf('function readCollectionTotalPages(header, endpoint)');
const fetchStart = source.indexOf('async function fetchWpCollectionBrowser(endpoint)', helperStart);
const fetchEnd = source.indexOf('\n    function addDiscoveredLink', fetchStart);
if (helperStart < 0 || fetchStart < 0 || fetchEnd < 0) {
  fail('REST pagination helper or collection fetch block is missing');
}
const paginationScope = helperStart >= 0 && fetchEnd > helperStart
  ? source.slice(helperStart, fetchEnd)
  : '';
const fetchBlock = fetchStart >= 0 && fetchEnd > fetchStart
  ? source.slice(fetchStart, fetchEnd)
  : '';

for (const marker of [
  'function readCollectionTotalPages(header, endpoint)',
  '!Number.isInteger(totalPages) || totalPages < 1',
  'while (page <= maxPages + 1)',
  "response.headers.get('X-WP-TotalPages')",
  'REST collection exceeds pagination cap:',
  'REST collection exceeds inferred pagination cap:',
  "payload?.code === 'rest_post_invalid_page_number'",
  'if (!response.ok)',
  'REST collection failed: ${endpoint}, page=${page}, status=${response.status}',
]) {
  if (!paginationScope.includes(marker)) {
    fail(`missing pagination contract marker: ${marker}`);
  }
}

for (const forbidden of [
  'Math.min(totalPages, maxPages)',
  'Number.isFinite(totalPages)',
]) {
  if (paginationScope.includes(forbidden)) {
    fail(`pagination contains unsafe marker: ${forbidden}`);
  }
}

const nonOkGuard = fetchBlock.indexOf('if (!response.ok)');
const invalidPageBreak = fetchBlock.indexOf('if (await responseIsInvalidPage(response)) break;');
const httpFailure = fetchBlock.indexOf('REST collection failed: ${endpoint}, page=${page}, status=${response.status}');
const totalPagesRead = fetchBlock.indexOf('const totalPages = readCollectionTotalPages(');
const payloadRead = fetchBlock.indexOf('const items = await response.json()');
const emptyBreak = fetchBlock.indexOf('if (items.length === 0) break');
const probeGuard = fetchBlock.indexOf('if (page > maxPages)');
const collectedPush = fetchBlock.indexOf('collected.push(...items)');

if (
  nonOkGuard < 0
  || invalidPageBreak < 0
  || httpFailure < 0
  || nonOkGuard > invalidPageBreak
  || invalidPageBreak > httpFailure
) {
  fail('non-OK responses must only tolerate invalid-page termination and throw every other HTTP failure');
}
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
