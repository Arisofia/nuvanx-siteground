#!/usr/bin/env node
/**
 * Static contract for the NUVANX entity graph (Yoast @graph extensions).
 *
 * Guards the fixes from the full schema audit:
 * - BTL detail registry reachable via snake_case alias
 * - Logo ImageObject materialization (no dangling #/schema/logo/image/)
 * - WebPage.mainEntity linkage for treatments
 * - Single Schema.org surface (Yoast graph only)
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const theme = path.join(root, 'wp-content/themes/nuvanx-medical');
const failures = [];

function read(rel) {
  const target = path.join(theme, rel);
  if (!fs.existsSync(target)) {
    failures.push(`missing ${rel}`);
    return '';
  }
  return fs.readFileSync(target, 'utf8');
}

const structured = read('inc/nvx-structured-data.php');
const seo = read('inc/nvx-seo-production-readiness.php');
const btl = read('inc/nvx-btl-detail-pages.php');
const jsonld = read('inc/nvx-jsonld-content.php');
const integrations = read('inc/nvx-integrations.php');

const required = [
  [btl, 'function nvx_btl_detail_registry(): array', 'BTL snake_case registry alias'],
  [btl, 'return nvxBtlDetailRegistry();', 'BTL alias delegates to camelCase registry'],
  [structured, "function_exists( 'nvx_btl_detail_registry' )", 'structured-data calls BTL registry'],
  [structured, 'function nvx_schema_link_webpage_main_entity(', 'WebPage.mainEntity linker'],
  [seo, 'function nvx_seo_schema_materialize_logo_node(', 'logo ImageObject materializer'],
  [seo, 'function nvx_seo_schema_ensure_webpage_main_entity(', 'mainEntity ensurer'],
  [seo, "function_exists( 'nvx_btl_detail_registry' )", 'SEO readiness BTL FAQ uses registry'],
  [jsonld, 'return is_front_page() || is_singular();', 'JSON-LD strip covers posts+pages'],
  [integrations, 'yoast-schema-graph', 'document normalizer preserves Yoast graph'],
  [integrations, 'nvx_theme_normalize_public_document', 'document normalizer registered'],
];

for (const [source, marker, label] of required) {
  if (!source.includes(marker)) failures.push(`${label}: missing \`${marker}\``);
}

// Avoid regex-based whole-file scans: inspect normalized lines with fixed-string checks.
const structuredLines = structured.split(/\r?\n/).map((line) => line.toLowerCase());
const printsJsonLd = structuredLines.some(
  (line) => (line.includes('echo') || line.includes('print')) && line.includes('application/ld+json'),
);
const embedsJsonLdScript = structuredLines.some(
  (line) => line.includes('<script') && line.includes('ld+json'),
);
if (printsJsonLd || embedsJsonLdScript) {
  failures.push('structured-data must not print application/ld+json directly');
}

// Clinic refs only for emitted nodes (prior dangling-ref fix).
if (!structured.includes('no dangling @id when a single branch page is rendered')) {
  failures.push('clinic attach must document emitted-only refs');
}
if (!structured.includes("nvx_schema_path_matches( $path, NVX_SD_PATH_EQUIPO_MEDICO )")) {
  failures.push('equipo must emit both MedicalClinic branches');
}

if (failures.length) {
  console.error('Entity graph contract FAILED:');
  for (const failure of failures) console.error(` - ${failure}`);
  process.exit(1);
}

console.log('Entity graph contract OK');
