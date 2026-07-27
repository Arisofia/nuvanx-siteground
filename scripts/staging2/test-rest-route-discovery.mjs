#!/usr/bin/env node
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { discoverWordPressRoutes } from './rest-route-discovery.mjs';

const origin = 'https://staging2.nuvanx.com';
const logPath = process.env.REST_DISCOVERY_TEST_LOG || 'rest-route-discovery-test.log';
const logLines = [];
const originalFetch = globalThis.fetch;
const originalLocation = globalThis.location;
globalThis.location = { origin };

function log(message) {
  logLines.push(message);
  console.log(message);
}

function response(status, payload, totalPages = null) {
  return {
    ok: status >= 200 && status < 300,
    status,
    headers: {
      get(name) {
        return name.toLowerCase() === 'x-wp-totalpages' ? totalPages : null;
      },
    },
    async json() {
      return structuredClone(payload);
    },
  };
}

function collectionFromUrl(rawUrl) {
  const url = new URL(rawUrl, origin);
  const match = url.pathname.match(/\/wp-json\/wp\/v2\/(pages|posts|categories)$/);
  if (!match) throw new Error(`Unexpected REST URL: ${url.href}`);
  return { collection: match[1], page: Number(url.searchParams.get('page')) };
}

async function runScenario(routes, options = {}) {
  const calls = [];
  globalThis.fetch = async (rawUrl) => {
    const request = collectionFromUrl(rawUrl);
    calls.push(request);
    const key = `${request.collection}:${request.page}`;
    const configured = routes[key]
      ?? (request.page === 1 ? response(200, []) : response(400, { code: 'rest_post_invalid_page_number' }));
    return configured;
  };
  const result = await discoverWordPressRoutes(['/'], options.perPage ?? 2, options.maxPages ?? 2);
  return { result, calls };
}

async function expectFailure(routes, pattern, options = {}) {
  await assert.rejects(
    () => runScenario(routes, options),
    pattern,
  );
}

async function scenario(name, callback) {
  log(`START ${name}`);
  try {
    await callback();
    log(`PASS ${name}`);
  } catch (error) {
    logLines.push(`FAIL ${name}`);
    logLines.push(error instanceof Error ? error.stack || error.message : String(error));
    throw error;
  }
}

try {
  await scenario('valid-header-within-cap', async () => {
    const { result, calls } = await runScenario({
      'pages:1': response(200, [
        { link: `${origin}/uno/` },
        { link: `${origin}/dos/` },
      ], '2'),
      'pages:2': response(200, [{ link: `${origin}/tres/` }], '2'),
    });
    assert.equal(result.counts.pages, 3);
    assert.deepEqual(result.routes, ['/', '/dos/', '/tres/', '/uno/']);
    assert.deepEqual(
      calls.filter(({ collection }) => collection === 'pages').map(({ page }) => page),
      [1, 2],
    );
  });

  await scenario('valid-header-over-cap-empty-payload', async () => {
    await expectFailure(
      { 'pages:1': response(200, [], '3') },
      /exceeds pagination cap: endpoint=.*totalPages=3, maxPages=2/,
    );
  });

  await scenario('missing-header-invalid-page-probe', async () => {
    const { result, calls } = await runScenario({
      'pages:1': response(200, [
        { link: `${origin}/uno/` },
        { link: `${origin}/dos/` },
      ]),
      'pages:2': response(200, [
        { link: `${origin}/tres/` },
        { link: `${origin}/cuatro/` },
      ]),
      'pages:3': response(400, { code: 'rest_post_invalid_page_number' }),
    });
    assert.equal(result.counts.pages, 4);
    assert.deepEqual(
      calls.filter(({ collection }) => collection === 'pages').map(({ page }) => page),
      [1, 2, 3],
    );
  });

  await scenario('invalid-header-empty-probe', async () => {
    const { result } = await runScenario({
      'pages:1': response(200, [
        { link: `${origin}/uno/` },
        { link: `${origin}/dos/` },
      ], 'invalid'),
      'pages:2': response(200, [
        { link: `${origin}/tres/` },
        { link: `${origin}/cuatro/` },
      ], 'invalid'),
      'pages:3': response(200, [], 'invalid'),
    });
    assert.equal(result.counts.pages, 4);
  });

  await scenario('probe-detects-overflow', async () => {
    await expectFailure(
      {
        'pages:1': response(200, [
          { link: `${origin}/uno/` },
          { link: `${origin}/dos/` },
        ]),
        'pages:2': response(200, [
          { link: `${origin}/tres/` },
          { link: `${origin}/cuatro/` },
        ]),
        'pages:3': response(200, [{ link: `${origin}/cinco/` }]),
      },
      /exceeds inferred pagination cap: endpoint=.*probePage=3, items=1, maxPages=2/,
    );
  });

  await scenario('unrelated-http-error-fails', async () => {
    await expectFailure(
      {
        'pages:1': response(200, [
          { link: `${origin}/uno/` },
          { link: `${origin}/dos/` },
        ]),
        'pages:2': response(500, { code: 'server_error' }),
      },
      /REST collection failed: endpoint=.*page=2, status=500/,
    );
  });

  await scenario('invalid-first-page-fails', async () => {
    await expectFailure(
      { 'pages:1': response(400, { code: 'rest_post_invalid_page_number' }) },
      /REST collection failed: endpoint=.*page=1, status=400/,
    );
  });

  await scenario('empty-before-declared-end-fails', async () => {
    await expectFailure(
      { 'pages:1': response(200, [], '2') },
      /empty page before declared end: endpoint=.*page=1, totalPages=2/,
    );
  });

  log('REST_ROUTE_DISCOVERY_TESTS_OK');
} finally {
  fs.writeFileSync(logPath, `${logLines.join('\n')}\n`);
  globalThis.fetch = originalFetch;
  if (originalLocation === undefined) delete globalThis.location;
  else globalThis.location = originalLocation;
}
