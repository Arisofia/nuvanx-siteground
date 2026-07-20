import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const THEME = path.join(ROOT, 'wp-content/themes/nuvanx-medical');
const CSS_DIR = path.join(THEME, 'assets/css');
const OWNER = 'nvx-components.css';

const BASE_CLASSES = ['nvx-button', 'nvx-btn', 'nvx-brand-btn'];
const ALLOWED_VARIANTS = new Set(['primary', 'secondary', 'light', 'secondary-on-dark']);
const VISUAL_PROPERTIES = new Set([
  'display',
  'align-items',
  'justify-content',
  'min-height',
  'height',
  'padding',
  'padding-inline',
  'padding-block',
  'border',
  'border-color',
  'border-radius',
  'background',
  'background-color',
  'color',
  'font-family',
  'font-size',
  'font-weight',
  'line-height',
  'letter-spacing',
  'text-align',
  'text-transform',
  'text-decoration',
  'box-shadow',
  'cursor',
  'transition',
  'outline',
  'outline-offset',
]);

function walk(directory, extensions, output = []) {
  const baseDir = path.resolve(directory);
  if (!fs.existsSync(baseDir)) return output;
  for (const entry of fs.readdirSync(baseDir, { withFileTypes: true })) {
    if (['.git', 'node_modules', 'vendor'].includes(entry.name)) continue;
    if (entry.name.includes('/') || entry.name.includes('\\') || entry.name === '..') continue;
    const absolute = path.resolve(baseDir, entry.name);
    if (!absolute.startsWith(baseDir)) continue;
    if (entry.isDirectory()) walk(absolute, extensions, output);
    else if (extensions.has(path.extname(entry.name))) output.push(absolute);
  }
  return output;
}

function lineNumber(source, index) {
  return source.slice(0, Math.max(0, index)).split('\n').length;
}

function declarationNames(block) {
  const names = new Set();
  for (const match of block.matchAll(/(?:^|;)\s*([a-z-]+)\s*:/gi)) {
    names.add(match[1].toLowerCase());
  }
  return names;
}

const violations = [];
const cssFilesAbs = walk(CSS_DIR, new Set(['.css'])).sort();
const aliasPattern = /\.nvx-(?:button|btn|brand-btn)(?:--[a-z0-9-]+)?(?:[^a-zA-Z0-9_-]|$)/;

for (const absolute of cssFilesAbs) {
  const relative = path.relative(CSS_DIR, absolute);
  const fileName = path.basename(absolute);
  const original = fs.readFileSync(absolute, 'utf8');
  // Blank comments in place so parse indices still map to original line numbers.
  const stripped = original.replace(/\/\*[\s\S]*?\*\//g, (match) => ' '.repeat(match.length));

  for (const match of stripped.matchAll(/([^{}]+)\{([^{}]*)\}/g)) {
    const selector = match[1].trim();
    if (!aliasPattern.test(selector) || fileName === OWNER) continue;

    const forbidden = [...declarationNames(match[2])].filter((property) => VISUAL_PROPERTIES.has(property));
    if (forbidden.length) {
      violations.push(
        `${relative}:${lineNumber(original, match.index)} redefines canonical button visuals (${forbidden.join(', ')}) in ${selector.replace(/\s+/g, ' ')}`
      );
    }
  }
}

const ownerPath = path.join(CSS_DIR, OWNER);
const ownerSource = fs.readFileSync(ownerPath, 'utf8');
for (const base of BASE_CLASSES) {
  if (!ownerSource.includes(`.${base}`)) violations.push(`${OWNER} is missing .${base}`);
  for (const variant of ALLOWED_VARIANTS) {
    if (!ownerSource.includes(`.${base}--${variant}`)) {
      violations.push(`${OWNER} is missing .${base}--${variant}`);
    }
  }
}

const runtimeFiles = walk(THEME, new Set(['.php', '.js', '.html']));
const variantPattern = /nvx-(?:button|btn|brand-btn)--([a-z0-9-]+)/g;
const variantUsage = new Map();

for (const absolute of runtimeFiles) {
  const relative = path.relative(ROOT, absolute);
  const source = fs.readFileSync(absolute, 'utf8');
  for (const match of source.matchAll(variantPattern)) {
    const variant = match[1];
    variantUsage.set(variant, (variantUsage.get(variant) || 0) + 1);
    if (!ALLOWED_VARIANTS.has(variant)) {
      violations.push(`${relative}:${lineNumber(source, match.index)} uses unsupported button variant --${variant}`);
    }
  }
}

const requiredFormHooks = [
  '.nvx-hubspot-form-section .hs-button',
  '.nvx-hs-lead-form input[type="submit"]',
];
for (const hook of requiredFormHooks) {
  if (!ownerSource.includes(hook)) violations.push(`${OWNER} is missing canonical form hook ${hook}`);
}

console.log(JSON.stringify({
  owner: OWNER,
  cssFiles: cssFilesAbs.length,
  runtimeFiles: runtimeFiles.length,
  allowedVariants: [...ALLOWED_VARIANTS],
  variantUsage: Object.fromEntries([...variantUsage.entries()].sort()),
  violations: violations.length,
}, null, 2));

if (violations.length) {
  console.error('\nBUTTON SYSTEM GATE FAILED');
  for (const violation of violations) console.error(`- ${violation}`);
  process.exit(1);
}

console.log('\nBUTTON SYSTEM GATE PASSED');
