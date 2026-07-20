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
const tokenDocs = read('docs/design-system/tokens.md');
const iconDocs = read('docs/design-system/icons.md');
const numberingDocs = read('docs/design-system/numbering.md');

requireMatch(integrations, /require_once __DIR__ \. '\/nvx-visual-system\.php';/, 'nvx-integrations.php must load nvx-visual-system.php');
requireMatch(visual, /function nvx_visual_icon_svg\(/, 'canonical inline icon registry is missing');
requireMatch(visual, /function nvx_visual_system_normalize_html\(/, 'legacy HTML normalizer is missing');
requireMatch(visual, /#icon-\(location\|phone\|clock\|doctor\)/, 'contact sprite migration map is incomplete');
requireMatch(visual, /resultados-definitivos[\s\S]*efecto-natural/, 'benefit icon migration map is incomplete');
requireMatch(visual, /\.nvx-index-number[\s\S]*--nvx-index-number-size/, 'sequential numbering role is missing');
requireMatch(visual, /ol:not\(\[class\]\)/, 'editorial ordered-list restoration is missing');
requireMatch(visual, /nvx-site-closing-cta[\s\S]*style=/, 'closing CTA inline-style cleanup is missing');

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

requireMatch(fonts, /font-weight:\s*700;/, 'Manrope 700 must be loaded because runtime components request it');
requireMatch(tokenDocs, /#FCFBF8[\s\S]*#756F69/i, 'token documentation does not describe the warm-neutral palette');
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

console.log('PASS: canonical colors, icons, typography roles and numbering contract');
