import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '../..');
const QA_DIR = path.join(ROOT, 'qa/design-system');
const CSS_DIR = path.join(ROOT, 'wp-content/themes/nuvanx-medical/assets/css');
const EXCEPTIONS_PATH = path.join(QA_DIR, 'active-css-exceptions.json');
const SUMMARY_PATH = path.join(QA_DIR, 'audit-summary.json');
const FUNCTIONS_PATH = path.join(ROOT, 'wp-content/themes/nuvanx-medical/functions.php');
const ENVIRONMENT_FLAGS_PATH = path.join(ROOT, 'wp-content/themes/nuvanx-medical/inc/nvx-environment-flags.php');
const TOKENS_PATH = path.join(CSS_DIR, 'nvx-tokens.css');

/** Default-on production CSS that is not part of the global ACTIVE_STACK. */
const CONDITIONAL_RUNTIME_STYLESHEETS = ['nvx-hero-blackout.css'];
const MAX_ACTIONABLE_DUPLICATE_SELECTORS = 220;
const CANONICAL_FONT_TOKENS = new Set(['var(--nvx-serif)', 'var(--nvx-sans)']);

function readJson(file) {
	const absolute = path.resolve(ROOT, file);
	if (!absolute.startsWith(ROOT)) throw new Error('Path traversal detected');
	return JSON.parse(fs.readFileSync(absolute, 'utf8'));
}

function requireMatch(source, pattern, message) {
	if (!pattern.test(source)) {
		throw new Error(message);
	}
}

function stripComments(source) {
	return source.replace(/\/\*[\s\S]*?\*\//g, '');
}

function findClosingBrace(source, openingIndex) {
	let depth = 1;
	let quote = '';
	let escaped = false;

	for (let index = openingIndex + 1; index < source.length; index += 1) {
		const char = source[index];
		if (quote) {
			if (escaped) {
				escaped = false;
				continue;
			}
			if ('\\' === char) {
				escaped = true;
				continue;
			}
			if (char === quote) quote = '';
			continue;
		}
		if ('"' === char || "'" === char) {
			quote = char;
			continue;
		}
		if ('{' === char) depth += 1;
		if ('}' === char) {
			depth -= 1;
			if (0 === depth) return index;
		}
	}
	return -1;
}

function lineNumber(source, index) {
	return source.slice(0, index).split('\n').length;
}

/**
 * Parse selectors together with their at-rule context. The legacy raw metric
 * counted a desktop rule and its intentional mobile override as a duplicate.
 * Actionable duplicates are repetitions inside the same cascade context.
 */
function contextualSelectorOccurrences(source, file, start = 0, end = source.length, contexts = [], output = []) {
	let statementStart = start;
	let quote = '';
	let escaped = false;

	for (let index = start; index < end; index += 1) {
		const char = source[index];
		if (quote) {
			if (escaped) {
				escaped = false;
				continue;
			}
			if ('\\' === char) {
				escaped = true;
				continue;
			}
			if (char === quote) quote = '';
			continue;
		}
		if ('"' === char || "'" === char) {
			quote = char;
			continue;
		}
		if (';' === char) {
			statementStart = index + 1;
			continue;
		}
		if ('{' !== char) continue;

		const prelude = source.slice(statementStart, index).trim();
		const closingIndex = findClosingBrace(source, index);
		if (-1 === closingIndex || closingIndex > end) break;

		if (prelude.startsWith('@')) {
			const atRule = prelude.replace(/\s+/g, ' ');
			if (/^@(media|supports|container|layer|scope)\b/i.test(atRule)) {
				contextualSelectorOccurrences(
					source,
					file,
					index + 1,
					closingIndex,
					[...contexts, atRule],
					output,
				);
			}
		} else if (prelude && !/^(from|to|\d+%)$/i.test(prelude)) {
			for (const selector of prelude.split(',').map((value) => value.trim().replace(/\s+/g, ' ')).filter(Boolean)) {
				output.push({
					file,
					selector,
					context: contexts.join(' > ') || 'global',
					line: lineNumber(source, statementStart),
				});
			}
		}

		index = closingIndex;
		statementStart = closingIndex + 1;
	}

	return output;
}

function extractRootTokens(css) {
	const tokens = new Set();
	for (const block of css.matchAll(/:root\s*\{([\s\S]*?)\}/g)) {
		for (const declaration of block[1].matchAll(/(--[a-zA-Z0-9_-]+)\s*:/g)) {
			tokens.add(declaration[1]);
		}
	}
	return tokens;
}

function parseSelectors(css, file) {
	const selectors = [];
	const clean = stripComments(css);
	for (const match of clean.matchAll(/([^{}]+)\{/g)) {
		const raw = match[1].trim();
		if (!raw || raw.startsWith('@') || raw.startsWith('from') || raw.startsWith('to') || /^\d+%$/.test(raw)) continue;
		for (const selector of raw.split(',').map((value) => value.trim()).filter(Boolean)) {
			selectors.push({
				file,
				selector: selector.replace(/\s+/g, ' '),
				line: lineNumber(clean, match.index),
			});
		}
	}
	return selectors;
}

/**
 * Split audit rows that carry a `file` key into active / conditional / inactive.
 */
function reclassifyFileRows(rows, conditionalSet, activeSet) {
	const conditional = [];
	const inactive = [];
	const other = [];
	for (const row of rows || []) {
		const file = row && row.file;
		if (conditionalSet.has(file)) conditional.push(row);
		else if (activeSet.has(file)) other.push(row);
		else inactive.push(row);
	}
	return { conditional, inactive, other };
}

const functionsSource = fs.readFileSync(FUNCTIONS_PATH, 'utf8');
const environmentSource = fs.readFileSync(ENVIRONMENT_FLAGS_PATH, 'utf8');

requireMatch(functionsSource, /function\s+nvx_theme_hero_blackout_enabled\s*\(/, 'hero blackout toggle function is missing');
requireMatch(functionsSource, /\$enabled\s*=\s*true\s*;/, 'hero blackout is no longer default-on; review the conditional classification');
requireMatch(functionsSource, /wp_enqueue_style\s*\([\s\S]*?['"]nvx-hero-blackout['"][\s\S]*?nvx-hero-blackout\.css/, 'hero blackout stylesheet is not enqueued at runtime');
requireMatch(environmentSource, /nvx_theme_hero_blackout_enabled/, 'staging-only blackout filter is not registered');
requireMatch(environmentSource, /staging2\.nuvanx\.com/, 'staging2 host safeguard is missing');
// Host-only staging2: generic WP_ENVIRONMENT_TYPE=staging must not reveal media.
requireMatch(
	environmentSource,
	/function\s+nvx_environment_is_staging2[\s\S]*?staging2\.nuvanx\.com[\s\S]*?===[\s\S]*?\$host/,
	'staging2 detection must compare the HTTP host to staging2.nuvanx.com',
);
if (/return\s+['"]staging['"]\s*===\s*\$environment/.test(environmentSource) || /\$environment\s*===\s*['"]staging['"]/.test(environmentSource)) {
	throw new Error('staging2 detection must not treat generic WP_ENVIRONMENT_TYPE=staging as sufficient');
}

const exceptions = readJson(EXCEPTIONS_PATH);
const summary = readJson(SUMMARY_PATH);
const allStylesheets = new Set(exceptions.allStylesheets || []);
const activeStack = new Set(exceptions.activeStack || []);
const inactiveStylesheets = new Set(exceptions.inactiveCssFiles || []);
const referenced = new Set(exceptions.referencedStylesheets || []);
const conditionalSet = new Set(CONDITIONAL_RUNTIME_STYLESHEETS);

const overlappingClassifications = [...activeStack].filter((stylesheet) => inactiveStylesheets.has(stylesheet));
if (overlappingClassifications.length) {
	throw new Error(`stylesheet(s) classified as both active and inactive: ${overlappingClassifications.join(', ')}`);
}

const unclassifiedStylesheets = [...allStylesheets].filter(
	(stylesheet) => !activeStack.has(stylesheet) && !inactiveStylesheets.has(stylesheet),
);
if (unclassifiedStylesheets.length) {
	throw new Error(`unclassified stylesheet(s): ${unclassifiedStylesheets.join(', ')}`);
}

const unknownClassifications = [...activeStack, ...inactiveStylesheets].filter(
	(stylesheet) => !allStylesheets.has(stylesheet),
);
if (unknownClassifications.length) {
	throw new Error(`classified stylesheet(s) missing from inventory: ${[...new Set(unknownClassifications)].join(', ')}`);
}

for (const stylesheet of CONDITIONAL_RUNTIME_STYLESHEETS) {
	if (!allStylesheets.has(stylesheet)) {
		throw new Error(`conditional runtime stylesheet is missing from the CSS inventory: ${stylesheet}`);
	}
	if (!activeStack.has(stylesheet)) {
		throw new Error(`conditional runtime stylesheet must be audited in the active stack: ${stylesheet}`);
	}
	if ((exceptions.inactiveCssFiles || []).includes(stylesheet)) {
		throw new Error(`conditional runtime stylesheet is incorrectly classified as inactive: ${stylesheet}`);
	}
	if (!referenced.has(stylesheet)) {
		throw new Error(`conditional runtime stylesheet has no runtime reference evidence: ${stylesheet}`);
	}
}

// --- Reclassify inventory filenames ---
exceptions.conditionalRuntimeStylesheets = CONDITIONAL_RUNTIME_STYLESHEETS;
exceptions.inactiveCssFiles = (exceptions.inactiveCssFiles || []).filter(
	(stylesheet) => !conditionalSet.has(stylesheet),
);
exceptions.conditionalRuntimeReferenceEvidence = (exceptions.stylesheetReferenceEvidence || []).filter(
	(row) => conditionalSet.has(row.file),
);

// --- Reclassify per-file audit rows (not only the aggregate count) ---
const importantSplit = reclassifyFileRows(exceptions.importantInactive, conditionalSet, activeStack);
exceptions.importantConditionalRuntime = importantSplit.conditional;
exceptions.importantInactive = importantSplit.inactive;

const pixelsSplit = reclassifyFileRows(exceptions.hardcodedPixelsInactive, conditionalSet, activeStack);
exceptions.hardcodedPixelsConditionalRuntime = pixelsSplit.conditional;
exceptions.hardcodedPixelsInactive = pixelsSplit.inactive;

// Move any leftover inactive-bucket rows that belong to conditional files out of
// generic inactive collections that only list {file,line} style entries.
for (const key of Object.keys(exceptions)) {
	if (!Array.isArray(exceptions[key])) continue;
	if (key.startsWith('important') || key.startsWith('hardcodedPixels')) continue;
	if (key === 'inactiveCssFiles' || key === 'conditionalRuntimeStylesheets') continue;
	if (key === 'stylesheetReferenceEvidence' || key === 'conditionalRuntimeReferenceEvidence') continue;
	const sample = exceptions[key][0];
	if (!sample || typeof sample !== 'object' || !('file' in sample)) continue;
	// Only re-bucket rows that currently sit outside activeStack and look inactive.
	const split = reclassifyFileRows(exceptions[key], conditionalSet, activeStack);
	if (split.conditional.length && /inactive|Inactive/.test(key)) {
		const conditionalKey = key.replace(/[Ii]nactive/, 'ConditionalRuntime');
		exceptions[conditionalKey] = [...(exceptions[conditionalKey] || []), ...split.conditional];
		exceptions[key] = [...split.other, ...split.inactive];
	}
}

// --- Validate conditional runtime CSS (not only global ACTIVE_STACK) ---
const definedTokens = extractRootTokens(fs.readFileSync(TOKENS_PATH, 'utf8'));
const conditionalFatals = [];
const conditionalUndefinedTokens = [];
const conditionalUnsafeSelectors = [];
const conditionalNonCanonicalFonts = [];
const conditionalHardcodedColors = [];

for (const stylesheet of CONDITIONAL_RUNTIME_STYLESHEETS) {
	const absolute = path.join(CSS_DIR, stylesheet);
	const source = fs.readFileSync(absolute, 'utf8');
	const clean = stripComments(source);

	for (const match of clean.matchAll(/var\(\s*(--nvx-[a-zA-Z0-9_-]+)/g)) {
		const token = match[1];
		if (!definedTokens.has(token)) {
			conditionalUndefinedTokens.push({
				file: stylesheet,
				token,
				line: lineNumber(clean, match.index),
			});
		}
	}

	for (const row of parseSelectors(source, stylesheet)) {
		// Positional selectors remain forbidden. :has() is allowed in temporary
		// conditional overrides (hero blackout media rails).
		if (/:nth-child\(|:nth-of-type\(/.test(row.selector)) {
			conditionalUnsafeSelectors.push(row);
		}
	}

	for (const match of clean.matchAll(/(?:^|;)\s*(font-family)\s*:\s*([^;!}]+)/gim)) {
		const value = match[2].trim().toLowerCase().replace(/\s+/g, ' ');
		if (!CANONICAL_FONT_TOKENS.has(value) && value !== 'inherit' && value !== 'initial') {
			conditionalNonCanonicalFonts.push({
				file: stylesheet,
				property: 'font-family',
				value: match[2].trim(),
				line: lineNumber(clean, match.index),
			});
		}
	}

	for (const match of clean.matchAll(/(?:^|;)\s*((?:background-)?color|border(?:-(?:top|right|bottom|left))?-color|fill|stroke)\s*:\s*([^;!}]+)/gim)) {
		const value = match[2].trim().toLowerCase().replace(/\s+/g, ' ');
		if (
			value === 'transparent'
			|| value === 'currentcolor'
			|| value === 'none'
			|| value.includes('var(--nvx-')
		) {
			continue;
		}
		if (/#(?:[0-9a-f]{3,8})\b|rgba?\(|hsla?\(/i.test(value)) {
			conditionalHardcodedColors.push({
				file: stylesheet,
				property: match[1],
				value: match[2].trim(),
				line: lineNumber(clean, match.index),
			});
		}
	}
}

exceptions.conditionalUndefinedTokens = conditionalUndefinedTokens;
exceptions.conditionalUnsafeSelectors = conditionalUnsafeSelectors;
exceptions.conditionalNonCanonicalFonts = conditionalNonCanonicalFonts;
exceptions.conditionalHardcodedColors = conditionalHardcodedColors;

if (conditionalUndefinedTokens.length) {
	conditionalFatals.push(`found ${conditionalUndefinedTokens.length} undefined token reference(s) in conditional runtime CSS`);
}
if (conditionalUnsafeSelectors.length) {
	conditionalFatals.push(`found ${conditionalUnsafeSelectors.length} forbidden positional selector(s) in conditional runtime CSS`);
}
if (conditionalNonCanonicalFonts.length) {
	conditionalFatals.push(`found ${conditionalNonCanonicalFonts.length} non-canonical font-family declaration(s) in conditional runtime CSS`);
}
if (conditionalHardcodedColors.length) {
	conditionalFatals.push(`found ${conditionalHardcodedColors.length} literal color declaration(s) in conditional runtime CSS`);
}

// Consistency: important rows for conditional files must not remain under inactive.
const strayImportantInactive = (exceptions.importantInactive || []).filter((row) => conditionalSet.has(row.file));
if (strayImportantInactive.length) {
	conditionalFatals.push(`importantInactive still contains ${strayImportantInactive.length} conditional runtime row(s)`);
}

// --- Summary / classification counts ---
summary.conditionalRuntimeStylesheets = CONDITIONAL_RUNTIME_STYLESHEETS.length;
summary.activeStylesheets = activeStack.size;
summary.inactiveStylesheets = exceptions.inactiveCssFiles.length;
summary.totalStylesheets = allStylesheets.size;
summary.importantConditionalRuntime = (exceptions.importantConditionalRuntime || []).length;
summary.importantInactive = (exceptions.importantInactive || []).length;
summary.conditionalUndefinedTokens = conditionalUndefinedTokens.length;
summary.conditionalUnsafeSelectors = conditionalUnsafeSelectors.length;
summary.classification = {
	activeSourceStylesheets: activeStack.size,
	conditionalRuntimeWithinActive: summary.conditionalRuntimeStylesheets,
	inactive: inactiveStylesheets.size,
	total: allStylesheets.size,
};

const classifiedTotal = activeStack.size + inactiveStylesheets.size;
if (classifiedTotal !== allStylesheets.size) {
	throw new Error(`stylesheet classification mismatch: ${classifiedTotal} classified vs ${allStylesheets.size} total`);
}

const occurrences = [];
for (const stylesheet of activeStack) {
	const source = stripComments(fs.readFileSync(path.join(CSS_DIR, stylesheet), 'utf8'));
	contextualSelectorOccurrences(source, stylesheet, 0, source.length, [], occurrences);
}

const contextualMap = new Map();
for (const occurrence of occurrences) {
	const key = `${occurrence.selector}\u0000${occurrence.context}`;
	if (!contextualMap.has(key)) contextualMap.set(key, []);
	contextualMap.get(key).push(occurrence);
}

const actionableDuplicateSelectors = [...contextualMap.entries()]
	.filter(([, locations]) => locations.length > 1)
	.map(([key, locations]) => {
		const [selector, context] = key.split('\u0000');
		return {
			selector,
			context,
			locations: locations.map(({ file, line }) => ({ file, line, active: true })),
		};
	})
	.sort((left, right) => left.selector.localeCompare(right.selector) || left.context.localeCompare(right.context));

const rawDuplicateSelectors = exceptions.activeDuplicateSelectors || [];
const actionableSelectorNames = new Set(actionableDuplicateSelectors.map((row) => row.selector));
const responsiveOverrideSelectors = rawDuplicateSelectors.filter(
	(row) => !actionableSelectorNames.has(row.selector),
);

exceptions.activeDuplicateSelectorsRaw = rawDuplicateSelectors;
exceptions.activeDuplicateSelectors = actionableDuplicateSelectors;
exceptions.responsiveOverrideSelectors = responsiveOverrideSelectors;
exceptions.duplicateSelectorMethod = {
	actionable: 'same normalized selector repeated inside the same at-rule/cascade context',
	raw: 'same normalized selector repeated anywhere in the active stack',
};

summary.activeDuplicateSelectorsRaw = rawDuplicateSelectors.length;
summary.activeDuplicateSelectors = actionableDuplicateSelectors.length;
summary.responsiveOverrideSelectors = responsiveOverrideSelectors.length;
summary.activeDuplicateSelectorThreshold = MAX_ACTIONABLE_DUPLICATE_SELECTORS;

if (actionableDuplicateSelectors.length > MAX_ACTIONABLE_DUPLICATE_SELECTORS) {
	conditionalFatals.push(
		`found ${actionableDuplicateSelectors.length} actionable duplicate selectors; maximum is ${MAX_ACTIONABLE_DUPLICATE_SELECTORS}`,
	);
}

if (conditionalFatals.length) {
	console.error('\nCONDITIONAL RUNTIME CSS CLASSIFICATION FAILED');
	for (const error of conditionalFatals) console.error(`- ${error}`);
	process.exit(1);
}

fs.writeFileSync(EXCEPTIONS_PATH, `${JSON.stringify(exceptions, null, 2)}\n`);
fs.writeFileSync(SUMMARY_PATH, `${JSON.stringify(summary, null, 2)}\n`);
console.log(JSON.stringify(summary, null, 2));
