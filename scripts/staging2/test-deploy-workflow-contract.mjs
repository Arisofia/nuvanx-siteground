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
const deploy = read('tools/deploy/deploy-to-staging2.sh');
const migration = read('scripts/wp/nvx-production-readiness-command.php');
const smoke = read('scripts/staging2/smoke-verify-staging2.sh');
const acceptance = read('scripts/staging2/verify-rendered-acceptance.mjs');
const visual = read('scripts/staging2/capture-visual-qa.mjs');
const integrations = read('wp-content/themes/nuvanx-medical/inc/nvx-integrations.php');
const phasePages = read('wp-content/themes/nuvanx-medical/inc/nvx-signature-phase-pages.php');
const claims = read('wp-content/themes/nuvanx-medical/inc/nvx-clinical-language.php');
const protocolPages = read('wp-content/themes/nuvanx-medical/inc/nvx-protocol-pages.php');
const editorialSeo = read('wp-content/themes/nuvanx-medical/inc/nvx-editorial-seo-extension.php');

const governedSlugs = [
  'papada-definicion-mandibular-madrid',
  'calidad-piel-firmeza-luminosidad-madrid',
  'cicatrices-acne-poros-textura-madrid',
  'manchas-rojeces-fotorejuvenecimiento-ipl-madrid',
  'tratamiento-ojeras-bolsas-mirada-madrid',
  'grasa-localizada-abdomen-flancos-madrid',
  'flacidez-grasa-localizada-brazos-madrid',
  'grasa-espalda-zona-sujetador-madrid',
  'flacidez-muslos-internos-subgluteo-madrid',
  'tratamiento-rodillas-grasa-flacidez-madrid',
  'contorno-corporal-masculino-madrid',
];

for (const marker of [
  'workflow_dispatch:',
  'types: [opened, synchronize, reopened, labeled]',
  "github.event.label.name == 'deploy-staging2'",
  "github.actor == 'Arisofia'",
  'github.event.pull_request.head.repo.full_name == github.repository',
  "inputs.confirmation == 'DEPLOY_STAGING2'",
  'DEPLOY_AND_MIGRATE',
  'persist-credentials: false',
  'StrictHostKeyChecking yes',
  'Run rendered acceptance verification',
  'RENDERED_ACCEPTANCE_OK',
  'Run real browser visual QA',
  'VISUAL_QA_OK',
  'actions/upload-artifact@',
]) if (!workflow.includes(marker)) fail(`workflow missing marker: ${marker}`);
for (const forbidden of ['ssh-keyscan', 'StrictHostKeyChecking no', 'persist-credentials: true', '/home/customer/www/nuvanx.com/public_html']) {
  if (workflow.includes(forbidden)) fail(`workflow contains forbidden marker: ${forbidden}`);
}

for (const marker of [
  "EXPECTED_ROOT='/home/customer/www/staging2.nuvanx.com/public_html'",
  "BACKUP_ROOT='/home/customer/backups-nuvanx/staging2'",
  'wp db export', 'wp db import',
  'nvx production-readiness audit --allow-pending',
  'nvx production-readiness apply --confirm=retire-prototypes',
  'ROLLBACK_COMPLETE', 'DEPLOY_STAGING2_OK',
]) if (!deploy.includes(marker)) fail(`deploy script missing marker: ${marker}`);

for (const marker of [
  'retire-prototypes', '--allow-production', 'LOCK_OPTION',
  'apply_approved_pages', 'apply_governed_pages', 'apply_primary_menu',
  'canonical_menu_signature', 'current_menu_signature',
  'wp_update_nav_menu_item', 'set_theme_mod',
  'EMPTY_TRASH_DAYS', 'wp_trash_post',
]) if (!migration.includes(marker)) fail(`migration missing marker: ${marker}`);
for (const slug of governedSlugs) if (!migration.includes(`'${slug}'`)) fail(`migration missing page: ${slug}`);
if (/['"]post_status['"]\s*=>\s*['"]trash['"]/.test(migration)) fail('migration uses direct trash status update');

for (const marker of [
  "check_redirect '/tratamientos/' '/soluciones-medicas/'",
  "fetch_page '/soluciones-medicas/'",
  "fetch_page '/protocolos-signature/'",
  "check_redirect '/liposculpt-air/' '/remodelacion-corporal-laser-madrid/'",
  "check_redirect '/v-lift-awake/' '/protocolos-signature/'",
  'SMOKE_VERIFY_OK',
]) if (!smoke.includes(marker)) fail(`smoke missing marker: ${marker}`);
for (const slug of governedSlugs) if (!smoke.includes(`fetch_page '/${slug}/'`)) fail(`smoke missing page: ${slug}`);

for (const marker of [
  'EXPECTED_SHA must be a full lowercase 40-character SHA',
  '/tratamientos/',
  'No todas las ojeras son iguales. Por eso no todas se tratan igual.',
  'Contorno corporal masculino en Madrid',
  'nvx-deploy-sha', 'noindex', 'nofollow',
  'WebPage', 'Organization', 'canonicals.length !== 1',
  "redirect: 'manual'", 'report.json', 'RENDERED_ACCEPTANCE_OK',
]) if (!acceptance.includes(marker)) fail(`acceptance missing marker: ${marker}`);
for (const slug of governedSlugs) if (!acceptance.includes(`/${slug}/`)) fail(`acceptance missing page: ${slug}`);

for (const marker of [
  'Google Chrome or Chromium is not installed',
  'HTTP preflight failed',
  'Page.captureScreenshot',
  'captureBeyondViewport: true',
  'screenshot is unexpectedly small',
  'navigation-desktop-mega.png',
  'navigation-mobile-drawer.png',
  'Protocolos Signature mobile accordion toggle',
  'Contour Architecture nested mobile toggle',
  'horizontal overflow',
  'Escape did not close drawer',
  'VISUAL_QA_OK',
]) if (!visual.includes(marker)) fail(`visual QA missing marker: ${marker}`);
for (const slug of governedSlugs) if (!visual.includes(`/${slug}/`)) fail(`visual QA missing page: ${slug}`);

for (const marker of [
  "'liposculpt-air'", "'v-lift-awake'", "'tratamientos'",
  "require_once __DIR__ . '/nvx-signature-phase-pages.php';",
]) if (!integrations.includes(marker)) fail(`integration missing marker: ${marker}`);
for (const marker of [
  'function nvx_signature_phase2_catalog',
  'nvx_signature_navigation_blueprint',
  'NUVANX Contour Architecture™',
  'contorno-corporal-masculino-madrid',
  'nvx_signature_seo_title',
]) if (!phasePages.includes(marker)) fail(`phase module missing marker: ${marker}`);
for (const marker of [
  "'profile-definition' =>", "'skin-architecture' =>", "'surface-renewal' =>",
  "'tone-correction' =>", "'eye-frame' =>",
]) if (!protocolPages.includes(marker)) fail(`protocol catalogue missing marker: ${marker}`);

for (const marker of [
  'nvx_clinical_language_prohibited_phrases',
  'Sin bisturí ni puntos', 'Recuperación inmediata',
  'Sin dolor', 'Sin riesgos', 'Resultados garantizados',
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
  const spawnOpts = process.platform === 'win32' 
    ? { encoding: 'utf8' } 
    : { encoding: 'utf8', env: { ...process.env, PATH: '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin' } };
  const result = spawnSync('php', ['-l', path.join(root, relative)], spawnOpts);
  if (result.error || result.status !== 0) {
    if (result.error?.code === 'ENOENT') continue; // php binary not available locally
    fail(`PHP lint failed for ${relative}: ${((result.stderr || result.stdout || '') + '').trim()}`);
  }
}

if (failures.length) {
  console.error(`FAIL: ${failures.length} deployment contract finding(s)`);
  for (const finding of failures) console.error(`- ${finding}`);
  process.exit(1);
}
console.log('DEPLOY_WORKFLOW_CONTRACT_OK');
