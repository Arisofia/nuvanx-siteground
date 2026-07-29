#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relativePath) => fs.readFileSync(path.join(root, relativePath), 'utf8');
const failures = [];

const requireText = (scope, source, marker) => {
  if (!source.includes(marker)) failures.push(`${scope}: missing ${marker}`);
};
const forbidText = (scope, source, marker) => {
  if (source.includes(marker)) failures.push(`${scope}: forbidden ${marker}`);
};
const stripBlockComments = (source) => {
  let output = '';
  let cursor = 0;
  while (cursor < source.length) {
    const start = source.indexOf('/*', cursor);
    if (start < 0) {
      output += source.slice(cursor);
      break;
    }
    output += source.slice(cursor, start);
    const end = source.indexOf('*/', start + 2);
    if (end < 0) break;
    output += ' ';
    cursor = end + 2;
  }
  return output;
};

const seoMetadata = read('wp-content/themes/nuvanx-medical/inc/nvx-seo-metadata.php');
const editorialSeo = read('wp-content/themes/nuvanx-medical/inc/nvx-editorial-seo-extension.php');
const strategyPages = read('wp-content/themes/nuvanx-medical/inc/nvx-strategy-pages.php');
const pageHygiene = read('wp-content/themes/nuvanx-medical/inc/nvx-page-hygiene.php');
const treatmentHubSchema = read('wp-content/themes/nuvanx-medical/inc/nvx-treatment-hub-schema.php');
const structuredData = read('wp-content/themes/nuvanx-medical/inc/nvx-structured-data.php');
const footer = read('wp-content/themes/nuvanx-medical/footer.php');
const integrations = read('wp-content/themes/nuvanx-medical/inc/nvx-integrations.php');
const functions = read('wp-content/themes/nuvanx-medical/functions.php');
const cachePurge = read('tools/deploy/nvx-purge-wp-caches.sh');
const deployWorkflow = read('.github/workflows/deploy-staging2.yml');

const seoCode = stripBlockComments(seoMetadata.replace(/\/\/.*$/gm, ''));
const editorialSeoCode = stripBlockComments(editorialSeo.replace(/\/\/.*$/gm, ''));
forbidText('solution SEO ownership', seoCode, 'nvx_seo_metadata_from_solutions(');
forbidText('solution SEO ownership', seoCode, "function_exists( 'nvx_solution_pages_catalog' )");
requireText('solution SEO ownership', seoMetadata, "function_exists( 'nvx_editorial_seo_current' )");
requireText('solution SEO ownership', seoMetadata, '$editorial = nvx_editorial_seo_current()');
for (const route of [
  '/soluciones-medicas/',
  '/remodelacion-corporal-laser-madrid/',
  '/tratamiento-postparto-abdomen-contorno-corporal-madrid/',
  '/papada-definicion-mandibular-madrid/',
  '/calidad-piel-firmeza-luminosidad-madrid/',
  '/cicatrices-acne-poros-textura-madrid/',
  '/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/',
]) requireText('solution SEO catalog', editorialSeoCode, `'${route}'`);

requireText('strategy noindex source', strategyPages, 'function nvx_strategy_pending_page_ids(): array');
requireText('strategy noindex source', strategyPages, "'approved_for_publication' === ( $page['review_status'] ?? '' )");
requireText('strategy noindex consumer', pageHygiene, "function_exists( 'nvx_strategy_pending_page_ids' )");
requireText('strategy noindex consumer', pageHygiene, 'nvx_strategy_pending_page_ids()');
const strategyLoad = "require_once __DIR__ . '/nvx-strategy-pages.php';";
const hygieneLoad = "require_once __DIR__ . '/nvx-page-hygiene.php';";
requireText('strategy bootstrap order', integrations, strategyLoad);
requireText('strategy bootstrap order', integrations, hygieneLoad);
if (integrations.indexOf(strategyLoad) > integrations.indexOf(hygieneLoad)) {
  failures.push('strategy bootstrap order: strategy pages must load before page hygiene');
}

requireText('treatment hub predicate', treatmentHubSchema, 'function nvx_theme_is_treatments_hub(): bool');
requireText('treatment hub predicate', treatmentHubSchema, "return 'soluciones-medicas' === $slug;");
requireText('treatment hub schema', treatmentHubSchema, 'nvx_theme_is_treatments_hub()');
requireText('treatment hub schema', treatmentHubSchema, "add_filter( 'wpseo_schema_graph', 'nvx_treatment_hub_extend_yoast_graph', 99, 2 );");
requireText('treatment hub bootstrap', functions, "require_once get_template_directory() . '/inc/nvx-treatment-hub-schema.php';");

for (const marker of [
  'function nvx_co2_price_facial_eur(): float',
  "$catalog['laser_co2']['facial']['pvp'] ?? 330.00",
  'function nvx_co2_price_body_eur(): float',
  "$catalog['laser_co2']['corporal']['pvp'] ?? 450.00",
]) requireText('CO2 price contract', structuredData, marker);
if (structuredData.split('nvx_co2_price_facial_eur()').length - 1 < 2) {
  failures.push('CO2 price contract: facial helper is not consumed');
}
if (structuredData.split('nvx_co2_price_body_eur()').length - 1 < 2) {
  failures.push('CO2 price contract: body helper is not consumed');
}

forbidText('footer dead code', footer, '$nvx_footer_published_treatments');
forbidText('footer dead code', footer, 'nvx_navigation_published_treatments()');

for (const marker of [
  'set -Eeuo pipefail',
  'wp cache flush',
  'echo "wp_cache_flush=ok"',
  'wp sg purge',
  'echo "sg_purge=ok"',
  'rm -rf -- "${cache_targets[@]}"',
  "cache_root='wp-content/cache'",
  "! -name '.htaccess'",
  'opcache=not-applicable-cli',
]) requireText('cache purge', cachePurge, marker);
for (const marker of [
  'nvx_run_optional_wp_command',
  'wp cache flush ||',
  'wp sg purge ||',
  '|| true',
  'wp sg purge dynamic',
  'wp sg purge memcached',
  'wp-content/cache/*',
  'opcache_reset',
  "wp eval '",
]) forbidText('cache purge', cachePurge, marker);

const purgeWorkflowOccurrences = deployWorkflow.split('tools/deploy/nvx-purge-wp-caches.sh').length - 1;
if (purgeWorkflowOccurrences < 3) {
  failures.push(`staging2 deploy contract: expected purge script in path filter, bash lint and payload; found ${purgeWorkflowOccurrences}`);
}
requireText('staging2 deploy contract', deployWorkflow, "- 'tools/deploy/nvx-purge-wp-caches.sh'");
requireText('staging2 deploy contract', deployWorkflow, 'bash -n tools/deploy/nvx-purge-wp-caches.sh');

if (failures.length > 0) {
  console.error(`AUDIT_V3_CONTRACT_FAILED count=${failures.length}`);
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('AUDIT_V3_CONTRACT_OK');
