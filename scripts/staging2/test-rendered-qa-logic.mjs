#!/usr/bin/env node
/**
 * Unit tests for the pure helper logic embedded in rendered-qa.mjs.
 *
 * rendered-qa.mjs is a top-level script (it launches a real browser and
 * performs network I/O as soon as it is imported), so it cannot be safely
 * `import`-ed from a test. Its pure, side-effect-free helpers (`add`,
 * `collectTypes`, and the `criticalJs` classifier) are extracted verbatim
 * from the source text and evaluated in an isolated VM context so their
 * actual behaviour is exercised rather than a re-implementation of it.
 */
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import vm from 'node:vm';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const source = fs.readFileSync(path.join(root, 'scripts/staging2/rendered-qa.mjs'), 'utf8');

function extract(label, pattern) {
  const match = source.match(pattern);
  if (!match) {
    throw new Error(`Could not locate ${label} in rendered-qa.mjs; the source contract changed and this test must be updated.`);
  }
  return match[0];
}

const addSource = extract(
  'the add() finding helper',
  /function add\(list, severity, code, message, details = \{\}\) \{[^\n]*\}/,
);
const collectTypesSource = extract(
  'the collectTypes() schema walker',
  /function collectTypes\(value, set = new Set\(\)\) \{[\s\S]*?\n\}/,
);
const criticalJsSource = extract(
  'the criticalJs console-error classifier',
  /const criticalJs = \/.*\/i;/,
);

const sandbox = {};
vm.createContext(sandbox);
// `function` declarations attach to the vm context's global object automatically,
// but the `const criticalJs` does not, so it is re-exposed explicitly.
vm.runInContext(`${addSource}\n${collectTypesSource}\n${criticalJsSource}\nglobalThis.criticalJs = criticalJs;\n`, sandbox);

const { add, collectTypes, criticalJs } = sandbox;
assert.equal(typeof add, 'function', 'add() must be extractable as a standalone function');
assert.equal(typeof collectTypes, 'function', 'collectTypes() must be extractable as a standalone function');
// criticalJs is created inside a separate vm realm, so `instanceof RegExp` (which
// compares against this realm's RegExp constructor) would false-negative; compare
// the internal class tag instead.
assert.equal(Object.prototype.toString.call(criticalJs), '[object RegExp]', 'criticalJs must be a RegExp');

// --- add() ----------------------------------------------------------------

// add() runs inside the vm realm, so pushed finding objects are plain data but
// belong to a different realm's Object/Array prototypes than this file's. They
// are JSON-serializable, so round-tripping through JSON normalizes them into
// this realm before a strict structural comparison.
const plain = (value) => JSON.parse(JSON.stringify(value));

{
  const findings = [];
  add(findings, 'critical', 'missing-title', 'Empty title.');
  assert.deepEqual(plain(findings), [{ severity: 'critical', code: 'missing-title', message: 'Empty title.' }]);
}

{
  const findings = [];
  add(findings, 'warning', 'missing-alt', '2 images.', { images: ['a.jpg', 'b.jpg'] });
  assert.deepEqual(plain(findings), [
    { severity: 'warning', code: 'missing-alt', message: '2 images.', images: ['a.jpg', 'b.jpg'] },
  ]);
}

{
  const findings = [{ severity: 'critical', code: 'a', message: 'first' }];
  add(findings, 'warning', 'b', 'second');
  assert.equal(findings.length, 2, 'add() must append without mutating earlier findings');
  assert.equal(findings[0].code, 'a');
  assert.equal(findings[1].code, 'b');
}

{
  // details must not leak defaults across independent calls (no shared default object mutation).
  const findings = [];
  add(findings, 'warning', 'x', 'no details');
  add(findings, 'warning', 'y', 'with details', { extra: 1 });
  assert.equal('extra' in findings[0], false, 'findings without details must not inherit a shared default object');
  assert.equal(findings[1].extra, 1);
}

// --- collectTypes() ---------------------------------------------------------

assert.deepEqual([...collectTypes(null)], [], 'null input yields an empty set');
assert.deepEqual([...collectTypes(undefined)], [], 'undefined input yields an empty set');
assert.deepEqual([...collectTypes('MedicalClinic')], [], 'primitive input yields an empty set');
assert.deepEqual([...collectTypes(42)], [], 'number input yields an empty set');

assert.deepEqual(
  [...collectTypes({ '@type': 'MedicalClinic' })],
  ['MedicalClinic'],
  'a single @type string is collected',
);

assert.deepEqual(
  [...collectTypes({ '@type': ['Organization', 'MedicalOrganization'] })].sort(),
  ['MedicalOrganization', 'Organization'],
  'an @type array is collected in full',
);

assert.deepEqual(
  [...collectTypes([
    { '@type': 'MedicalClinic' },
    { '@type': ['Person', 'Physician'] },
  ])].sort(),
  ['MedicalClinic', 'Person', 'Physician'],
  'arrays of nodes are traversed and merged',
);

assert.deepEqual(
  [...collectTypes({
    '@context': 'https://schema.org',
    '@graph': [
      { '@type': ['Organization', 'MedicalOrganization'] },
      { '@type': 'MedicalClinic', name: 'NUVANX' },
      { '@type': ['Person', 'Physician'] },
    ],
  })].sort(),
  ['MedicalClinic', 'MedicalOrganization', 'Organization', 'Person', 'Physician'],
  'nested @graph structures are walked recursively',
);

assert.deepEqual(
  [...collectTypes({ '@type': 'MedicalClinic', nested: { '@type': 'MedicalClinic' } })],
  ['MedicalClinic'],
  'duplicate types collapse via the Set',
);

assert.deepEqual([...collectTypes({ name: 'No type here' })], [], 'objects without @type contribute nothing');

assert.deepEqual(
  [...collectTypes([])],
  [],
  'an empty array of JSON-LD blocks (no schema on the page) yields an empty set',
);

// --- criticalJs -------------------------------------------------------------

for (const text of [
  'TypeError: foo is not a function',
  'ReferenceError: bar is not defined',
  'Uncaught SyntaxError: Unexpected token',
  'FacebookSignal is not initialized',
  'some.value is not defined',
]) {
  assert.ok(criticalJs.test(text), `expected criticalJs to flag: ${text}`);
}

for (const text of [
  'Failed to load resource: net::ERR_BLOCKED_BY_CLIENT',
  'Third-party cookie will be blocked in a future release',
  '[Fast Refresh] rebuilding',
  '',
]) {
  assert.ok(!criticalJs.test(text), `expected criticalJs to ignore: ${text}`);
}

console.log('PASS: rendered-qa.mjs pure logic (add/collectTypes/criticalJs) behaves as contracted');