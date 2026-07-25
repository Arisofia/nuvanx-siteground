#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const failures = [];
const file = (relative) => path.join(root, relative);
const read = (relative) => {
  const target = file(relative);
  if (!fs.existsSync(target)) {
    failures.push(`missing ${relative}`);
    return '';
  }
  return fs.readFileSync(target, 'utf8');
};

const frontPage = read('wp-content/themes/nuvanx-medical/front-page.php');
const homeCss = read('wp-content/themes/nuvanx-medical/assets/css/nvx-home-structure.css');
const homeArt = read('wp-content/themes/nuvanx-medical/assets/images/nvx-home-hero-contours.svg');
const sedeTemplate = read('wp-content/themes/nuvanx-medical/templates/page-sede.php');
const valoracionTemplate = read('wp-content/themes/nuvanx-medical/templates/page-landing-valoracion.php');
const clinicsHub = read('wp-content/themes/nuvanx-medical/template-parts/content/nvx-clinics-hub-github.php');
const valoracionPage = read('wp-content/themes/nuvanx-medical/template-parts/content/nvx-valoracion-github.php');
const solutionsTemplate = read('wp-content/themes/nuvanx-medical/page-soluciones-medicas.php');
const solutionsPage = read('wp-content/themes/nuvanx-medical/template-parts/content/nvx-soluciones-medicas-github.php');
const solutionsCss = read('wp-content/themes/nuvanx-medical/assets/css/nvx-soluciones-medicas.css');
const solutionsArt = read('wp-content/themes/nuvanx-medical/assets/images/nvx-solutions-hero-architecture.svg');
const strategyPages = read('wp-content/themes/nuvanx-medical/inc/nvx-strategy-pages.php');

const required = [
  [frontPage, "'nvx-home-structure'", 'Home does not enqueue its GitHub-managed structure layer'],
  [frontPage, 'assets/images/nvx-home-hero-contours.svg', 'Home does not use repository-owned hero artwork'],
  [frontPage, 'nvx-home-v5', 'Home canonical root is missing'],
  [homeCss, '.nvx-home-v5 .nvx-home-manifesto', 'Home structural divisions are missing'],
  [homeCss, '.nvx-home-v5 .nvx-home-feature', 'Home text-only feature correction is missing'],
  [homeCss, '.nvx-home-v5 .nvx-home-hero__art', 'Home repository artwork presentation is missing'],
  [homeArt, '<svg', 'Home repository artwork is invalid or empty'],
  [sedeTemplate, "'template-parts/content/nvx-clinics-hub-github'", 'Clinics hub is not rendered from GitHub'],
  [valoracionTemplate, "'template-parts/content/nvx-valoracion-github'", 'Valoración is not rendered from GitHub'],
  [clinicsHub, 'CLÍNICAS NUVANX · MADRID', 'Clinics GitHub template is incomplete'],
  [clinicsHub, 'nvx-canonical-page-hero', 'Clinics canonical header is missing'],
  [valoracionPage, 'VALORACIÓN MÉDICA · MADRID', 'Valoración GitHub template is incomplete'],
  [valoracionPage, 'id="nvx-hubspot-form"', 'Valoración form anchor is missing'],
  [solutionsTemplate, "'template-parts/content/nvx-soluciones-medicas-github'", 'Solutions page is not rendered from GitHub'],
  [solutionsTemplate, "'nvx-soluciones-medicas'", 'Solutions page does not enqueue its canonical stylesheet'],
  [solutionsTemplate, "nvxSyncGithubManagedPageState( $page_id, 'solutions' )", 'Solutions CMS state is not normalized'],
  [solutionsPage, 'SOLUCIONES MÉDICAS · NUVANX MADRID', 'Solutions GitHub template is incomplete'],
  [solutionsPage, 'id="mapa-soluciones"', 'Solutions navigation map is missing'],
  [solutionsPage, 'CONTORNO CORPORAL', 'Solutions clinical content groups are incomplete'],
  [solutionsPage, 'Soluciones médicas para rostro, piel y contorno corporal.', 'Solutions canonical H1 is missing'],
  [solutionsCss, '.nvx-solutions-hero', 'Solutions canonical hero styles are missing'],
  [solutionsCss, '.nvx-solutions-group--dark', 'Solutions content surfaces are not differentiated'],
  [solutionsArt, '<svg', 'Solutions repository artwork is invalid or empty'],
];
for (const [source, marker, message] of required) {
  if (!source.includes(marker)) failures.push(message);
}

for (const [source, label] of [
  [frontPage, 'Home'],
  [valoracionTemplate, 'Valoración template'],
  [solutionsTemplate, 'Solutions template'],
]) {
  if (/\bthe_content\s*\(/.test(source)) failures.push(`${label} must not read visible content from WordPress`);
  if (/get_post_field\s*\(\s*['"]post_content['"]/.test(source)) failures.push(`${label} must not read CMS post_content`);
}

for (const [source, label] of [
  [frontPage, 'Home'],
  [clinicsHub, 'Clinics'],
  [valoracionPage, 'Valoración'],
  [solutionsPage, 'Solutions'],
  [solutionsTemplate, 'Solutions template'],
]) {
  if (/content_url\s*\(|wp-content\/uploads|\/uploads\//i.test(source)) {
    failures.push(`${label} must not depend on WordPress uploads for managed visible assets`);
  }
}

if (!/clinicas-de-medicina-estetica-nuvanx[\s\S]*nvx-clinics-hub-github/.test(sedeTemplate)) {
  failures.push('Sede template does not route the clinics hub before dynamic branch content');
}

for (const forbidden of [
  'function nvx_strategy_solution_card',
  'function nvx_strategy_solutions_markup',
  "'solutions' === $key",
  'nvx-catalog-grid',
]) {
  if (strategyPages.includes(forbidden)) failures.push(`Legacy solutions renderer remains active: ${forbidden}`);
}

const managedPhpFiles = [
  'wp-content/themes/nuvanx-medical/front-page.php',
  'wp-content/themes/nuvanx-medical/templates/page-sede.php',
  'wp-content/themes/nuvanx-medical/templates/page-landing-valoracion.php',
  'wp-content/themes/nuvanx-medical/page-soluciones-medicas.php',
  'wp-content/themes/nuvanx-medical/template-parts/content/nvx-clinics-hub-github.php',
  'wp-content/themes/nuvanx-medical/template-parts/content/nvx-valoracion-github.php',
  'wp-content/themes/nuvanx-medical/template-parts/content/nvx-soluciones-medicas-github.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-github-managed-page-state.php',
  'wp-content/themes/nuvanx-medical/inc/nvx-strategy-pages.php',
];
for (const relative of managedPhpFiles) {
  const result = spawnSync('/usr/bin/php', ['-l', file(relative)], { encoding: 'utf8' });
  if (result.error?.code === 'ENOENT') {
    console.warn(`WARNING: php executable unavailable; skipped ${relative}`);
    break;
  }
  if (result.error || result.status !== 0) {
    failures.push(`PHP syntax failed for ${relative}: ${String(result.stderr || result.stdout || '').trim()}`);
  }
}

if (failures.length) {
  console.error(`FAIL: ${failures.length} GitHub-managed page contract finding(s)`);
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('GITHUB_MANAGED_PAGES_OK');
