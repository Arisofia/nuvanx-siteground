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
  'external closure must use canonical size tokens outside comments and allowed breakpoints',
);

requireMatch(
  closure,
  /require_once __DIR__ \. '\/nvx-staging2-canonical-closure\.php';/,
  'external closure must load the Staging2 canonical dependency',
);
if (!fs.existsSync(path.join(root, canonicalClosurePath))) {
  failures.push('nvx-staging2-canonical-closure.php must exist');
}

requireAbsent(
  closure,
  /\.nvx-page__content > \.nvx-strategy-page\s*\{/,
  'late CSS must not override the strategy article nvx-shell',
);
requireMatch(
  closure,
  /\.nvx-strategy-page > \.nvx-brand-hero\s*\{[\s\S]*?min-height:\s*calc\(var\(--nvx-space-12\) \* 4\)/,
  'strategy hero presentation must remain available',
);
requireMatch(
  closure,
  /\.nvx-strategy-page > \.nvx-brand-section\s*\{[\s\S]*?width:\s*var\(--nvx-shell\);/,
  'strategy sections must retain canonical shell alignment',
);
requireMatch(
  closure,
  /\.nvx-strategy-page \.nvx-endolift-price-table-wrap\s*\{[\s\S]*?overflow-x:\s*auto;/,
  'strategy price tables must retain horizontal overflow protection',
);

requireAbsent(
  closure,
  /\.nvx-page--contact \.nvx-clinics-grid|\.nvx-page--contact \.nvx-clinic-card/,
  'Contact card components must live in nvx-components.css, not late inline CSS',
);

requireMatch(
  closure,
  /\.nvx-faq summary,[\s\S]*?\.nvx-brand-faq-accordion summary,[\s\S]*?\.nvx-home-faq-editorial summary\s*\{[\s\S]*?min-height:\s*var\(--nvx-control-size\);/,
  'FAQ summary controls must preserve the canonical interaction target',
);
requireMatch(closure, /\.nvx-cta-banner\s*\{[\s\S]*?text-align:\s*center;/, 'CTA banner must remain centered');
requireMatch(
  closure,
  /#nvx-footer-cta\s*\{[\s\S]*?text-transform:\s*uppercase;/,
  'footer CTA must keep its uppercase treatment',
);

// --- Narrow viewport collapse ---
requireMatch(
  closure,
  /@media \(max-width: 45em\)\s*\{[\s\S]*?\.nvx-strategy-page > \.nvx-brand-hero\s*\{[\s\S]*?min-height:\s*calc\(var\(--nvx-space-12\) \* 3\)/,
  'strategy hero minimum height must shrink under 45em',
);

if (failures.length) {
  console.error('External visual closure contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: external widget, typography and late visual closure contract');
