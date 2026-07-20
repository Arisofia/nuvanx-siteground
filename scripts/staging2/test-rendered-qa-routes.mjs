#!/usr/bin/env node

// Contract test for scripts/staging2/rendered-qa-routes.json — the route list
// consumed by rendered-qa.mjs.

import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const routesPath = path.join(here, 'rendered-qa-routes.json');
const raw = fs.readFileSync(routesPath, 'utf8');

// Must be valid JSON (mirrors the CI "Validate audit source" step).
const config = JSON.parse(raw);

assert.equal(typeof config.baseUrl, 'string');
assert.equal(config.baseUrl, 'https://staging2.nuvanx.com');
assert.doesNotThrow(() => new URL(config.baseUrl), 'baseUrl must be a valid absolute URL');
assert.equal(config.baseUrl.endsWith('/'), false, 'baseUrl must not have a trailing slash');

assert.ok(Array.isArray(config.routes), 'routes must be an array');
assert.ok(config.routes.length > 0, 'routes must not be empty');

const slugs = new Set();
const paths = new Set();

for (const entry of config.routes) {
  assert.ok(Array.isArray(entry), `each route entry must be an array, got ${JSON.stringify(entry)}`);
  assert.equal(entry.length, 2, `each route entry must be a [slug, path] pair, got ${JSON.stringify(entry)}`);

  const [slug, routePath] = entry;
  assert.equal(typeof slug, 'string');
  assert.ok(slug.length > 0, 'slug must not be empty');
  assert.match(slug, /^[a-z0-9-]+$/, `slug "${slug}" must be lowercase kebab-case`);

  assert.equal(typeof routePath, 'string');
  assert.match(routePath, /^\//, `path "${routePath}" must start with a leading slash`);
  assert.ok(
    routePath === '/' || routePath.endsWith('/'),
    `path "${routePath}" must be root or end with a trailing slash`,
  );
  assert.doesNotThrow(
    () => new URL(routePath, config.baseUrl),
    `path "${routePath}" must resolve to a valid URL against baseUrl`,
  );

  assert.equal(slugs.has(slug), false, `slug "${slug}" must be unique`);
  assert.equal(paths.has(routePath), false, `path "${routePath}" must be unique`);
  slugs.add(slug);
  paths.add(routePath);
}

// The audited surface must cover the routes referenced by rendered-qa.mjs's
// per-slug schema expectations.
for (const requiredSlug of ['home', 'contacto', 'clinicas', 'endolift', 'laser', 'medicina-estetica']) {
  assert.ok(slugs.has(requiredSlug), `routes must include the "${requiredSlug}" slug used by schema checks`);
}

// The home route must be the root path.
const home = config.routes.find(([slug]) => slug === 'home');
assert.deepEqual(home, ['home', '/']);

console.log(`PASS: rendered-qa-routes.json contract (${config.routes.length} routes)`);