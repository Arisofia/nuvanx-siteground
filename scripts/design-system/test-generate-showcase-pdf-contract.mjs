#!/usr/bin/env node
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import process from 'node:process';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const scriptRelative = 'scripts/design-system/generate-showcase-pdf.mjs';
const scriptPath = path.join(root, scriptRelative);
const failures = [];

function requireMatch(source, pattern, message) {
  if (!pattern.test(source)) failures.push(message);
}

function requireAbsent(source, pattern, message) {
  if (pattern.test(source)) failures.push(message);
}

function runScript(args, options = {}) {
  return spawnSync(process.execPath, [scriptPath, ...args], {
    cwd: options.cwd || root,
    encoding: 'utf8',
    timeout: 15_000,
  });
}

if (!fs.existsSync(scriptPath)) {
  failures.push(`missing generator script: ${scriptRelative}`);
} else {
  // -------------------------------------------------------------------------
  // Source-level contract: the guard rails the script promises to enforce.
  // -------------------------------------------------------------------------
  const source = fs.readFileSync(scriptPath, 'utf8');

  requireMatch(source, /function readArg\(/, 'script must expose a readArg() CLI argument helper');
  requireMatch(source, /if \(!fs\.existsSync\(htmlPath\)\)/, 'script must guard against a missing showcase HTML file before doing any work');
  requireMatch(source, /throw new Error\(`Showcase HTML not found: \$\{htmlPath\}`\)/, 'missing-HTML error message must include the resolved path');
  requireMatch(source, /await import\('puppeteer'\)/, 'script must lazily import puppeteer');
  requireMatch(source, /Puppeteer is required only while generating the PDF\./, 'script must explain that puppeteer is an on-demand dependency');
  requireMatch(source, /npm install puppeteer --no-save/, 'script must tell the operator how to install puppeteer without persisting it');
  requireMatch(source, /document\.fonts\.check\('16px "Playfair Display"'\)/, 'script must verify Playfair Display actually loaded in the page');
  requireMatch(source, /document\.fonts\.check\('16px "Manrope"'\)/, 'script must verify Manrope actually loaded in the page');
  requireMatch(source, /status\.sheets !== 6/, 'script must assert exactly 6 showcase pages before exporting');
  requireMatch(source, /stats\.size < 50_000/, 'script must reject an unexpectedly small generated PDF');
  requireMatch(source, /headless:\s*true/, 'script must launch the browser headlessly');
  requireMatch(source, /PUPPETEER_EXECUTABLE_PATH/, 'script must honor a custom PUPPETEER_EXECUTABLE_PATH override');
  requireMatch(source, /preferCSSPageSize:\s*true/, 'script must defer to CSS-defined page sizing when exporting');
  requireMatch(source, /console\.log\(`PASS: generated/, 'script must log a PASS summary including the output path, size and page count');

  // -------------------------------------------------------------------------
  // Functional contract: exercise the real guard clauses via a subprocess.
  // Puppeteer is intentionally not installed in this environment, so every
  // invocation below is expected to fail fast without a browser or network.
  // -------------------------------------------------------------------------

  // 1. Default arguments resolve to the real showcase HTML, which exists, so
  //    the script should proceed to the puppeteer import and fail there.
  const defaultRun = runScript([]);
  if (defaultRun.status === 0) {
    failures.push('running the generator with default arguments unexpectedly succeeded without puppeteer installed');
  }
  requireMatch(
    defaultRun.stderr,
    /Puppeteer is required only while generating the PDF\./,
    'default invocation must reach the puppeteer guard once the default HTML is found',
  );

  // 2. An explicit --html pointing at a file that does not exist must fail
  //    fast with a descriptive, absolute-path error, before ever attempting
  //    to import puppeteer.
  const missingHtmlPath = path.join(os.tmpdir(), 'nvx-showcase-does-not-exist.html');
  const missingHtmlRun = runScript(['--html', missingHtmlPath]);
  if (missingHtmlRun.status === 0) {
    failures.push('running the generator against a missing --html file unexpectedly succeeded');
  }
  requireMatch(
    missingHtmlRun.stderr,
    new RegExp(`Showcase HTML not found: ${missingHtmlPath.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}`),
    'missing --html file must produce an error naming the resolved absolute path',
  );
  requireAbsent(
    missingHtmlRun.stderr,
    /Puppeteer is required only while generating the PDF\./,
    'missing-HTML failures must short-circuit before the puppeteer import is attempted',
  );

  // 3. A --html flag supplied without a following value must fall back to
  //    the documented default rather than resolving to an empty path.
  const missingValueRun = runScript(['--html']);
  if (missingValueRun.status === 0) {
    failures.push('running the generator with a valueless --html flag unexpectedly succeeded');
  }
  requireMatch(
    missingValueRun.stderr,
    /Puppeteer is required only while generating the PDF\./,
    'a valueless --html flag must fall back to the default showcase HTML and reach the puppeteer guard',
  );
  requireAbsent(
    missingValueRun.stderr,
    /Showcase HTML not found/,
    'a valueless --html flag must not be treated as a missing/invalid path',
  );

  // 4. Relative --html paths must resolve against the script's own root
  //    directory, not the caller's current working directory.
  const relativeMissingRun = runScript(['--html', 'docs/design-system/missing-relative-showcase.html'], {
    cwd: os.tmpdir(),
  });
  if (relativeMissingRun.status === 0) {
    failures.push('running the generator against a missing relative --html file unexpectedly succeeded');
  }
  const expectedResolvedPath = path.join(root, 'docs/design-system/missing-relative-showcase.html');
  requireMatch(
    relativeMissingRun.stderr,
    new RegExp(`Showcase HTML not found: ${expectedResolvedPath.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}`),
    'relative --html paths must resolve against the repository root, independent of the process cwd',
  );
}

if (failures.length) {
  console.error('generate-showcase-pdf.mjs contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: generate-showcase-pdf.mjs guards its inputs and defers safely to puppeteer');