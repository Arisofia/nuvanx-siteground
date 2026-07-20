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
requireAbsent(closure, /(?:\d+(?:\.\d+)?px|\d+(?:\.\d+)?rem)/i, 'external closure must use canonical size tokens only');

if (failures.length) {
  console.error('External visual closure contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: Joinchat and late typography use canonical NUVANX tokens');
