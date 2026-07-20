#!/usr/bin/env node
/**
 * Contract test for scripts/staging2/rendered-qa.mjs.
 *
 * The script drives a real Chromium browser against a live Staging2 URL, so
 * it cannot be executed inside this test. Instead this asserts, via source
 * inspection, that the safety-critical findings, thresholds and codes the CI
 * workflow and job summary rely on are present and have not silently
 * regressed (e.g. a threshold being loosened or a finding code being
 * renamed without updating the workflow/report consumers).
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const scriptPath = 'scripts/staging2/rendered-qa.mjs';
const source = fs.readFileSync(path.join(root, scriptPath), 'utf8');
const failures = [];

function requireMatch(pattern, message) {
  if (!pattern.test(source)) failures.push(message);
}

function requireAbsent(pattern, message) {
  if (pattern.test(source)) failures.push(message);
}

// --- configuration & defaults ----------------------------------------------

requireMatch(
  /const config = JSON\.parse\(fs\.readFileSync\(new URL\('\.\/rendered-qa-routes\.json', import\.meta\.url\), 'utf8'\)\);/,
  'script must load routes from the colocated rendered-qa-routes.json',
);
requireMatch(
  /const baseUrl = \(process\.env\.BASE_URL \|\| config\.baseUrl\)\.replace\(\/\\\/\$\/, ''\);/,
  'baseUrl must default to config.baseUrl and strip a trailing slash',
);
requireMatch(
  /const out = path\.resolve\(process\.env\.QA_OUTPUT_DIR \|\| 'qa-artifacts\/staging2-rendered'\);/,
  'artifact output directory must default to qa-artifacts/staging2-rendered',
);
requireMatch(
  /const canonicalHost = process\.env\.EXPECTED_CANONICAL_HOST \|\| 'nuvanx\.com';/,
  'canonical host must default to nuvanx.com',
);
requireMatch(
  /const viewports = \[\['desktop', 1440, 1100\], \['mobile', 390, 844\]\];/,
  'script must audit both a desktop (1440x1100) and a mobile (390x844) viewport',
);

// --- console/JS error classification ----------------------------------------

for (const token of ['ReferenceError', 'TypeError', 'SyntaxError', 'Uncaught', 'FacebookSignal', 'is not defined']) {
  requireMatch(new RegExp(`criticalJs = /.*${token}.*/i`), `criticalJs classifier must flag "${token}"`);
}

// --- third-party network noise allowlist ------------------------------------

requireMatch(
  /google\|facebook\|doubleclick\|hubspot\|hs-scripts\|clarity/,
  'request-failed noise filter must allowlist known third-party trackers',
);
requireMatch(
  /request\.resourceType\(\) !== 'media'/,
  'request-failed noise filter must exclude media resource failures',
);

// --- rendered-page findings: presence and codes -----------------------------

const expectedFindings = [
  ['siteground-captcha', /rendered\.captcha\) add\(findings, 'critical', 'siteground-captcha'/],
  ['missing-title', /!rendered\.title\) add\(findings, 'critical', 'missing-title'/],
  ['missing-description', /!rendered\.description\) add\(findings, 'warning', 'missing-description'/],
  ['h1-count', /rendered\.h1s\.length !== 1\) add\(findings, 'critical', 'h1-count'/],
  ['staging-indexable', /add\(findings, 'critical', 'staging-indexable'/],
  ['missing-canonical', /!rendered\.canonical\) add\(findings, 'critical', 'missing-canonical'/],
  ['canonical-host', /add\(findings, 'warning', 'canonical-host', host\)/],
  ['invalid-canonical', /add\(findings, 'critical', 'invalid-canonical', rendered\.canonical\)/],
  ['horizontal-overflow', /rendered\.overflow > 2\) add\(findings, 'critical', 'horizontal-overflow'/],
  ['duplicate-ids', /rendered\.duplicateIds\.length\) add\(findings, 'critical', 'duplicate-ids'/],
  ['missing-alt', /rendered\.missingAlt\.length\) add\(findings, 'warning', 'missing-alt'/],
  ['body-font', /!\/Manrope\/i\.test\(rendered\.bodyFont\)\) add\(findings, 'warning', 'body-font'/],
  ['heading-font', /!\/Playfair Display\/i\.test\(rendered\.h1Font\)\) add\(findings, 'warning', 'heading-font'/],
  ['small-controls', /rendered\.smallControls\.length\) add\(findings, 'warning', 'small-controls'/],
  ['invalid-jsonld', /add\(findings, 'critical', 'invalid-jsonld'/],
  ['schema-medical-clinic', /add\(findings, 'warning', 'schema-medical-clinic'/],
  ['schema-medical-procedure', /add\(findings, 'warning', 'schema-medical-procedure'/],
  ['navigation-failed', /add\(findings, 'critical', 'navigation-failed', error\.message\)/],
  ['http-status', /add\(findings, 'critical', 'http-status'/],
  ['console-error', /add\(findings, 'critical', 'console-error', msg\.text\(\)\)/],
  ['page-error', /add\(findings, 'critical', 'page-error', error\.message\)/],
  ['request-failed', /add\(findings, 'warning', 'request-failed', url\)/],
];

for (const [code, pattern] of expectedFindings) {
  requireMatch(pattern, `finding code "${code}" must be raised by the expected condition`);
}

// --- accessibility target-size thresholds -----------------------------------

requireMatch(
  /box\.width < 44 \|\| box\.height < 44/,
  'small interactive controls must be flagged below the 44x44 CSS px accessible target size',
);
requireMatch(
  /size\(rendered\.joinchatButton, 48, 'joinchat-frame-size'\);/,
  'joinchat launcher frame must be checked against a 48px target',
);
requireMatch(
  /size\(rendered\.joinchatIcon, 24, 'joinchat-icon-size'\);/,
  'joinchat icon must be checked against a 24px target',
);
requireMatch(
  /size\(rendered\.inlineWhatsapp, 16, 'inline-whatsapp-size'\);/,
  'inline WhatsApp icon must be checked against a 16px target',
);

// --- per-route schema expectations -------------------------------------------

requireMatch(
  /\['home', 'contacto', 'clinicas'\]\.includes\(slug\) && !rendered\.schemaTypes\.includes\('MedicalClinic'\)/,
  'home/contacto/clinicas routes must be checked for MedicalClinic schema',
);
requireMatch(
  /\['endolift', 'laser', 'medicina-estetica'\]\.includes\(slug\) && !rendered\.schemaTypes\.includes\('MedicalProcedure'\)/,
  'endolift/laser/medicina-estetica routes must be checked for MedicalProcedure schema',
);

// --- screenshot naming & report artifacts ------------------------------------

requireMatch(
  /path\.join\(out, `\$\{slug\}-\$\{viewport\}\.png`\)/,
  'full-page screenshots must be named "<slug>-<viewport>.png"',
);
requireMatch(/fullPage: true/, 'screenshots must capture the full page, not only the viewport');
requireMatch(
  /fs\.writeFileSync\(path\.join\(out, 'report\.json'\)/,
  'a machine-readable report.json must be written to the output directory',
);
requireMatch(
  /fs\.writeFileSync\(path\.join\(out, 'report\.md'\)/,
  'a human-readable report.md must be written to the output directory',
);

// --- summary & exit code contract --------------------------------------------

requireMatch(
  /result: critical\.length \? 'FAIL' : 'PASS_WITH_WARNINGS'/,
  'the report summary must resolve to FAIL when any critical finding exists',
);
requireMatch(
  /if \(critical\.length\) process\.exit\(1\);/,
  'the script must exit non-zero when critical findings are present so CI enforcement can act on it',
);
requireAbsent(
  /process\.exit\(0\)/,
  'the script must not force a zero exit code (that would defeat the CI enforcement step)',
);

// --- browser launch hardening -------------------------------------------------

requireMatch(
  /chromium\.launch\(\{ headless: true, args: \['--no-sandbox', '--disable-setuid-sandbox'\] \}\)/,
  'chromium must launch headless with sandbox flags suitable for CI containers',
);
requireMatch(
  /await browser\.close\(\);/,
  'the browser must always be closed (in a finally block) once the audit completes',
);

if (failures.length) {
  console.error(`Rendered QA script contract failed (${scriptPath}):`);
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: rendered-qa.mjs findings/thresholds/exit-code contract');