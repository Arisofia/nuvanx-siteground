#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const auditPath = path.join(root, 'scripts/staging2/audit-full-site-ui.mjs');
const source = fs.readFileSync(auditPath, 'utf8');
const failures = [];
const fail = (message) => failures.push(message);

function declarationStart(name, fromIndex = 0) {
  const candidates = [
    source.indexOf(`function ${name}(`, fromIndex),
    source.indexOf(`async function ${name}(`, fromIndex),
    source.indexOf(`const ${name} =`, fromIndex),
  ].filter((index) => index >= 0);
  return candidates.length > 0 ? Math.min(...candidates) : -1;
}

const helperStart = declarationStart('readCollectionTotalPages');
const pageFetchStart = declarationStart('fetchWpCollectionPage', helperStart);
const fetchStart = declarationStart('fetchWpCollectionBrowser', helperStart);
const fetchEnd = declarationStart('addDiscoveredLink', fetchStart);
const splitPageFetch = pageFetchStart >= 0 && pageFetchStart < fetchStart;

if (helperStart < 0 || fetchStart < 0 || fetchEnd < 0) {
  fail('REST pagination helper or collection fetch block is missing');
}

const paginationScope = helperStart >= 0 && fetchEnd > helperStart
  ? source.slice(helperStart, fetchEnd)
  : '';
const pageFetchBlock = splitPageFetch
  ? source.slice(pageFetchStart, fetchStart)
  : '';
const fetchBlock = fetchStart >= 0 && fetchEnd > fetchStart
  ? source.slice(fetchStart, fetchEnd)
  : '';
const requestBlock = splitPageFetch ? pageFetchBlock : fetchBlock;

for (const [label, pattern] of [
  ['readCollectionTotalPages declaration', /(?:function\s+readCollectionTotalPages\s*\(|const\s+readCollectionTotalPages\s*=)/],
  ['validated positive total-page count', /!Number\.isInteger\(totalPages\)\s*\|\|\s*totalPages\s*<\s*1/],
  ['bounded probe loop', /while\s*\(page\s*<=\s*maxPages\s*\+\s*1\)/],
  ['WordPress total-pages header', /response\.headers\.get\(['"]X-WP-TotalPages['"]\)/],
  ['declared pagination-cap failure', /REST collection exceeds pagination cap:/],
  ['inferred pagination-cap failure', /REST collection exceeds inferred pagination cap:/],
  ['invalid-page REST termination', /payload\?\.code\s*===\s*['"]rest_post_invalid_page_number['"]/],
  ['non-OK response guard', /if\s*\(!response\.ok\)/],
  ['non-OK response failure', /REST collection failed: \$\{endpoint\}, page=\$\{page\}, status=\$\{response\.status\}/],
]) {
  if (!pattern.test(paginationScope)) {
    fail(`missing pagination contract marker: ${label}`);
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

const nonOkGuard = requestBlock.indexOf('if (!response.ok)');
const invalidPageTermination = Math.max(
  requestBlock.indexOf('if (await responseIsInvalidPage(response)) break;'),
  requestBlock.indexOf('if (await responseIsInvalidPage(response)) return null;'),
);
const httpFailure = requestBlock.indexOf('REST collection failed: ${endpoint}, page=${page}, status=${response.status}');
const totalPagesRead = requestBlock.indexOf("response.headers.get('X-WP-TotalPages')");
const payloadRead = requestBlock.indexOf('const items = await response.json()');
const payloadShapeGuard = requestBlock.indexOf('if (!Array.isArray(items))');
const pageResultReturn = requestBlock.indexOf('return { items, totalPages }');

if (
  nonOkGuard < 0
  || invalidPageTermination < 0
  || httpFailure < 0
  || nonOkGuard > invalidPageTermination
  || invalidPageTermination > httpFailure
) {
  fail('non-OK responses must only tolerate invalid-page termination and throw every other HTTP failure');
}
if (totalPagesRead < 0 || payloadRead < 0 || totalPagesRead > payloadRead) {
  fail('X-WP-TotalPages must be validated before reading or accepting the payload');
}
if (
  payloadRead < 0
  || payloadShapeGuard < 0
  || payloadRead > payloadShapeGuard
  || (splitPageFetch && (pageResultReturn < 0 || payloadShapeGuard > pageResultReturn))
) {
  fail('payload shape must be validated before a page result can be accepted');
}

if (splitPageFetch) {
  const pageFetchCall = fetchBlock.indexOf('const pageData = await fetchWpCollectionPage(endpoint, page)');
  const emptyBreak = fetchBlock.indexOf('if (!pageData || pageData.items.length === 0) break;');
  const probeGuard = fetchBlock.indexOf('if (page > maxPages)');
  const collectedPush = fetchBlock.indexOf('collected.push(...pageData.items)');

  if (pageFetchCall < 0 || emptyBreak < 0 || pageFetchCall > emptyBreak) {
    fail('invalid or empty page results must terminate before collection');
  }
  if (probeGuard < 0 || collectedPush < 0 || probeGuard > collectedPush) {
    fail('probe-page overflow must fail before probe elements can be collected');
  }
} else {
  const emptyBreak = fetchBlock.indexOf('if (items.length === 0) break;');
  const probeGuard = fetchBlock.indexOf('if (page > maxPages)');
  const collectedPush = fetchBlock.indexOf('collected.push(...items)');

  if (payloadShapeGuard < 0 || emptyBreak < 0 || payloadShapeGuard > emptyBreak) {
    fail('payload shape must be validated before the empty-page termination');
  }
  if (probeGuard < 0 || collectedPush < 0 || probeGuard > collectedPush) {
    fail('probe-page overflow must fail before probe elements can be collected');
  }
}

if (failures.length > 0) {
  console.error(`FAIL: ${failures.length} REST pagination contract finding(s)`);
  for (const finding of failures) console.error(`- ${finding}`);
  process.exit(1);
}

console.log('REST_PAGINATION_CONTRACT_OK');
