#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const failures = [];
const fail = (message) => failures.push(message);
const file = (relative) => path.join(root, relative);
const read = (relative) => {
  const target = file(relative);
  if (!fs.existsSync(target)) { fail(`missing ${relative}`); return ''; }
  return fs.readFileSync(target, 'utf8');
};

const workflow = read('.github/workflows/deploy-staging2.yml');
const deploy = read('tools/deploy/deploy-to-staging2.sh');
const diagnostics = read('scripts/staging2/collect-staging2-diagnostics.sh');
const migration = read('scripts/wp/nvx-production-readiness-command.php');
const smoke = read('scripts/staging2/smoke-verify-staging2.sh');
const acceptance = read('scripts/staging2/verify-rendered-acceptance.mjs');
const integrations = read('wp-content/themes/nuvanx-medical/inc/nvx-integrations.php');
const protocolHub = read('wp-content/themes/nuvanx-medical/inc/nvx-protocol-hub.php');
const protocolPages = read('wp-content/themes/nuvanx-medical/inc/nvx-protocol-pages.php');
const anatomicalPages = read('wp-content/themes/nuvanx-medical/inc/nvx-anatomical-pages.php');
const strategyPages = read('wp-content/themes/nuvanx-medical/inc/nvx-strategy-pages.php');
const editorialSeo = read('wp-content/themes/nuvanx-medical/inc/nvx-editorial-seo-extension.php');
const controlledPublicContent = [protocolHub, protocolPages, anatomicalPages, strategyPages].join('\n');

for (const marker of [
  'workflow_dispatch:', "inputs.confirmation == 'DEPLOY_STAGING2'", 'PREFLIGHT_ONLY', 'DEPLOY_AND_MIGRATE', 'SMOKE_ONLY',
  'environment:', 'name: staging2', 'persist-credentials: false', 'StrictHostKeyChecking yes', 'STAGING2_SSH_KNOWN_HOSTS',
  'git_sha must equal the selected workflow ref HEAD', 'scripts/staging2/collect-staging2-diagnostics.sh',
  'scripts/wp/nvx-production-readiness-command.php', 'scripts/staging2/smoke-verify-staging2.sh',
  'scripts/staging2/verify-rendered-acceptance.mjs', 'RENDERED_ACCEPTANCE_OK', 'staging2-deployment-evidence',
]) if (!workflow.includes(marker)) fail(`workflow missing contract marker: ${marker}`);
for (const forbidden of ['ssh-keyscan', 'StrictHostKeyChecking no', 'persist-credentials: true', '/home/customer/www/nuvanx.com/public_html']) {
  if (workflow.includes(forbidden)) fail(`workflow contains forbidden marker: ${forbidden}`);
}

for (const marker of [
  "EXPECTED_ROOT='/home/customer/www/staging2.nuvanx.com/public_html'", "EXPECTED_URL='https://staging2.nuvanx.com'",
  "BACKUP_ROOT='/home/customer/backups-nuvanx/staging2'", 'wp db export', 'wp db import',
  'nvx production-readiness audit --allow-pending', 'nvx production-readiness apply --confirm=retire-prototypes',
  'nvx production-readiness audit', 'SMOKE_VERIFY_OK', 'ROLLBACK_COMPLETE', 'DEPLOY_STAGING2_OK',
]) if (!deploy.includes(marker)) fail(`deploy script missing contract marker: ${marker}`);

for (const marker of [
  "EXPECTED_ROOT='/home/customer/www/staging2.nuvanx.com/public_html'", "EXPECTED_URL='https://staging2.nuvanx.com'",
  'wordpress.siteurl=', 'wordpress.home=', 'wordpress.active_theme=', 'wordpress.db_check=', 'STAGING2_PREFLIGHT_OK',
]) if (!diagnostics.includes(marker)) fail(`diagnostics missing contract marker: ${marker}`);
for (const forbidden of ['mkdir -p', 'rm -rf', 'wp db import', 'wp option update', 'wp rewrite flush']) {
  if (diagnostics.includes(forbidden)) fail(`diagnostics contains mutating marker: ${forbidden}`);
}

const requiredPublishedSlugs = [
  'soluciones-medicas', 'protocolos-signature', 'remodelacion-corporal-laser-madrid',
  'tratamiento-postparto-abdomen-contorno-corporal-madrid', 'papada-definicion-mandibular-madrid',
  'calidad-piel-firmeza-luminosidad-madrid', 'cicatrices-acne-poros-textura-madrid',
  'manchas-rojeces-fotorejuvenecimiento-ipl-madrid', 'grasa-localizada-abdomen-flancos-madrid',
  'flacidez-grasa-localizada-brazos-madrid', 'grasa-espalda-zona-sujetador-madrid',
  'flacidez-muslos-internos-subgluteo-madrid', 'tratamiento-rodillas-grasa-flacidez-madrid',
  'contorno-corporal-masculino-madrid',
];
for (const marker of [
  'retire-prototypes', '--allow-production', 'LOCK_OPTION', 'staging2.nuvanx.com', 'apply_approved_pages',
  'apply_governed_pages', 'synchronize_primary_menu', 'PRIMARY_MENU_NAME', 'wp_create_nav_menu',
  "set_theme_mod( 'nav_menu_locations'", 'EMPTY_TRASH_DAYS', 'wp_trash_post',
  "WP_CLI::add_command( 'nvx production-readiness'",
]) if (!migration.includes(marker)) fail(`migration missing contract marker: ${marker}`);
for (const slug of requiredPublishedSlugs) if (!migration.includes(`'${slug}'`)) fail(`migration missing approved page: ${slug}`);
for (const label of ['Soluciones', 'Protocolos Signature', 'Tecnología', 'Casos clínicos', 'Equipo médico', 'Clínicas', 'Journal', 'Contacto']) {
  if (!migration.includes(`'${label}'`)) fail(`migration missing canonical menu label: ${label}`);
}
if (/['"]post_status['"]\s*=>\s*['"]trash['"]/.test(migration)) fail('migration uses direct trash status update');

for (const marker of [
  "check_redirect '/tratamientos/' '/soluciones-medicas/'", "check_redirect '/eye-frame-rejuvenecimiento-mirada-madrid/' '/soluciones-medicas/'",
  "fetch_page '/papada-definicion-mandibular-madrid/'", "fetch_page '/calidad-piel-firmeza-luminosidad-madrid/'",
  "fetch_page '/cicatrices-acne-poros-textura-madrid/'", "fetch_page '/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/'",
  "fetch_page '/grasa-localizada-abdomen-flancos-madrid/'", "fetch_page '/flacidez-grasa-localizada-brazos-madrid/'",
  "fetch_page '/grasa-espalda-zona-sujetador-madrid/'", "fetch_page '/flacidez-muslos-internos-subgluteo-madrid/'",
  "fetch_page '/tratamiento-rodillas-grasa-flacidez-madrid/'", "fetch_page '/contorno-corporal-masculino-madrid/'",
  'SMOKE_VERIFY_OK',
]) if (!smoke.includes(marker)) fail(`smoke script missing contract marker: ${marker}`);

for (const marker of [
  'EXPECTED_SHA must be a full lowercase 40-character SHA', '/protocolos-signature/', '/papada-definicion-mandibular-madrid/',
  '/contorno-corporal-masculino-madrid/', 'internal_links', 'verifyInternalLinks', 'canonical_count',
  'legacy TRATAMIENTOS item is still present', 'nvx-deploy-sha', 'noindex', 'nofollow', 'report.json', 'RENDERED_ACCEPTANCE_OK',
]) if (!acceptance.includes(marker)) fail(`rendered acceptance missing contract marker: ${marker}`);
for (const forbidden of ['https://nuvanx.com/wp-admin', 'wp option update', 'wp post update', 'wp db import', 'DELETE ']) {
  if (acceptance.includes(forbidden)) fail(`rendered acceptance contains mutating marker: ${forbidden}`);
}

for (const marker of ["'liposculpt-air'", "'v-lift-awake'", "'tratamientos'", "'eye-frame-rejuvenecimiento-mirada-madrid'", "'status' => 'draft'"]) {
  if (!integrations.includes(marker)) fail(`governed routes missing marker: ${marker}`);
}
for (const marker of ['NUVANX Contour Architecture™', 'NUVANX Post-Maternity Contour™', 'NUVANX Profile Definition™', 'NUVANX Skin Architecture™', 'NUVANX Surface Renewal™', 'NUVANX Tone Correction™']) {
  if (!(protocolHub + protocolPages).includes(marker)) fail(`protocol content missing marker: ${marker}`);
}
for (const forbidden of ['Couture Sculpt™', 'Contour Sculpt™', 'NUVANX Eye Frame™']) {
  if ((protocolHub + protocolPages).includes(forbidden)) fail(`public protocol content contains retired name: ${forbidden}`);
}
for (const slug of requiredPublishedSlugs.slice(8)) if (!anatomicalPages.includes(slug)) fail(`Phase 2 catalogue missing slug: ${slug}`);
for (const marker of ['nvx_protocol_pages_catalog', 'nvx_anatomical_pages_catalog', 'nvx_editorial_seo_title', 'nvx_editorial_seo_description']) {
  if (!editorialSeo.includes(marker)) fail(`editorial SEO missing marker: ${marker}`);
}

const forbiddenClaims = [
  'garantizar resultados', 'control térmico absoluto', 'sin huellas quirúrgicas evidentes', 'sin bisturí ni puntos',
  'todo en vigilia', 'mínima recuperación', 'sin cicatrices', 'elimina grasa en cualquier zona', 'resultado definitivo',
  'una sola sesión', 'de rostro a tobillos', 'Tiny Tuck', 'AirTite', 'Mommy Makeover', 'destruyendo los adipocitos',
  'forzando a la piel', 'obligamos a tus células', 'obligar a los fibroblastos', 'ayudar en el cierre de la diástasis',
  'garantía de que volverá', 'sin hospitalización',
];
for (const forbidden of forbiddenClaims) {
  if (controlledPublicContent.toLowerCase().includes(forbidden.toLowerCase())) fail(`controlled public content contains forbidden claim: ${forbidden}`);
  if (!acceptance.toLowerCase().includes(forbidden.toLowerCase())) fail(`rendered acceptance does not govern forbidden claim: ${forbidden}`);
  if (!smoke.toLowerCase().includes(forbidden.toLowerCase())) fail(`smoke does not govern forbidden claim: ${forbidden}`);
}

for (const relative of [
  'scripts/wp/nvx-production-readiness-command.php', 'wp-content/themes/nuvanx-medical/inc/nvx-integrations.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-editorial-seo-extension.php', 'wp-content/themes/nuvanx-medical/inc/nvx-protocol-hub.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-protocol-pages.php', 'wp-content/themes/nuvanx-medical/inc/nvx-anatomical-pages.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-strategy-pages.php',
]) {
  const result = spawnSync('php', ['-l', file(relative)], { encoding: 'utf8' });
  if (result.status !== 0) fail(`PHP lint failed for ${relative}: ${(result.stderr || result.stdout).trim()}`);
}

if (failures.length) {
  console.error(`FAIL: ${failures.length} staging2 deployment contract finding(s)`);
  for (const finding of failures) console.error(`- ${finding}`);
  process.exit(1);
}
console.log('DEPLOY_WORKFLOW_CONTRACT_OK');
