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
const extractFunctionBody = (source, functionName) => {
  const signatureIndex = source.indexOf(`function ${functionName}`);
  const openingBrace = signatureIndex >= 0 ? source.indexOf('{', signatureIndex) : -1;
  if (openingBrace < 0) return '';

  let depth = 0;
  for (let index = openingBrace; index < source.length; index += 1) {
    if (source[index] === '{') depth += 1;
    if (source[index] === '}') depth -= 1;
    if (depth === 0) return source.slice(signatureIndex, index + 1);
  }
  return '';
};

const integrations = read('wp-content/themes/nuvanx-medical/inc/nvx-integrations.php');
const moduleSource = read('wp-content/themes/nuvanx-medical/inc/nvx-hero-layout-coherence.php');
const layoutCss = read('wp-content/themes/nuvanx-medical/assets/css/nvx-hero-layout-coherence.css');
const videoControlCss = read('wp-content/themes/nuvanx-medical/assets/css/nvx-home-hero-video-control.css');
const videoControlJs = read('wp-content/themes/nuvanx-medical/assets/js/nvx-home-hero-video.js');
const frontPage = read('wp-content/themes/nuvanx-medical/front-page.php');
const equipoPage = read('wp-content/themes/nuvanx-medical/inc/nvx-equipo-page.php');
const contactoFixes = read('wp-content/themes/nuvanx-medical/inc/nvx-contacto-audit-fixes.php');
const contactoTemplate = read('wp-content/themes/nuvanx-medical/templates/template-contact.php');
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
forbidText('layout module', moduleSource, 'nvx_hero_layout_coherence_enqueue_assets');
requireText('Home video assets', moduleSource, "'assets/css/nvx-home-hero-video-control.css'");
requireText('Home video assets', moduleSource, "'assets/js/nvx-home-hero-video.js'");
requireText('Home video assets', moduleSource, "'nvx-home-hero-video-control'");
requireText('Home video assets', moduleSource, "'nvx-home-hero-video'");

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

for (const marker of [
  '.nvx-home-v5 .nvx-home-hero__visual',
  '.nvx-home-v5 .nvx-home-hero__motion-toggle',
  ':focus-visible',
  '@media (prefers-reduced-motion: reduce)',
]) requireText('Home video control CSS', videoControlCss, marker);
forbidText('Home video control CSS', videoControlCss, '!important');

for (const marker of [
  "window.matchMedia('(prefers-reduced-motion: reduce)')",
  "video.removeAttribute('autoplay')",
  'video.pause()',
  "toggle.setAttribute('aria-label'",
  "toggle.setAttribute('aria-controls'",
  'data-nvx-home-video-toggle',
]) requireText('Home video control JS', videoControlJs, marker);
forbidText('Home video control JS', videoControlJs, "document.createElement('button')");
forbidText('Home video control JS', videoControlJs, "media.removeAttribute('aria-hidden')");

const equipoDetector = extractFunctionBody(equipoPage, 'nvx_content_is_equipo_page');
requireText('Equipo route ownership', equipoDetector, "is_page( 'equipo-medico' )");
requireText('Equipo route ownership', equipoDetector, "nvx_schema_path_matches( $path, '/equipo-medico/' )");
forbidText('Equipo route ownership', equipoDetector, 'preg_match(');
forbidText('Equipo route ownership', equipoDetector, 'equipo especialista');

requireText('Contacto static H1', contactoTemplate, "esc_html_e( 'Contacto NUVANX en Madrid', 'nuvanx-medical' )");
forbidText('Contacto static H1', contactoTemplate, "esc_html_e( 'Clínicas NUVANX en Madrid — Chamberí y Salamanca–Goya', 'nuvanx-medical' )");
requireText('Contacto scoped fallback', contactoFixes, '/<h1\\b([^>]*)>\\s*Agenda tu valoración médica\\s*<\\/h1>/iu');
forbidText('Contacto scoped fallback', contactoFixes, "'Agenda tu valoración médica'                => 'Contacto NUVANX en Madrid'");

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
  'class="nvx-home-hero__visual" aria-hidden="true"',
  'id="nvx-home-hero-video" class="nvx-home-hero__video"',
  'autoplay muted loop playsinline',
  'preload="metadata"',
  '<source src="<?php echo esc_url( $hero_video_url ); ?>" type="video/mp4">',
  'poster="<?php echo esc_url( $hero_art_url ); ?>"',
  'data-nvx-home-video-toggle',
  'data-nvx-home-video-label',
  'aria-controls="nvx-home-hero-video"',
]) requireText('Home video markup', frontPage, marker);

const mediaIndex = frontPage.indexOf('class="nvx-home-hero__media"');
const visualIndex = frontPage.indexOf('class="nvx-home-hero__visual"');
const videoIndex = frontPage.indexOf('class="nvx-home-hero__video"');
const toggleIndex = frontPage.indexOf('data-nvx-home-video-toggle');
const copyIndex = frontPage.indexOf('class="nvx-home-hero__copy"');
if (
  mediaIndex < 0
  || visualIndex < 0
  || videoIndex < 0
  || toggleIndex < 0
  || copyIndex < 0
  || mediaIndex > visualIndex
  || visualIndex > videoIndex
  || videoIndex > toggleIndex
  || toggleIndex > copyIndex
) {
  failures.push('Home markup must render decorative video, accessible control, then hero copy.');
}

if (failures.length > 0) {
  console.error(`HERO_LAYOUT_CONTRACT_FAILED count=${failures.length}`);
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('HERO_LAYOUT_CONTRACT_OK');