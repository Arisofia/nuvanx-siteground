#!/usr/bin/env node
/**
 * Contract test for scripts/staging2/rendered-qa-routes.json.
 *
 * Validates the config shape rendered-qa.mjs relies on (JSON.parse of a
 * {baseUrl, routes: [[slug, path], ...]} object) and cross-checks that the
 * route slugs rendered-qa.mjs hard-codes schema expectations for are still
 * present in the config, so the two files cannot silently drift apart.
 */
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const configPath = path.join(root, 'scripts/staging2/rendered-qa-routes.json');
const raw = fs.readFileSync(configPath, 'utf8');

let config;
assert.doesNotThrow(() => { config = JSON.parse(raw); }, 'rendered-qa-routes.json must be valid JSON');

// --- top-level shape ---------------------------------------------------------

assert.equal(typeof config.baseUrl, 'string', 'baseUrl must be a string');
assert.match(config.baseUrl, /^https:\/\/[a-z0-9.-]+$/i, 'baseUrl must be an https origin with no trailing slash or path');
assert.equal(config.baseUrl.endsWith('/'), false, 'baseUrl must not end with a trailing slash (the script only strips one, not normalize double slashes)');

assert.ok(Array.isArray(config.routes), 'routes must be an array');
assert.ok(config.routes.length > 0, 'routes must not be empty');

// --- per-route shape ----------------------------------------------------------

const seenSlugs = new Set();
const seenPaths = new Set();

for (const route of config.routes) {
  assert.ok(Array.isArray(route), 'each route entry must be an array');
  assert.equal(route.length, 2, 'each route entry must be a [slug, path] tuple');
  const [slug, routePath] = route;

  assert.equal(typeof slug, 'string', `route slug must be a string (got ${JSON.stringify(slug)})`);
  assert.ok(slug.length > 0, 'route slug must not be empty');
  assert.match(slug, /^[a-z0-9-]+$/, `route slug "${slug}" must be lowercase kebab-case (used verbatim in screenshot filenames)`);
  assert.equal(seenSlugs.has(slug), false, `route slug "${slug}" must be unique`);
  seenSlugs.add(slug);

  assert.equal(typeof routePath, 'string', `route path for "${slug}" must be a string`);
  assert.ok(routePath.startsWith('/'), `route path "${routePath}" must be root-relative`);
  assert.ok(routePath.endsWith('/'), `route path "${routePath}" must end with a trailing slash to match WordPress permalink structure`);
  assert.equal(seenPaths.has(routePath), false, `route path "${routePath}" must be unique`);
  seenPaths.add(routePath);
}

// --- cross-reference with rendered-qa.mjs's hard-coded schema expectations ----

const scriptSource = fs.readFileSync(path.join(root, 'scripts/staging2/rendered-qa.mjs'), 'utf8');
const requiredSlugPattern = /\[('[a-z0-9-]+'(?:, '[a-z0-9-]+')*)\]\.includes\(slug\)/g;
const requiredSlugGroups = [...scriptSource.matchAll(requiredSlugPattern)].map(
  (match) => match[1].split(',').map((item) => item.trim().replace(/^'|'$/g, '')),
);

assert.ok(requiredSlugGroups.length >= 2, 'expected rendered-qa.mjs to hard-code at least two per-slug schema expectation groups');

for (const group of requiredSlugGroups) {
  for (const requiredSlug of group) {
    assert.ok(
      seenSlugs.has(requiredSlug),
      `rendered-qa.mjs expects a route slug "${requiredSlug}" that is missing from rendered-qa-routes.json`,
    );
  }
}

// The home page must always be audited and must resolve to the site root.
const home = config.routes.find(([slug]) => slug === 'home');
assert.ok(home, 'a "home" route must be present');
assert.equal(home[1], '/', 'the home route must map to the site root');

console.log(`PASS: rendered-qa-routes.json contract (${config.routes.length} routes, baseUrl ${config.baseUrl})`);