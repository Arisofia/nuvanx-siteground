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

const CONDITIONAL_RUNTIME_STYLESHEETS = ['nvx-hero-blackout.css'];
const MAX_ACTIONABLE_DUPLICATE_SELECTORS = 220;

function readJson(file) {
	return JSON.parse(fs.readFileSync(file, 'utf8'));
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

const functionsSource = fs.readFileSync(FUNCTIONS_PATH, 'utf8');
const environmentSource = fs.readFileSync(ENVIRONMENT_FLAGS_PATH, 'utf8');

requireMatch(functionsSource, /function\s+nvx_theme_hero_blackout_enabled\s*\(/, 'hero blackout toggle function is missing');
requireMatch(functionsSource, /\$enabled\s*=\s*true\s*;/, 'hero blackout is no longer default-on; review the conditional classification');
requireMatch(functionsSource, /wp_enqueue_style\s*\([\s\S]*?['"]nvx-hero-blackout['"][\s\S]*?nvx-hero-blackout\.css/, 'hero blackout stylesheet is not enqueued at runtime');
requireMatch(environmentSource, /nvx_theme_hero_blackout_enabled/, 'staging-only blackout filter is not registered');
requireMatch(environmentSource, /staging2\.nuvanx\.com/, 'staging2 host safeguard is missing');

const exceptions = readJson(EXCEPTIONS_PATH);
const summary = readJson(SUMMARY_PATH);
const allStylesheets = new Set(exceptions.allStylesheets || []);
const activeStack = new Set(exceptions.activeStack || []);
const referenced = new Set(exceptions.referencedStylesheets || []);

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

exceptions.conditionalRuntimeStylesheets = CONDITIONAL_RUNTIME_STYLESHEETS;
exceptions.conditionalRuntimeReferenceEvidence = (exceptions.stylesheetReferenceEvidence || []).filter(
	(row) => CONDITIONAL_RUNTIME_STYLESHEETS.includes(row.file),
);

summary.conditionalRuntimeStylesheets = CONDITIONAL_RUNTIME_STYLESHEETS.length;
summary.inactiveStylesheets = exceptions.inactiveCssFiles.length;
summary.classification = {
	activeSourceStylesheets: summary.activeStylesheets,
	conditionalRuntimeWithinActive: summary.conditionalRuntimeStylesheets,
	inactive: summary.inactiveStylesheets,
	total: summary.totalStylesheets,
};

const classifiedTotal = summary.activeStylesheets + summary.inactiveStylesheets;
if (classifiedTotal !== summary.totalStylesheets) {
	throw new Error(`stylesheet classification mismatch: ${classifiedTotal} classified vs ${summary.totalStylesheets} total`);
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
	throw new Error(
		`found ${actionableDuplicateSelectors.length} actionable duplicate selectors; maximum is ${MAX_ACTIONABLE_DUPLICATE_SELECTORS}`,
	);
}

fs.writeFileSync(EXCEPTIONS_PATH, `${JSON.stringify(exceptions, null, 2)}\n`);
fs.writeFileSync(SUMMARY_PATH, `${JSON.stringify(summary, null, 2)}\n`);
console.log(JSON.stringify(summary, null, 2));
