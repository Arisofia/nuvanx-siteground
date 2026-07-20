#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const failures = [];

function requireMatch(source, pattern, message) {
  if (!pattern.test(source)) failures.push(message);
}
function requireAbsent(source, pattern, message) {
  if (pattern.test(source)) failures.push(message);
}

const integrations = read('wp-content/themes/nuvanx-medical/inc/nvx-integrations.php');
const metadata = read('wp-content/themes/nuvanx-medical/inc/nvx-seo-metadata.php');
const structured = read('wp-content/themes/nuvanx-medical/inc/nvx-structured-data.php');
const readiness = read('wp-content/themes/nuvanx-medical/inc/nvx-seo-production-readiness.php');

requireMatch(
  integrations,
  /require_once __DIR__ \. '\/nvx-seo-metadata\.php';[\s\S]*require_once __DIR__ \. '\/nvx-seo-production-readiness\.php';/,
  'production readiness must load after the environment-aware SEO metadata policy',
);

requireMatch(metadata, /array\( 'nuvanx\.com', 'www\.nuvanx\.com' \)/, 'public host allowlist must contain only the two production hosts');
requireMatch(metadata, /return 'production' !== \$environment \|\| ! \$public;/, 'nonproduction detection must require both production environment and public host');
requireMatch(metadata, /return 'noindex, nofollow';/, 'nonproduction Yoast robots guard is missing');
requireMatch(metadata, /\$robots\['noindex'\]\s*=\s*true;[\s\S]*\$robots\['nofollow'\]\s*=\s*true;/, 'nonproduction Core robots guard is missing');

requireMatch(readiness, /X-Robots-Tag[\s\S]*noindex, nofollow, noarchive, nosnippet/, 'nonproduction X-Robots-Tag guard is missing');
requireAbsent(readiness, /X-Robots-Tag[^\n]*index, follow/, 'production must not receive a global index header that could override page hygiene');
requireMatch(readiness, /\['department'\]\s*=\s*\$clinic_refs/, 'MedicalOrganization must expose clinic departments');
requireMatch(readiness, /unset\( \$graph\[ \$index \]\['subOrganization'\] \)/, 'legacy subOrganization relation must be consolidated');
requireMatch(readiness, /MedicalProcedure[\s\S]*NoninvasiveProcedure/, 'EXION, EMFUSION and EXILITE service nodes must be normalized as medical procedures');
requireMatch(readiness, /nvx_btl_detail_registry\(\)[\s\S]*\['faqs'\]/, 'BTL FAQ schema must source the visible registry');
requireMatch(readiness, /wpseo_schema_graph[\s\S]*120/, 'production schema normalization must run after existing graph filters');
requireAbsent(readiness, /<script\b[^>]*application\/ld\+json/i, 'readiness module must not print a second JSON-LD block');

for (const value of [
  'Calle de Fernández de la Hoz, 4, Bajo Derecha',
  '+34669319836',
  "'opens'     => '12:00'",
  "'closes'    => '20:00'",
  "'dayOfWeek' => 'Saturday'",
  "'opens'     => '10:00'",
  "'closes'    => '18:00'",
  'Calle de Fernán González, 26',
  '+34647505107',
  "'opens'     => '11:00'",
]) {
  requireMatch(structured, new RegExp(value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')), `clinic contract is missing: ${value}`);
}

requireMatch(structured, /'@type'\s*=>\s*array\( 'Organization', 'MedicalOrganization' \)/, 'parent organization must be a MedicalOrganization');
requireMatch(structured, /'@type'\s*=>\s*'MedicalClinic'/, 'branch nodes must use MedicalClinic');
requireMatch(structured, /parentOrganization/, 'clinic nodes must reference the parent organization');
requireMatch(structured, /'@type'\s*=>\s*array\( 'MedicalProcedure', 'Service' \)/, 'core treatment nodes must use MedicalProcedure + Service');
requireMatch(structured, /FAQPage/, 'canonical FAQPage generation is missing');

if (failures.length) {
  console.error('Production SEO contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: production indexing, clinic data and medical Schema contract');
