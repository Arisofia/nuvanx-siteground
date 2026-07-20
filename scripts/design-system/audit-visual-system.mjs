#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const theme = path.join(root, 'wp-content/themes/nuvanx-medical');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const walk = (directory) => fs.readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
  const target = path.join(directory, entry.name);
  return entry.isDirectory() ? walk(target) : [target];
});

const visual = read('wp-content/themes/nuvanx-medical/inc/nvx-visual-system.php');
const tokens = read('wp-content/themes/nuvanx-medical/assets/css/nvx-tokens.css');
const runtimeFiles = walk(theme).filter((file) => /\.(?:php|css|svg)$/i.test(file));
const rows = runtimeFiles.map((file) => ({
  file: path.relative(root, file).replaceAll('\\', '/'),
  source: fs.readFileSync(file, 'utf8'),
}));

const bannedColors = ['#9A8A78', '#B89A5B', '#C5A880'];
const activeRetiredColorFindings = [];
for (const row of rows) {
  // Historical source strings are tolerated only where the final normalizer explicitly removes them.
  const isGovernedLegacyMarkup = /(?:nvx-content-presentation\.php|nvx-components\.css)$/i.test(row.file);
  for (const color of bannedColors) {
    if (row.source.toUpperCase().includes(color.toUpperCase()) && !isGovernedLegacyMarkup) {
      activeRetiredColorFindings.push({ file: row.file, color });
    }
  }
}

const benefitReferences = rows.flatMap((row) => [
  ...row.source.matchAll(/assets\/images\/benefits\/([a-z0-9-]+\.svg)/giu),
].map((match) => ({ file: row.file, asset: match[1] })));

const contactSpriteReferences = rows.flatMap((row) => [
  ...row.source.matchAll(/<use\s+href=["']#icon-(location|phone|clock|doctor)["']/giu),
].map((match) => ({ file: row.file, icon: match[1] })));

const iconClasses = [
  '.nvx-icon',
  '.nvx-laser-icon',
  '.nvx-aes-icon',
  '.nvx-endolift-step__icon',
  '.nvx-benefit-icon',
  '.icon-whatsapp',
];
const missingIconClasses = iconClasses.filter((selector) => !visual.includes(selector));

const requiredTokens = [
  '--nvx-icon-xs',
  '--nvx-icon-sm',
  '--nvx-icon-md',
  '--nvx-icon-lg',
  '--nvx-icon-frame',
  '--nvx-icon-stroke',
  '--nvx-index-number-size',
  '--nvx-index-number-weight',
  '--nvx-index-number-track',
];
const missingTokens = requiredTokens.filter((token) => !tokens.includes(`${token}:`));

const retiredAssets = [
  'resultados-definitivos.svg',
  'recuperacion-rapida.svg',
  'paciente-despierto.svg',
  'sin-bisturi.svg',
  'solo-una-vez.svg',
  'efecto-natural.svg',
];
const survivingRetiredAssets = retiredAssets.filter((asset) => fs.existsSync(path.join(theme, 'assets/images/benefits', asset)));

const benefitMigrationCovered = benefitReferences.every(({ asset }) => visual.includes(asset.replace(/\.svg$/i, '')));
const contactMigrationCovered = contactSpriteReferences.every(({ icon }) => visual.includes(`|${icon}`) || visual.includes(`'${icon}'`));

const summary = {
  generatedAt: new Date().toISOString(),
  canonicalPaletteFile: 'wp-content/themes/nuvanx-medical/assets/css/nvx-tokens.css',
  canonicalVisualModule: 'wp-content/themes/nuvanx-medical/inc/nvx-visual-system.php',
  runtimeFilesScanned: rows.length,
  activeRetiredColorFindings,
  survivingRetiredAssets,
  benefitReferences,
  benefitMigrationCovered,
  contactSpriteReferences,
  contactMigrationCovered,
  missingIconClasses,
  missingTokens,
};

const output = path.join(root, 'qa/design-system/visual-system-summary.json');
fs.mkdirSync(path.dirname(output), { recursive: true });
fs.writeFileSync(output, `${JSON.stringify(summary, null, 2)}\n`);

const failures = [
  ...activeRetiredColorFindings.map((item) => `${item.file}: retired color ${item.color}`),
  ...survivingRetiredAssets.map((asset) => `retired asset remains: ${asset}`),
  ...missingIconClasses.map((selector) => `canonical CSS misses ${selector}`),
  ...missingTokens.map((token) => `tokens file misses ${token}`),
];
if (!benefitMigrationCovered) failures.push('legacy benefit references are not fully covered by the normalizer');
if (!contactMigrationCovered) failures.push('legacy contact sprite references are not fully covered by the normalizer');

if (failures.length) {
  console.error('Visual system audit failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log(`PASS: visual system audit (${rows.length} runtime files scanned)`);
