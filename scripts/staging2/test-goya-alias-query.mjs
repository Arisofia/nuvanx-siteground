import assert from 'node:assert/strict';
import fs from 'node:fs';
import {
  EX_TEMPFAIL,
  isSiteGroundTransientResponse,
} from './siteground-transient-classifier.mjs';
import { runRenderedSchemaContract } from './rendered-schema-contract.mjs';
import { runJsonLdStorageDiagnostic } from './jsonld-storage-diagnostic.mjs';

const base = String(process.env.STAGING_URL || '').replace(/\/+$/, '');
assert.ok(base.startsWith('https://'), 'STAGING_URL must be HTTPS');

const source = new URL('/medicina-estetica-goya/', `${base}/`);
source.searchParams.set('gclid', 'QA_REDIRECT_CI_GCLID_001');
source.searchParams.set('utm_source', 'google');
source.searchParams.set('utm_medium', 'cpc');
source.searchParams.set('utm_campaign', 'qa_redirect_contract');

function parseHeaderDump(raw) {
  const blocks = String(raw || '')
    .replace(/\r/g, '')
    .split(/\n\n+/)
    .filter((block) => /^HTTP\/\S+\s+\d{3}\b/.test(block));
  assert.ok(blocks.length > 0, 'Origin response file contains no HTTP header block');

  const lines = blocks.at(-1).split('\n');
  const statusMatch = lines.shift().match(/^HTTP\/\S+\s+(\d{3})\b/);
  assert.ok(statusMatch, 'Origin response file contains no valid status line');
  const headers = {};
  for (const line of lines) {
    const separator = line.indexOf(':');
    if (separator < 1) continue;
    headers[line.slice(0, separator).trim().toLowerCase()] = line.slice(separator + 1).trim();
  }
  return { status: Number(statusMatch[1]), headers };
}

const responseFile = String(process.env.GOYA_ALIAS_RESPONSE_FILE || '').trim();
let status;
let headers;
let validationMode = 'public-edge';

if (responseFile) {
  ({ status, headers } = parseHeaderDump(fs.readFileSync(responseFile, 'utf8')));
  validationMode = 'origin-fallback';
  if (isSiteGroundTransientResponse(status, headers, source.href)) {
    console.error(`GOYA_ALIAS_QUERY_CONTRACT=BLOCKED_TRANSIENT status=${status} mode=${validationMode}`);
    process.exit(EX_TEMPFAIL);
  }
} else {
  const response = await fetch(source, {
    redirect: 'manual',
    headers: {
      accept: 'text/html,application/xhtml+xml',
      'cache-control': 'no-cache',
      pragma: 'no-cache',
      cookie: 'wpSGCacheBypass=1',
      'user-agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',
    },
  });
  status = response.status;
  headers = Object.fromEntries(response.headers.entries());
  if (isSiteGroundTransientResponse(status, headers, source.href)) {
    console.error(`GOYA_ALIAS_QUERY_CONTRACT=BLOCKED_TRANSIENT status=${status} mode=${validationMode}`);
    process.exit(EX_TEMPFAIL);
  }
}

assert.equal(status, 301, `Expected 301, received ${status}`);

const locationHeader = headers.location;
assert.ok(locationHeader, 'Redirect response has no Location header');

const destination = new URL(locationHeader, source);
assert.equal(
  destination.pathname,
  '/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/',
  `Unexpected destination: ${destination.href}`,
);

assert.equal(destination.searchParams.get('gclid'), 'QA_REDIRECT_CI_GCLID_001');
assert.equal(destination.searchParams.get('utm_source'), 'google');
assert.equal(destination.searchParams.get('utm_medium'), 'cpc');
assert.equal(destination.searchParams.get('utm_campaign'), 'qa_redirect_contract');

assert.equal(
  headers['x-redirect-by'],
  'NUVANX',
  `Unexpected redirect owner: ${headers['x-redirect-by']}`,
);

console.log(
  `GOYA_ALIAS_QUERY_CONTRACT=PASS status=${status} owner=NUVANX mode=${validationMode} destination=${destination.href}`,
);

try {
  await runRenderedSchemaContract({
    expectedHost: new URL(base).hostname,
    expectedSha: String(process.env.EXPECTED_SHA || '').trim(),
    originSshAlias: String(process.env.ORIGIN_SSH_ALIAS || 'nvx-staging2'),
  });
} catch (error) {
  console.error('RENDERED_SCHEMA_FORENSICS=START reason=semantic-contract-failure');
  try {
    const diagnostic = await runJsonLdStorageDiagnostic({
      originSshAlias: String(process.env.ORIGIN_SSH_ALIAS || 'nvx-staging2'),
    });
    console.error(
      `RENDERED_SCHEMA_FORENSICS=${diagnostic.pass ? 'PASS' : 'INCOMPLETE'} ` +
      `core_matches=${diagnostic.report?.core_matches ?? 'unknown'} ` +
      `custom_columns=${diagnostic.report?.custom_columns ?? 'unknown'} ` +
      `runtime_emitters=${diagnostic.report?.runtime_emitters ?? 'unknown'}`,
    );
  } catch (diagnosticError) {
    console.error(`RENDERED_SCHEMA_FORENSICS=UNAVAILABLE reason=${String(diagnosticError?.message || diagnosticError).replace(/\s+/g, '_').slice(0, 500)}`);
  }
  throw error;
}
