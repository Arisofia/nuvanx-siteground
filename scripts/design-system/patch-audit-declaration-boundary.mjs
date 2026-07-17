import fs from 'fs';

const file = 'scripts/design-system/audit-css.mjs';
const source = fs.readFileSync(file, 'utf8');
const before = "for (const match of clean.matchAll(new RegExp(`(${propertyPattern})\\s*:\\s*([^;}{]+)`, 'gi'))) {";
const after = "for (const match of clean.matchAll(new RegExp(`(?<![\\w-])(${propertyPattern})\\s*:\\s*([^;}{]+)`, 'gi'))) {";

if (!source.includes(before)) {
  throw new Error('Expected declarationRows matcher was not found.');
}

const patched = source.replace(before, after);
fs.writeFileSync(file, patched);

const fixture = '--nvx-icon-stroke: 1.5px; stroke: currentColor;';
const regex = new RegExp(`(?<![\\w-])(stroke)\\s*:\\s*([^;}{]+)`, 'gi');
const matches = [...fixture.matchAll(regex)];
if (matches.length !== 1 || matches[0][2].trim() !== 'currentColor') {
  throw new Error('Declaration boundary regression test failed.');
}
