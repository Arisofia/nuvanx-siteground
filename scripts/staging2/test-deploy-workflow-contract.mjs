#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const workflowPath = path.join(root, '.github/workflows/deploy-staging2.yml');
const deployPath = path.join(root, 'tools/deploy/deploy-to-staging2.sh');
const diagnosticsPath = path.join(root, 'scripts/staging2/collect-staging2-diagnostics.sh');
const migrationPath = path.join(root, 'scripts/wp/nvx-production-readiness-command.php');
const smokePath = path.join(root, 'scripts/staging2/smoke-verify-staging2.sh');
const renderedAcceptancePath = path.join(root, 'scripts/staging2/verify-rendered-acceptance.mjs');
const seoMetadataPath = path.join(root, 'wp-content/themes/nuvanx-medical/inc/nvx-seo-metadata.php');
const portfolioPath = path.join(root, 'wp-content/themes/nuvanx-medical/inc/nvx-portfolio-hub.php');
const protocolHubPath = path.join(root, 'wp-content/themes/nuvanx-medical/inc/nvx-protocol-hub.php');
const protocolPagesPath = path.join(root, 'wp-content/themes/nuvanx-medical/inc/nvx-protocol-pages.php');
const strategyPagesPath = path.join(root, 'wp-content/themes/nuvanx-medical/inc/nvx-strategy-pages.php');
const nativeStylePath = path.join(root, 'wp-content/themes/nuvanx-medical/inc/nvx-native-style-governance.php');
const integrationsPath = path.join(root, 'wp-content/themes/nuvanx-medical/inc/nvx-integrations.php');
const pageShellPath = path.join(root, 'wp-content/themes/nuvanx-medical/template-parts/content/nvx-page-shell.php');
const externalVisualClosurePath = path.join(root, 'wp-content/themes/nuvanx-medical/inc/nvx-external-visual-closure.php');
const failures = [];

/**
 * Records a contract validation failure.
 * @param {string} message - The failure message to record.
 */
function fail(message) {
  failures.push(message);
}

function read(file) {
  if (!fs.existsSync(file)) {
    fail(`missing ${path.relative(root, file)}`);
    return '';
  }
  return fs.readFileSync(file, 'utf8');
}

const workflow = read(workflowPath);
const deploy = read(deployPath);
const diagnostics = read(diagnosticsPath);
const migration = read(migrationPath);
const smoke = read(smokePath);
const renderedAcceptance = read(renderedAcceptancePath);
const seoMetadata = read(seoMetadataPath);
const portfolio = read(portfolioPath);
const protocolHub = read(protocolHubPath);
const protocolPages = read(protocolPagesPath);
const strategyPages = read(strategyPagesPath);
const nativeStyle = read(nativeStylePath);
const integrations = read(integrationsPath);
const pageShell = read(pageShellPath);
const externalVisualClosure = read(externalVisualClosurePath);
const controlledPublicContent = [portfolio, protocolHub, protocolPages, strategyPages].join('\n');

for (const marker of [
  'workflow_dispatch:',
  "inputs.confirmation == 'DEPLOY_STAGING2'",
  'PREFLIGHT_ONLY',
  'DEPLOY_AND_MIGRATE',
  'SMOKE_ONLY',
  'environment:',
  'name: staging2',
  'persist-credentials: false',
  'StrictHostKeyChecking yes',
  'STAGING2_SSH_KNOWN_HOSTS',
  'git_sha must equal the selected workflow ref HEAD',
  'ref: ${{ github.sha }}',
  'scripts/staging2/collect-staging2-diagnostics.sh',
  '< scripts/staging2/collect-staging2-diagnostics.sh',
  'scripts/wp/nvx-production-readiness-command.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-seo-metadata.php',
  'php -l wp-content/themes/nuvanx-medical/inc/nvx-seo-metadata.php',
  'scripts/staging2/smoke-verify-staging2.sh',
  'scripts/staging2/verify-rendered-acceptance.mjs',
  'node --check scripts/staging2/verify-rendered-acceptance.mjs',
  'Run rendered acceptance verification',
  'EXPECTED_SHA: ${{ inputs.git_sha }}',
  'RENDERED_ACCEPTANCE_OK',
  'rendered-acceptance.log',
  'staging2-deployment-evidence',
  'actions/upload-artifact@',
  'ssh-debug.log',
  'preflight.log',
  'remote-deploy.log',
  'independent-smoke.log',
  '--migration-script',
  '--smoke-script',
]) {
  if (!workflow.includes(marker)) fail(`workflow missing contract marker: ${marker}`);
}

for (const forbidden of [
  'ssh-keyscan',
  'StrictHostKeyChecking no',
  'persist-credentials: true',
  'NUVANX_CONFIRM=yes bash tools/deploy/deploy-to-prod.sh',
  '/home/customer/www/nuvanx.com/public_html',
  "github.ref == 'refs/heads/master'",
  'git merge-base --is-ancestor "$DEPLOY_SHA" origin/master',
  'bash scripts/staging2/collect-staging2-diagnostics.sh --wp-root "$WP_ROOT" \\\n            | ssh',
]) {
  if (workflow.includes(forbidden)) fail(`workflow contains forbidden marker: ${forbidden}`);
}

for (const marker of [
  "EXPECTED_ROOT='/home/customer/www/staging2.nuvanx.com/public_html'",
  "EXPECTED_URL='https://staging2.nuvanx.com'",
  "BACKUP_ROOT='/home/customer/backups-nuvanx/staging2'",
  '--migration-script',
  '--smoke-script',
  'wp db export',
  'wp db import',
  'nvx production-readiness audit --allow-pending',
  'nvx production-readiness apply --confirm=retire-prototypes',
  'nvx production-readiness audit',
  'SMOKE_VERIFY_OK',
  'ROLLBACK_COMPLETE',
  'DEPLOY_STAGING2_OK',
]) {
  if (!deploy.includes(marker)) fail(`deploy script missing contract marker: ${marker}`);
}

for (const forbidden of [
  '/home/customer/www/nuvanx.com/public_html',
  'ssh-keyscan',
  'BACKUP_DIR="$WP_ROOT/wp-content/',
]) {
  if (deploy.includes(forbidden)) fail(`deploy script contains forbidden marker: ${forbidden}`);
}

for (const marker of [
  "EXPECTED_ROOT='/home/customer/www/staging2.nuvanx.com/public_html'",
  "EXPECTED_URL='https://staging2.nuvanx.com'",
  "BACKUP_ROOT='/home/customer/backups-nuvanx/staging2'",
  'command.$command_name=available',
  'wordpress.siteurl=',
  'wordpress.home=',
  'wordpress.active_theme=',
  'wordpress.empty_trash_days=',
  'wordpress.db_check=',
  'path.backup_root_status=',
  'path.deployment_root_status=',
  'http.home_status=',
  'STAGING2_PREFLIGHT_OK',
]) {
  if (!diagnostics.includes(marker)) fail(`diagnostics missing contract marker: ${marker}`);
}
for (const forbidden of ['mkdir -p', 'rm -rf', 'wp db import', 'wp option update', 'wp rewrite flush']) {
  if (diagnostics.includes(forbidden)) fail(`diagnostics contains mutating marker: ${forbidden}`);
}

for (const marker of [
  'retire-prototypes',
  '--allow-pending',
  '--allow-production',
  'LOCK_OPTION',
  'staging2.nuvanx.com',
  'nvx_production_readiness_governed_pages',
  'validate_invocation',
  'apply_approved_pages',
  'apply_governed_pages',
  'EMPTY_TRASH_DAYS',
  'wp_trash_post',
  "WP_CLI::add_command( 'nvx production-readiness'",
]) {
  if (!migration.includes(marker)) fail(`migration missing contract marker: ${marker}`);
}
if (/['"]post_status['"]\s*=>\s*['"]trash['"]/.test(migration)) fail('migration uses direct trash status update');

for (const marker of [
  "fetch_page '/tratamientos/'",
  "fetch_page '/protocolos-signature/'",
  "fetch_page '/remodelacion-corporal-laser-madrid/'",
  "fetch_page '/por-que-nuvanx/'",
  "fetch_page '/inversion-medicina-estetica/'",
  "check_redirect '/liposculpt-air/'",
  "check_redirect '/v-lift-awake/' '/protocolos-signature/'",
  "check_redirect '/tratamiento-postparto-abdomen-contorno-corporal-madrid/'",
  'SMOKE_VERIFY_OK',
]) {
  if (!smoke.includes(marker)) fail(`smoke script missing contract marker: ${marker}`);
}

for (const marker of [
  "'https://staging2.nuvanx.com'",
  'EXPECTED_SHA must be a full lowercase 40-character SHA',
  '/tratamientos/',
  '/protocolos-signature/',
  '/remodelacion-corporal-laser-madrid/',
  '/por-que-nuvanx/',
  '/inversion-medicina-estetica/',
  'Tratamientos Medicina Estética Láser Madrid | NUVANX',
  'Protocolos Signature | NUVANX Madrid',
  'Remodelación corporal láser Madrid | NUVANX',
  'Por qué NUVANX | Criterio médico en Madrid',
  'Inversión en medicina estética | NUVANX Madrid',
  'expected_title',
  'expected_description',
  'og_title',
  'og_description',
  'result.title !== page.title',
  'result.description !== page.description',
  'result.og_title !== page.title',
  'result.og_description !== page.description',
  'Portafolio clínico.',
  'Protocolos Signature: Medicina estética de diagnóstico.',
  'Remodelación corporal láser diseñada según tu anatomía.',
  'El diagnóstico precede a la indicación.',
  'El presupuesto forma parte de una decisión informada.',
  'nvx-deploy-sha',
  'noindex',
  'nofollow',
  'ItemList',
  'Organization',
  'allowedCanonicalTargets',
  'seoTargets',
  'canonical or og:url is absent',
  'https://www.nuvanx.com',
  '/liposculpt-air/',
  "['/v-lift-awake/', '/protocolos-signature/']",
  '/tratamiento-postparto-abdomen-contorno-corporal-madrid/',
  "redirect: 'manual'",
  'response.status !== 301',
  'targetResponse.status !== 200',
  'report.json',
  'RENDERED_ACCEPTANCE_OK',
]) {
  if (!renderedAcceptance.includes(marker)) fail(`rendered acceptance missing contract marker: ${marker}`);
}
for (const forbidden of [
  'https://nuvanx.com/wp-admin',
  'wp option update',
  'wp post update',
  'wp db import',
  'DELETE ',
]) {
  if (renderedAcceptance.includes(forbidden)) fail(`rendered acceptance contains mutating marker: ${forbidden}`);
}

for (const marker of [
  "is_page_template( 'page-tratamientos.php' )",
  "'tratamientos' === (string) get_post_field( 'post_name', get_queried_object_id() )",
]) {
  if (!nativeStyle.includes(marker)) fail(`treatments hub detection missing contract marker: ${marker}`);
}

for (const marker of [
  "function_exists( 'nvx_content_is_protocol_hub' )",
  "function_exists( 'nvx_protocol_pages_current_key' )",
]) {
  if (!pageShell.includes(marker)) fail(`managed protocol shell missing contract marker: ${marker}`);
}

if (!/\.nvx-hub-hero__title\s*\{[^}]*color:\s*var\(--nvx-light\);/s.test(externalVisualClosure)) {
  fail('visual closure missing visible treatments hero H1 contract');
}

for (const marker of [
  '.nvx-strategy-page:not(.nvx-shell)',
  '.nvx-strategy-page:not(.nvx-shell) > .nvx-strategy-intro',
  'padding-inline: var(--nvx-gutter-inner);',
  '.nvx-strategy-page .nvx-endolift-price-table tbody tr',
  'grid-template-columns: minmax(0, 1fr) auto;',
  'overflow-wrap: anywhere;',
  'content: "PVP con IVA";',
]) {
  if (!externalVisualClosure.includes(marker)) fail(`visual closure missing staging2 QA marker: ${marker}`);
}

for (const marker of [
  "'v-lift-awake'",
  "'target' => '/protocolos-signature/'",
]) {
  if (!integrations.includes(marker)) fail(`governed V-Lift redirect missing contract marker: ${marker}`);
}

for (const marker of [
  "'protocolos_signature'",
  "'couture_sculpt'",
  "'/protocolos-signature/' => 'protocolos_signature'",
  "'/remodelacion-corporal-laser-madrid/' => 'couture_sculpt'",
  'Protocolos Signature | NUVANX Madrid',
  'Remodelación corporal láser Madrid | NUVANX',
  'Protocolos Signature de medicina estética en Madrid',
  'Remodelación corporal láser en Madrid por unidades anatómicas',
]) {
  if (!seoMetadata.includes(marker)) fail(`SEO metadata missing Signature marker: ${marker}`);
}

for (const marker of [
  'expectativas realistas',
  'según diagnóstico y fototipo',
  'protocolo anestésico cuando corresponda',
  'Una promoción puntual no modifica la indicación',
  'justificación clínica documentada',
]) {
  if (!controlledPublicContent.includes(marker)) fail(`controlled public content missing governance marker: ${marker}`);
}

for (const forbidden of [
  'garantizar resultados',
  'asegurar que cada intervención',
  'control térmico absoluto',
  'sin huellas quirúrgicas evidentes',
  'presupuesto muy bajo',
  'no usamos descuentos estacionales',
  'este procedimiento no es habitual en el sector',
  'el estándar de oro',
  'renovación epidérmica severa',
  'absoluta discreción',
  'protocolo comercial estrella',
]) {
  if (controlledPublicContent.toLowerCase().includes(forbidden.toLowerCase())) {
    fail(`controlled public content contains forbidden claim: ${forbidden}`);
  }
}

if (failures.length) {
  console.error(`FAIL: ${failures.length} staging2 deployment contract finding(s)`);
  for (const finding of failures) console.error(`- ${finding}`);
  process.exit(1);
}

console.log('DEPLOY_WORKFLOW_CONTRACT_OK');
