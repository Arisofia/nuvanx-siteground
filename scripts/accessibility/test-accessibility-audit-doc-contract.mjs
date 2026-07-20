#!/usr/bin/env node
// Contract test for docs/audits/accessibility-wcag-aa-20260720.md.
//
// The audit report is a static Markdown document, not executable code, so
// this test validates its required structure (POUR framework sections,
// status markers, priority follow-ups) and cross-checks the concrete
// files/selectors/functions it cites against the actual repository so the
// report cannot silently reference things that no longer (or never) exist.
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const exists = (relative) => fs.existsSync(path.join(root, relative));
const failures = [];

/**
 * Records a failure message when a pattern does not match the provided source.
 * @param {string} source - The text to search.
 * @param {RegExp} pattern - The pattern to test against the source.
 * @param {string} message - The failure message to record when the pattern does not match.
 */
function requireMatch(source, pattern, message) {
  if (!pattern.test(source)) failures.push(message);
}

const docPath = 'docs/audits/accessibility-wcag-aa-20260720.md';

if (!exists(docPath)) {
  console.error(`Missing accessibility audit report: ${docPath}`);
  process.exit(1);
}

const doc = read(docPath);

// --- Required metadata -------------------------------------------------------

requireMatch(doc, /\*\*Date\*\*:\s*2026-07-20/, 'audit must record its issue date');
requireMatch(doc, /\*\*Scope\*\*:/, 'audit must record its scope');
requireMatch(doc, /\*\*Methodology\*\*:/, 'audit must record its methodology');
requireMatch(doc, /WCAG 2\.1 AA/, 'audit title/scope must name the WCAG 2.1 AA standard being evaluated');

// --- POUR framework sections must all be present -----------------------------

const pourSections = ['1. Perceivable', '2. Operable', '3. Understandable', '4. Robust'];
for (const section of pourSections) {
  requireMatch(doc, new RegExp(`^## ${section.replace('.', '\\.')}`, 'm'), `audit is missing the "${section}" POUR section`);
}

// Every section must declare an explicit pass/fail-style status so the
// report can be scanned mechanically, not just read.
const statusMatches = doc.match(/\*\*Status\*\*:\s*`[^`]+`/g) || [];
if (statusMatches.length < pourSections.length) {
  failures.push(
    `audit must declare a **Status** marker for each POUR section (found ${statusMatches.length}, `
    + `expected at least ${pourSections.length})`,
  );
}
for (const status of statusMatches) {
  requireMatch(status, /`(PASS|FAIL|PASS \/ PARTIAL)`/, `unexpected status marker format: ${status}`);
}

requireMatch(doc, /## Priority Fixes & Next Steps/, 'audit must end with an actionable priority-fixes section');
requireMatch(doc, /Focus Trap Testing/, 'priority fixes must call out manual focus-trap/screen-reader verification');
requireMatch(doc, /Alt Text Enforcement/, 'priority fixes must call out alt-text enforcement for medical imagery');

// --- Concrete contrast/touch-target claims -----------------------------------

requireMatch(doc, /--nvx-ink/, 'contrast findings must cite the --nvx-ink token');
requireMatch(doc, /--nvx-light/, 'contrast findings must cite the --nvx-light token');
requireMatch(doc, /4\.5:1/, 'audit must state the 4.5:1 normal-text contrast requirement');
requireMatch(doc, /3:1/, 'audit must state the 3:1 large-text contrast requirement');
requireMatch(doc, /48px/, 'audit must state the 48px minimum touch-target requirement');
requireMatch(doc, /--nvx-icon-frame/, 'touch-target finding must cite the --nvx-icon-frame token');

const tokens = exists('wp-content/themes/nuvanx-medical/assets/css/nvx-tokens.css')
  ? read('wp-content/themes/nuvanx-medical/assets/css/nvx-tokens.css')
  : '';
requireMatch(tokens, /--nvx-ink\s*:/, 'audit cites --nvx-ink, but it is missing from nvx-tokens.css');
requireMatch(tokens, /--nvx-light\s*:/, 'audit cites --nvx-light, but it is missing from nvx-tokens.css');
requireMatch(tokens, /--nvx-icon-frame\s*:/, 'audit cites --nvx-icon-frame, but it is missing from nvx-tokens.css');

// --- Cross-reference every concrete script the audit cites as evidence -------
//
// The report cites the contrast contract test by its full relative path, but
// only cites the two design-system audits by their bare filenames (e.g.
// `audit-css.mjs`). Match each citation the way the document actually
// phrases it, and separately confirm the full path it implicitly refers to
// really exists in the repository.
const citedScripts = [
  { path: 'scripts/accessibility/test-contrast-contract.mjs', citedAs: 'scripts/accessibility/test-contrast-contract.mjs' },
  { path: 'scripts/design-system/audit-css.mjs', citedAs: 'audit-css.mjs' },
  { path: 'scripts/design-system/audit-visual-system.mjs', citedAs: 'audit-visual-system.mjs' },
];
for (const { path: script, citedAs } of citedScripts) {
  requireMatch(doc, new RegExp(citedAs.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')), `audit should cite ${citedAs} as evidence`);
  if (!exists(script)) {
    failures.push(`audit cites ${citedAs}, but ${script} does not exist in the repository`);
  }
}

// --- Cross-reference cited selectors/functions against known theme files ----

const visualSystem = exists('wp-content/themes/nuvanx-medical/inc/nvx-visual-system.php')
  ? read('wp-content/themes/nuvanx-medical/inc/nvx-visual-system.php')
  : '';
const externalClosure = exists('wp-content/themes/nuvanx-medical/inc/nvx-external-visual-closure.php')
  ? read('wp-content/themes/nuvanx-medical/inc/nvx-external-visual-closure.php')
  : '';
const contentPresentation = exists('wp-content/themes/nuvanx-medical/inc/nvx-content-presentation.php')
  ? read('wp-content/themes/nuvanx-medical/inc/nvx-content-presentation.php')
  : '';

requireMatch(doc, /#nvx-site-closing-cta/, 'audit should cite the #nvx-site-closing-cta selector');
if (!visualSystem.includes('nvx-site-closing-cta') && !contentPresentation.includes('nvx-site-closing-cta')) {
  failures.push('audit cites #nvx-site-closing-cta, but that identifier could not be found in the visual-system/content-presentation theme includes');
}

requireMatch(doc, /\.joinchat__button/, 'audit should cite the .joinchat__button selector');
if (!externalClosure.includes('.joinchat__button')) {
  failures.push('audit cites .joinchat__button, but that selector could not be found in nvx-external-visual-closure.php');
}

// --- Regression: the audit names a helper function that does not exist ------
//
// The report states that the WhatsApp/Joinchat SVG is injected via a
// canonical helper called `nvx_visual_whatsapp_icon_svg()`. The theme's
// actual icon registry only defines `nvx_visual_icon_svg()`
// (wp-content/themes/nuvanx-medical/inc/nvx-visual-system.php); no function
// named `nvx_visual_whatsapp_icon_svg` exists anywhere in the repository.
// This pins that factual error down so the audit cannot cite a
// non-existent helper without this test failing.
requireMatch(doc, /nvx_visual_whatsapp_icon_svg\(\)/, 'expected the audit to still cite nvx_visual_whatsapp_icon_svg() (update this test if the report is corrected)');
if (visualSystem.includes('function nvx_visual_whatsapp_icon_svg(')) {
  failures.push('nvx_visual_whatsapp_icon_svg() now exists — the audit citation is correct, update this regression test to assert success instead of documenting the gap');
} else {
  failures.push(
    'known factual error: docs/audits/accessibility-wcag-aa-20260720.md credits '
    + '`nvx_visual_whatsapp_icon_svg()` as the canonical icon helper, but no such function exists in the '
    + 'theme; the real helper is `nvx_visual_icon_svg()` in nvx-visual-system.php.',
  );
}

if (failures.length) {
  console.error('Accessibility audit report contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: WCAG 2.1 AA audit report structure and citation contract');