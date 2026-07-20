import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '../..');
const THEME = path.join(ROOT, 'wp-content/themes/nuvanx-medical');
const CSS_DIR = path.join(THEME, 'assets/css');
const OUT_DIR = path.join(ROOT, 'qa/design-system');

// Keep in enqueue order (see functions.php nvx_theme_scripts + conditional enqueues).
// Conditional stylesheets remain active architecture: a runtime code path can enqueue
// them, so the audit must enforce the same rules regardless of the current request.
// nvx-brand-home.css is loaded sitewide; it owns .hero-cta-group + .nvx-home-hero-ctas styles.
const ACTIVE_STACK = [
	'nvx-fonts.css',
	'nvx-tokens.css',
	'nvx-base.css',
	'nvx-site-layout.css',
	'nvx-components.css',
	'nvx-patterns-editorial.css',
	'nvx-header.css',
	'nvx-footer.css',
	'nvx-brand-home.css',
	'nvx-hero-blackout.css',
	'nvx-mobile-hero-hierarchy.css',
	'nvx-posts.css',
];

const duplicateActiveStylesheets = ACTIVE_STACK.filter(
	(file, index) => ACTIVE_STACK.indexOf(file) !== index,
);

if (duplicateActiveStylesheets.length) {
	throw new Error(`Duplicate stylesheet(s) in ACTIVE_STACK: ${[...new Set(duplicateActiveStylesheets)].join(', ')}`);
}

const CANONICAL_FONT_TOKENS = new Set(['var(--nvx-serif)', 'var(--nvx-sans)']);
const ALLOWED_ICON_COLORS = new Set([
	'currentcolor',
	'var(--nvx-ink)',
	'var(--nvx-light)',
	'var(--nvx-accent-muted)',
	'var(--nvx-text-muted)',
]);
const ALLOWED_BREAKPOINTS = new Set(['1280', '980', '961', '960', '782', '721', '720', '680', '480']);
const ALLOWED_HARDCODED_PX = new Set([
	'0', '1', '2', '8', '12', '14', '16', '20', '24', '32', '36', '40', '42', '48', '56', '64',
	'72', '80', '86', '96', '100', '112', '120', '128', '140', '152', '168', '180', '220', '240',
	'256', '280', '320', '400', '560', '720', '860', '980', '999', '1240',
]);

function read(filePath) {
	return fs.readFileSync(filePath, 'utf8');
}

function stripComments(source) {
	return source.replace(/\/\*[\s\S]*?\*\//g, '');
}

function lineNumber(source, index) {
	return source.slice(0, index).split('\n').length;
}

function extractRootTokens(css) {
	const tokens = new Map();
	for (const block of css.matchAll(/:root\s*\{([\s\S]*?)\}/g)) {
		for (const declaration of block[1].matchAll(/(--[a-zA-Z0-9_-]+)\s*:\s*([^;]+);/g)) {
			tokens.set(declaration[1], declaration[2].trim());
		}
	}
	return tokens;
}

function tokenReferences(css, file) {
	const references = [];
	for (const match of css.matchAll(/var\(\s*(--nvx-[a-zA-Z0-9_-]+)/g)) {
		references.push({ file, token: match[1], line: lineNumber(css, match.index) });
	}
	return references;
}

function parseSelectors(css, file) {
	const selectors = [];
	const clean = stripComments(css);
	for (const match of clean.matchAll(/([^{}]+)\{/g)) {
		const raw = match[1].trim();
		if (!raw || raw.startsWith('@') || raw.startsWith('from') || raw.startsWith('to') || /^\d+%$/.test(raw)) continue;
		for (const selector of raw.split(',').map((value) => value.trim()).filter(Boolean)) {
			selectors.push({ file, selector: selector.replace(/\s+/g, ' '), line: lineNumber(clean, match.index) });
		}
	}
	return selectors;
}

function specificity(selector) {
	const withoutWhere = selector.replace(/:where\([^)]*\)/g, '');
	const ids = (withoutWhere.match(/#[\w-]+/g) || []).length;
	const classes = (withoutWhere.match(/\.[\w-]+|\[[^\]]+\]|:(?!:)[\w-]+(?:\([^)]*\))?/g) || []).length;
	const elements = (withoutWhere.replace(/::[\w-]+/g, ' element ').match(/(^|[\s>+~])([a-z][\w-]*)/gi) || []).length;
	return { ids, classes, elements, score: ids * 100 + classes * 10 + elements };
}

function scanHardcodedPixels(css, file) {
	const hits = [];
	const clean = stripComments(css);
	for (const match of clean.matchAll(/(-?\d+(?:\.\d+)?)px\b/g)) {
		const value = match[1];
		const before = clean.slice(Math.max(0, match.index - 40), match.index);
		const isMedia = /(?:min|max)-width\s*:\s*$/.test(before);
		if (isMedia && ALLOWED_BREAKPOINTS.has(value)) continue;
		if (ALLOWED_HARDCODED_PX.has(value)) continue;
		hits.push({ file, value: `${value}px`, line: lineNumber(clean, match.index) });
	}
	return hits;
}

function walk(directory, extensions, output = []) {
	for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
		if (['.git', 'node_modules', 'vendor', 'qa', 'docs'].includes(entry.name)) continue;
		const absolute = path.join(directory, entry.name);
		if (entry.isDirectory()) walk(absolute, extensions, output);
		else if (extensions.has(path.extname(entry.name))) output.push(absolute);
	}
	return output;
}

function declarationRows(css, file, propertyPattern) {
	const clean = stripComments(css);
	const rows = [];
	for (const match of clean.matchAll(new RegExp(`(${propertyPattern})\\s*:\\s*([^;}{]+)`, 'gi'))) {
		rows.push({
			file,
			property: match[1].toLowerCase(),
			value: match[2].trim(),
			line: lineNumber(clean, match.index),
		});
	}
	return rows;
}

function normalizeValue(value) {
	return value.toLowerCase().replace(/\s+/g, ' ').trim();
}

function stylesheetBasename(reference) {
	const withoutQuery = reference.split(/[?#]/, 1)[0];
	return path.posix.basename(withoutQuery.replace(/\\/g, '/'));
}

function addCssReferences(target, source, pattern, group = 1) {
	for (const match of source.matchAll(pattern)) {
		const reference = match[group];
		if (reference) target.add(stylesheetBasename(reference));
	}
}

function extractRuntimeStylesheetReferences(source) {
	const references = new Set();
	const clean = stripComments(source);

	// WordPress style registration/enqueue calls. Inspect only their argument lists,
	// then collect CSS string literals rather than matching arbitrary prose.
	for (const call of clean.matchAll(/\bwp_(?:enqueue|register)_style\s*\(([\s\S]*?)\)\s*;/g)) {
		addCssReferences(references, call[1], /["'`]([^"'`]+\.css(?:[?#][^"'`]*)?)["'`]/gi);
	}

	// Literal HTML links and module/bundler imports.
	addCssReferences(references, clean, /<link\b[^>]*\bhref\s*=\s*["']([^"']+\.css(?:[?#][^"']*)?)["'][^>]*>/gi);
	addCssReferences(references, clean, /\b(?:import|require)\s*\(\s*["']([^"']+\.css(?:[?#][^"']*)?)["']\s*\)/gi);
	addCssReferences(references, clean, /\bimport\s+["']([^"']+\.css(?:[?#][^"']*)?)["']/gi);

	return references;
}

function extractCssImports(source) {
	const references = new Set();
	const clean = stripComments(source);
	addCssReferences(references, clean, /@import\s+(?:url\(\s*)?["']?([^"')\s]+\.css(?:[?#][^"')\s]*)?)/gi);
	return references;
}

for (const file of ACTIVE_STACK) {
	if (!fs.existsSync(path.join(CSS_DIR, file))) throw new Error(`Missing active stylesheet: ${file}`);
}

const sourceFiles = fs.readdirSync(CSS_DIR)
	.filter((file) => file.endsWith('.css') && !file.endsWith('.min.css'))
	.sort();
const inactiveFiles = sourceFiles.filter((file) => !ACTIVE_STACK.includes(file));
const allSources = Object.fromEntries(sourceFiles.map((file) => [file, read(path.join(CSS_DIR, file))]));
const activeSources = Object.fromEntries(ACTIVE_STACK.map((file) => [file, allSources[file]]));
const tokenFile = activeSources['nvx-tokens.css'];
const definedTokens = extractRootTokens(tokenFile);

const runtimeFiles = walk(THEME, new Set(['.php', '.js', '.html']));
const runtimeReferencedStylesheets = new Set();
const referencedStylesheets = new Set(ACTIVE_STACK);
const stylesheetReferenceEvidence = [];

for (const absolute of runtimeFiles) {
	const relative = path.relative(ROOT, absolute);
	for (const file of extractRuntimeStylesheetReferences(read(absolute))) {
		runtimeReferencedStylesheets.add(file);
		referencedStylesheets.add(file);
		stylesheetReferenceEvidence.push({ file, referencedBy: relative });
	}
}

for (const [owner, source] of Object.entries(allSources)) {
	for (const file of extractCssImports(source)) {
		runtimeReferencedStylesheets.add(file);
		referencedStylesheets.add(file);
		stylesheetReferenceEvidence.push({ file, referencedBy: path.join('assets/css', owner) });
	}
}

const orphanStylesheets = sourceFiles.filter((file) => !referencedStylesheets.has(file));
const runtimeStylesheetsOutsideActiveStack = sourceFiles.filter(
	(file) => runtimeReferencedStylesheets.has(file) && !ACTIVE_STACK.includes(file)
);

const rootBlocks = [];
const important = [];
const tokenRefs = [];
const selectors = [];
const hardcodedPixels = [];
const unsafeSelectors = [];
const fontFamilies = [];
const iconColors = [];
const fixedSpacing = [];
const literalColors = [];

for (const [file, source] of Object.entries(allSources)) {
	for (const match of source.matchAll(/:root\s*\{/g)) rootBlocks.push({ file, line: lineNumber(source, match.index) });
	for (const match of source.matchAll(/!important\b/g)) important.push({ file, line: lineNumber(source, match.index) });
	tokenRefs.push(...tokenReferences(source, file));
	selectors.push(...parseSelectors(source, file));
	hardcodedPixels.push(...scanHardcodedPixels(source, file));
	fontFamilies.push(...declarationRows(source, file, 'font-family'));
	fixedSpacing.push(...declarationRows(source, file, 'margin(?:-(?:top|right|bottom|left|inline|block)(?:-(?:start|end))?)?|padding(?:-(?:top|right|bottom|left|inline|block)(?:-(?:start|end))?)?|gap|row-gap|column-gap'));
	literalColors.push(...declarationRows(source, file, 'color|background-color|border(?:-(?:top|right|bottom|left))?-color|fill|stroke'));

	for (const row of declarationRows(source, file, 'color|fill|stroke')) {
		const selectorWindow = source.slice(Math.max(0, source.lastIndexOf('{', source.indexOf(`${row.property}:`)) - 180), source.indexOf(`${row.property}:`) + 180);
		if (/icon|svg|toggle|arrow|chevron|marker|hamburger|close/i.test(selectorWindow)) iconColors.push(row);
	}
}

const activeSet = new Set(ACTIVE_STACK);
const activeRows = (rows) => rows.filter((row) => activeSet.has(row.file));
const inactiveRows = (rows) => rows.filter((row) => !activeSet.has(row.file));

const undefinedTokens = tokenRefs.filter((reference) => activeSet.has(reference.file) && !definedTokens.has(reference.token));
const canonicalRootBlocks = rootBlocks.filter((block) => block.file === 'nvx-tokens.css');
const scopedRootBlocks = rootBlocks.filter((block) => block.file !== 'nvx-tokens.css');
const highSpecificity = activeRows(selectors)
	.map((row) => ({ ...row, specificity: specificity(row.selector) }))
	.filter((row) => row.specificity.ids > 0 || row.specificity.score >= 50)
	.sort((a, b) => b.specificity.score - a.specificity.score);

for (const row of activeRows(selectors)) {
	if (/:nth-child\(|:nth-of-type\(|:has\(/.test(row.selector)) unsafeSelectors.push(row);
}

const selectorMap = new Map();
for (const row of selectors) {
	if (!selectorMap.has(row.selector)) selectorMap.set(row.selector, []);
	selectorMap.get(row.selector).push({ file: row.file, line: row.line, active: activeSet.has(row.file) });
}
const duplicateSelectors = [...selectorMap.entries()]
	.filter(([, locations]) => locations.length > 1)
	.map(([selector, locations]) => ({ selector, locations }));
const activeDuplicateSelectors = duplicateSelectors.filter((row) => row.locations.filter((location) => location.active).length > 1);

const nonCanonicalFonts = fontFamilies.filter((row) => {
	if (row.file === 'nvx-fonts.css') return false;
	const value = normalizeValue(row.value);
	return !CANONICAL_FONT_TOKENS.has(value);
});

const hardcodedSpacing = fixedSpacing.filter((row) => {
	const value = normalizeValue(row.value);
	if (value === '0' || value === 'auto' || value.includes('var(--nvx-') || value.includes('calc(') || value.includes('clamp(')) return false;
	return /(?:^|\s)-?\d+(?:\.\d+)?(?:px|rem|em|vw|vh|svh|%)\b/.test(value);
});

const hardcodedColors = literalColors.filter((row) => {
	const value = normalizeValue(row.value);
	if (value === 'transparent' || value === 'currentcolor' || value.includes('var(--nvx-')) return false;
	return /#(?:[0-9a-f]{3,8})\b|rgba?\(|hsla?\(/i.test(value);
});

const inconsistentIconColors = iconColors.filter((row) => {
	const value = normalizeValue(row.value);
	if (value === 'none') return false;
	return !ALLOWED_ICON_COLORS.has(value) && !value.includes('var(--nvx-');
});

const inlineStyles = [];
const embeddedStyleTags = [];
const runtimeImportant = [];
for (const absolute of runtimeFiles) {
	const relative = path.relative(ROOT, absolute);
	const source = read(absolute);
	for (const match of source.matchAll(/\sstyle\s*=\s*["']/gi)) inlineStyles.push({ file: relative, line: lineNumber(source, match.index) });
	for (const match of source.matchAll(/<style\b/gi)) embeddedStyleTags.push({ file: relative, line: lineNumber(source, match.index) });
	for (const match of source.matchAll(/!important\b/g)) runtimeImportant.push({ file: relative, line: lineNumber(source, match.index) });
}

const exceptions = {
	activeStack: ACTIVE_STACK,
	allStylesheets: sourceFiles,
	inactiveCssFiles: inactiveFiles,
	referencedStylesheets: [...referencedStylesheets].sort(),
	runtimeReferencedStylesheets: [...runtimeReferencedStylesheets].sort(),
	runtimeStylesheetsOutsideActiveStack,
	stylesheetReferenceEvidence,
	orphanStylesheets,
	rootBlocks,
	canonicalRootBlocks,
	scopedRootBlocks,
	importantActive: activeRows(important),
	importantInactive: inactiveRows(important),
	runtimeImportant,
	undefinedTokens,
	hardcodedPixelsActive: activeRows(hardcodedPixels),
	hardcodedPixelsInactive: inactiveRows(hardcodedPixels),
	highSpecificity,
	unsafeSelectors,
	duplicateSelectors,
	activeDuplicateSelectors,
	fontFamilies,
	nonCanonicalFonts,
	hardcodedSpacing,
	hardcodedColors,
	iconColors,
	inconsistentIconColors,
	inlineStyles,
	embeddedStyleTags,
};

const summary = {
	generatedAt: new Date().toISOString(),
	activeStylesheets: ACTIVE_STACK.length,
	totalStylesheets: sourceFiles.length,
	inactiveStylesheets: inactiveFiles.length,
	runtimeStylesheetsOutsideActiveStack: runtimeStylesheetsOutsideActiveStack.length,
	orphanStylesheets: orphanStylesheets.length,
	canonicalTokens: definedTokens.size,
	canonicalRootBlocks: canonicalRootBlocks.length,
	scopedRootBlocks: scopedRootBlocks.length,
	importantActive: activeRows(important).length,
	importantInactive: inactiveRows(important).length,
	runtimeImportant: runtimeImportant.length,
	undefinedTokens: undefinedTokens.length,
	hardcodedPixelsActive: activeRows(hardcodedPixels).length,
	activeDuplicateSelectors: activeDuplicateSelectors.length,
	allDuplicateSelectors: duplicateSelectors.length,
	nonCanonicalFonts: nonCanonicalFonts.length,
	hardcodedSpacing: hardcodedSpacing.length,
	hardcodedColors: hardcodedColors.length,
	inconsistentIconColors: inconsistentIconColors.length,
	highSpecificity: highSpecificity.length,
	unsafeSelectors: unsafeSelectors.length,
	inlineStyles: inlineStyles.length,
	embeddedStyleTags: embeddedStyleTags.length,
};

fs.mkdirSync(OUT_DIR, { recursive: true });
fs.writeFileSync(path.join(OUT_DIR, 'active-css-exceptions.json'), JSON.stringify(exceptions, null, 2));
fs.writeFileSync(path.join(OUT_DIR, 'audit-summary.json'), JSON.stringify(summary, null, 2));
console.log(JSON.stringify(summary, null, 2));

const fatal = [];
if (canonicalRootBlocks.length !== 1) fatal.push(`expected exactly one canonical :root in nvx-tokens.css; found ${canonicalRootBlocks.length}`);
if (activeRows(important).length) fatal.push(`found ${activeRows(important).length} !important declaration(s) in active CSS`);
if (runtimeImportant.length) fatal.push(`found ${runtimeImportant.length} !important declaration(s) in runtime PHP/JS/HTML`);
if (undefinedTokens.length) fatal.push(`found ${undefinedTokens.length} undefined token reference(s)`);
if (unsafeSelectors.length) fatal.push(`found ${unsafeSelectors.length} forbidden positional/relational selector(s)`);
if (embeddedStyleTags.length) fatal.push(`found ${embeddedStyleTags.length} embedded <style> tag(s) in runtime files`);
if (runtimeStylesheetsOutsideActiveStack.length) fatal.push(`found ${runtimeStylesheetsOutsideActiveStack.length} runtime stylesheet(s) omitted from ACTIVE_STACK: ${runtimeStylesheetsOutsideActiveStack.join(', ')}`);
if (orphanStylesheets.length) fatal.push(`found ${orphanStylesheets.length} unreferenced stylesheet(s)`);
if (nonCanonicalFonts.length) fatal.push(`found ${nonCanonicalFonts.length} non-canonical font-family declaration(s)`);
if (inconsistentIconColors.length) fatal.push(`found ${inconsistentIconColors.length} inconsistent icon color declaration(s)`);
if (hardcodedColors.length) fatal.push(`found ${hardcodedColors.length} literal color declaration(s) outside tokens`);

if (fatal.length) {
	console.error('\nCSS SYSTEM GATE FAILED');
	for (const error of fatal) console.error(`- ${error}`);
	process.exit(1);
}
