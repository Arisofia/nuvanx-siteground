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
const visualQa = read('scripts/staging2/capture-visual-qa.mjs');
const integrations = read('wp-content/themes/nuvanx-medical/inc/nvx-integrations.php');
const functions = read('wp-content/themes/nuvanx-medical/functions.php');
const phasePages = read('wp-content/themes/nuvanx-medical/inc/nvx-signature-phase-pages.php');
const clinicalLanguage = read('wp-content/themes/nuvanx-medical/inc/nvx-clinical-language.php');
const editorialSeo = read('wp-content/themes/nuvanx-medical/inc/nvx-editorial-seo-extension.php');
const protocolHub = read('wp-content/themes/nuvanx-medical/inc/nvx-protocol-hub.php');
const protocolPages = read('wp-content/themes/nuvanx-medical/inc/nvx-protocol-pages.php');
const strategyPages = read('wp-content/themes/nuvanx-medical/inc/nvx-strategy-pages.php');

for (const marker of [
  'workflow_dispatch:', "inputs.confirmation == 'DEPLOY_STAGING2'", 'PREFLIGHT_ONLY', 'DEPLOY_AND_MIGRATE', 'SMOKE_ONLY',
  'environment:', 'name: staging2', 'persist-credentials: false', 'StrictHostKeyChecking yes', 'STAGING2_SSH_KNOWN_HOSTS',
  'git_sha must equal the selected workflow ref HEAD', 'scripts/staging2/collect-staging2-diagnostics.sh',
  'scripts/wp/nvx-production-readiness-command.php', 'scripts/staging2/smoke-verify-staging2.sh',
  'scripts/staging2/verify-rendered-acceptance.mjs', 'Run rendered acceptance verification', 'RENDERED_ACCEPTANCE_OK',
  'scripts/staging2/capture-visual-qa.mjs', 'Validate visual QA Node syntax', 'Run real browser visual QA', 'VISUAL_QA_OK',
  'EVIDENCE_DIR: staging2-deployment-evidence/visual-qa', 'Visual QA:',
  'staging2-deployment-evidence', 'actions/upload-artifact@',
]) if (!workflow.includes(marker)) fail(`workflow missing contract marker: ${marker}`);
for (const forbidden of ['ssh-keyscan', 'StrictHostKeyChecking no', 'persist-credentials: true', '/home/customer/www/nuvanx.com/public_html']) {
  if (workflow.includes(forbidden)) fail(`workflow contains forbidden marker: ${forbidden}`);
}

for (const marker of [
  "EXPECTED_ROOT='/home/customer/www/staging2.nuvanx.com/public_html'", "EXPECTED_URL='https://staging2.nuvanx.com'",
  "BACKUP_ROOT='/home/customer/backups-nuvanx/staging2'", '--migration-script', '--smoke-script', 'wp db export', 'wp db import',
  'nvx production-readiness audit --allow-pending', 'nvx production-readiness apply --confirm=retire-prototypes',
  'nvx production-readiness audit', 'SMOKE_VERIFY_OK', 'ROLLBACK_COMPLETE', 'DEPLOY_STAGING2_OK',
]) if (!deploy.includes(marker)) fail(`deploy script missing contract marker: ${marker}`);

for (const marker of [
  "EXPECTED_ROOT='/home/customer/www/staging2.nuvanx.com/public_html'", "EXPECTED_URL='https://staging2.nuvanx.com'",
  "BACKUP_ROOT='/home/customer/backups-nuvanx/staging2'", 'wordpress.siteurl=', 'wordpress.home=', 'wordpress.active_theme=',
  'wordpress.empty_trash_days=', 'wordpress.db_check=', 'STAGING2_PREFLIGHT_OK',
]) if (!diagnostics.includes(marker)) fail(`diagnostics missing contract marker: ${marker}`);

const phaseSlugs = [
  'papada-definicion-mandibular-madrid', 'calidad-piel-firmeza-luminosidad-madrid',
  'cicatrices-acne-poros-textura-madrid', 'manchas-rojeces-fotorejuvenecimiento-ipl-madrid',
  'grasa-localizada-abdomen-flancos-madrid', 'flacidez-grasa-localizada-brazos-madrid',
  'grasa-espalda-zona-sujetador-madrid', 'flacidez-muslos-internos-subgluteo-madrid',
  'tratamiento-rodillas-grasa-flacidez-madrid', 'contorno-corporal-masculino-madrid',
];
for (const marker of [
  'retire-prototypes', "'allow-pending'", '--allow-production', 'LOCK_OPTION', 'staging2.nuvanx.com',
  'nvx_production_readiness_governed_pages', 'apply_approved_pages', 'apply_governed_pages', 'apply_primary_menu',
  'canonical_menu_signature', 'current_menu_signature', 'wp_update_nav_menu_item', 'set_theme_mod',
  'EMPTY_TRASH_DAYS', 'wp_trash_post', "WP_CLI::add_command( 'nvx production-readiness'",
  "'soluciones-medicas' =>", "'tratamiento-postparto-abdomen-contorno-corporal-madrid' =>", "'promote_draft' => true",
]) if (!migration.includes(marker)) fail(`migration missing contract marker: ${marker}`);
for (const slug of phaseSlugs) if (!migration.includes(`'${slug}' =>`)) fail(`migration missing approved phase slug: ${slug}`);
if (/['"]post_status['"]\s*=>\s*['"]trash['"]/.test(migration)) fail('migration uses direct trash status update');

for (const marker of [
  "check_redirect '/tratamientos/' '/soluciones-medicas/'", "fetch_page '/soluciones-medicas/'", "fetch_page '/protocolos-signature/'",
  "fetch_page '/remodelacion-corporal-laser-madrid/'", "fetch_page '/tratamiento-postparto-abdomen-contorno-corporal-madrid/'",
  "check_redirect '/liposculpt-air/'", "check_redirect '/v-lift-awake/' '/protocolos-signature/'", 'SMOKE_VERIFY_OK',
]) if (!smoke.includes(marker)) fail(`smoke script missing contract marker: ${marker}`);
for (const slug of phaseSlugs) if (!smoke.includes(`fetch_page '/${slug}/'`)) fail(`smoke missing phase page: ${slug}`);

for (const marker of [
  "'https://staging2.nuvanx.com'", 'EXPECTED_SHA must be a full lowercase 40-character SHA',
  '/tratamientos/', '/soluciones-medicas/', '/protocolos-signature/', '/remodelacion-corporal-laser-madrid/',
  '/tratamiento-postparto-abdomen-contorno-corporal-madrid/', 'NUVANX Contour Architecture™',
  'Papada y definición mandibular Madrid | NUVANX', 'Contorno corporal masculino Madrid | NUVANX',
  'nvx-deploy-sha', 'noindex', 'nofollow', 'WebPage', 'Organization', 'canonicals.length !== 1',
  "redirect: 'manual'", 'report.json', 'RENDERED_ACCEPTANCE_OK',
]) if (!acceptance.includes(marker)) fail(`rendered acceptance missing contract marker: ${marker}`);
for (const slug of phaseSlugs) if (!acceptance.includes(`/${slug}/`)) fail(`rendered acceptance missing phase route: ${slug}`);
for (const forbidden of ['https://nuvanx.com/wp-admin', 'wp option update', 'wp post update', 'wp db import', 'DELETE ']) {
  if (acceptance.includes(forbidden)) fail(`rendered acceptance contains mutating marker: ${forbidden}`);
}
for (const marker of [
  'Google Chrome or Chromium is not installed', 'HTTP preflight failed', String.raw`403\s*-\s*Forbidden`,
  'Page.captureScreenshot', 'captureBeyondViewport: true', 'screenshot is unexpectedly small',
  'navigation-desktop-mega.png', 'navigation-mobile-drawer.png',
  'Input.dispatchMouseEvent', "document.getElementById('nvx-hamburger-btn')?.click()",
  'Protocolos Signature mobile accordion toggle', 'Contour Architecture nested mobile toggle',
  'horizontal overflow', 'focus did not move to close button', 'Escape did not close drawer',
  'Couture Sculpt', 'Contour Sculpt', 'Eye Frame', 'VISUAL_QA_OK',
]) if (!visualQa.includes(marker)) fail(`visual QA missing contract marker: ${marker}`);
for (const slug of phaseSlugs) if (!visualQa.includes(`/${slug}/`)) fail(`visual QA missing phase route: ${slug}`);
if (!visualQa.includes('const pages = [') || !visualQa.includes("{ name: 'desktop'") || !visualQa.includes("{ name: 'mobile'")) {
  fail('visual QA must capture both desktop and mobile page states');
}

for (const marker of ["'liposculpt-air'", "'v-lift-awake'", "'tratamientos'", "'target' => '/protocolos-signature/'"]) {
  if (!integrations.includes(marker)) fail(`governed redirects missing marker: ${marker}`);
}
if (!functions.includes("require_once get_template_directory() . '/inc/nvx-signature-phase-pages.php';")) fail('functions.php does not load phase pages');

for (const marker of [
  'function nvx_signature_phase_catalog', 'function nvx_signature_phase_navigation_blueprint',
  'NUVANX Contour Architecture™', 'NUVANX Profile Definition™', 'NUVANX Skin Architecture™',
  'NUVANX Surface Renewal™', 'NUVANX Tone Correction™', 'Eye Frame',
  'grasa-localizada-abdomen-flancos-madrid', 'contorno-corporal-masculino-madrid',
  'nvx_signature_phase_seo_title', 'nvx_signature_phase_seo_description',
]) if (!phasePages.includes(marker)) fail(`phase-page module missing marker: ${marker}`);
if (!/continue;\s*\}/.test(phasePages)) fail('phase-page navigation does not explicitly skip unsupported nodes');

for (const marker of [
  'nvx_clinical_language_prohibited_phrases', 'Sin bisturí ni puntos', 'Recuperación inmediata',
  'Sin dolor', 'Sin riesgos', 'Resultados garantizados', 'Generalmente 3–4 sesiones',
  'Reducción del dolor', 'Eritema reducido', 'Control térmico absoluto',
]) if (!clinicalLanguage.includes(marker)) fail(`clinical language gate missing marker: ${marker}`);

for (const marker of [
  '/remodelacion-corporal-laser-madrid/', 'Remodelación corporal láser en Madrid | NUVANX Contour Architecture',
  'NUVANX Contour Architecture™: remodelación corporal láser por unidades anatómicas',
]) if (!editorialSeo.includes(marker)) fail(`editorial SEO missing Contour Architecture marker: ${marker}`);
if (!protocolHub.includes('NUVANX Contour Architecture™')) fail('protocol hub does not use the canonical body protocol name');

const controlledPublicContent = [protocolHub, protocolPages, strategyPages, phasePages].join('\n').toLowerCase();
for (const forbidden of ['garantizar resultados', 'control térmico absoluto', 'sin huellas quirúrgicas evidentes', 'resultado definitivo']) {
  if (controlledPublicContent.includes(forbidden)) fail(`controlled public content contains forbidden claim: ${forbidden}`);
}

for (const relative of ['scripts/staging2/verify-rendered-acceptance.mjs', 'scripts/staging2/capture-visual-qa.mjs', 'scripts/staging2/test-deploy-workflow-contract.mjs']) {
  const result = spawnSync(process.execPath, ['--check', file(relative)], { encoding: 'utf8' });
  if (result.status !== 0) fail(`Node syntax failed for ${relative}: ${(result.stderr || result.stdout).trim()}`);
}

const phpFiles = [
  'scripts/wp/nvx-production-readiness-command.php',
  'wp-content/themes/nuvanx-medical/functions.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-integrations.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-editorial-seo-extension.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-protocol-hub.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-protocol-pages.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-strategy-pages.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-signature-phase-pages.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-clinical-language.php',
];
for (const relative of phpFiles) {
  const spawnOpts = process.platform === 'win32' 
    ? { encoding: 'utf8' } 
    : { encoding: 'utf8', env: { ...process.env, PATH: '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin' } };
  const result = spawnSync('php', ['-l', file(relative)], spawnOpts);
  if (result.error || result.status !== 0) {
    if (result.error?.code === 'ENOENT') continue; // php binary not available locally
    fail(`PHP lint failed for ${relative}: ${((result.stderr || result.stdout || '') + '').trim()}`);
  }
}

if (failures.length) {
  console.error(`FAIL: ${failures.length} staging2 deployment contract finding(s)`);
  for (const finding of failures) console.error(`- ${finding}`);
  process.exit(1);
}
console.log('DEPLOY_WORKFLOW_CONTRACT_OK');
