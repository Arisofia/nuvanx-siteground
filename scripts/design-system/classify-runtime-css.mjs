import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '../..');
const QA_DIR = path.join(ROOT, 'qa/design-system');
const EXCEPTIONS_PATH = path.join(QA_DIR, 'active-css-exceptions.json');
const SUMMARY_PATH = path.join(QA_DIR, 'audit-summary.json');
const FUNCTIONS_PATH = path.join(ROOT, 'wp-content/themes/nuvanx-medical/functions.php');
const ENVIRONMENT_FLAGS_PATH = path.join(ROOT, 'wp-content/themes/nuvanx-medical/inc/nvx-environment-flags.php');

const CONDITIONAL_RUNTIME_STYLESHEETS = ['nvx-hero-blackout.css'];

function readJson(file) {
	return JSON.parse(fs.readFileSync(file, 'utf8'));
}

function requireMatch(source, pattern, message) {
	if (!pattern.test(source)) {
		throw new Error(message);
	}
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
	if (activeStack.has(stylesheet)) {
		throw new Error(`conditional runtime stylesheet must not be counted as globally active: ${stylesheet}`);
	}
	if (!referenced.has(stylesheet)) {
		throw new Error(`conditional runtime stylesheet has no runtime reference evidence: ${stylesheet}`);
	}
}

exceptions.conditionalRuntimeStylesheets = CONDITIONAL_RUNTIME_STYLESHEETS;
exceptions.inactiveCssFiles = (exceptions.inactiveCssFiles || []).filter(
	(stylesheet) => !CONDITIONAL_RUNTIME_STYLESHEETS.includes(stylesheet),
);
exceptions.conditionalRuntimeReferenceEvidence = (exceptions.stylesheetReferenceEvidence || []).filter(
	(row) => CONDITIONAL_RUNTIME_STYLESHEETS.includes(row.file),
);

summary.conditionalRuntimeStylesheets = CONDITIONAL_RUNTIME_STYLESHEETS.length;
summary.inactiveStylesheets = exceptions.inactiveCssFiles.length;
summary.classification = {
	globalActive: summary.activeStylesheets,
	conditionalRuntime: summary.conditionalRuntimeStylesheets,
	inactive: summary.inactiveStylesheets,
	total: summary.totalStylesheets,
};

const classifiedTotal = summary.activeStylesheets + summary.conditionalRuntimeStylesheets + summary.inactiveStylesheets;
if (classifiedTotal !== summary.totalStylesheets) {
	throw new Error(`stylesheet classification mismatch: ${classifiedTotal} classified vs ${summary.totalStylesheets} total`);
}

fs.writeFileSync(EXCEPTIONS_PATH, `${JSON.stringify(exceptions, null, 2)}\n`);
fs.writeFileSync(SUMMARY_PATH, `${JSON.stringify(summary, null, 2)}\n`);
console.log(JSON.stringify(summary, null, 2));
