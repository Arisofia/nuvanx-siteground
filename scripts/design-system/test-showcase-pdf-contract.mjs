#!/usr/bin/env node
// Contract test for scripts/design-system/generate-showcase-pdf.mjs and its
// input fixture docs/design-system/theme-showcase.html.
//
// This is a static/content contract test (no headless browser is launched)
// so it can run in any environment without a Puppeteer/Chromium install.
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const exists = (relative) => fs.existsSync(path.join(root, relative));
const failures = [];

/**
 * Records a failure when the source does not match the specified pattern.
 * @param {string} source - The content to test.
 * @param {RegExp} pattern - The pattern to match against the content.
 * @param {string} message - The failure message to record.
 */
function requireMatch(source, pattern, message) {
  if (!pattern.test(source)) failures.push(message);
}

/**
 * Records a failure when the source matches a prohibited pattern.
 * @param {string} source - The content to inspect.
 * @param {RegExp} pattern - The pattern that must not match the source.
 * @param {string} message - The failure message to record.
 */
function requireAbsent(source, pattern, message) {
  if (pattern.test(source)) failures.push(message);
}

const scriptPath = 'scripts/design-system/generate-showcase-pdf.mjs';
const htmlPath = 'docs/design-system/theme-showcase.html';
const pdfPath = 'docs/design-system/theme-showcase.pdf';

if (!exists(scriptPath)) {
  console.error(`Missing script: ${scriptPath}`);
  process.exit(1);
}
if (!exists(htmlPath)) {
  console.error(`Missing showcase fixture: ${htmlPath}`);
  process.exit(1);
}

const script = read(scriptPath);
const html = read(htmlPath);

// --- generate-showcase-pdf.mjs: CLI contract -------------------------------

requireMatch(script, /^#!\/usr\/bin\/env node/, 'script must be directly executable via shebang');
requireMatch(
  script,
  /readArg\('--html', 'docs\/design-system\/theme-showcase\.html'\)/,
  'default --html argument must point at the canonical showcase fixture',
);
requireMatch(
  script,
  /readArg\('--output', 'docs\/design-system\/theme-showcase\.pdf'\)/,
  'default --output argument must point at the canonical showcase PDF',
);
requireMatch(
  script,
  /if\s*\(!fs\.existsSync\(htmlPath\)\)\s*\{\s*throw new Error/,
  'script must fail fast when the input HTML is missing',
);

// --- Puppeteer usage must be defensive and sandboxed ------------------------

requireMatch(
  script,
  /await import\('puppeteer'\)/,
  'script must lazily import puppeteer so it is only required for PDF generation',
);
requireMatch(
  script,
  /catch\s*\(error\)\s*\{[\s\S]*npm install puppeteer --no-save[\s\S]*throw error/,
  'missing puppeteer dependency must produce an actionable install hint and rethrow',
);
requireMatch(script, /headless:\s*true/, 'browser must launch headless for CI usage');
requireMatch(
  script,
  /args:\s*\['--no-sandbox', '--disable-setuid-sandbox'\]/,
  'browser must launch with sandbox-safe flags for containerized CI runners',
);
requireMatch(
  script,
  /process\.env\.PUPPETEER_EXECUTABLE_PATH/,
  'script must respect a custom Chromium executable path when provided',
);

// --- Font + page-count assertions before writing the PDF --------------------

requireMatch(script, /await document\.fonts\.ready/, 'script must wait for web fonts to finish loading');
requireMatch(
  script,
  /document\.fonts\.check\(\s*'16px "Playfair Display"'\s*\)/,
  'script must verify Playfair Display actually loaded',
);
requireMatch(
  script,
  /document\.fonts\.check\(\s*'16px "Manrope"'\s*\)/,
  'script must verify Manrope actually loaded',
);
requireMatch(
  script,
  /if\s*\(!status\.playfair\s*\|\|\s*!status\.manrope\)\s*\{\s*throw new Error/,
  'script must abort the PDF export if either official font failed to load',
);
requireMatch(
  script,
  /document\.querySelectorAll\('\.sheet'\)\.length/,
  'script must count rendered `.sheet` pages before exporting',
);
requireMatch(
  script,
  /if\s*\(status\.sheets\s*!==\s*6\)\s*\{\s*throw new Error/,
  'script must require exactly 6 showcase pages',
);

// --- Output handling ---------------------------------------------------------

requireMatch(
  script,
  /fs\.mkdirSync\(path\.dirname\(outputPath\), \{ recursive: true \}\)/,
  'script must ensure the output directory exists before writing the PDF',
);
requireMatch(
  script,
  /printBackground:\s*true/,
  'PDF export must render backgrounds so brand colors are preserved',
);
requireMatch(
  script,
  /preferCSSPageSize:\s*true/,
  'PDF export must defer page sizing to the document CSS',
);
requireMatch(
  script,
  /margin:\s*\{\s*top:\s*0,\s*right:\s*0,\s*bottom:\s*0,\s*left:\s*0\s*\}/,
  'PDF export must use zero margins so the showcase controls its own layout',
);
requireMatch(
  script,
  /if\s*\(stats\.size\s*<\s*50_000\)\s*\{\s*throw new Error/,
  'script must guard against generating a suspiciously small/empty PDF',
);
requireMatch(
  script,
  /\}\s*finally\s*\{\s*await browser\.close\(\);/,
  'browser must always be closed, even if generation throws',
);

// --- theme-showcase.html fixture contract ------------------------------------

requireMatch(
  html,
  /nvx-fonts\.css/,
  'showcase fixture must load the canonical nvx-fonts.css file',
);
requireMatch(
  html,
  /nvx-tokens\.css/,
  'showcase fixture must load the canonical nvx-tokens.css file',
);
requireAbsent(
  html,
  /Bodoni|Cormorant|\bInter\b|Source Sans|Pinyon/i,
  'showcase fixture must not reference any non-canonical/decorative font family',
);
requireMatch(
  html,
  /--showcase-sapphire:\s*#[0-9a-f]{6};/i,
  'showcase fixture must expose a local sapphire accent variable',
);
requireMatch(
  html,
  /--showcase-gold:\s*#[0-9a-f]{6};/i,
  'showcase fixture must expose a local gold accent variable',
);
requireAbsent(html, /!important/i, 'showcase fixture must not rely on !important overrides');

{
  const sheetMatches = html.match(/class="[^"]*\bsheet\b[^"]*"/g) || [];
  if (sheetMatches.length !== 6) {
    failures.push(`showcase fixture must define exactly 6 .sheet sections (found ${sheetMatches.length})`);
  }
}

// --- Sanity check on the already-generated artifact --------------------------

if (exists(pdfPath)) {
  const stats = fs.statSync(path.join(root, pdfPath));
  if (stats.size < 50_000) {
    failures.push(`checked-in ${pdfPath} is smaller than the generator's own 50KB floor (${stats.size} bytes)`);
  }
  const pdfHeader = fs.readFileSync(path.join(root, pdfPath)).subarray(0, 5).toString('latin1');
  requireMatch(
    pdfHeader,
    /^%PDF-/,
    `${pdfPath} must be a valid PDF file (missing %PDF- header)`,
  );
} else {
  failures.push(`expected the generated artifact to be committed at ${pdfPath}`);
}

if (failures.length) {
  console.error('Showcase PDF generator contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: generate-showcase-pdf.mjs contract and theme-showcase.html fixture');
