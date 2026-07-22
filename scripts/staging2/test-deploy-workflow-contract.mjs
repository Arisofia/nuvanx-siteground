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
const portfolio = read('wp-content/themes/nuvanx-medical/inc/nvx-portfolio-hub.php');
const protocolHub = read('wp-content/themes/nuvanx-medical/inc/nvx-protocol-hub.php');
const protocolPages = read('wp-content/themes/nuvanx-medical/inc/nvx-protocol-pages.php');
const strategyPages = read('wp-content/themes/nuvanx-medical/inc/nvx-strategy-pages.php');
const seoMetadata = read('wp-content/themes/nuvanx-medical/inc/nvx-seo-metadata.php');
const editorialSeo = read('wp-content/themes/nuvanx-medical/inc/nvx-editorial-seo-extension.php');

const nativeStyle = read('wp-content/themes/nuvanx-medical/inc/nvx-native-style-governance.php');
const pageShell = read('wp-content/themes/nuvanx-medical/template-parts/content/nvx-page-shell.php');
const externalVisualClosure = read('wp-content/themes/nuvanx-medical/inc/nvx-external-visual-closure.php');
const controlledPublicContent = [portfolio, protocolHub, protocolPages, strategyPages].join('\n');

for (const marker of [
  'workflow_dispatch:', "inputs.confirmation == 'DEPLOY_STAGING2'", 'PREFLIGHT_ONLY', 'DEPLOY_AND_MIGRATE', 'SMOKE_ONLY',
  'environment:', 'name: staging2', 'persist-credentials: false', 'StrictHostKeyChecking yes', 'STAGING2_SSH_KNOWN_HOSTS',
  'git_sha must equal the selected workflow ref HEAD', 'ref: ${{ github.sha }}',
  'scripts/staging2/collect-staging2-diagnostics.sh', 'scripts/wp/nvx-production-readiness-command.php',
  'scripts/staging2/smoke-verify-staging2.sh', 'scripts/staging2/verify-rendered-acceptance.mjs',
  'Run rendered acceptance verification', 'EXPECTED_SHA: ${{ inputs.git_sha }}', 'RENDERED_ACCEPTANCE_OK',
  'staging2-deployment-evidence', 'actions/upload-artifact@', 'ssh-debug.log', 'preflight.log', 'remote-deploy.log', 'independent-smoke.log',
]) if (!workflow.includes(marker)) fail(`workflow missing contract marker: ${marker}`);
for (const forbidden of ['ssh-keyscan', 'StrictHostKeyChecking no', 'persist-credentials: true', '/home/customer/www/nuvanx.com/public_html', "github.ref == 'refs/heads/master'"]) {
  if (workflow.includes(forbidden)) fail(`workflow contains forbidden marker: ${forbidden}`);
}

for (const marker of [
  "EXPECTED_ROOT='/home/customer/www/staging2.nuvanx.com/public_html'", "EXPECTED_URL='https://staging2.nuvanx.com'",
  "BACKUP_ROOT='/home/customer/backups-nuvanx/staging2'", '--migration-script', '--smoke-script', 'wp db export', 'wp db import',
  'nvx production-readiness audit --allow-pending', 'nvx production-readiness apply --confirm=retire-prototypes',
  'nvx production-readiness audit', 'SMOKE_VERIFY_OK', 'ROLLBACK_COMPLETE', 'DEPLOY_STAGING2_OK',
]) if (!deploy.includes(marker)) fail(`deploy script missing contract marker: ${marker}`);
for (const forbidden of ['/home/customer/www/nuvanx.com/public_html', 'ssh-keyscan', 'BACKUP_DIR="$WP_ROOT/wp-content/']) {
  if (deploy.includes(forbidden)) fail(`deploy script contains forbidden marker: ${forbidden}`);
}

for (const marker of [
  "EXPECTED_ROOT='/home/customer/www/staging2.nuvanx.com/public_html'", "EXPECTED_URL='https://staging2.nuvanx.com'",
  "BACKUP_ROOT='/home/customer/backups-nuvanx/staging2'", 'command.$command_name=available', 'wordpress.siteurl=',
  'wordpress.home=', 'wordpress.active_theme=', 'wordpress.empty_trash_days=', 'wordpress.db_check=',
  'path.backup_root_status=', 'path.deployment_root_status=', 'http.home_status=', 'STAGING2_PREFLIGHT_OK',
]) if (!diagnostics.includes(marker)) fail(`diagnostics missing contract marker: ${marker}`);
for (const forbidden of ['mkdir -p', 'rm -rf', 'wp db import', 'wp option update', 'wp rewrite flush']) {
  if (diagnostics.includes(forbidden)) fail(`diagnostics contains mutating marker: ${forbidden}`);
}

for (const marker of [
  'retire-prototypes', "'allow-pending'", '--allow-production', 'LOCK_OPTION', 'staging2.nuvanx.com',
  'nvx_production_readiness_governed_pages', 'validate_invocation', 'apply_approved_pages', 'apply_governed_pages',
  'EMPTY_TRASH_DAYS', 'wp_trash_post', "WP_CLI::add_command( 'nvx production-readiness'",
  "'soluciones-medicas' =>", "'tratamiento-postparto-abdomen-contorno-corporal-madrid' =>", "'promote_draft' => true",
]) if (!migration.includes(marker)) fail(`migration missing contract marker: ${marker}`);
if (/['"]post_status['"]\s*=>\s*['"]trash['"]/.test(migration)) fail('migration uses direct trash status update');

for (const marker of [
  "check_redirect '/tratamientos/' '/soluciones-medicas/'", "fetch_page '/soluciones-medicas/'", "fetch_page '/protocolos-signature/'",
  "fetch_page '/remodelacion-corporal-laser-madrid/'", "fetch_page '/tratamiento-postparto-abdomen-contorno-corporal-madrid/'",
  "fetch_page '/por-que-nuvanx/'", "fetch_page '/inversion-medicina-estetica/'",
  "check_redirect '/liposculpt-air/'", "check_redirect '/v-lift-awake/' '/protocolos-signature/'", 'SMOKE_VERIFY_OK',
]) if (!smoke.includes(marker)) fail(`smoke script missing contract marker: ${marker}`);
if (smoke.includes("check_redirect '/tratamiento-postparto-abdomen-contorno-corporal-madrid/'")) fail('smoke still treats Post-Maternity as redirect');

for (const marker of [
  "'https://staging2.nuvanx.com'", 'EXPECTED_SHA must be a full lowercase 40-character SHA',
  '/soluciones-medicas/', '/protocolos-signature/', '/remodelacion-corporal-laser-madrid/',
  '/tratamiento-postparto-abdomen-contorno-corporal-madrid/', '/por-que-nuvanx/', '/inversion-medicina-estetica/',
  'Soluciones médicas para rostro y cuerpo | NUVANX Madrid', 'Tratamiento postparto abdomen Madrid | NUVANX',
  'Soluciones médicas para rostro, piel y contorno corporal.',
  'Protocolos Signature: Medicina estética de diagnóstico.', 'Remodelación corporal láser diseñada según tu anatomía.',
  'Tratamiento Postparto: Abdomen y Contorno Corporal en Madrid', 'Por qué NUVANX. Sin retórica de marketing.',
  'El presupuesto forma parte de una decisión informada.', 'expected_markers', 'h2_count',
  'nvx-deploy-sha', 'noindex', 'nofollow', 'ItemList', 'Organization', 'allowedCanonicalTargets',
  '/liposculpt-air/', "['/v-lift-awake/', '/protocolos-signature/']", "redirect: 'manual'", 'report.json', 'RENDERED_ACCEPTANCE_OK',
]) if (!acceptance.includes(marker)) fail(`rendered acceptance missing contract marker: ${marker}`);
if (/redirects\s*=\s*\[[\s\S]*tratamiento-postparto-abdomen-contorno-corporal-madrid/.test(acceptance)) fail('rendered acceptance still redirects Post-Maternity');
for (const forbidden of ['https://nuvanx.com/wp-admin', 'wp option update', 'wp post update', 'wp db import', 'DELETE ']) {
  if (acceptance.includes(forbidden)) fail(`rendered acceptance contains mutating marker: ${forbidden}`);
}

for (const marker of ["'liposculpt-air'", "'v-lift-awake'", "'target' => '/protocolos-signature/'"]) {
  if (!integrations.includes(marker)) fail(`governed redirects missing marker: ${marker}`);
}
if (integrations.includes("'tratamiento-postparto-abdomen-contorno-corporal-madrid' =>")) fail('integrations still governs published Post-Maternity');
if (!integrations.includes("require_once __DIR__ . '/nvx-editorial-seo-extension.php';")) fail('integrations missing editorial SEO extension');

for (const marker of [
  'Contorno Corporal y Posgestacional', 'Post-Maternity Contour™', '/tratamiento-postparto-abdomen-contorno-corporal-madrid/',
  "'post-maternity' =>", 'nvx_protocol_pages_post_maternity_markup', 'Preguntas frecuentes',
]) if (!(protocolHub + protocolPages).includes(marker)) fail(`protocol content missing parity marker: ${marker}`);

for (const marker of [
  "'solutions' =>", 'Soluciones médicas para rostro, piel y contorno corporal.', 'Valoración de procedimientos previos',
  'Por qué NUVANX. Sin retórica de marketing.', 'Responsabilidad médica y continuidad asistencial',
  'Qué incluye siempre el plan en NUVANX', 'Qué no encontrarás aquí', 'Una promoción puntual no modifica la indicación',
]) if (!strategyPages.includes(marker)) fail(`strategy content missing parity marker: ${marker}`);



for (const marker of [
  '/soluciones-medicas/', 'Soluciones médicas para rostro y cuerpo | NUVANX Madrid',
  '/tratamiento-postparto-abdomen-contorno-corporal-madrid/', 'Tratamiento postparto abdomen Madrid | NUVANX',
  'nvx_editorial_seo_title', 'nvx_editorial_seo_description', 'nvx_editorial_seo_url',
]) if (!editorialSeo.includes(marker)) fail(`editorial SEO missing marker: ${marker}`);
for (const marker of ["'protocolos_signature'", "'contour_sculpt'", 'Protocolos Signature | NUVANX Madrid', 'Remodelación corporal láser Madrid | NUVANX']) {
  if (!seoMetadata.includes(marker)) fail(`canonical SEO metadata missing marker: ${marker}`);
}


for (const marker of ["function_exists( 'nvx_strategy_current_page_key' )", "function_exists( 'nvx_content_is_protocol_hub' )", "function_exists( 'nvx_protocol_pages_current_key' )"]) {
  if (!pageShell.includes(marker)) fail(`managed shell missing marker: ${marker}`);
}

if (!/\.nvx-hub-hero__title\s*\{[^}]*color:\s*var\(--nvx-light\);/s.test(externalVisualClosure)) fail('visual closure missing visible treatments H1 contract');
for (const marker of ['.nvx-strategy-page .nvx-endolift-price-table tbody tr', 'grid-template-columns: minmax(0, 1fr) auto;', 'overflow-wrap: anywhere;', 'content: "PVP con IVA";']) {
  if (!externalVisualClosure.includes(marker)) fail(`visual closure missing responsive tariff marker: ${marker}`);
}

for (const marker of ['objetivos realistas', 'según diagnóstico y fototipo', 'Protocolo anestésico cuando corresponde', 'justificación clínica documentada']) {
  if (!controlledPublicContent.includes(marker)) fail(`controlled public content missing governance marker: ${marker}`);
}
for (const forbidden of [
  'garantizar resultados', 'asegurar que cada intervención', 'control térmico absoluto', 'sin huellas quirúrgicas evidentes',
  'presupuesto muy bajo', 'no usamos descuentos estacionales', 'este procedimiento no es habitual en el sector',
  'el estándar de oro', 'renovación epidérmica severa', 'absoluta discreción', 'protocolo comercial estrella',
]) if (controlledPublicContent.toLowerCase().includes(forbidden.toLowerCase())) fail(`controlled public content contains forbidden claim: ${forbidden}`);

const phpFiles = [
  'scripts/wp/nvx-production-readiness-command.php',

  'wp-content/themes/nuvanx-medical/inc/nvx-integrations.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-editorial-seo-extension.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-protocol-hub.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-protocol-pages.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-strategy-pages.php',
];
for (const relative of phpFiles) {
  const result = spawnSync('php', ['-l', file(relative)], { encoding: 'utf8' });
  if (result.error || result.status !== 0) {
    if (result.error && result.error.code === 'ENOENT') continue; // php binary not available locally
    fail(`PHP lint failed for ${relative}: ${((result.stderr || result.stdout || '') + '').trim()}`);
  }
}

if (failures.length) {
  console.error(`FAIL: ${failures.length} staging2 deployment contract finding(s)`);
  for (const finding of failures) console.error(`- ${finding}`);
  process.exit(1);
}
console.log('DEPLOY_WORKFLOW_CONTRACT_OK');
