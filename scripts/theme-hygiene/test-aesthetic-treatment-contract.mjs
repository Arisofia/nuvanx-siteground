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
const governanceBoilerplate = read('inc/nvx-governance-boilerplate.php');
const structuredData = read('inc/nvx-structured-data.php');
const hub = read('inc/nvx-aesthetic-medicine-page.php');
const hubGov = read('inc/nvx-aesthetic-hub-governance.php');
const nav = read('inc/nvx-navigation-filters.php');
const coherence = read('inc/nvx-site-coherence.php');
const valoracion = read('inc/nvx-valoracion-modal.php');
const smokeSh = fs.readFileSync(path.join(root, 'scripts/staging2/smoke-verify-staging2.sh'), 'utf8');
const smokeExternal = fs.readFileSync(path.join(root, 'scripts/staging2/smoke-verify-external.mjs'), 'utf8');

for (const marker of [
  'function nvxAestheticTreatmentIsRenderable(',
  'function nvxAestheticTreatmentCatalogForRender(',
  "nvx_register_catalog_content_filter( 'nvxAestheticTreatmentCatalogForRender', 80 )",
  "'pending_medical_review'",
  "nvx_aesthetic_treatment_meta_field( 'seo_title'",
  'nvx_seed_staging_pages(',
]) {
  if (!aestheticPages.includes(marker)) {
    failures.push(`aesthetic treatment pages missing gate marker: ${marker}`);
  }
}

for (const marker of [
  'function nvx_seed_staging_pages(',
  "function_exists( 'nvx_environment_is_staging2' )",
  "'_nvx_medical_review_status'",
  "update_post_meta( $page->ID, $meta_key_name, $key )",
]) {
  if (!governanceBoilerplate.includes(marker)) {
    failures.push(`governance seeder missing contract marker: ${marker}`);
  }
}

const aestheticCatalog = read('inc/data/nvx-aesthetic-treatment-catalog.json');
let parsedCatalog = {};
try {
  parsedCatalog = JSON.parse(aestheticCatalog);
} catch (e) {
  failures.push('aesthetic treatment catalog is not valid JSON');
}

if (Object.keys(parsedCatalog).length === 0) {
  failures.push('no catalogue items found in JSON');
}

for (const [key, entry] of Object.entries(parsedCatalog)) {
  if (!entry.review_status || (entry.review_status !== 'pending_medical_review' && entry.review_status !== 'approved_for_publication')) {
    failures.push(`catalogue entry '${key}' missing explicit review_status (pending_medical_review|approved_for_publication)`);
  }
}

// Dead staging page IDs must not reappear.
for (const deadId of ['3318', '3319', '3320', '3321']) {
  if (aestheticCatalog.includes(`"page_id": ${deadId}`)) {
    failures.push(`aesthetic catalogue still hardcodes dead page_id ${deadId}`);
  }
}

for (const slug of EXPECTED_SLUGS) {
  if (!aestheticCatalog.includes(`"${slug}"`)) {
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
  if (!aestheticCatalog.includes(`"${key}"`)) {
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

// The shared staging seeder owns the environment gate and pending review meta.
for (const marker of [
  'function nvx_seed_staging_pages(',
  "function_exists( 'nvx_environment_is_staging2' )",
  '! nvx_environment_is_staging2()',
  "'_nvx_medical_review_status'",
  "'pending'",
]) {
  if (!governanceBoilerplate.includes(marker)) {
    failures.push(`central staging seeder missing contract marker: ${marker}`);
  }
}

if (failures.length) {
  console.error('Aesthetic treatment contract FAILED:');
  for (const f of failures) console.error(`  - ${f}`);
  process.exit(1);
}

console.log('Aesthetic treatment contract OK');
