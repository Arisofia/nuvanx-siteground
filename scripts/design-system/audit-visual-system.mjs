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
const fonts = read('wp-content/themes/nuvanx-medical/assets/css/nvx-fonts.css');
const runtimeFiles = walk(theme).filter((file) => /\.(?:php|css|svg)$/i.test(file));
const rows = runtimeFiles.map((file) => ({
  file: path.relative(root, file).replaceAll('\\', '/'),
  source: fs.readFileSync(file, 'utf8'),
}));

const bannedColors = ['#9A8A78', '#B89A5B', '#C5A880'];
const activeRetiredColorFindings = [];
for (const row of rows) {
  const isGovernedLegacyMarkup = /(?:nvx-content-presentation\.php|nvx-components\.css)$/i.test(row.file);
  for (const color of bannedColors) {
    if (row.source.toUpperCase().includes(color.toUpperCase()) && !isGovernedLegacyMarkup) {
      activeRetiredColorFindings.push({ file: row.file, color });
    }
  }
}

const bannedFontPattern = /font-family\s*:[^;}]*(?:Bodoni Moda|Cormorant Garamond|Source Sans(?: 3)?|\bInter\b|Pinyon Script)/giu;
const bannedFontFindings = rows.flatMap((row) => [
  ...row.source.matchAll(bannedFontPattern),
].map((match) => ({ file: row.file, declaration: match[0].trim() })));

const literalFontPattern = /font-family\s*:\s*(?!var\()[^;}]*(?:Playfair Display|Manrope)/giu;
const literalFontFindings = rows
  .filter((row) => !/(?:nvx-fonts|nvx-tokens)\.css$/i.test(row.file))
  .flatMap((row) => [...row.source.matchAll(literalFontPattern)]
    .map((match) => ({ file: row.file, declaration: match[0].trim() })));

const forbiddenFontRequests = [];
if (/family=(?:Bodoni|Cormorant|Inter|Source\+Sans)/i.test(fonts)) {
  forbiddenFontRequests.push('nvx-fonts.css requests an alternate font family');
}
if (!/family=Playfair\+Display/.test(fonts) || !/family=Manrope/.test(fonts)) {
  forbiddenFontRequests.push('nvx-fonts.css does not request both canonical families');
}

const expectedTypography = new Map([
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
]);
const typographyTokenMismatches = [];
for (const [token, expected] of expectedTypography) {
  const escaped = token.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const match = tokens.match(new RegExp(`${escaped}\\s*:\\s*([^;]+);`));
  const actual = match?.[1]?.trim() ?? null;
  if (actual !== expected) typographyTokenMismatches.push({ token, expected, actual });
}

const parallelFontVariables = [...tokens.matchAll(/--nvx-(?:serif|sans)-[123]\s*:/g)].map((match) => match[0]);

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
const contactMigrationCovered = contactSpriteReferences.every(({ icon }) => visual.includes(icon));

const summary = {
  generatedAt: new Date().toISOString(),
  canonicalPaletteFile: 'wp-content/themes/nuvanx-medical/assets/css/nvx-tokens.css',
  canonicalFontFile: 'wp-content/themes/nuvanx-medical/assets/css/nvx-fonts.css',
  canonicalVisualModule: 'wp-content/themes/nuvanx-medical/inc/nvx-visual-system.php',
  canonicalFonts: ['Playfair Display', 'Manrope'],
  runtimeFilesScanned: rows.length,
  activeRetiredColorFindings,
  bannedFontFindings,
  literalFontFindings,
  forbiddenFontRequests,
  typographyTokenMismatches,
  parallelFontVariables,
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
  ...bannedFontFindings.map((item) => `${item.file}: banned ${item.declaration}`),
  ...literalFontFindings.map((item) => `${item.file}: literal family outside tokens ${item.declaration}`),
  ...forbiddenFontRequests,
  ...typographyTokenMismatches.map((item) => `${item.token}: expected ${item.expected}, found ${item.actual}`),
  ...parallelFontVariables.map((item) => `parallel font variable exists: ${item}`),
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

console.log(`PASS: Playfair Display + Manrope visual system (${rows.length} runtime files scanned)`);
