import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '../..');
const THEME = path.join(ROOT, 'wp-content/themes/nuvanx-medical');
const CSS_DIR = path.join(THEME, 'assets/css');
const OUT_DIR = path.join(ROOT, 'qa/design-system');

const ACTIVE_STACK = [
	'nvx-tokens.css',
	'nvx-base.css',
	'nvx-site-layout.css',
	'nvx-components.css',
	'nvx-patterns-editorial.css',
	'nvx-header.css',
	'nvx-footer.css',
	'nvx-brand-home.css',
];

const ALLOWED_BREAKPOINTS = new Set(['1280', '960', '782', '720', '480']);
const ALLOWED_HARDCODED_PX = new Set([
	'0', '1', '2', '8', '16', '24', '32', '40', '48', '56', '64', '72', '80',
	'96', '112', '120', '128', '152', '168', '240', '256', '320', '400', '999', '1240',
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

for (const file of ACTIVE_STACK) {
	if (!fs.existsSync(path.join(CSS_DIR, file))) throw new Error(`Missing active stylesheet: ${file}`);
}

const sourceFiles = fs.readdirSync(CSS_DIR).filter((file) => file.endsWith('.css') && !file.endsWith('.min.css')).sort();
const inactiveFiles = sourceFiles.filter((file) => !ACTIVE_STACK.includes(file));
const activeSources = Object.fromEntries(ACTIVE_STACK.map((file) => [file, read(path.join(CSS_DIR, file))]));
const tokenFile = activeSources['nvx-tokens.css'];
const definedTokens = extractRootTokens(tokenFile);

const rootBlocks = [];
const important = [];
const tokenRefs = [];
const selectors = [];
const hardcodedPixels = [];
const unsafeSelectors = [];

for (const [file, source] of Object.entries(activeSources)) {
	for (const match of source.matchAll(/:root\s*\{/g)) rootBlocks.push({ file, line: lineNumber(source, match.index) });
	for (const match of source.matchAll(/!important\b/g)) important.push({ file, line: lineNumber(source, match.index) });
	tokenRefs.push(...tokenReferences(source, file));
	selectors.push(...parseSelectors(source, file));
	hardcodedPixels.push(...scanHardcodedPixels(source, file));
}

const undefinedTokens = tokenRefs.filter((reference) => !definedTokens.has(reference.token));
const rootOutsideTokens = rootBlocks.filter((block) => block.file !== 'nvx-tokens.css');
const highSpecificity = selectors
	.map((row) => ({ ...row, specificity: specificity(row.selector) }))
	.filter((row) => row.specificity.ids > 0 || row.specificity.score >= 50)
	.sort((a, b) => b.specificity.score - a.specificity.score);

for (const row of selectors) {
	if (/:nth-child\(|:nth-of-type\(|:has\(/.test(row.selector)) unsafeSelectors.push(row);
}

const selectorMap = new Map();
for (const row of selectors) {
	if (!selectorMap.has(row.selector)) selectorMap.set(row.selector, []);
	selectorMap.get(row.selector).push({ file: row.file, line: row.line });
}
const duplicateSelectors = [...selectorMap.entries()]
	.filter(([, locations]) => locations.length > 1)
	.map(([selector, locations]) => ({ selector, locations }));

const runtimeFiles = walk(THEME, new Set(['.php', '.js', '.html']));
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
	inactiveCssFiles: inactiveFiles,
	rootBlocks,
	rootOutsideTokens,
	important,
	runtimeImportant,
	undefinedTokens,
	hardcodedPixels,
	highSpecificity,
	unsafeSelectors,
	duplicateSelectors,
	inlineStyles,
	embeddedStyleTags,
};

const summary = {
	generatedAt: new Date().toISOString(),
	activeStylesheets: ACTIVE_STACK.length,
	inactiveStylesheets: inactiveFiles.length,
	canonicalTokens: definedTokens.size,
	rootOutsideTokens: rootOutsideTokens.length,
	important: important.length,
	runtimeImportant: runtimeImportant.length,
	undefinedTokens: undefinedTokens.length,
	hardcodedPixels: hardcodedPixels.length,
	highSpecificity: highSpecificity.length,
	unsafeSelectors: unsafeSelectors.length,
	duplicateSelectors: duplicateSelectors.length,
	inlineStyles: inlineStyles.length,
	embeddedStyleTags: embeddedStyleTags.length,
};

fs.mkdirSync(OUT_DIR, { recursive: true });
fs.writeFileSync(path.join(OUT_DIR, 'active-css-exceptions.json'), JSON.stringify(exceptions, null, 2));
fs.writeFileSync(path.join(OUT_DIR, 'audit-summary.json'), JSON.stringify(summary, null, 2));
console.log(JSON.stringify(summary, null, 2));

const fatal = [];
if (rootBlocks.length !== 1 || rootOutsideTokens.length) fatal.push(`expected one :root in nvx-tokens.css; found ${rootBlocks.length}`);
if (important.length) fatal.push(`found ${important.length} !important declaration(s) in active CSS`);
if (runtimeImportant.length) fatal.push(`found ${runtimeImportant.length} !important declaration(s) in runtime PHP/JS/HTML`);
if (undefinedTokens.length) fatal.push(`found ${undefinedTokens.length} undefined token reference(s)`);
if (unsafeSelectors.length) fatal.push(`found ${unsafeSelectors.length} forbidden positional/relational selector(s)`);
if (embeddedStyleTags.length) fatal.push(`found ${embeddedStyleTags.length} embedded <style> tag(s) in runtime files`);

if (fatal.length) {
	console.error('\nCSS SYSTEM GATE FAILED');
	for (const error of fatal) console.error(`- ${error}`);
	process.exit(1);
}
