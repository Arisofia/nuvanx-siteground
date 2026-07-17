import fs from 'fs';
import path from 'path';

const root = process.cwd();
const cssDir = path.join(root, 'wp-content/themes/nuvanx-medical/assets/css');
const cssFiles = fs.readdirSync(cssDir).filter((file) => file.endsWith('.css') && !file.endsWith('.min.css'));

const redundantFallback = /var\(\s*(--[a-zA-Z0-9_-]+)\s*,\s*var\(\s*\1\s*\)\s*\)/g;
let replacements = 0;

for (const file of cssFiles) {
  const filePath = path.join(cssDir, file);
  const source = fs.readFileSync(filePath, 'utf8');
  const output = source.replace(redundantFallback, (_match, token) => {
    replacements += 1;
    return `var(${token})`;
  });
  if (output !== source) fs.writeFileSync(filePath, output);
}

const homePath = path.join(cssDir, 'nvx-brand-home.css');
const home = fs.readFileSync(homePath, 'utf8');
const secondaryCtaRule = /\.nvx-home-hero-ctas \.nvx-brand-btn--secondary\s*,\s*\.nvx-home-hero-ctas \.nvx-button--secondary\s*,\s*\.nvx-home-hero-ctas \.nvx-btn--secondary\s*\{[^}]*border-color\s*:\s*var\(--nvx-border-on-dark-55\)/s;
if (!secondaryCtaRule.test(home)) {
  throw new Error('Home hero secondary CTA selector group or border token is incomplete.');
}

for (const file of cssFiles) {
  const source = fs.readFileSync(path.join(cssDir, file), 'utf8');
  if (redundantFallback.test(source)) {
    throw new Error(`Redundant same-token fallback remains in ${file}`);
  }
  redundantFallback.lastIndex = 0;
}

console.log(`Normalized ${replacements} redundant token fallback(s).`);
