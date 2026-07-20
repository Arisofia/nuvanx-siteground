#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const exists = (relative) => fs.existsSync(path.join(root, relative));
const failures = [];

function requireMatch(source, pattern, message) {
  if (!pattern.test(source)) failures.push(message);
}

const auditPath = 'docs/audits/accessibility-wcag-aa-20260720.md';
if (!exists(auditPath)) {
  console.error(`missing accessibility audit: ${auditPath}`);
  process.exit(1);
}
const audit = read(auditPath);

// --- Document metadata must match the filename --------------------------
requireMatch(audit, /\*\*Date\*\*:\s*2026-07-20/, 'audit "Date" field must match the 20260720 filename suffix');
requireMatch(audit, /WCAG 2\.1 AA/, 'audit title must name the WCAG 2.1 AA standard');
requireMatch(audit, /POUR Framework/, 'audit methodology must name the POUR framework');

// --- All four POUR pillars must be present as top-level sections --------
for (const [order, pillar] of [
  [1, 'Perceivable'],
  [2, 'Operable'],
  [3, 'Understandable'],
  [4, 'Robust'],
]) {
  requireMatch(audit, new RegExp(`## ${order}\\. ${pillar}`), `audit is missing the "${order}. ${pillar}" section`);
}

// --- Every finding must declare one of the known status values ----------
const statusPattern = /\*\*Status\*\*:\s*`([^`]+)`/g;
const knownStatuses = new Set(['PASS', 'PASS / PARTIAL', 'FAIL']);
const statuses = [...audit.matchAll(statusPattern)].map((match) => match[1]);
if (statuses.length === 0) {
  failures.push('audit must declare at least one **Status** field');
}
for (const status of statuses) {
  if (!knownStatuses.has(status)) failures.push(`unrecognized audit status value: "${status}"`);
}

// --- The document must end with an actionable next-steps section --------
requireMatch(audit, /## Priority Fixes & Next Steps/, 'audit must include a "Priority Fixes & Next Steps" section');
requireMatch(audit, /## Priority Fixes & Next Steps\n(?:\d+\. .+\n?)+/, 'priority fixes section must contain a numbered action list');

// --- Cross-reference factual claims against the live codebase -----------
// The audit claims specific tokens and scripts exist; verify those claims
// so the audit does not silently drift from the design system it describes.
const tokens = read('wp-content/themes/nuvanx-medical/assets/css/nvx-tokens.css');
requireMatch(tokens, /--nvx-ink:\s*#1a1a1a;/i, 'audit cites --nvx-ink: #1a1a1a, but the token value has changed');
requireMatch(tokens, /--nvx-light:\s*#fcfbf8;/i, 'audit cites --nvx-light: #fcfbf8, but the token value has changed');
requireMatch(tokens, /--nvx-icon-frame:\s*48px;/i, 'audit cites the 48px --nvx-icon-frame touch target, but the token value has changed');

for (const referencedScript of [
  'scripts/accessibility/test-contrast-contract.mjs',
  'scripts/design-system/audit-css.mjs',
  'scripts/design-system/audit-visual-system.mjs',
]) {
  const basename = path.basename(referencedScript).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  requireMatch(audit, new RegExp(basename), `audit must reference ${referencedScript} by name`);
  if (!exists(referencedScript)) failures.push(`audit references ${referencedScript}, but that script no longer exists`);
}

if (failures.length) {
  console.error('Accessibility audit document contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: accessibility-wcag-aa-20260720.md matches the POUR framework and cites live design-system facts');