#!/usr/bin/env node
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';
import { spawnSync } from 'node:child_process';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const scriptPath = path.join(root, 'scripts/design-system/generate-showcase-pdf.mjs');
const source = fs.readFileSync(scriptPath, 'utf8');
const failures = [];

function requireMatch(text, pattern, message) {
  if (!pattern.test(text)) failures.push(message);
}

function run(args) {
  return spawnSync(process.execPath, [scriptPath, ...args], {
    cwd: root,
    encoding: 'utf8',
    timeout: 20000,
  });
}

// --- Missing HTML input must fail fast, before touching Puppeteer -------
const missingHtmlPath = path.join(os.tmpdir(), 'nvx-showcase-missing-does-not-exist.html');
if (fs.existsSync(missingHtmlPath)) fs.rmSync(missingHtmlPath);

const missingHtmlResult = run(['--html', missingHtmlPath]);
if (missingHtmlResult.status === 0) {
  failures.push('script must exit with a non-zero status when --html points at a missing file');
}
if (!missingHtmlResult.stderr.includes('Showcase HTML not found')) {
  failures.push('script must report "Showcase HTML not found" when the HTML input is missing');
}
if (!missingHtmlResult.stderr.includes(missingHtmlPath)) {
  failures.push('missing-HTML error must include the resolved path that was checked');
}
if (missingHtmlResult.stderr.includes('Puppeteer is required')) {
  failures.push('the missing-HTML check must short-circuit before attempting to import Puppeteer');
}

// --- Valid HTML input still requires Puppeteer to proceed ----------------
const tmpDir = fs.mkdtempSync(path.join(os.tmpdir(), 'nvx-showcase-'));
const validHtmlPath = path.join(tmpDir, 'sample.html');
const outputPath = path.join(tmpDir, 'out.pdf');
fs.writeFileSync(validHtmlPath, '<!doctype html><html><body>sample</body></html>');

try {
  const validHtmlResult = run(['--html', validHtmlPath, '--output', outputPath]);
  if (validHtmlResult.status === 0) {
    failures.push('script must exit with a non-zero status when Puppeteer is unavailable');
  }
  if (!validHtmlResult.stderr.includes('Puppeteer is required only while generating the PDF.')) {
    failures.push('script must surface an instructive message when the puppeteer module cannot be imported');
  }
  if (!validHtmlResult.stderr.includes('npm install puppeteer --no-save')) {
    failures.push('script must tell the operator how to install puppeteer on demand');
  }
  if (validHtmlResult.stderr.includes('Showcase HTML not found')) {
    failures.push('a valid --html path must not trigger the missing-file error');
  }
  if (fs.existsSync(outputPath)) {
    failures.push('no PDF should be written when PDF generation never starts');
  }
} finally {
  fs.rmSync(tmpDir, { recursive: true, force: true });
}

// --- Default --html/--output fall back to the canonical showcase paths ---
const defaultShowcaseHtml = path.join(root, 'docs/design-system/theme-showcase.html');
if (!fs.existsSync(defaultShowcaseHtml)) {
  failures.push('default showcase HTML (docs/design-system/theme-showcase.html) must exist on disk');
}

const defaultArgsResult = run(['--output', path.join(os.tmpdir(), 'nvx-showcase-default-output.pdf')]);
if (defaultArgsResult.stderr.includes('Showcase HTML not found')) {
  failures.push('omitting --html must fall back to the canonical docs/design-system/theme-showcase.html file');
}
if (!defaultArgsResult.stderr.includes('Puppeteer is required')) {
  failures.push('omitting --html should still proceed past the existence check and fail only on the puppeteer import');
}

// --- A trailing --html flag with no value must fall back to the default --
const trailingFlagResult = run(['--html']);
if (trailingFlagResult.stderr.includes('Showcase HTML not found')) {
  failures.push('a trailing --html flag with no value must fall back to the default path instead of failing');
}

// --- Committed showcase PDF artifact matches the generator's own rules ---
const committedPdfPath = path.join(root, 'docs/design-system/theme-showcase.pdf');
if (!fs.existsSync(committedPdfPath)) {
  failures.push('committed docs/design-system/theme-showcase.pdf is missing');
} else {
  const pdfBuffer = fs.readFileSync(committedPdfPath);
  if (pdfBuffer.subarray(0, 5).toString('latin1') !== '%PDF-') {
    failures.push('theme-showcase.pdf must start with a valid %PDF- header');
  }
  if (!pdfBuffer.subarray(-1024).toString('latin1').includes('%%EOF')) {
    failures.push('theme-showcase.pdf must end with a valid %%EOF marker');
  }
  if (pdfBuffer.length < 50_000) {
    failures.push(`committed theme-showcase.pdf is smaller than the generator's own 50KB floor (${pdfBuffer.length} bytes)`);
  }
}

// --- Static contract: validation and safety checks remain in the source --
requireMatch(source, /if \(!status\.playfair \|\| !status\.manrope\)/, 'script must verify both canonical fonts loaded before generating the PDF');
requireMatch(source, /status\.sheets !== 6/, 'script must enforce the expected six-page showcase deck');
requireMatch(source, /stats\.size < 50_000/, 'script must reject unexpectedly small PDF output');
requireMatch(source, /args: \['--no-sandbox', '--disable-setuid-sandbox'\]/, 'headless launch must keep the sandbox-safe flags for CI runners');
requireMatch(source, /await browser\.close\(\);/, 'script must always close the browser (cleanup in a finally block)');
requireMatch(source, /preferCSSPageSize: true/, 'PDF export must respect the showcase page CSS sizing');

if (failures.length) {
  console.error('generate-showcase-pdf.mjs contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: generate-showcase-pdf.mjs validates inputs and fails safely without puppeteer');