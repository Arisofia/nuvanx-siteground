import fs from 'fs';
import path from 'path';

const root = process.cwd();
const cssDir = path.join(root, 'wp-content/themes/nuvanx-medical/assets/css');
const tokensPath = path.join(cssDir, 'nvx-tokens.css');

function replaceAllChecked(source, replacements, file) {
  let output = source;
  for (const [from, to] of replacements) {
    output = output.split(from).join(to);
  }
  if (output === source && replacements.length) {
    console.log(`No literal color replacements needed in ${file}`);
  }
  return output;
}

let tokens = fs.readFileSync(tokensPath, 'utf8');
const tokenAnchor = '  --nvx-color-line: rgba(23, 23, 23, 0.16);\n';
const semanticTokens = `${tokenAnchor}\n  /* Text and borders on dark surfaces. Literal color values live only here. */\n  --nvx-text-on-dark: var(--nvx-light);\n  --nvx-text-on-dark-92: rgba(255, 250, 242, 0.92);\n  --nvx-text-on-dark-90: rgba(255, 250, 242, 0.90);\n  --nvx-text-on-dark-88: rgba(255, 250, 242, 0.88);\n  --nvx-text-on-dark-82: rgba(255, 250, 242, 0.82);\n  --nvx-text-on-dark-78: rgba(255, 250, 242, 0.78);\n  --nvx-text-on-dark-72: rgba(255, 250, 242, 0.72);\n  --nvx-border-on-dark-55: rgba(255, 250, 242, 0.55);\n  --nvx-border-on-dark-50: rgba(255, 250, 242, 0.50);\n  --nvx-border-on-dark-45: rgba(255, 250, 242, 0.45);\n  --nvx-border-on-dark-42: rgba(255, 250, 242, 0.42);\n  --nvx-ink-muted: rgba(23, 23, 23, 0.82);\n`;
if (!tokens.includes('--nvx-text-on-dark-90:')) {
  if (!tokens.includes(tokenAnchor)) throw new Error('Token insertion anchor not found.');
  tokens = tokens.replace(tokenAnchor, semanticTokens);
  fs.writeFileSync(tokensPath, tokens);
}

const replacementsByFile = {
  'nvx-brand-home.css': [
    ['rgba(255,255,255,.9)', 'var(--nvx-text-on-dark-90)'],
    ['rgba(255,255,255,.82)', 'var(--nvx-text-on-dark-82)'],
    ['rgba(255,255,255,.55)', 'var(--nvx-border-on-dark-55)'],
    ['rgba(255,255,255,.42)', 'var(--nvx-border-on-dark-42)'],
  ],
  'nvx-components.css': [
    ['rgba(20, 22, 26, 0.22)', 'var(--nvx-color-line)'],
  ],
  'nvx-footer.css': [
    ['rgba(255, 255, 255, 0.45)', 'var(--nvx-border-on-dark-45)'],
    ['rgba(255, 255, 255, 0.72)', 'var(--nvx-text-on-dark-72)'],
  ],
  'nvx-patterns-editorial.css': [
    ['rgba(255, 255, 255, 0.9)', 'var(--nvx-text-on-dark-90)'],
    ['rgba(255, 255, 255, 0.72)', 'var(--nvx-text-on-dark-72)'],
    ['rgba(255, 255, 255, 0.78)', 'var(--nvx-text-on-dark-78)'],
    ['rgba(255, 255, 255, 0.55)', 'var(--nvx-border-on-dark-55)'],
    ['rgba(255, 255, 255, 0.5)', 'var(--nvx-border-on-dark-50)'],
    ['rgba(245, 232, 216, 0.92)', 'var(--nvx-text-on-dark-92)'],
    ['rgba(245, 232, 216, 0.9)', 'var(--nvx-text-on-dark-90)'],
    ['rgba(245, 232, 216, 0.88)', 'var(--nvx-text-on-dark-88)'],
    ['rgba(245, 232, 216, 0.82)', 'var(--nvx-text-on-dark-82)'],
    ['rgba(245, 232, 216, 0.78)', 'var(--nvx-text-on-dark-78)'],
    ['rgba(245, 232, 216, 0.72)', 'var(--nvx-text-on-dark-72)'],
    ['rgba(245, 232, 216, 0.5)', 'var(--nvx-border-on-dark-50)'],
    ['rgba(28, 28, 28, 0.82)', 'var(--nvx-ink-muted)'],
    ['#f5e8d8', 'var(--nvx-text-on-dark)'],
    ['#c5a880', 'var(--nvx-accent-muted)'],
    ['#1c1c1c', 'var(--nvx-ink)'],
  ],
};

for (const [file, replacements] of Object.entries(replacementsByFile)) {
  const filePath = path.join(cssDir, file);
  const source = fs.readFileSync(filePath, 'utf8');
  const output = replaceAllChecked(source, replacements, file);
  if (output !== source) fs.writeFileSync(filePath, output);
}

const auditPath = path.join(root, 'scripts/design-system/audit-css.mjs');
let audit = fs.readFileSync(auditPath, 'utf8');
const fatalAnchor = "if (inconsistentIconColors.length) fatal.push(`found ${inconsistentIconColors.length} inconsistent icon color declaration(s)`);";
const fatalReplacement = `${fatalAnchor}\nif (hardcodedColors.length) fatal.push(\`found \${hardcodedColors.length} literal color declaration(s) outside tokens\`);`;
if (!audit.includes('literal color declaration(s) outside tokens')) {
  if (!audit.includes(fatalAnchor)) throw new Error('Audit fatal anchor not found.');
  audit = audit.replace(fatalAnchor, fatalReplacement);
  fs.writeFileSync(auditPath, audit);
}

console.log('Literal color migration complete.');
