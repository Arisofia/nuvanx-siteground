#!/usr/bin/env node
import fs from 'node:fs';

const read = (path) => fs.readFileSync(path, 'utf8');
const failures = [];
const requireText = (scope, text, marker) => {
  if (!text.includes(marker)) failures.push(`${scope}: missing ${marker}`);
};
const forbidText = (scope, text, marker) => {
  if (text.includes(marker)) failures.push(`${scope}: forbidden ${marker}`);
};

const audit = read('scripts/staging2/audit-full-site-ui.mjs');
forbidText('full-site audit', audit, 'authorizedRedirects');
requireText('full-site audit', audit, 'Unexpected internal redirect');

const hygiene = read('wp-content/themes/nuvanx-medical/inc/nvx-page-hygiene.php');
forbidText('page hygiene', hygiene, 'nvx_redirect_superseded_legal_pages');

const integrations = read('wp-content/themes/nuvanx-medical/inc/nvx-integrations.php');
forbidText('integrations', integrations, 'nvx_redirect_governed_routes');
forbidText('integrations', integrations, 'wp_safe_redirect(');

const migration = read('scripts/wp/nvx-canonical-route-migration.php').replace(/\s+/g, ' ');
for (const marker of [
  "'legacy' => 'mas-informacion-sobre-las-cookies', 'source_id' => 18, 'target' => 'politica-de-cookies-ue', 'target_id' => 577",
  "'legacy' => 'politica-de-cookies', 'source_id' => 31, 'target' => 'politica-de-cookies-ue', 'target_id' => 577",
  "'legacy' => 'politica-de-privacidad'",
  "'legacy' => 'tratamiento-retirado'",
  "'legacy' => 'tratamientos'",
  "'legacy' => 'liposculpt-air'",
  "'legacy' => 'v-lift-awake'",
  "'legacy' => 'dr-javier-rivera-tejeda'",
  "'legacy' => 'eye-frame-rejuvenecimiento-mirada-madrid'",
  "'legacy' => 'eye-frame'",
  "'target' => 'ojeras-surco-lagrimal-madrid'",
  "'_wp_old_slug'",
  'Canonical route audit passed.',
]) requireText('canonical migration', migration, marker.replace(/\s+/g, ' '));

const deploy = read('tools/deploy/deploy-to-staging2.sh');
for (const marker of [
  '--canonical-migration-script',
  'nvx canonical-routes audit --allow-pending',
  'nvx canonical-routes apply --confirm=canonicalize-legacy-routes',
]) requireText('guarded deployment', deploy, marker);

if (!/nvx canonical-routes audit(?!\s+--allow-pending)/.test(deploy)) {
  failures.push('guarded deployment: missing standalone nvx canonical-routes audit (post-apply verification)');
}

const smoke = read('scripts/staging2/smoke-verify-staging2.sh');
requireText('staging2 smoke', smoke, 'CANONICAL_ROUTE_REDIRECTS_OK');
requireText('staging2 smoke', smoke, 'rest_route=/wp/v2/pages');

const gitignore = read('.gitignore');
for (const marker of ['.env', '.env.*', '!.env.example']) requireText('.gitignore', gitignore, marker);

const envExample = read('.env.example');
requireText('.env.example', envExample, 'BASE_URL=https://staging2.nuvanx.com');

// Enforce that .env.example stays "sin secretos" by ensuring suspicious keys only use placeholder values
const sensitivePrefixes = [
  'API_KEY=',
  'SECRET=',
  'TOKEN=',
  'PASSWORD=',
  'CLIENT_SECRET=',
  'ACCESS_KEY=',
];

for (const line of envExample.split('\n')) {
  const trimmed = line.trim();
  if (!trimmed || trimmed.startsWith('#')) continue;

  const prefix = sensitivePrefixes.find(p => trimmed.startsWith(p));
  if (!prefix) continue;

  const value = trimmed.slice(prefix.length).trim();

  // Allow empty or obvious placeholder values only
  const isPlaceholder =
    !value ||
    /^CHANGEME$/i.test(value) ||
    /^PLACEHOLDER$/i.test(value) ||
    /^REPLACE_ME$/i.test(value) ||
    /^TODO$/i.test(value);

  if (!isPlaceholder) {
    failures.push(`.env.example must not contain non-placeholder secret value: "${trimmed}"`);
  }
}

if (failures.length) {
  console.error(`CANONICAL_ROUTE_CONTRACT_FAILED count=${failures.length}`);
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}
console.log('CANONICAL_ROUTE_CONTRACT_OK');
