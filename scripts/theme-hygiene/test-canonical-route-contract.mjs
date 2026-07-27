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
requireText('full-site audit', audit, 'REST collection exceeds pagination cap');

const hygiene = read('wp-content/themes/nuvanx-medical/inc/nvx-page-hygiene.php');
forbidText('page hygiene', hygiene, 'nvx_redirect_superseded_legal_pages');
forbidText('page hygiene', hygiene, 'wp_safe_redirect(');

const integrations = read('wp-content/themes/nuvanx-medical/inc/nvx-integrations.php');
forbidText('integrations', integrations, 'nvx_redirect_governed_routes');
forbidText('integrations', integrations, 'wp_safe_redirect(');
forbidText('integrations', integrations, 'wp_redirect(');
for (const marker of [
  'function nvx_retired_legacy_route_slugs(): array',
  'function nvx_is_retired_legacy_route_request(): bool',
  'function nvx_disable_retired_legacy_route_redirect',
  'function nvx_serve_retired_legacy_route(): void',
  "add_filter( 'redirect_canonical', 'nvx_disable_retired_legacy_route_redirect'",
  "add_action( 'template_redirect', 'nvx_serve_retired_legacy_route', -1000000 )",
  "remove_action( 'template_redirect', 'redirect_canonical' )",
  'status_header( 410 )',
  'X-Robots-Tag: noindex, nofollow',
  'X-NUVANX-Retired-Route: 1',
  "'mas-informacion-sobre-las-cookies'",
  "'politica-de-cookies'",
  "'politica-de-privacidad'",
  "'tratamiento-retirado'",
  "'tratamientos'",
  "'liposculpt-air'",
  "'v-lift-awake'",
  "'dr-javier-rivera-tejeda'",
  "'eye-frame-rejuvenecimiento-mirada-madrid'",
  "'eye-frame'",
]) requireText('retired route runtime guard', integrations, marker);

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
  "WP_CLI::add_command( 'nvx legacy-routes'",
  'Legacy route retirement audit passed.',
  'Legacy route retirement applied.',
  'wp_trash_post',
]) requireText('legacy retirement migration', migration, marker.replace(/\s+/g, ' '));
forbidText('legacy retirement migration', migration, 'add_post_meta');
forbidText('legacy retirement migration', migration, 'canonicalize-legacy-routes');

const deploy = read('tools/deploy/deploy-to-staging2.sh');
for (const marker of [
  '--canonical-migration-script',
  'nvx legacy-routes audit --allow-pending',
  'nvx legacy-routes apply --confirm=retire-legacy-routes',
]) requireText('guarded deployment', deploy, marker);
if (!/nvx legacy-routes audit(?!\s+--allow-pending)/.test(deploy)) {
  failures.push('guarded deployment: missing standalone nvx legacy-routes audit');
}

const smoke = read('scripts/staging2/smoke-verify-staging2.sh');
for (const marker of [
  'LEGACY_ROUTES_RETIRED_OK',
  'check_retired_route',
  'check_target_page',
  'x_redirect_by=',
  "grep -i '^x-redirect-by:'",
  '/mas-informacion-sobre-las-cookies/',
  '/politica-de-cookies/',
  '/politica-de-privacidad/',
  '/tratamiento-retirado/',
  '/tratamientos/',
  '/liposculpt-air/',
  '/v-lift-awake/',
  '/dr-javier-rivera-tejeda/',
  '/eye-frame-rejuvenecimiento-mirada-madrid/',
  '/eye-frame/',
]) requireText('staging2 smoke', smoke, marker);
forbidText('staging2 smoke', smoke, 'check_redirect');
forbidText('staging2 smoke', smoke, 'CANONICAL_ROUTE_REDIRECTS_OK');

const gitignore = read('.gitignore');
for (const marker of ['.env', '.env.*', '!.env.example']) requireText('.gitignore', gitignore, marker);

const envExample = read('.env.example');
requireText('.env.example', envExample, 'BASE_URL=https://staging2.nuvanx.com');
requireText('.env.example', envExample, 'NVX_AUDIT_REST_MAX_PAGES=50');

const sensitivePrefixes = [
  'API_KEY=', 'SECRET=', 'TOKEN=', 'PASSWORD=',
  'CLIENT_SECRET=', 'ACCESS_KEY=',
];
for (const line of envExample.split('\n')) {
  const trimmed = line.trim();
  if (!trimmed || trimmed.startsWith('#')) continue;
  const prefix = sensitivePrefixes.find((candidate) => trimmed.startsWith(candidate));
  if (!prefix) continue;
  const value = trimmed.slice(prefix.length).trim();
  const placeholder = !value || /^(CHANGEME|PLACEHOLDER|REPLACE_ME|TODO)$/i.test(value);
  if (!placeholder) failures.push(`.env.example contains a non-placeholder secret value: ${trimmed}`);
}

if (failures.length) {
  console.error(`LEGACY_ROUTE_RETIREMENT_CONTRACT_FAILED count=${failures.length}`);
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}
console.log('LEGACY_ROUTE_RETIREMENT_CONTRACT_OK');
