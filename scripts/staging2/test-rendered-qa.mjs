#!/usr/bin/env node

// Unit and contract tests for scripts/staging2/rendered-qa.mjs.
//
// The module launches a real Chromium browser and performs network requests
// as a side effect of being imported (there is no `isMain` guard), so it
// cannot be safely `import`-ed from a test. Instead we:
//   1. Extract the two pure helper functions (`add`, `collectTypes`) from the
//      source text and exercise them directly with real inputs/outputs.
//   2. Assert on the remaining behaviour (thresholds, codes, defaults,
//      allowlists, exit-code contract) via targeted regex checks against the
//      source, mirroring the existing contract-test convention used
//      elsewhere in this repository (see test-deploy-workflow-contract.mjs).

import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const scriptPath = path.join(here, 'rendered-qa.mjs');
const source = fs.readFileSync(scriptPath, 'utf8');

/**
 * Extracts the full source text of a top-level `function name(...) {...}`
 * declaration, correctly handling default-parameter braces/parens (e.g.
 * `details = {}`) and nested braces in the function body.
 */
function extractFunctionSource(text, name) {
  const marker = `function ${name}(`;
  const start = text.indexOf(marker);
  if (start === -1) throw new Error(`Could not locate function ${name} in source`);

  let i = start + marker.length - 1; // index of the opening '(' of the parameter list
  let parenDepth = 0;
  for (; i < text.length; i += 1) {
    if (text[i] === '(') parenDepth += 1;
    else if (text[i] === ')') {
      parenDepth -= 1;
      if (parenDepth === 0) { i += 1; break; }
    }
  }
  while (text[i] !== '{') i += 1;

  let braceDepth = 0;
  for (; i < text.length; i += 1) {
    if (text[i] === '{') braceDepth += 1;
    else if (text[i] === '}') {
      braceDepth -= 1;
      if (braceDepth === 0) { i += 1; break; }
    }
  }
  return text.slice(start, i);
}

function loadFunction(name) {
  const code = extractFunctionSource(source, name);
  // eslint-disable-next-line no-new-func -- deliberately sandboxing the real source text
  const factory = new Function(`${code}\nreturn ${name};`);
  return factory();
}

const add = loadFunction('add');
const collectTypes = loadFunction('collectTypes');

// --- node --check on the real script (mirrors the CI "Validate audit source" step) ---
execFileSync(process.execPath, ['--check', scriptPath], { stdio: 'pipe' });

// --- add() ---
{
  const list = [];
  add(list, 'critical', 'missing-title', 'Empty title.');
  assert.deepEqual(list, [{ severity: 'critical', code: 'missing-title', message: 'Empty title.' }]);
}

{
  const list = [];
  add(list, 'warning', 'missing-alt', '2 images.', { images: ['a.jpg', 'b.jpg'] });
  assert.deepEqual(list, [
    { severity: 'warning', code: 'missing-alt', message: '2 images.', images: ['a.jpg', 'b.jpg'] },
  ]);
}

{
  // Multiple calls accumulate in order and do not clobber each other.
  const list = [];
  add(list, 'critical', 'h1-count', 'Expected 1 H1, found 0.', { h1s: [] });
  add(list, 'warning', 'body-font', 'Arial');
  assert.equal(list.length, 2);
  assert.equal(list[0].code, 'h1-count');
  assert.deepEqual(list[0].h1s, []);
  assert.equal(list[1].code, 'body-font');
  assert.equal(Object.keys(list[1]).includes('h1s'), false);
}

// --- collectTypes() ---
assert.deepEqual([...collectTypes(null)], []);
assert.deepEqual([...collectTypes(undefined)], []);
assert.deepEqual([...collectTypes('a string')], []);
assert.deepEqual([...collectTypes(42)], []);
assert.deepEqual([...collectTypes({})], []);

assert.deepEqual([...collectTypes({ '@type': 'MedicalClinic' })], ['MedicalClinic']);

assert.deepEqual(
  [...collectTypes({ '@type': ['Organization', 'MedicalOrganization'] })].sort(),
  ['MedicalOrganization', 'Organization'],
);

assert.deepEqual(
  [...collectTypes([{ '@type': 'A' }, { '@type': ['B', 'C'] }])].sort(),
  ['A', 'B', 'C'],
);

// Nested structures (e.g. an @graph array) are traversed recursively.
assert.deepEqual(
  [...collectTypes({ '@graph': [{ '@type': 'MedicalClinic' }, { '@type': 'Physician' }] })].sort(),
  ['MedicalClinic', 'Physician'],
);

// Duplicate types across nested nodes are de-duplicated (it's a Set).
assert.deepEqual([...collectTypes([{ '@type': 'A' }, { '@type': 'A' }])], ['A']);

// An existing Set can be passed in and is populated in place.
{
  const seed = new Set(['Preexisting']);
  const result = collectTypes({ '@type': 'MedicalClinic' }, seed);
  assert.equal(result, seed);
  assert.deepEqual([...result].sort(), ['MedicalClinic', 'Preexisting']);
}

// Objects without an @type key contribute nothing but their descendants still are visited.
assert.deepEqual(
  [...collectTypes({ wrapper: { nested: { '@type': 'Service' } } })],
  ['Service'],
);

console.log('PASS: rendered-qa.mjs pure helper unit tests');

// --- Contract checks for the remaining (non-extractable) behaviour ---
const failures = [];
const requireMatch = (pattern, message) => { if (!pattern.test(source)) failures.push(message); };

requireMatch(/const viewports = \[\['desktop', 1440, 1100\], \['mobile', 390, 844\]\]/, 'must audit exactly desktop (1440x1100) and mobile (390x844) viewports');
requireMatch(/criticalJs = \/\(ReferenceError\|TypeError\|SyntaxError\|Uncaught\|FacebookSignal\|is not defined\)\/i/, 'must classify known critical JS error signatures');
requireMatch(/process\.env\.BASE_URL \|\| config\.baseUrl/, 'must allow BASE_URL env override of the routes config baseUrl');
requireMatch(/process\.env\.QA_OUTPUT_DIR \|\| 'qa-artifacts\/staging2-rendered'/, 'must default the output dir to qa-artifacts/staging2-rendered');
requireMatch(/process\.env\.EXPECTED_CANONICAL_HOST \|\| 'nuvanx\.com'/, 'must default the canonical host to nuvanx.com');
requireMatch(/waitUntil: 'domcontentloaded', timeout: 45000/, 'navigation must use a bounded 45s timeout');
requireMatch(/status && status < 400/, 'rendered checks must only run for non-error HTTP responses');
requireMatch(/rendered\.h1s\.length !== 1/, 'must flag pages without exactly one H1');
requireMatch(/staging-indexable/, 'must flag staging pages that are indexable (missing noindex)');
requireMatch(/missing-canonical/, 'must flag pages missing a canonical link');
requireMatch(/rendered\.overflow > 2/, 'must flag horizontal overflow beyond a 2px tolerance');
requireMatch(/duplicate-ids/, 'must flag duplicate element ids');
requireMatch(/Manrope/i, 'must assert the expected body font family');
requireMatch(/Playfair Display/i, 'must assert the expected heading font family');
requireMatch(/box\.width < 44 \|\| box\.height < 44/, 'must flag touch targets smaller than the 44px minimum');
requireMatch(/size\(rendered\.joinchatButton, 48, 'joinchat-frame-size'\)/, 'must assert the 48px Joinchat button size');
requireMatch(/size\(rendered\.joinchatIcon, 24, 'joinchat-icon-size'\)/, 'must assert the 24px Joinchat icon size');
requireMatch(/size\(rendered\.inlineWhatsapp, 16, 'inline-whatsapp-size'\)/, 'must assert the 16px inline WhatsApp icon size');
requireMatch(/\['home', 'contacto', 'clinicas'\]\.includes\(slug\) && !rendered\.schemaTypes\.includes\('MedicalClinic'\)/, 'home/contacto/clinicas routes must require MedicalClinic schema');
requireMatch(/\['endolift', 'laser', 'medicina-estetica'\]\.includes\(slug\) && !rendered\.schemaTypes\.includes\('MedicalProcedure'\)/, 'treatment routes must require MedicalProcedure schema');
requireMatch(/invalid-jsonld/, 'must flag unparsable JSON-LD blocks');
requireMatch(/siteground-captcha/, 'must flag rendered SiteGround bot-challenge pages');
requireMatch(/\/\(google\|facebook\|doubleclick\|hubspot\|hs-scripts\|clarity\)\\\.\//i, 'must ignore known third-party domains when tracking failed requests');
requireMatch(/args: \['--no-sandbox', '--disable-setuid-sandbox'\]/, 'headless Chromium must launch with sandbox flags suitable for CI');
requireMatch(/path\.join\(out, `\$\{slug\}-\$\{viewport\}\.png`\)/, 'screenshots must be named "<slug>-<viewport>.png"');
requireMatch(/fs\.writeFileSync\(path\.join\(out, 'report\.json'\)/, 'must write a report.json artifact');
requireMatch(/fs\.writeFileSync\(path\.join\(out, 'report\.md'\)/, 'must write a report.md artifact');
requireMatch(/if \(critical\.length\) process\.exit\(1\);/, 'the process must exit non-zero when any critical finding exists');
requireMatch(/result: critical\.length \? 'FAIL' : 'PASS_WITH_WARNINGS'/, 'the report summary result must reflect FAIL vs PASS_WITH_WARNINGS');

if (failures.length) {
  console.error('rendered-qa.mjs contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: rendered-qa.mjs behavioural contract');