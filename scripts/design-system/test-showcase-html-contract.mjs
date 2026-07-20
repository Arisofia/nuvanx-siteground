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

function requireCount(source, pattern, expected, message) {
  const count = (source.match(pattern) || []).length;
  if (count !== expected) failures.push(`${message} (expected ${expected}, found ${count})`);
}

const html = read('docs/design-system/theme-showcase.html');

// --- Document shell ---------------------------------------------------
requireMatch(html, /^<!DOCTYPE html>/, 'showcase must declare an HTML5 doctype');
requireMatch(html, /<html lang="es">/, 'showcase must declare the Spanish language attribute');
requireMatch(html, /<title>NUVANX Theme Factory Showcase<\/title>/, 'showcase title is missing or renamed');
requireCount(html, /<div\b/g, (html.match(/<\/div>/g) || []).length, 'opening and closing <div> tags must balance');

// --- Typography contract: Playfair Display + Manrope only -------------
requireMatch(html, /family=Playfair\+Display:wght@400;500;600/, 'Playfair Display Google Fonts request is missing or changed');
requireMatch(html, /family=Manrope:wght@400;500;600/, 'Manrope Google Fonts request is missing or changed');
requireMatch(html, /--nvx-serif:\s*'Playfair Display',\s*serif;/, 'serif token must map to Playfair Display');
requireMatch(html, /--nvx-sans:\s*'Manrope',\s*sans-serif;/, 'sans token must map to Manrope');
requireMatch(html, /h1,\s*h2,\s*h3\s*\{[\s\S]*?font-family:\s*var\(--nvx-serif\)/, 'headings must use the serif token');
requireMatch(html, /body\s*\{[\s\S]*?font-family:\s*var\(--nvx-sans\)/, 'body copy must use the sans token');
requireAbsent(html, /Bodoni|Cormorant|Pinyon/i, 'showcase must not reference retired decorative serif fonts');
requireAbsent(html, /\bInter\b|Source Sans/i, 'showcase must not reference alternate sans fonts');

// --- Quiet Luxury tone & production palette ----------------------------
requireMatch(html, /Quiet Luxury/, 'showcase must name the Quiet Luxury contract explicitly');
requireMatch(html, /--nvx-light:\s*#fcfbf8;/i, 'Metal Pulido "light" token value is missing or changed');
requireMatch(html, /--nvx-ink:\s*#1a1a1a;/i, 'Metal Pulido "ink" token value is missing or changed');
requireMatch(html, /Light \(#fcfbf8\)/, 'swatch label for Light must match its token hex value');
requireMatch(html, /Ink \(#1a1a1a\)/, 'swatch label for Ink must match its token hex value');

// --- Marketing-only accent variants ------------------------------------
requireMatch(html, /--nvx-accent-sapphire:\s*#1a365d;/i, 'Zafiro accent token value is missing or changed');
requireMatch(html, /--nvx-accent-gold:\s*#9a8a78;/i, 'Oro accent token value is missing or changed');
requireMatch(html, /Zafiro \(#1a365d\)/, 'swatch label for Zafiro must match its token hex value');
requireMatch(html, /Oro Suave \(#9a8a78\)/, 'swatch label for Oro Suave must match its token hex value');
requireMatch(html, /Exclusivo Marketing/, 'accent variants section must be labeled marketing-only');

// --- Button component contract -----------------------------------------
requireMatch(html, /\.nvx-btn\s*\{[\s\S]*?border-radius:\s*999px;/, 'buttons must keep the canonical pill shape');
requireMatch(html, /\.nvx-btn\s*\{[\s\S]*?font-family:\s*var\(--nvx-sans\);/, 'buttons must use the functional (Manrope) typeface');
for (const variant of ['nvx-btn--primary', 'nvx-btn--sapphire', 'nvx-btn--gold']) {
  requireMatch(html, new RegExp(`\\.${variant}\\s*\\{`), `missing ${variant} button variant style block`);
  requireMatch(html, new RegExp(`class="nvx-btn ${variant}"`), `missing rendered ${variant} button in the markup`);
}
requireCount(html, /class="nvx-btn /g, 3, 'showcase must render exactly three demonstration buttons');

// --- Structural sanity: palette swatches ---------------------------------
for (const swatch of ['swatch-light', 'swatch-ink', 'swatch-sapphire', 'swatch-gold']) {
  requireMatch(html, new RegExp(`\\.${swatch}\\s*\\{`), `missing .${swatch} style rule`);
  requireMatch(html, new RegExp(`color-swatch ${swatch}`), `missing rendered .${swatch} swatch`);
}
requireCount(html, /class="color-swatch /g, 4, 'showcase must render exactly four palette swatches');
requireCount(html, /<h2>/g, 3, 'showcase must present exactly three numbered sections');

if (failures.length) {
  console.error('theme-showcase.html contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: theme-showcase.html keeps Playfair Display + Manrope, Metal Pulido and marketing-only accents');