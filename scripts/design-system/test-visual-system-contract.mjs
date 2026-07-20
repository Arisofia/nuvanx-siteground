#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const exists = (relative) => fs.existsSync(path.join(root, relative));
const failures = [];

function requireMatch(source, pattern, message) {
  if (!pattern.test(source)) failures.push(message);
}

function requireAbsent(source, pattern, message) {
  if (pattern.test(source)) failures.push(message);
}

const integrations = read('wp-content/themes/nuvanx-medical/inc/nvx-integrations.php');
const visual = read('wp-content/themes/nuvanx-medical/inc/nvx-visual-system.php');
const tokens = read('wp-content/themes/nuvanx-medical/assets/css/nvx-tokens.css');
const fonts = read('wp-content/themes/nuvanx-medical/assets/css/nvx-fonts.css');
const home = read('wp-content/themes/nuvanx-medical/assets/css/nvx-brand-home.css');
const footer = read('wp-content/themes/nuvanx-medical/assets/css/nvx-footer.css');
const tokenDocs = read('docs/design-system/tokens.md');
const typeDocs = read('docs/design-system/typography.md');
const iconDocs = read('docs/design-system/icons.md');
const numberingDocs = read('docs/design-system/numbering.md');

requireMatch(integrations, /require_once __DIR__ \. '\/nvx-visual-system\.php';/, 'nvx-integrations.php must load nvx-visual-system.php');
requireMatch(visual, /function nvx_visual_icon_svg\(/, 'canonical inline icon registry is missing');
requireMatch(visual, /function nvx_visual_system_normalize_html\(/, 'legacy HTML normalizer is missing');
requireMatch(visual, /resultados-definitivos[\s\S]*efecto-natural/, 'benefit icon migration map is incomplete');
requireMatch(visual, /location\|phone\|clock\|doctor/, 'contact sprite migration map is incomplete');
requireMatch(visual, /\.nvx-index-number[\s\S]*--nvx-index-number-size/, 'sequential numbering role is missing');
requireMatch(visual, /ol:not\(\[class\]\)/, 'editorial ordered-list restoration is missing');
requireMatch(visual, /nvx-site-closing-cta[\s\S]*style=/, 'closing CTA inline-style cleanup is missing');

const exactTypography = [
  ['--nvx-serif', '"Playfair Display", Georgia, "Times New Roman", serif'],
  ['--nvx-sans', '"Manrope", "Helvetica Neue", Arial, sans-serif'],
  ['--nvx-type-display', 'clamp(2.8rem, 5vw, 4.2rem)'],
  ['--nvx-type-h1', 'clamp(2.2rem, 4vw, 3.2rem)'],
  ['--nvx-type-h2', 'clamp(1.7rem, 3vw, 2.4rem)'],
  ['--nvx-type-h3', '1.4rem'],
  ['--nvx-type-body', '1.0625rem'],
  ['--nvx-type-small', '0.875rem'],
  ['--nvx-type-caption', '0.75rem'],
  ['--nvx-fw-heading', '500'],
  ['--nvx-lh-body', '1.6'],
  ['--nvx-lh-display', '1.15'],
  ['--nvx-track-display', '-0.02em'],
  ['--nvx-track-caption', '0.04em'],
];
for (const [token, value] of exactTypography) {
  const escaped = `${token}: ${value}`.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  requireMatch(tokens, new RegExp(escaped), `${token} must equal ${value}`);
}

for (const token of [
  '--nvx-icon-xs',
  '--nvx-icon-sm',
  '--nvx-icon-md',
  '--nvx-icon-lg',
  '--nvx-icon-frame',
  '--nvx-icon-stroke',
  '--nvx-index-number-size',
  '--nvx-index-number-weight',
  '--nvx-index-number-track',
]) {
  requireMatch(tokens, new RegExp(`${token.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\s*:`), `missing token ${token}`);
}

requireMatch(fonts, /family=Playfair\+Display/, 'Playfair Display Google Fonts request is missing');
requireMatch(fonts, /family=Manrope:wght@300;400;500;600;700/, 'Manrope weight request is incomplete');
requireAbsent(fonts, /Bodoni|Cormorant/i, 'font loader must not request alternate serif families');
requireAbsent(tokens, /Bodoni|Cormorant|--nvx-serif-[123]|--nvx-sans-[123]/i, 'tokens must expose only the canonical font pair');
requireAbsent(home, /font-size:\s*(?:clamp\(|[0-9.]+rem)/i, 'Home contains a private font-size outside the canonical tokens');
requireAbsent(footer, /font-size:\s*(?:clamp\(|[0-9.]+rem)/i, 'Footer contains a private font-size outside the canonical tokens');

requireMatch(visual, /font-weight:\s*var\(--nvx-fw-heading\)/, 'global headings must use the canonical 500 weight token');
requireMatch(visual, /\.nvx-caption[\s\S]*--nvx-track-caption[\s\S]*text-transform:\s*uppercase/, 'caption role is incomplete');
requireMatch(tokenDocs, /Playfair Display[\s\S]*Manrope/, 'token documentation does not describe the canonical pair');
requireMatch(typeDocs, /Playfair Display[\s\S]*Manrope/, 'typography documentation does not describe the canonical pair');
requireMatch(typeDocs, /Bodoni Moda y Cormorant Garamond no forman parte del sistema activo/i, 'typography docs must explicitly retire alternate serif families');
requireMatch(tokenDocs, /Fuentes prohibidas[\s\S]*Bodoni Moda[\s\S]*Cormorant Garamond/i, 'token docs must explicitly retire alternate serif families');
requireMatch(iconDocs, /currentColor[\s\S]*--nvx-icon-stroke/, 'icon documentation does not describe the currentColor contract');
requireMatch(numberingDocs, /01[\s\S]*02[\s\S]*03/, 'numbering documentation does not define the canonical sequence');

const retiredAssets = [
  'resultados-definitivos.svg',
  'recuperacion-rapida.svg',
  'paciente-despierto.svg',
  'sin-bisturi.svg',
  'solo-una-vez.svg',
  'efecto-natural.svg',
];
for (const asset of retiredAssets) {
  if (exists(`wp-content/themes/nuvanx-medical/assets/images/benefits/${asset}`)) {
    failures.push(`retired color-locked asset still exists: ${asset}`);
  }
}

for (const temporary of [
  '.github/workflows/one-time-visual-system-consolidation.yml',
  'scripts/design-system/run-visual-system-consolidation.sh',
  'scripts/design-system/.consolidate_visual_system.py',
]) {
  if (exists(temporary)) failures.push(`temporary migration machinery still exists: ${temporary}`);
}

requireAbsent(visual, /#(?:9A8A78|B89A5B|C5A880)/i, 'canonical visual module contains a retired gold literal');

if (failures.length) {
  console.error('Visual system contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: Playfair Display + Manrope, canonical colors, icons and numbering');
