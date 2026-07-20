#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const docRelative = 'docs/audits/accessibility-wcag-aa-20260720.md';
const docPath = path.join(root, docRelative);
const failures = [];

function requireMatch(source, pattern, message) {
  if (!pattern.test(source)) failures.push(message);
}

function requireAbsent(source, pattern, message) {
  if (pattern.test(source)) failures.push(message);
}

if (!fs.existsSync(docPath)) {
  failures.push(`missing accessibility audit: ${docRelative}`);
} else {
  const audit = fs.readFileSync(docPath, 'utf8');

  requireMatch(audit, /^# NUVANX Accessibility Audit \(WCAG 2\.1 AA\)/, 'audit must open with the WCAG 2.1 AA title');
  requireMatch(audit, /\*\*Date\*\*:\s*2026-07-20/, 'audit must record the 2026-07-20 audit date');
  requireMatch(audit, /\*\*Methodology\*\*:\s*Static Code Analysis & POUR Framework Evaluation/, 'audit must declare the POUR methodology');

  const pourSections = [
    '## 1. Perceivable',
    '## 2. Operable',
    '## 3. Understandable',
    '## 4. Robust',
  ];
  for (const section of pourSections) {
    requireMatch(audit, new RegExp(`^${section.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}`, 'm'), `audit must include the "${section}" POUR section`);
  }

  requireMatch(audit, /### 1\.2 Contrast \(Minimum\)/, 'audit must include a contrast (minimum) subsection');
  requireMatch(audit, /4\.5:1/, 'audit must cite the 4.5:1 minimum contrast ratio for normal text');
  requireMatch(audit, /3:1/, 'audit must cite the 3:1 minimum contrast ratio for large text');
  requireMatch(audit, /scripts\/accessibility\/test-contrast-contract\.mjs/, 'audit must reference the contrast contract test as evidence');

  requireMatch(audit, /### 2\.2 Navigable \(Touch Targets\)/, 'audit must include a touch-target subsection');
  requireMatch(audit, /48px/, 'audit must cite the 48px minimum touch target size');
  requireMatch(audit, /--nvx-icon-frame/, 'audit must reference the --nvx-icon-frame token backing the touch target size');

  requireMatch(audit, /audit-css\.mjs/, 'audit must reference audit-css.mjs as consistency evidence');
  requireMatch(audit, /audit-visual-system\.mjs/, 'audit must reference audit-visual-system.mjs as robustness evidence');

  requireMatch(audit, /## Priority Fixes & Next Steps/, 'audit must include a priority fixes / next steps section');
  requireMatch(audit, /Focus Trap Testing/, 'audit must call out focus-trap testing as a follow-up action');
  requireMatch(audit, /VoiceOver\/NVDA/, 'audit must name concrete screen readers for manual verification');
  requireMatch(audit, /Alt Text Enforcement/, 'audit must call out alt-text enforcement as a follow-up action');

  requireAbsent(audit, /`FAIL`/, 'audit currently reports no outright accessibility failures; unexpected FAIL status found');
}

if (failures.length) {
  console.error('WCAG accessibility audit document contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: accessibility-wcag-aa-20260720.md documents the POUR framework findings and follow-ups');