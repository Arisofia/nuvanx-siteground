#!/usr/bin/env node
/**
 * Contract: facial aesthetic injectable catalogue gates, slug coupling, registry.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, '../..');
const theme = path.join(root, 'wp-content/themes/nuvanx-medical');

const failures = [];
const read = (relative) => fs.readFileSync(path.join(theme, relative), 'utf8');

const EXPECTED_SLUGS = [
  'labios-acido-hialuronico-madrid',
  'rinomodelacion-sin-cirugia-madrid',
  'ojeras-surco-lagrimal-madrid',
  'bioestimuladores-colageno-madrid',
];

const EXPECTED_KEYS = ['lips_ha', 'rhinomodeling_ha', 'tear_trough_ha', 'biostimulators'];

const aestheticPages = read('inc/nvx-aesthetic-treatment-pages.php');
const structuredData = read('inc/nvx-structured-data.php');
const hub = read('inc/nvx-aesthetic-medicine-page.php');
const hubGov = read('inc/nvx-aesthetic-hub-governance.php');
const nav = read('inc/nvx-navigation-filters.php');
const coherence = read('inc/nvx-site-coherence.php');
const valoracion = read('inc/nvx-valoracion-modal.php');
const smokeSh = fs.readFileSync(path.join(root, 'scripts/staging2/smoke-verify-staging2.sh'), 'utf8');
const smokeExternal = fs.readFileSync(path.join(root, 'scripts/staging2/smoke-verify-external.mjs'), 'utf8');

for (const marker of [
  'function nvx_aesthetic_treatment_is_renderable(',
  'function nvx_aesthetic_treatment_catalog_for_render(',
  "nvx_register_catalog_content_filter( 'nvx_aesthetic_treatment_catalog_for_render', 80 )",
  "'pending_medical_review'",
  'nvx_aesthetic_treatment_meta_field( \'seo_title\'',
  'function nvx_aesthetic_treatment_seed_sync_meta(',
]) {
  if (!aestheticPages.includes(marker)) {
    failures.push(`aesthetic treatment pages missing gate marker: ${marker}`);
  }
}

// Fail-closed default: entries must declare review_status (not rely on matcher omit→approved).
const reviewStatusCount = (aestheticPages.match(/'pending_medical_review'|'approved_for_publication'/g) || []).length;
if (reviewStatusCount < 4) {
  failures.push(`expected at least 4 explicit review_status values in catalogue, found ${reviewStatusCount}`);
}

// Dead staging page IDs must not reappear.
for (const deadId of ['3318', '3319', '3320', '3321']) {
  if (new RegExp(`\\b${deadId}\\b`).test(aestheticPages)) {
    failures.push(`aesthetic catalogue still hardcodes dead page_id ${deadId}`);
  }
}

for (const slug of EXPECTED_SLUGS) {
  if (!aestheticPages.includes(`'${slug}'`)) {
    failures.push(`catalogue missing slug ${slug}`);
  }
  for (const [source, label] of [
    [hub, 'hub cards'],
    [hubGov, 'hub governance'],
    [nav, 'navigation'],
    [coherence, 'site coherence'],
    [valoracion, 'valoracion modal'],
    [smokeSh, 'smoke shell'],
    [smokeExternal, 'smoke external'],
  ]) {
    if (!source.includes(slug)) {
      failures.push(`${label} missing coupled slug ${slug}`);
    }
  }
}

for (const key of EXPECTED_KEYS) {
  if (!aestheticPages.includes(`'${key}'`)) {
    failures.push(`catalogue missing key ${key}`);
  }
  const pathSnippet = {
    lips_ha: '/labios-acido-hialuronico-madrid/',
    rhinomodeling_ha: '/rinomodelacion-sin-cirugia-madrid/',
    tear_trough_ha: '/ojeras-surco-lagrimal-madrid/',
    biostimulators: '/bioestimuladores-colageno-madrid/',
  }[key];
  if (!structuredData.includes(`'${key}'`) || !structuredData.includes(pathSnippet)) {
    failures.push(`schema registry missing path-only treatment ${key} → ${pathSnippet}`);
  }
}

// Staging2 seeder must remain environment-gated.
if (!aestheticPages.includes("function_exists( 'nvx_environment_is_staging2' )")) {
  failures.push('aesthetic seeder does not guard nvx_environment_is_staging2');
}

if (failures.length) {
  console.error('Aesthetic treatment contract FAILED:');
  for (const f of failures) console.error(`  - ${f}`);
  process.exit(1);
}

console.log('Aesthetic treatment contract OK');
console.log(`  slugs=${EXPECTED_SLUGS.length} keys=${EXPECTED_KEYS.length} review_status_tokens>=4`);
