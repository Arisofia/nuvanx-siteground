import assert from 'node:assert/strict';

const base = String(process.env.STAGING_URL || '').replace(/\/+$/, '');
assert.ok(base.startsWith('https://'), 'STAGING_URL must be HTTPS');

const source = new URL('/medicina-estetica-goya/', `${base}/`);
source.searchParams.set('gclid', 'QA_REDIRECT_CI_GCLID_001');
source.searchParams.set('utm_source', 'google');
source.searchParams.set('utm_medium', 'cpc');
source.searchParams.set('utm_campaign', 'qa_redirect_contract');

const response = await fetch(source, {
  redirect: 'manual',
  headers: {
    'cache-control': 'no-cache',
    pragma: 'no-cache',
    cookie: 'wpSGCacheBypass=1',
  },
});

assert.equal(response.status, 301, `Expected 301, received ${response.status}`);

const locationHeader = response.headers.get('location');
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
  response.headers.get('x-redirect-by'),
  'NUVANX',
  `Unexpected redirect owner: ${response.headers.get('x-redirect-by')}`,
);

console.log(
  `GOYA_ALIAS_QUERY_CONTRACT=PASS status=${response.status} owner=NUVANX destination=${destination.href}`,
);
