#!/usr/bin/env node
/** Contract and unit tests for the Staging2 rendered QA script, its route config, and its workflow. */

import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const failures = [];

function requireMatch(source, pattern, message) {
  if (!pattern.test(source)) failures.push(message);
}

function requireAbsent(source, pattern, message) {
  if (pattern.test(source)) failures.push(message);
}

const scriptPath = 'scripts/staging2/rendered-qa.mjs';
const routesPath = 'scripts/staging2/rendered-qa-routes.json';
const workflowPath = '.github/workflows/staging2-rendered-qa.yml';

for (const requiredPath of [scriptPath, routesPath, workflowPath]) {
  if (!fs.existsSync(path.join(root, requiredPath))) {
    failures.push(`missing required rendered QA contract file: ${requiredPath}`);
  }
}

if (failures.length) {
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

const script = read(scriptPath);
const routesRaw = read(routesPath);
const workflow = read(workflowPath);

// ---------------------------------------------------------------------------
// Unit tests for the pure helper functions extracted from the live source.
// The script executes browser automation at module load time, so it cannot
// be imported directly in a unit test; instead we extract the two pure
// helper functions from the real source text and exercise them in isolation.
// ---------------------------------------------------------------------------

/**
 * Extracts a top-level `function name(...) { ... }` declaration from source
 * text using balanced paren/brace matching (robust to nested parens such as
 * default parameter values, and nested braces in the function body).
 */
function extractFunction(source, name) {
  const marker = `function ${name}(`;
  const start = source.indexOf(marker);
  assert.ok(start !== -1, `expected to find function ${name} in ${scriptPath}`);

  let index = start + marker.length - 1; // position of the opening '('
  let depth = 0;
  for (; index < source.length; index += 1) {
    if (source[index] === '(') depth += 1;
    else if (source[index] === ')') {
      depth -= 1;
      if (depth === 0) { index += 1; break; }
    }
  }

  while (/\s/.test(source[index])) index += 1;
  assert.equal(source[index], '{', `expected function ${name} body to start with '{'`);

  depth = 0;
  for (; index < source.length; index += 1) {
    if (source[index] === '{') depth += 1;
    else if (source[index] === '}') {
      depth -= 1;
      if (depth === 0) { index += 1; break; }
    }
  }

  return source.slice(start, index);
}

const add = new Function(`return (${extractFunction(script, 'add')});`)();
const collectTypes = new Function(`return (${extractFunction(script, 'collectTypes')});`)();

// add(): builds a structured finding and appends it to the supplied list.
{
  const findings = [];
  add(findings, 'critical', 'missing-title', 'Empty title.');
  assert.deepEqual(findings, [{ severity: 'critical', code: 'missing-title', message: 'Empty title.' }]);
}
{
  const findings = [];
  add(findings, 'warning', 'missing-alt', '2 images.', { images: ['a.jpg', 'b.jpg'] });
  assert.deepEqual(findings, [
    { severity: 'warning', code: 'missing-alt', message: '2 images.', images: ['a.jpg', 'b.jpg'] },
  ]);
}
{
  // Multiple calls append in order and do not mutate previous entries.
  const findings = [];
  add(findings, 'critical', 'a', 'first');
  add(findings, 'warning', 'b', 'second', { extra: 1 });
  assert.equal(findings.length, 2);
  assert.equal(findings[0].code, 'a');
  assert.equal(findings[1].extra, 1);
}

// collectTypes(): recursively collects unique @type values from JSON-LD-like structures.
assert.deepEqual([...collectTypes({ '@type': 'MedicalClinic' })], ['MedicalClinic']);
assert.deepEqual(
  [...collectTypes({ '@type': ['Organization', 'MedicalOrganization'] })].sort(),
  ['MedicalOrganization', 'Organization'],
);
assert.deepEqual(
  [...collectTypes([{ '@type': 'A' }, { '@type': 'B' }])].sort(),
  ['A', 'B'],
);
assert.deepEqual(
  [...collectTypes({ nested: { '@type': 'Deep' }, arr: [{ '@type': 'Deep2' }] })].sort(),
  ['Deep', 'Deep2'],
);
// Duplicate types across nested nodes are de-duplicated by the Set.
assert.deepEqual(
  [...collectTypes({ '@type': 'Dup', child: { '@type': 'Dup' } })],
  ['Dup'],
);
// Non-object / primitive input yields an empty set without throwing.
assert.deepEqual([...collectTypes(null)], []);
assert.deepEqual([...collectTypes(undefined)], []);
assert.deepEqual([...collectTypes('a string')], []);
assert.deepEqual([...collectTypes(42)], []);
// An existing Set can be passed in and accumulates alongside new values.
assert.deepEqual(
  [...collectTypes({ '@type': 'X' }, new Set(['Y']))].sort(),
  ['X', 'Y'],
);
// Nodes without an @type contribute nothing but their children are still walked.
assert.deepEqual([...collectTypes({ '@context': 'https://schema.org', child: { '@type': 'Z' } })], ['Z']);

// ---------------------------------------------------------------------------
// scripts/staging2/rendered-qa-routes.json structural contract.
// ---------------------------------------------------------------------------

const config = JSON.parse(routesRaw);
assert.equal(typeof config.baseUrl, 'string');
assert.match(config.baseUrl, /^https:\/\//, 'baseUrl must be an absolute https URL');
assert.ok(Array.isArray(config.routes) && config.routes.length > 0, 'routes must be a non-empty array');

const seenSlugs = new Set();
const seenPaths = new Set();
for (const entry of config.routes) {
  assert.ok(Array.isArray(entry) && entry.length === 2, `route entry must be a [slug, path] tuple: ${JSON.stringify(entry)}`);
  const [slug, routePath] = entry;
  assert.match(slug, /^[a-z0-9-]+$/, `slug must be lowercase kebab-case: ${slug}`);
  assert.match(routePath, /^\//, `route path must start with '/': ${routePath}`);
  assert.equal(seenSlugs.has(slug), false, `duplicate route slug: ${slug}`);
  assert.equal(seenPaths.has(routePath), false, `duplicate route path: ${routePath}`);
  seenSlugs.add(slug);
  seenPaths.add(routePath);
}

// The script applies schema-type expectations to specific slugs; those
// slugs must actually exist in the route config or the checks are dead code.
for (const slug of ['home', 'contacto', 'clinicas', 'endolift', 'laser', 'medicina-estetica']) {
  assert.ok(seenSlugs.has(slug), `rendered-qa.mjs expects a "${slug}" route but it is missing from rendered-qa-routes.json`);
}

// ---------------------------------------------------------------------------
// scripts/staging2/rendered-qa.mjs behavioral contract (regex over source).
// ---------------------------------------------------------------------------

requireMatch(script, /import \{ chromium \} from 'playwright';/, 'script must drive Playwright chromium');
requireMatch(script, /const viewports = \[\['desktop', 1440, 1100\], \['mobile', 390, 844\]\];/, 'script must audit both desktop and mobile viewports');
requireMatch(
  script,
  /const criticalJs = \/\(ReferenceError\|TypeError\|SyntaxError\|Uncaught\|FacebookSignal\|is not defined\)\/i;/,
  'script must classify a defined set of JS error patterns as critical',
);

requireMatch(script, /process\.env\.BASE_URL \|\| config\.baseUrl/, 'BASE_URL env var must override the configured base URL');
requireMatch(script, /process\.env\.QA_OUTPUT_DIR \|\| 'qa-artifacts\/staging2-rendered'/, 'QA_OUTPUT_DIR env var must control the output directory');
requireMatch(script, /process\.env\.EXPECTED_DEPLOY_SHA/, 'script must read the expected deploy SHA from the environment');
requireMatch(script, /process\.env\.EXPECTED_CANONICAL_HOST \|\| 'nuvanx\.com'/, 'script must default the canonical host to nuvanx.com');
requireMatch(script, /fs\.mkdirSync\(out, \{ recursive: true \}\)/, 'script must create the output directory before writing artifacts');

requireMatch(script, /page\.on\('console'/, 'script must listen for console errors');
requireMatch(script, /page\.on\('pageerror'/, 'script must listen for uncaught page errors');
requireMatch(script, /page\.on\('requestfailed'/, 'script must listen for failed requests');
requireMatch(
  script,
  /\(google\|facebook\|doubleclick\|hubspot\|hs-scripts\|clarity\)\\\.\/i\.test\(url\)/,
  'script must ignore known third-party trackers when flagging failed requests',
);

requireMatch(script, /waitUntil: 'domcontentloaded', timeout: 45000/, 'navigation must use a bounded timeout');
requireMatch(script, /if \(!status \|\| status >= 400\) add\(findings, 'critical', 'http-status'/, 'script must flag non-2xx/3xx responses as critical');

requireMatch(script, /if \(rendered\.captcha\) add\(findings, 'critical', 'siteground-captcha'/, 'script must flag rendered bot-challenge pages as critical');
requireMatch(script, /if \(!rendered\.title\) add\(findings, 'critical', 'missing-title'/, 'script must flag an empty title as critical');
requireMatch(script, /if \(!rendered\.description\) add\(findings, 'warning', 'missing-description'/, 'script must flag an empty meta description as a warning');
requireMatch(script, /if \(rendered\.h1s\.length !== 1\) add\(findings, 'critical', 'h1-count'/, 'script must require exactly one H1');
requireMatch(script, /if \(!\/noindex\/i\.test\(rendered\.robots\)[\s\S]*add\(findings, 'critical', 'staging-indexable'/, 'script must flag indexable staging pages as critical');
requireMatch(script, /if \(!rendered\.canonical\) add\(findings, 'critical', 'missing-canonical'/, 'script must require a canonical link');
requireMatch(script, /if \(rendered\.overflow > 2\) add\(findings, 'critical', 'horizontal-overflow'/, 'script must flag horizontal overflow as critical');
requireMatch(script, /if \(rendered\.duplicateIds\.length\) add\(findings, 'critical', 'duplicate-ids'/, 'script must flag duplicate DOM ids as critical');
requireMatch(script, /if \(rendered\.missingAlt\.length\) add\(findings, 'warning', 'missing-alt'/, 'script must flag images missing alt text as a warning');
requireMatch(script, /if \(!\/Manrope\/i\.test\(rendered\.bodyFont\)\) add\(findings, 'warning', 'body-font'/, 'script must warn when the body font is not Manrope');
requireMatch(script, /if \(rendered\.h1s\.length && !\/Playfair Display\/i\.test\(rendered\.h1Font\)\) add\(findings, 'warning', 'heading-font'/, 'script must warn when the heading font is not Playfair Display');
requireMatch(script, /if \(rendered\.smallControls\.length\) add\(findings, 'warning', 'small-controls'/, 'script must warn about touch targets smaller than 44px');

requireMatch(script, /size\(rendered\.joinchatButton, 48, 'joinchat-frame-size'\)/, 'script must validate the Joinchat button size (48px)');
requireMatch(script, /size\(rendered\.joinchatIcon, 24, 'joinchat-icon-size'\)/, 'script must validate the Joinchat icon size (24px)');
requireMatch(script, /size\(rendered\.inlineWhatsapp, 16, 'inline-whatsapp-size'\)/, 'script must validate the inline WhatsApp icon size (16px)');

requireMatch(script, /add\(findings, 'critical', 'invalid-jsonld'/, 'script must flag malformed JSON-LD as critical');
requireMatch(
  script,
  /\['home', 'contacto', 'clinicas'\]\.includes\(slug\) && !rendered\.schemaTypes\.includes\('MedicalClinic'\)/,
  'script must require MedicalClinic schema on clinic-facing pages',
);
requireMatch(
  script,
  /\['endolift', 'laser', 'medicina-estetica'\]\.includes\(slug\) && !rendered\.schemaTypes\.includes\('MedicalProcedure'\)/,
  'script must require MedicalProcedure schema on treatment pages',
);

requireMatch(script, /await page\.screenshot\(\{ path: path\.join\(out, `\$\{slug\}-\$\{viewport\}\.png`\)/, 'script must save a full-page screenshot per slug/viewport');
requireMatch(script, /fs\.writeFileSync\(path\.join\(out, 'report\.json'\)/, 'script must write a JSON report artifact');
requireMatch(script, /fs\.writeFileSync\(path\.join\(out, 'report\.md'\)/, 'script must write a Markdown report artifact');
requireMatch(script, /result: critical\.length \? 'FAIL' : 'PASS_WITH_WARNINGS'/, 'report summary must reflect critical-finding count');
requireMatch(script, /if \(critical\.length\) process\.exit\(1\);/, 'script must exit non-zero when critical findings exist');

// ---------------------------------------------------------------------------
// .github/workflows/staging2-rendered-qa.yml contract.
// ---------------------------------------------------------------------------

requireMatch(workflow, /^\s{2}workflow_dispatch:/m, 'workflow must be manually triggerable');
requireAbsent(workflow, /^\s{2}push:/m, 'workflow must not run on push');
requireAbsent(workflow, /^\s{2}pull_request:/m, 'workflow must not run on pull_request');
requireAbsent(workflow, /^\s{2}schedule:/m, 'workflow must not run on a schedule');

requireMatch(workflow, /permissions:\s*\n\s*contents: read/, 'workflow must use read-only contents permission');
requireMatch(workflow, /cancel-in-progress: true/, 'concurrent QA runs for the same ref must be cancelled');
requireMatch(workflow, /timeout-minutes: 30/, 'job must have a bounded timeout');

requireMatch(workflow, /BASE_URL: \$\{\{ inputs\.base_url \|\| 'https:\/\/staging2\.nuvanx\.com' \}\}/, 'BASE_URL must default to the public staging2 URL');
requireMatch(workflow, /EXPECTED_CANONICAL_HOST: nuvanx\.com/, 'workflow must pin the expected canonical host');
requireMatch(workflow, /QA_OUTPUT_DIR: qa-artifacts\/staging2-rendered/, 'workflow must pin the QA output directory');

requireMatch(workflow, /uses: actions\/checkout@[0-9a-f]{40}/, 'checkout action must be pinned to a commit SHA');
requireMatch(workflow, /persist-credentials: false/, 'checkout must not persist credentials for this read-only job');
requireMatch(workflow, /uses: actions\/setup-node@[0-9a-f]{40}/, 'setup-node action must be pinned to a commit SHA');
requireMatch(workflow, /node-version: '22'/, 'workflow must pin the Node.js version');
requireMatch(workflow, /uses: actions\/upload-artifact@[0-9a-f]{40}/, 'upload-artifact action must be pinned to a commit SHA');

requireMatch(workflow, /node --check scripts\/staging2\/rendered-qa\.mjs/, 'workflow must syntax-check the audit script before running it');
requireMatch(
  workflow,
  /JSON\.parse\(require\('fs'\)\.readFileSync\('scripts\/staging2\/rendered-qa-routes\.json','utf8'\)\)/,
  'workflow must validate the routes JSON before running the audit',
);

requireMatch(workflow, /npm install playwright@1\.54\.1 --no-save/, 'workflow must install a pinned Playwright version');
requireMatch(workflow, /npx playwright install --with-deps chromium/, 'workflow must install the Chromium browser binary');

requireMatch(workflow, /id: audit/, 'audit step must expose an id for downstream steps');
requireMatch(workflow, /set \+e/, 'audit step must not fail the shell immediately on a non-zero exit');
requireMatch(workflow, /node scripts\/staging2\/rendered-qa\.mjs/, 'workflow must run the rendered QA script');
requireMatch(workflow, /echo "exit_code=\$exit_code" >> "\$GITHUB_OUTPUT"/, 'audit step must capture its exit code as a step output');

requireMatch(
  workflow,
  /cat qa-artifacts\/staging2-rendered\/report\.md >> "\$GITHUB_STEP_SUMMARY"/,
  'workflow must publish the Markdown report to the job summary',
);
requireMatch(
  workflow,
  /name: staging2-rendered-qa-\$\{\{ github\.run_id \}\}[\s\S]*?path: qa-artifacts\/staging2-rendered[\s\S]*?if-no-files-found: error[\s\S]*?retention-days: 14/,
  'artifact upload must be named per run, require files, and retain for 14 days',
);

requireMatch(
  workflow,
  /AUDIT_EXIT_CODE: \$\{\{ steps\.audit\.outputs\.exit_code \}\}[\s\S]*?test "\$AUDIT_EXIT_CODE" = "0" \|\| \{[\s\S]*?exit 1/,
  'workflow must fail the job when the audit script reports critical findings',
);

// Steps that must always run regardless of earlier failures.
for (const stepName of ['Publish QA report in job summary', 'Upload screenshots and reports', 'Enforce critical findings']) {
  const stepBlock = workflow.match(new RegExp(`- name: ${stepName}[\\s\\S]*?(?=\\n {6}- name:|$)`));
  requireMatch(stepBlock ? stepBlock[0] : '', /if: always\(\)/, `"${stepName}" step must run with if: always()`);
}

if (failures.length) {
  console.error('Staging2 rendered QA contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: staging2 rendered QA script, routes config, and workflow contract');