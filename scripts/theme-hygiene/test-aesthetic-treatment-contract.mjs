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
  'function nvxAestheticTreatmentIsRenderable(',
  'function nvxAestheticTreatmentCatalogForRender(',
  "nvx_register_catalog_content_filter( 'nvxAestheticTreatmentCatalogForRender', 80 )",
  "'pending_medical_review'",
  "nvx_aesthetic_treatment_meta_field( 'seo_title'",
  'function nvxAestheticTreatmentSeedSyncMeta(',
]) {
  if (!aestheticPages.includes(marker)) {
    failures.push(`aesthetic treatment pages missing gate marker: ${marker}`);
  }
}

// Fail-closed: every catalogue entry must declare an explicit trailing review_status.
// Do not rely on nvx_match_catalog_page's omit→approved default.
// Linear string scans only (avoid super-linear regex backtracking on large PHP sources).
if (!aestheticPages.includes("'review_status'") || !aestheticPages.includes('$review_status')) {
  failures.push("nvx_aesthetic_treatment_entry must store 'review_status' => $review_status");
}

const entryNeedle = '=> nvx_aesthetic_treatment_entry(';
const catalogEntries = [];
let searchFrom = 0;
while (searchFrom < aestheticPages.length) {
  const callAt = aestheticPages.indexOf(entryNeedle, searchFrom);
  if (callAt < 0) {
    break;
  }
  const before = aestheticPages.slice(Math.max(0, callAt - 64), callAt);
  const keyStart = before.lastIndexOf("'");
  const keyEnd = keyStart > 0 ? before.lastIndexOf("'", keyStart - 1) : -1;
  const key = keyEnd >= 0 ? before.slice(keyEnd + 1, keyStart) : '';
  if (key && /^[a-z0-9_]+$/.test(key)) {
    catalogEntries.push({ key, index: callAt });
  }
  searchFrom = callAt + entryNeedle.length;
}
if (catalogEntries.length === 0) {
  failures.push('no nvx_aesthetic_treatment_entry catalogue items found');
}
const gateEnd = aestheticPages.indexOf('function nvxAestheticTreatmentIsRenderable');
for (let i = 0; i < catalogEntries.length; i += 1) {
  const { key, index: start } = catalogEntries[i];
  let end;
  if (i + 1 < catalogEntries.length) {
    // For every entry except the last: scan up to the next entry's opening.
    end = catalogEntries[i + 1].index;
  } else {
    // For the last entry: the closing paren + review_status are immediately
    // after the entry call, not near gateStart (which may be thousands of
    // chars away). Find the actual closing of this entry call so the 240-char
    // tail captures the review_status correctly.
    const entryBodyEnd = aestheticPages.indexOf('\n\t);\n', start);
    end = entryBodyEnd > start ? entryBodyEnd + 4 : (gateEnd > start ? gateEnd : aestheticPages.length);
  }
  // Status is always the last argument of the entry call — check a fixed tail window.
  const tail = aestheticPages.slice(Math.max(start, end - 240), end);
  const hasStatus = tail.includes("'pending_medical_review'") || tail.includes("'approved_for_publication'");
  if (!hasStatus) {
    failures.push(`catalogue entry '${key}' missing explicit trailing review_status (pending_medical_review|approved_for_publication)`);
  }
}

// Dead staging page IDs must not reappear.
for (const deadId of ['3318', '3319', '3320', '3321']) {
  if (aestheticPages.includes(deadId)) {
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
console.log(`  slugs=${EXPECTED_SLUGS.length} keys=${EXPECTED_KEYS.length} catalog_entries=${catalogEntries.length}`);
