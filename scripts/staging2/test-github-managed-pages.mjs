#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const failures = [];
const read = (relative) => {
  const target = path.join(root, relative);
  if (!fs.existsSync(target)) {
    failures.push(`missing ${relative}`);
    return '';
  }
  return fs.readFileSync(target, 'utf8');
};

const frontPage = read('wp-content/themes/nuvanx-medical/front-page.php');
const homeCss = read('wp-content/themes/nuvanx-medical/assets/css/nvx-home-structure.css');
const sedeTemplate = read('wp-content/themes/nuvanx-medical/templates/page-sede.php');
const valoracionTemplate = read('wp-content/themes/nuvanx-medical/templates/page-landing-valoracion.php');
const clinicsHub = read('wp-content/themes/nuvanx-medical/template-parts/content/nvx-clinics-hub-github.php');
const valoracionPage = read('wp-content/themes/nuvanx-medical/template-parts/content/nvx-valoracion-github.php');

const required = [
  [frontPage, "'nvx-home-structure'", 'Home does not enqueue its GitHub-managed structure layer'],
  [frontPage, 'nvx-home-v5', 'Home canonical root is missing'],
  [homeCss, '.nvx-home-v5 .nvx-home-manifesto', 'Home structural divisions are missing'],
  [homeCss, '.nvx-home-v5 .nvx-home-feature', 'Home text-only feature correction is missing'],
  [sedeTemplate, "'template-parts/content/nvx-clinics-hub-github'", 'Clinics hub is not rendered from GitHub'],
  [valoracionTemplate, "'template-parts/content/nvx-valoracion-github'", 'Valoración is not rendered from GitHub'],
  [clinicsHub, 'CLÍNICAS NUVANX · MADRID', 'Clinics GitHub template is incomplete'],
  [clinicsHub, 'nvx-canonical-page-hero', 'Clinics canonical header is missing'],
  [valoracionPage, 'VALORACIÓN MÉDICA · MADRID', 'Valoración GitHub template is incomplete'],
  [valoracionPage, 'id="nvx-hubspot-form"', 'Valoración form anchor is missing'],
];
for (const [source, marker, message] of required) {
  if (!source.includes(marker)) failures.push(message);
}

for (const [source, label] of [
  [frontPage, 'Home'],
  [valoracionTemplate, 'Valoración template'],
]) {
  if (/\bthe_content\s*\(/.test(source)) failures.push(`${label} must not read visible content from WordPress`);
  if (/get_post_field\s*\(\s*['"]post_content['"]/.test(source)) failures.push(`${label} must not read CMS post_content`);
}

if (!/clinicas-de-medicina-estetica-nuvanx[\s\S]*nvx-clinics-hub-github/.test(sedeTemplate)) {
  failures.push('Sede template does not route the clinics hub before dynamic branch content');
}

if (failures.length) {
  console.error(`FAIL: ${failures.length} GitHub-managed page contract finding(s)`);
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('GITHUB_MANAGED_PAGES_OK');
