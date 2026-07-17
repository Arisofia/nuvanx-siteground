import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const THEME = path.join(ROOT, 'wp-content/themes/nuvanx-medical');
const CSS_DIR = path.join(THEME, 'assets/css');
const cssFiles = fs.readdirSync(CSS_DIR).filter((name) => name.endsWith('.css') && !name.endsWith('.min.css'));

const tokenRenames = new Map([
  ['--nvx-warm-white', '--nvx-light'],
  ['--nvx-ivory', '--nvx-surface-base'],
  ['--nvx-pearl', '--nvx-surface-base'],
  ['--nvx-mist', '--nvx-surface-soft'],
  ['--nvx-silver', '--nvx-border-soft'],
  ['--nvx-platinum', '--nvx-accent-muted'],
  ['--nvx-white', '--nvx-light'],
]);

function normalizeTokens(source, file) {
  let output = source;
  for (const [legacy, canonical] of tokenRenames) {
    output = output.replaceAll(legacy, canonical);
  }

  if (file === 'nvx-tokens.css') {
    // Remove aliases that collapse onto an already-defined canonical role.
    const seen = new Set();
    output = output.split('\n').filter((line) => {
      const match = line.match(/^\s*(--nvx-[\w-]+)\s*:/);
      if (!match) return true;
      if (seen.has(match[1])) return false;
      seen.add(match[1]);
      return true;
    }).join('\n');
  }

  output = output
    .replace(/Marfil editorial \+ Blanco cálido/gi, 'Neutros cálidos editoriales')
    .replace(/champagne/gi, 'accent')
    .replace(/ivory/gi, 'warm surface')
    .replace(/marfil/gi, 'superficie cálida')
    .replace(/pearl/gi, 'surface')
    .replace(/platinum/gi, 'accent');

  return output;
}

for (const file of cssFiles) {
  const target = path.join(CSS_DIR, file);
  const before = fs.readFileSync(target, 'utf8');
  let after = normalizeTokens(before, file);
  if (file === 'nvx-base.css') {
    after = after
      .replace('font-family: var(--nvx-sans, "Manrope", sans-serif);', 'font-family: var(--nvx-sans);')
      .replace('color: var(--nvx-text-body, #3c4048);', 'color: var(--nvx-text-body);')
      .replace('background: var(--nvx-color-paper, #f2f3f5);', 'background: var(--nvx-color-paper);');
  }
  if (after !== before) fs.writeFileSync(target, after);
}

console.log(`Normalized ${cssFiles.length} canonical stylesheets.`);
