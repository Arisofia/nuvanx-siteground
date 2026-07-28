#!/usr/bin/env node
/**
 * Static contract for the NUVANX entity graph (Yoast @graph extensions).
 *
 * Guards the fixes from the full schema audit:
 * - BTL detail registry reachable via snake_case alias
 * - Logo ImageObject materialization (no dangling #/schema/logo/image/)
 * - WebPage.mainEntity linkage for treatments
 * - Single Schema.org surface (Yoast graph only)
 * - Live audit covers both clinics and rejects route-changing redirects
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

function readRoot(rel) {
  const target = path.join(root, rel);
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
const liveAudit = readRoot('scripts/staging2/audit-entity-graph.mjs');

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
  [liveAudit, '/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/', 'live audit covers Goya'],
  [liveAudit, 'unexpected redirect:', 'live audit rejects route-changing redirects'],
  [liveAudit, 'AbortSignal.timeout(30000)', 'live audit bounds request time'],
  [liveAudit, 'minClinics', 'clinic expectations are minimum thresholds'],
  [liveAudit, 'minPhysicians', 'physician expectations are minimum thresholds'],
];

for (const [source, marker, label] of required) {
  if (!source.includes(marker)) failures.push(`${label}: missing \`${marker}\``);
}

// Must not reintroduce a second top-level schema.org script printer in structured-data.
// Detect single-line and multi-line constructs including concatenation, variables, ECHO/print variants.
const structuredNormalized = structured.replace(/\r?\n/g, ' ').replace(/\s+/g, ' ').toLowerCase();

// Check for echo/print statements with ld+json (case-insensitive, multi-line aware)
const printsJsonLd = /(?:echo|print)\b[^;{]*application\/ld\+json/i.test(structuredNormalized);

// Check for <script constructs with ld+json (multi-line aware)
const embedsJsonLdScript = /<script\b[^>]*ld\+json/i.test(structuredNormalized);

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
