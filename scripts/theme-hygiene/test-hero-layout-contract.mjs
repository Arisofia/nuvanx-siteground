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

const integrations = read('wp-content/themes/nuvanx-medical/inc/nvx-integrations.php');
const moduleSource = read('wp-content/themes/nuvanx-medical/inc/nvx-hero-layout-coherence.php');
const layoutCss = read('wp-content/themes/nuvanx-medical/assets/css/nvx-hero-layout-coherence.css');
const frontPage = read('wp-content/themes/nuvanx-medical/front-page.php');
const equipoPage = read('wp-content/themes/nuvanx-medical/inc/nvx-equipo-page.php');
const contactoFixes = read('wp-content/themes/nuvanx-medical/inc/nvx-contacto-audit-fixes.php');
const fullSiteAudit = read('scripts/staging2/audit-full-site-ui.mjs');

requireText(
  'theme bootstrap',
  integrations,
  "require_once __DIR__ . '/nvx-hero-layout-coherence.php';",
);
requireText('layout module', moduleSource, "add_action( 'wp_head', 'nvxHeroLayoutCoherenceEnqueueAssets', 1 );");
requireText('layout module', moduleSource, "'nvx-site-coherence'");
requireText('layout module', moduleSource, "wp_style_is( 'nvx-home-structure', 'enqueued' )");
requireText('layout module', moduleSource, "'nvx-home-structure'");

for (const marker of [
  '.nvx-site-coherent-page .nvx-canonical-page-hero + .nvx-hero-intro--coherent',
  'background: var(--nvx-ink);',
  'color: var(--nvx-text-on-dark-90);',
  '.nvx-page--contact > .nvx-brand-hero',
  '.nvx-page--contact > .nvx-brand-hero .nvx-lead',
  '.nvx-home-v5 .nvx-home-hero',
  'grid-template-columns: 1fr;',
  '.nvx-home-v5 .nvx-home-hero__media',
  'grid-row: 1;',
  '.nvx-home-v5 .nvx-home-hero__copy',
  'grid-row: 2;',
  '.nvx-home-v5 .nvx-home-hero__media :where(img, video)',
]) requireText('hero layout CSS', layoutCss, marker);

forbidText('hero layout CSS', layoutCss, '!important');
forbidText('hero layout CSS', layoutCss, 'grid-template-columns: minmax(28rem');

const equipoDetectorStart = equipoPage.indexOf('function nvx_content_is_equipo_page');
const equipoDetectorEnd = equipoPage.indexOf('\n/**\n * Hero.', equipoDetectorStart);
const equipoDetector = equipoDetectorStart >= 0 && equipoDetectorEnd > equipoDetectorStart
  ? equipoPage.slice(equipoDetectorStart, equipoDetectorEnd)
  : '';
requireText('Equipo route ownership', equipoDetector, "is_page( 'equipo-medico' )");
requireText('Equipo route ownership', equipoDetector, "nvx_schema_path_matches( $path, '/equipo-medico/' )");
forbidText('Equipo route ownership', equipoDetector, 'preg_match(');
forbidText('Equipo route ownership', equipoDetector, 'equipo especialista');

requireText('Contacto route ownership', contactoFixes, "'Agenda tu valoración médica'                => 'Contacto NUVANX en Madrid'");
forbidText('Contacto route ownership', contactoFixes, "'Agenda tu valoración médica'                => 'Clínicas NUVANX en Madrid — Chamberí y Salamanca–Goya'");

for (const headingMarker of [
  'const routeHeadingContracts = new Map([',
  "['/contacto/', { exact: 'Contacto NUVANX en Madrid' }]",
  "['/medicina-estetica-chamberi/', { forbidden:",
  "['/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/', { forbidden:",
  "['/medicina-estetica/', { forbidden:",
  'route inherited forbidden H1',
]) requireText('rendered route heading contract', fullSiteAudit, headingMarker);

for (const marker of [
  "content_url( '/uploads/2026/06/nvx-home-video-portada-hero-12s-720p.mp4' )",
  'class="nvx-home-hero__video"',
  'autoplay muted loop playsinline',
  'preload="metadata"',
  '<source src="<?php echo esc_url( $hero_video_url ); ?>" type="video/mp4">',
  'poster="<?php echo esc_url( $hero_art_url ); ?>"',
]) requireText('Home video markup', frontPage, marker);

const mediaIndex = frontPage.indexOf('class="nvx-home-hero__media"');
const videoIndex = frontPage.indexOf('class="nvx-home-hero__video"');
const copyIndex = frontPage.indexOf('class="nvx-home-hero__copy"');
if (
  mediaIndex < 0
  || videoIndex < 0
  || copyIndex < 0
  || mediaIndex > videoIndex
  || videoIndex > copyIndex
) {
  failures.push('Home markup must render verified hero video before hero copy.');
}

if (failures.length > 0) {
  console.error(`HERO_LAYOUT_CONTRACT_FAILED count=${failures.length}`);
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('HERO_LAYOUT_CONTRACT_OK');
