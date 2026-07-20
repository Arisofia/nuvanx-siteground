#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { pathToFileURL, fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');

/**
 * Retrieves a command-line argument value or uses a fallback.
 * @param {string} name - The argument name to find in the process arguments.
 * @param {*} fallback - The value to return when the argument is missing or has no value.
 * @return {*} The argument value or the fallback value.
 */
function readArg(name, fallback) {
  const index = process.argv.indexOf(name);
  return index >= 0 && process.argv[index + 1] ? process.argv[index + 1] : fallback;
}

const htmlPath = path.resolve(root, readArg('--html', 'docs/design-system/theme-showcase.html'));
const outputPath = path.resolve(root, readArg('--output', 'docs/design-system/theme-showcase.pdf'));

if (!fs.existsSync(htmlPath)) {
  throw new Error(`Showcase HTML not found: ${htmlPath}`);
}

let puppeteer;
try {
  ({ default: puppeteer } = await import('puppeteer'));
} catch (error) {
  console.error('Puppeteer is required only while generating the PDF.');
  console.error('Run: npm install puppeteer --no-save');
  throw error;
}

const launchOptions = {
  headless: true,
  args: ['--no-sandbox', '--disable-setuid-sandbox'],
};
if (process.env.PUPPETEER_EXECUTABLE_PATH) {
  launchOptions.executablePath = process.env.PUPPETEER_EXECUTABLE_PATH;
}

const browser = await puppeteer.launch(launchOptions);
try {
  const page = await browser.newPage();
  await page.goto(pathToFileURL(htmlPath).href, {
    waitUntil: 'networkidle0',
    timeout: 120000,
  });

  await page.evaluate(async () => {
    await document.fonts.ready;
  });

  const status = await page.evaluate(() => ({
    playfair: document.fonts.check('16px "Playfair Display"'),
    manrope: document.fonts.check('16px "Manrope"'),
    sheets: document.querySelectorAll('.sheet').length,
  }));

  if (!status.playfair || !status.manrope) {
    throw new Error(`Official fonts did not load: ${JSON.stringify(status)}`);
  }
  if (status.sheets !== 6) {
    throw new Error(`Expected 6 showcase pages, found ${status.sheets}`);
  }

  fs.mkdirSync(path.dirname(outputPath), { recursive: true });
  await page.pdf({
    path: outputPath,
    printBackground: true,
    preferCSSPageSize: true,
    margin: { top: 0, right: 0, bottom: 0, left: 0 },
  });

  const stats = fs.statSync(outputPath);
  if (stats.size < 50_000) {
    throw new Error(`Generated PDF is unexpectedly small (${stats.size} bytes)`);
  }

  console.log(`PASS: generated ${path.relative(root, outputPath)} (${stats.size} bytes, ${status.sheets} pages)`);
} finally {
  await browser.close();
}
