from pathlib import Path

path = Path('scripts/design-system/audit-css.mjs')
source = path.read_text(encoding='utf-8')

old_strip = """function stripComments(source) {
\treturn source.replace(/\\/\\*[\\s\\S]*?\\*\\//g, '');
}
"""
new_strip = """function stripComments(source) {
\treturn source.replace(/\\r\\n?/g, '\\n').replace(/\\/\\*[\\s\\S]*?\\*\\//g, '');
}
"""

old_declarations = """function declarationRows(css, file, propertyPattern) {
\tconst clean = stripComments(css);
\tconst rows = [];
\t// Property names must begin at a declaration boundary. Without this guard,
\t// `stroke` incorrectly matches inside custom properties such as
\t// `--nvx-icon-stroke: 1.5`.
\tconst pattern = new RegExp(`(?<![-\\\\w])(${propertyPattern})\\\\s*:\\\\s*([^;}{]+)`, 'gi');
\tfor (const match of clean.matchAll(pattern)) {
\t\trows.push({
\t\t\tfile,
\t\t\tproperty: match[1].toLowerCase(),
\t\t\tvalue: match[2].trim(),
\t\t\tline: lineNumber(clean, match.index),
\t\t\tindex: match.index,
\t\t});
\t}
\treturn rows;
}
"""
new_declarations = """function selectorForDeclaration(clean, declarationIndex) {
\tconst blockStart = clean.lastIndexOf('{', declarationIndex);
\tif (blockStart < 0) return '';
\tconst previousClose = clean.lastIndexOf('}', blockStart - 1);
\tconst parentOpen = clean.lastIndexOf('{', blockStart - 1);
\tconst boundary = Math.max(previousClose, parentOpen);
\treturn clean.slice(boundary + 1, blockStart).trim().replace(/\\s+/g, ' ');
}

function declarationRows(css, file, propertyPattern) {
\tconst clean = stripComments(css);
\tconst rows = [];
\t// Property names must begin at a declaration boundary. Without this guard,
\t// `stroke` incorrectly matches inside custom properties such as
\t// `--nvx-icon-stroke: 1.5`.
\tconst pattern = new RegExp(`(?<![-\\\\w])(${propertyPattern})\\\\s*:\\\\s*([^;}{]+)`, 'gi');
\tfor (const match of clean.matchAll(pattern)) {
\t\trows.push({
\t\t\tfile,
\t\t\tproperty: match[1].toLowerCase(),
\t\t\tvalue: match[2].trim(),
\t\t\tline: lineNumber(clean, match.index),
\t\t\tindex: match.index,
\t\t\tselector: selectorForDeclaration(clean, match.index),
\t\t});
\t}
\treturn rows;
}

function validateDeclarationParser() {
\tconst fixture = [
\t\t'/* icon appears only in this removed comment */',
\t\t'.plain-link { color: inherit; }',
\t\t'.button-label { color: var(--button-color); }',
\t\t'.nvx-icon,',
\t\t'.nvx-icon path { stroke: currentColor; }',
\t\t':root { --nvx-icon-stroke: 1.5; }',
\t].join('\\r\\n');
\tconst rows = declarationRows(fixture, 'fixture.css', 'color|fill|stroke');
\tconst plain = rows.find((row) => row.value === 'inherit');
\tconst button = rows.find((row) => row.value === 'var(--button-color)');
\tconst icon = rows.find((row) => row.value === 'currentColor');
\tif (plain?.selector !== '.plain-link') throw new Error('CSS parser lost the exact selector for a plain declaration');
\tif (button?.selector !== '.button-label') throw new Error('CSS parser lost the exact selector for a button declaration');
\tif (!icon?.selector.includes('.nvx-icon')) throw new Error('CSS parser failed to associate an icon declaration with its selector');
\tif (rows.some((row) => row.value === '1.5')) throw new Error('CSS parser matched a property name inside a custom token');
}
validateDeclarationParser();
"""

old_icon_scan = """\tfor (const row of declarationRows(source, file, 'color|fill|stroke')) {
\t\tconst selectorWindow = source.slice(Math.max(0, source.lastIndexOf('{', row.index) - 180), row.index + 180);
\t\tif (/icon|svg|toggle|arrow|chevron|marker|hamburger|close/i.test(selectorWindow)) iconColors.push(row);
\t}
"""
new_icon_scan = """\tfor (const row of declarationRows(source, file, 'color|fill|stroke')) {
\t\tif (/icon|svg|toggle|arrow|chevron|marker|hamburger|close/i.test(row.selector)) iconColors.push(row);
\t}
"""

replacements = [
    (old_strip, new_strip, 'stripComments'),
    (old_declarations, new_declarations, 'declarationRows'),
    (old_icon_scan, new_icon_scan, 'icon selector scan'),
    ('console.log(iconColors); if (fatal.length) {', 'if (fatal.length) {', 'debug output'),
]

for old, new, label in replacements:
    if old not in source:
        raise SystemExit(f'Expected block not found: {label}')
    source = source.replace(old, new, 1)

path.write_text(source, encoding='utf-8', newline='\n')
