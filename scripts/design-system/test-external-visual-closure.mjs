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
const closure = read('wp-content/themes/nuvanx-medical/inc/nvx-external-visual-closure.php');
const canonicalClosurePath = 'wp-content/themes/nuvanx-medical/inc/nvx-staging2-canonical-closure.php';

// Strip comments and allowed @media breakpoint values before enforcing the
// "canonical size tokens only" rule below, mirroring scripts/design-system/audit-css.mjs.
const ALLOWED_BREAKPOINTS = new Set(['1280', '980', '961', '960', '782', '721', '720', '680', '480']);
const closureWithoutComments = closure.replace(/\/\*[\s\S]*?\*\//g, '');
const closureWithoutBreakpoints = closureWithoutComments.replace(
  /((?:min|max)-width\s*:\s*)(\d+)px/g,
  (full, prefix, value) => (ALLOWED_BREAKPOINTS.has(value) ? `${prefix}0` : full),
);

requireMatch(
  integrations,
  /require_once __DIR__ \. '\/nvx-external-visual-closure\.php';/,
  'nvx-integrations.php must load the external visual closure',
);
requireMatch(
  closure,
  /\.joinchat\s*\{[\s\S]*--s:\s*var\(--nvx-icon-frame\)/,
  'Joinchat floating control must use the canonical 48px frame',
);
requireMatch(
  closure,
  /\.joinchat__button[\s\S]*width:\s*var\(--nvx-icon-frame\)[\s\S]*height:\s*var\(--nvx-icon-frame\)/,
  'Joinchat button width and height are not canonical',
);
requireMatch(
  closure,
  /\.joinchat__open__icon[\s\S]*width:\s*var\(--nvx-icon-sm\)[\s\S]*height:\s*var\(--nvx-icon-sm\)/,
  'Joinchat chat icon must use the canonical 24px size',
);
requireMatch(
  closure,
  /\.icon-whatsapp[\s\S]*width:\s*var\(--nvx-icon-xs\)[\s\S]*height:\s*var\(--nvx-icon-xs\)/,
  'inline WhatsApp icons must use the canonical 16px size',
);
requireMatch(
  closure,
  /:where\(\.joinchat, \.joinchat \*\)[\s\S]*font-family:\s*var\(--nvx-sans\)/,
  'Joinchat text must inherit Manrope',
);
requireMatch(
  closure,
  /nvx-external-visual-closure[\s\S]*wp_add_inline_style[\s\S]*1000/,
  'external closure must load after theme and plugin styles',
);
requireAbsent(closure, /(?:Bodoni|Cormorant|Source Sans|\bInter\b|Pinyon)/i, 'alternate font found');
requireAbsent(closure, /!important/i, 'external closure must not use !important');
requireAbsent(
  closureWithoutBreakpoints,
  /(?:\d+(?:\.\d+)?px|\d+(?:\.\d+)?rem)/i,
  'external closure must use canonical size tokens only outside of comments and allowed @media breakpoints',
);

// --- nvx-staging2-canonical-closure.php dependency (loaded by this file) ---
requireMatch(
  closure,
  /require_once __DIR__ \. '\/nvx-staging2-canonical-closure\.php';/,
  'the external visual closure must load the staging2 canonical closure dependency',
);
if (!fs.existsSync(path.join(root, canonicalClosurePath))) {
  failures.push('nvx-staging2-canonical-closure.php referenced by the require_once must exist');
}

// --- Strategy pages own one full-width editorial hierarchy ---
requireMatch(
  closure,
  /\.nvx-page__content > \.nvx-strategy-page\s*\{[\s\S]*?width:\s*100%;[\s\S]*?max-width:\s*none;[\s\S]*?margin:\s*0;/,
  'strategy pages must own a full-width editorial hierarchy inside the page content wrapper',
);
requireMatch(
  closure,
  /\.nvx-strategy-page > \.nvx-brand-hero\s*\{[\s\S]*?min-height:\s*calc\(var\(--nvx-space-12\) \* 4\)/,
  'the strategy page hero must define the canonical minimum height using space tokens',
);
requireMatch(
  closure,
  /\.nvx-strategy-page > \.nvx-brand-hero \.nvx-brand-title\s*\{[\s\S]*?max-width:\s*22ch;/,
  'the strategy page hero title must cap its measure at 22ch',
);
requireMatch(
  closure,
  /\.nvx-strategy-page > \.nvx-brand-hero \.nvx-brand-lead\s*\{[\s\S]*?max-width:\s*var\(--nvx-measure-lead\);/,
  'the strategy page hero lead must use the canonical measure-lead token',
);
requireMatch(
  closure,
  /\.nvx-strategy-page > \.nvx-brand-section\s*\{[\s\S]*?width:\s*var\(--nvx-shell\);/,
  'strategy page sections must align to the canonical shell width',
);
requireMatch(
  closure,
  /\.nvx-strategy-page \.nvx-endolift-price-table-wrap\s*\{[\s\S]*?overflow-x:\s*auto;/,
  'the Endolift price table wrapper must allow horizontal scroll on narrow viewports',
);

// --- Contact cards use the same shell, card and control contracts as treatment pages ---
requireMatch(
  closure,
  /\.nvx-page--contact \.nvx-clinics-grid\s*\{[\s\S]*?grid-template-columns:\s*repeat\(2, minmax\(0, 1fr\)\);/,
  'contact clinic cards must render in a two-column grid',
);
requireMatch(
  closure,
  /\.nvx-page--contact \.nvx-clinic-card\s*\{[\s\S]*?border:\s*var\(--nvx-border-hairline\) solid var\(--nvx-color-line\);/,
  'contact clinic cards must use the canonical card border contract',
);
requireMatch(
  closure,
  /\.nvx-page--contact \.nvx-clinic-card__map iframe\s*\{[\s\S]*?border:\s*0;/,
  'the clinic map iframe must have its border removed via CSS now that the inline style attribute was dropped',
);
requireMatch(
  closure,
  /\.nvx-page--contact \.nvx-clinic-card > \.nvx-btn\s*\{[\s\S]*?align-self:\s*flex-start;/,
  'the clinic card CTA button must align to the start of the flex column',
);

// --- Native details controls must preserve a 44px interaction target ---
requireMatch(
  closure,
  /\.nvx-faq summary,[\s\S]*?\.nvx-brand-faq-accordion summary,[\s\S]*?\.nvx-home-faq-editorial summary\s*\{[\s\S]*?min-height:\s*var\(--nvx-control-size\);/,
  'FAQ summary controls must preserve the canonical control-size interaction target',
);

// --- Closing CTA presentation formerly duplicated in markup attributes ---
requireMatch(closure, /\.nvx-cta-banner\s*\{[\s\S]*?text-align:\s*center;/, 'the CTA banner must center its content');
requireMatch(
  closure,
  /#nvx-footer-cta\s*\{[\s\S]*?text-transform:\s*uppercase;/,
  'the footer CTA button must keep its uppercase button treatment',
);

// --- Narrow viewport collapse ---
requireMatch(
  closure,
  /@media \(max-width: 45em\)\s*\{[\s\S]*?\.nvx-page--contact \.nvx-clinics-grid\s*\{[\s\S]*?grid-template-columns:\s*1fr;/,
  'contact clinic cards must collapse to a single column on narrow viewports',
);
requireMatch(
  closure,
  /@media \(max-width: 45em\)\s*\{[\s\S]*?\.nvx-strategy-page > \.nvx-brand-hero\s*\{[\s\S]*?min-height:\s*calc\(var\(--nvx-space-12\) \* 3\)/,
  'the strategy hero minimum height must shrink on narrow viewports',
);

if (failures.length) {
  console.error('External visual closure contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: Joinchat and late typography use canonical NUVANX tokens');
