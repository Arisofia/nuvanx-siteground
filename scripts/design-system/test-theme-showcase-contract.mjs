#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const htmlRelative = 'docs/design-system/theme-showcase.html';
const pdfRelative = 'docs/design-system/theme-showcase.pdf';
const htmlPath = path.join(root, htmlRelative);
const pdfPath = path.join(root, pdfRelative);
const failures = [];

function requireMatch(source, pattern, message) {
  if (!pattern.test(source)) failures.push(message);
}

function requireAbsent(source, pattern, message) {
  if (pattern.test(source)) failures.push(message);
}

// ---------------------------------------------------------------------------
// docs/design-system/theme-showcase.html
// ---------------------------------------------------------------------------
if (!fs.existsSync(htmlPath)) {
  failures.push(`missing showcase HTML: ${htmlRelative}`);
} else {
  const html = fs.readFileSync(htmlPath, 'utf8');

  requireMatch(html, /<!DOCTYPE html>/i, 'showcase HTML must declare a doctype');
  requireMatch(html, /<html lang="es">/, 'showcase HTML must declare the Spanish locale');
  requireMatch(html, /<title>NUVANX Theme Factory Showcase<\/title>/, 'showcase HTML must carry the expected title');

  requireMatch(
    html,
    /fonts\.googleapis\.com\/css2\?family=Playfair\+Display[^"]*family=Manrope/,
    'showcase HTML must load Playfair Display and Manrope from Google Fonts',
  );

  const requiredTokens = [
    ['--nvx-light', '#fcfbf8'],
    ['--nvx-surface-base', '#f8f7f4'],
    ['--nvx-ink', '#1a1a1a'],
    ['--nvx-charcoal', '#2b2926'],
    ['--nvx-accent-sapphire', '#1a365d'],
    ['--nvx-accent-gold', '#9a8a78'],
  ];
  for (const [token, value] of requiredTokens) {
    requireMatch(html, new RegExp(`${token}:\\s*${value}`, 'i'), `showcase HTML must define ${token} as ${value}`);
  }

  requireMatch(html, /--nvx-serif:\s*'Playfair Display', serif/, 'showcase HTML must map --nvx-serif to Playfair Display');
  requireMatch(html, /--nvx-sans:\s*'Manrope', sans-serif/, 'showcase HTML must map --nvx-sans to Manrope');

  requireMatch(html, /font-family:\s*var\(--nvx-serif\)/, 'headings must reference the --nvx-serif token');
  requireMatch(html, /font-family:\s*var\(--nvx-sans\)/, 'body copy must reference the --nvx-sans token');

  requireMatch(html, /\.nvx-btn\s*\{/, 'showcase HTML must define the base .nvx-btn component');
  requireMatch(html, /border-radius:\s*999px/, 'buttons must keep the canonical pill shape');
  for (const variant of ['nvx-btn--primary', 'nvx-btn--sapphire', 'nvx-btn--gold']) {
    requireMatch(html, new RegExp(`\\.${variant}\\s*\\{`), `showcase HTML must define the .${variant} button variant`);
  }

  for (const swatch of ['swatch-light', 'swatch-ink', 'swatch-sapphire', 'swatch-gold']) {
    requireMatch(html, new RegExp(`\\.${swatch}\\s*\\{`), `showcase HTML must define the .${swatch} palette swatch`);
  }

  requireMatch(html, /1\. Paleta Monot[eé]mática \(Producci[oó]n\)/, 'showcase HTML must document the production monochrome palette section');
  requireMatch(html, /2\. Variantes de Acento \(Exclusivo Marketing\)/, 'showcase HTML must document the marketing-only accent variants section');
  requireMatch(html, /3\. Componentes y Tipograf[ií]a/, 'showcase HTML must document the components and typography section');

  requireAbsent(html, /!important/, 'showcase HTML must not rely on !important overrides');
  requireAbsent(html, /Bodoni|Cormorant|Pinyon/i, 'showcase HTML must not reference retired decorative fonts');
}

// ---------------------------------------------------------------------------
// docs/design-system/theme-showcase.pdf
// ---------------------------------------------------------------------------
if (!fs.existsSync(pdfPath)) {
  failures.push(`missing showcase PDF: ${pdfRelative}`);
} else {
  const buffer = fs.readFileSync(pdfPath);
  const header = buffer.subarray(0, 5).toString('latin1');
  if (header !== '%PDF-') {
    failures.push(`showcase PDF must start with the %PDF- magic header, found "${header}"`);
  }

  const tail = buffer.subarray(-16).toString('latin1');
  if (!tail.includes('%%EOF')) {
    failures.push('showcase PDF must end with the %%EOF trailer marker');
  }

  if (buffer.length < 50_000) {
    failures.push(`showcase PDF is unexpectedly small (${buffer.length} bytes); generation may be incomplete`);
  }
}

if (failures.length) {
  console.error('Theme showcase artifact contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: theme-showcase.html tokens/components and theme-showcase.pdf artifact are well-formed');