#!/usr/bin/env node
import assert from 'node:assert/strict';
import fs from 'node:fs';

const hygiene = fs.readFileSync(
  'wp-content/themes/nuvanx-medical/inc/nvx-page-hygiene.php',
  'utf8',
);

assert.match(
  hygiene,
  /function nvx_unpublished_public_route_redirects\(\): array/,
  'page hygiene must declare the unpublished public-route map',
);
assert.match(
  hygiene,
  /intrusismo-tratamientos-inyectables-riesgos'\s*=>\s*'\/blog\//,
  'indexed draft journal slug must 301 to /blog/ while unpublished',
);
assert.match(
  hygiene,
  /acido-hialuronico-relleno-madrid'\s*=>\s*'\/medicina-estetica\//,
  'production-only HA draft must 301 to the published medicine hub while unpublished',
);
assert.match(
  hygiene,
  /nvx_published_singular_exists_for_slug/,
  'redirects must stand down once the slug is published',
);
assert.match(
  hygiene,
  /add_action\(\s*'template_redirect',\s*'nvx_redirect_unpublished_public_routes',\s*0\s*\)/,
  'unpublished-route redirects must run on template_redirect',
);

console.log('UNPUBLISHED_ROUTE_REDIRECTS=PASS');
