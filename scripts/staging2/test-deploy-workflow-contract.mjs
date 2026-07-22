#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const failures = [];
const fail = (message) => failures.push(message);
const read = (relative) => {
  const target = path.join(root, relative);
  if (!fs.existsSync(target)) { fail(`missing ${relative}`); return ''; }
  return fs.readFileSync(target, 'utf8');
};

const workflow = read('.github/workflows/deploy-staging2.yml');
const migration = read('scripts/wp/nvx-production-readiness-command.php');
const smoke = read('scripts/staging2/smoke-verify-staging2.sh');
const acceptance = read('scripts/staging2/verify-rendered-acceptance.mjs');
const visual = read('scripts/staging2/capture-visual-qa.mjs');
const integrations = read('wp-content/themes/nuvanx-medical/inc/nvx-integrations.php');
const phases = read('wp-content/themes/nuvanx-medical/inc/nvx-signature-phase-pages.php');
const claims = read('wp-content/themes/nuvanx-medical/inc/nvx-clinical-language.php');
const phaseOne = read('wp-content/themes/nuvanx-medical/inc/nvx-protocol-pages.php');
const editorialSeo = read('wp-content/themes/nuvanx-medical/inc/nvx-editorial-seo-extension.php');

const phaseSlugs = [
  'papada-definicion-mandibular-madrid', 'calidad-piel-firmeza-luminosidad-madrid',
  'cicatrices-acne-poros-textura-madrid', 'manchas-rojeces-fotorejuvenecimiento-ipl-madrid',
  'grasa-localizada-abdomen-flancos-madrid', 'flacidez-grasa-localizada-brazos-madrid',
  'grasa-espalda-zona-sujetador-madrid', 'flacidez-muslos-internos-subgluteo-madrid',
  'tratamiento-rodillas-grasa-flacidez-madrid', 'contorno-corporal-masculino-madrid',
];

for (const marker of [
  'workflow_dispatch:', 'types: [opened, synchronize, reopened, labeled]',
  "github.event.label.name == 'deploy-staging2'", "github.actor == 'Arisofia'",
  'github.event.pull_request.head.repo.full_name == github.repository',
  "inputs.confirmation == 'DEPLOY_STAGING2'", 'DEPLOY_AND_MIGRATE',
  'persist-credentials: false', 'StrictHostKeyChecking yes',
  'scripts/staging2/capture-visual-qa.mjs', 'Run real browser visual QA', 'VISUAL_QA_OK',
  'Run rendered acceptance verification', 'RENDERED_ACCEPTANCE_OK', 'actions/upload-artifact@',
]) if (!workflow.includes(marker)) fail(`workflow missing marker: ${marker}`);
for (const forbidden of ['ssh-keyscan', 'StrictHostKeyChecking no', 'persist-credentials: true', '/home/customer/www/nuvanx.com/public_html']) {
  if (workflow.includes(forbidden)) fail(`workflow contains forbidden marker: ${forbidden}`);
}

for (const marker of [
  'retire-prototypes', '--allow-production', 'LOCK_OPTION', 'apply_approved_pages',
  'apply_governed_pages', 'apply_primary_menu', 'canonical_menu_signature',
  'current_menu_signature', 'wp_update_nav_menu_item', 'set_theme_mod',
  'EMPTY_TRASH_DAYS', 'wp_trash_post',
]) if (!migration.includes(marker)) fail(`migration missing marker: ${marker}`);
for (const slug of phaseSlugs) if (!migration.includes(`'${slug}' =>`)) fail(`migration missing page: ${slug}`);

for (const marker of [
  "check_redirect '/tratamientos/' '/soluciones-medicas/'", "fetch_page '/soluciones-medicas/'",
  "fetch_page '/protocolos-signature/'", "check_redirect '/liposculpt-air/'",
  "check_redirect '/v-lift-awake/' '/protocolos-signature/'", 'SMOKE_VERIFY_OK',
]) if (!smoke.includes(marker)) fail(`smoke missing marker: ${marker}`);
for (const slug of phaseSlugs) if (!smoke.includes(`fetch_page '/${slug}/'`)) fail(`smoke missing page: ${slug}`);

for (const marker of [
  'EXPECTED_SHA must be a full lowercase 40-character SHA', '/tratamientos/',
  'Papada y mandíbula: a veces es grasa, a veces es piel.', 'Contorno corporal masculino en Madrid',
  'nvx-deploy-sha', 'noindex', 'nofollow', 'WebPage', 'Organization',
  'canonicals.length !== 1', "redirect: 'manual'", 'report.json', 'RENDERED_ACCEPTANCE_OK',
]) if (!acceptance.includes(marker)) fail(`acceptance missing marker: ${marker}`);
for (const slug of phaseSlugs) if (!acceptance.includes(`/${slug}/`)) fail(`acceptance missing page: ${slug}`);

for (const marker of [
  'Google Chrome or Chromium is not installed', 'HTTP preflight failed',
  'Page.captureScreenshot', 'captureBeyondViewport: true', 'screenshot is unexpectedly small',
  'navigation-desktop-mega.png', 'navigation-mobile-drawer.png',
  'Protocolos Signature mobile accordion toggle', 'Contour Architecture nested mobile toggle',
  'horizontal overflow', 'Escape did not close drawer', 'VISUAL_QA_OK',
]) if (!visual.includes(marker)) fail(`visual QA missing marker: ${marker}`);
for (const slug of phaseSlugs) if (!visual.includes(`/${slug}/`)) fail(`visual QA missing page: ${slug}`);

for (const marker of [
  "'liposculpt-air'", "'v-lift-awake'", "'tratamientos'",
  "require_once __DIR__ . '/nvx-signature-phase-pages.php';",
]) if (!integrations.includes(marker)) fail(`integration missing marker: ${marker}`);
for (const marker of [
  'function nvx_signature_phase1_catalog', 'function nvx_signature_phase2_catalog',
  'nvx_signature_phase1_enrichment_filter', 'nvx_signature_phase_navigation_blueprint',
  'NUVANX Contour Architecture™', 'Eye Frame', 'contorno-corporal-masculino-madrid',
]) if (!phases.includes(marker)) fail(`phase module missing marker: ${marker}`);
for (const marker of ["'profile-definition' =>", "'skin-architecture' =>", "'surface-renewal' =>", "'tone-correction' =>"]) {
  if (!phaseOne.includes(marker)) fail(`Phase 1 renderer missing marker: ${marker}`);
}
for (const marker of [
  'nvx_clinical_language_prohibited_phrases', 'Sin bisturí ni puntos',
  'Recuperación inmediata', 'Sin dolor', 'Sin riesgos', 'Resultados garantizados',
  'Generalmente 3–4 sesiones', 'Reducción del dolor', 'Eritema reducido',
]) if (!claims.includes(marker)) fail(`claims gate missing marker: ${marker}`);
for (const marker of [
  '/remodelacion-corporal-laser-madrid/',
  'Remodelación corporal láser en Madrid | NUVANX Contour Architecture',
]) if (!editorialSeo.includes(marker)) fail(`editorial SEO missing marker: ${marker}`);

for (const relative of [
  'scripts/staging2/verify-rendered-acceptance.mjs',
  'scripts/staging2/capture-visual-qa.mjs',
  'scripts/staging2/test-deploy-workflow-contract.mjs',
]) {
  const result = spawnSync(process.execPath, ['--check', path.join(root, relative)], { encoding: 'utf8' });
  if (result.status !== 0) fail(`Node syntax failed for ${relative}: ${(result.stderr || result.stdout).trim()}`);
}
for (const relative of [
  'scripts/wp/nvx-production-readiness-command.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-integrations.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-signature-phase-pages.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-clinical-language.php',
]) {
  const result = spawnSync('php', ['-l', path.join(root, relative)], { encoding: 'utf8' });
  if (result.status !== 0) fail(`PHP lint failed for ${relative}: ${(result.stderr || result.stdout).trim()}`);
}

if (failures.length) {
  console.error(`FAIL: ${failures.length} deployment contract finding(s)`);
  for (const finding of failures) console.error(`- ${finding}`);
  process.exit(1);
}
console.log('DEPLOY_WORKFLOW_CONTRACT_OK');
