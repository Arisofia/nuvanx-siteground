#!/usr/bin/env node
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');

const routesPath = 'scripts/staging2/rendered-qa-routes.json';
const scriptPath = 'scripts/staging2/rendered-qa.mjs';
const workflowPath = '.github/workflows/staging2-rendered-qa.yml';

for (const requiredPath of [routesPath, scriptPath, workflowPath]) {
  assert.ok(fs.existsSync(path.join(root, requiredPath)), `missing required rendered QA contract file: ${requiredPath}`);
}

// ---------------------------------------------------------------------------
// rendered-qa-routes.json data contract.
//
// This exercises the real parsed data (not just text matching) because the
// file is plain JSON config consumed directly by rendered-qa.mjs.
// ---------------------------------------------------------------------------
const routesRaw = read(routesPath);
let config;
assert.doesNotThrow(() => { config = JSON.parse(routesRaw); }, 'rendered-qa-routes.json must be valid JSON');

assert.equal(typeof config.baseUrl, 'string', 'config.baseUrl must be a string');
assert.match(config.baseUrl, /^https:\/\//, 'config.baseUrl must use https');
assert.equal(config.baseUrl, config.baseUrl.replace(/\/$/, ''), 'config.baseUrl must not have a trailing slash in the source config');
assert.ok(new URL(config.baseUrl).hostname.endsWith('nuvanx.com'), 'config.baseUrl must target a nuvanx.com host');

assert.ok(Array.isArray(config.routes), 'config.routes must be an array');
assert.ok(config.routes.length > 0, 'config.routes must not be empty');

const expectedSlugs = [
  'home', 'contacto', 'clinicas', 'tratamientos', 'endolift', 'laser',
  'medicina-estetica', 'equipo', 'blog', 'por-que-nuvanx', 'inversion',
  'liposculpt-review', 'v-lift-review',
];
assert.equal(
  config.routes.length,
  expectedSlugs.length,
  `expected ${expectedSlugs.length} audited routes, found ${config.routes.length}`,
);

const seenSlugs = new Set();
const seenPaths = new Set();
for (const entry of config.routes) {
  assert.ok(Array.isArray(entry), `each route entry must be an array, got ${JSON.stringify(entry)}`);
  assert.equal(entry.length, 2, `each route entry must be a [slug, path] tuple, got ${JSON.stringify(entry)}`);
  const [slug, route] = entry;
  assert.equal(typeof slug, 'string', `route slug must be a string, got ${JSON.stringify(entry)}`);
  assert.match(slug, /^[a-z0-9-]+$/, `route slug "${slug}" must be lowercase kebab-case`);
  assert.equal(typeof route, 'string', `route path for "${slug}" must be a string`);
  assert.match(route, /^\//, `route path for "${slug}" must start with "/"`);
  if (route !== '/') assert.match(route, /\/$/, `route path for "${slug}" (${route}) must end with a trailing slash`);
  assert.ok(!seenSlugs.has(slug), `duplicate route slug: ${slug}`);
  assert.ok(!seenPaths.has(route), `duplicate route path: ${route}`);
  seenSlugs.add(slug);
  seenPaths.add(route);
}

for (const slug of expectedSlugs) {
  assert.ok(seenSlugs.has(slug), `expected route slug missing from rendered-qa-routes.json: ${slug}`);
}

const homeEntry = config.routes.find(([slug]) => slug === 'home');
assert.deepEqual(homeEntry, ['home', '/'], 'the home route must map to "/"');

// The schema checks hard-coded in rendered-qa.mjs reference these slugs by
// name, so they must always exist in the route list or the checks are dead code.
for (const slug of ['home', 'contacto', 'clinicas', 'endolift', 'laser', 'medicina-estetica']) {
  assert.ok(seenSlugs.has(slug), `rendered-qa.mjs expects a schema check for "${slug}" but it is missing from routes`);
}

// ---------------------------------------------------------------------------
// rendered-qa.mjs behavioral contract.
//
// The script self-executes a real Playwright browser session at module load
// time (it has no guarded entry point and no exported functions), so it
// cannot be imported directly in a fast unit test. Its behavior is instead
// pinned via a text contract, following the pattern already used for
// scripts/staging2/test-deploy-workflow-contract.mjs in this repository.
// ---------------------------------------------------------------------------
const script = read(scriptPath);
const failures = [];
function requireMatch(source, pattern, message) { if (!pattern.test(source)) failures.push(message); }
function requireAbsent(source, pattern, message) { if (pattern.test(source)) failures.push(message); }

requireMatch(script, /import \{ chromium \} from 'playwright'/, 'script must use the playwright chromium driver');
requireMatch(script, /rendered-qa-routes\.json/, 'script must read routes from rendered-qa-routes.json');
requireMatch(script, /process\.env\.BASE_URL/, 'script must allow overriding the base URL via BASE_URL');
requireMatch(script, /process\.env\.QA_OUTPUT_DIR/, 'script must allow overriding the output directory via QA_OUTPUT_DIR');
requireMatch(script, /process\.env\.EXPECTED_DEPLOY_SHA/, 'script must record the expected deploy SHA');
requireMatch(script, /process\.env\.EXPECTED_CANONICAL_HOST/, 'script must allow overriding the canonical host');

requireMatch(script, /\['desktop', 1440, 1100\]/, 'script must audit a desktop viewport at 1440x1100');
requireMatch(script, /\['mobile', 390, 844\]/, 'script must audit a mobile viewport at 390x844');

requireMatch(
  script,
  /ReferenceError\|TypeError\|SyntaxError\|Uncaught\|FacebookSignal\|is not defined/,
  'criticalJs regex must flag common runtime JS errors',
);

requireMatch(script, /'critical', 'missing-title'/, 'script must flag an empty title as critical');
requireMatch(script, /'critical', 'h1-count'/, 'script must flag a wrong number of H1 headings as critical');
requireMatch(script, /Expected 1 H1/, 'script must require exactly one H1 per page');
requireMatch(script, /'critical', 'staging-indexable'/, 'script must flag a missing noindex directive on staging as critical');
requireMatch(script, /'critical', 'missing-canonical'/, 'script must flag a missing canonical link as critical');
requireMatch(script, /'critical', 'horizontal-overflow'/, 'script must flag horizontal overflow as critical');
requireMatch(script, /'critical', 'duplicate-ids'/, 'script must flag duplicate DOM ids as critical');
requireMatch(script, /'critical', 'siteground-captcha'/, 'script must flag a rendered bot challenge as critical');
requireMatch(script, /'critical', 'invalid-jsonld'/, 'script must flag invalid JSON-LD as critical');
requireMatch(script, /'critical', 'navigation-failed'/, 'script must flag navigation errors as critical');
requireMatch(script, /'critical', 'http-status'/, 'script must flag non-2xx\/3xx HTTP responses as critical');

requireMatch(script, /'warning', 'missing-description'/, 'script must flag an empty meta description as a warning');
requireMatch(script, /'warning', 'missing-alt'/, 'script must flag images missing alt text as a warning');
requireMatch(script, /'warning', 'body-font'/, 'script must flag an unexpected body font as a warning');
requireMatch(script, /Manrope/, 'script must check the body font against Manrope');
requireMatch(script, /'warning', 'heading-font'/, 'script must flag an unexpected heading font as a warning');
requireMatch(script, /Playfair Display/, 'script must check the heading font against Playfair Display');
requireMatch(script, /'warning', 'small-controls'/, 'script must flag undersized tap targets as a warning');
requireMatch(script, /box\.width < 44 \|\| box\.height < 44/, 'script must enforce a 44px minimum touch target');

requireMatch(script, /joinchat-frame-size/, 'script must check the joinchat button size');
requireMatch(script, /joinchat-icon-size/, 'script must check the joinchat icon size');
requireMatch(script, /inline-whatsapp-size/, 'script must check the inline WhatsApp icon size');
requireMatch(
  script,
  /size\(rendered\.joinchatButton, 48, 'joinchat-frame-size'\)/,
  'joinchat button must be checked against a 48px target',
);
requireMatch(
  script,
  /size\(rendered\.joinchatIcon, 24, 'joinchat-icon-size'\)/,
  'joinchat icon must be checked against a 24px target',
);
requireMatch(
  script,
  /size\(rendered\.inlineWhatsapp, 16, 'inline-whatsapp-size'\)/,
  'inline WhatsApp icon must be checked against a 16px target',
);

requireMatch(
  script,
  /\['home', 'contacto', 'clinicas'\]\.includes\(slug\)[\s\S]{0,100}MedicalClinic/,
  'home\/contacto\/clinicas routes must require MedicalClinic schema',
);
requireMatch(
  script,
  /\['endolift', 'laser', 'medicina-estetica'\]\.includes\(slug\)[\s\S]{0,100}MedicalProcedure/,
  'endolift\/laser\/medicina-estetica routes must require MedicalProcedure schema',
);

requireMatch(
  script,
  /page\.screenshot\(\{ path: path\.join\(out, `\$\{slug\}-\$\{viewport\}\.png`\), fullPage: true/,
  'script must capture a full-page screenshot named "<slug>-<viewport>.png"',
);

requireMatch(
  script,
  /google\|facebook\|doubleclick\|hubspot\|hs-scripts\|clarity/,
  'script must exclude known third-party domains from request-failed warnings',
);

requireMatch(script, /'--no-sandbox', '--disable-setuid-sandbox'/, 'chromium must launch with CI-safe sandbox flags');
requireMatch(script, /locale: 'es-ES'/, 'browser context must use the es-ES locale');
requireMatch(script, /reducedMotion: 'reduce'/, 'browser context must reduce motion for stable screenshots');

requireMatch(script, /fs\.writeFileSync\(path\.join\(out, 'report\.json'\)/, 'script must write report.json');
requireMatch(script, /fs\.writeFileSync\(path\.join\(out, 'report\.md'\)/, 'script must write report.md');
requireMatch(
  script,
  /result: critical\.length \? 'FAIL' : 'PASS_WITH_WARNINGS'/,
  'report summary must distinguish FAIL from PASS_WITH_WARNINGS',
);
requireMatch(script, /if \(critical\.length\) process\.exit\(1\);/, 'script must exit non-zero when critical findings exist');
requireAbsent(script, /process\.exit\(0\)/, 'script must not force a zero exit code that would mask critical findings');

// ---------------------------------------------------------------------------
// .github/workflows/staging2-rendered-qa.yml contract.
// ---------------------------------------------------------------------------
const workflow = read(workflowPath);

requireMatch(workflow, /^\s{2}workflow_dispatch:/m, 'workflow must be manually triggered via workflow_dispatch');
requireAbsent(workflow, /^\s{2}push:/m, 'workflow must not trigger on push');
requireAbsent(workflow, /^\s{2}pull_request:/m, 'workflow must not trigger on pull_request');
requireAbsent(workflow, /^\s{2}schedule:/m, 'workflow must not trigger on a schedule');

requireMatch(workflow, /base_url:[\s\S]{0,120}required: true/, 'base_url input must be required');
requireMatch(workflow, /default: 'https:\/\/staging2\.nuvanx\.com'/, 'base_url input must default to the staging2 URL');
requireMatch(workflow, /expected_sha:[\s\S]{0,120}required: true/, 'expected_sha input must be required');
requireMatch(
  workflow,
  /default: '53847cf51edc1c68df1bac0683ad331e22b9c602'/,
  'expected_sha input must default to the known deployed SHA',
);

requireMatch(workflow, /permissions:\s*\n\s+contents: read/, 'workflow must use least-privilege read-only permissions');
requireMatch(
  workflow,
  /concurrency:\s*\n\s+group: staging2-rendered-qa-\$\{\{ github\.ref \}\}/,
  'workflow must scope concurrency per ref',
);
requireMatch(workflow, /cancel-in-progress:\s*true/, 'stale rendered QA runs on the same ref must be cancelled');
requireMatch(workflow, /timeout-minutes:\s*30/, 'job must have a bounded timeout');

requireMatch(workflow, /EXPECTED_CANONICAL_HOST:\s*nuvanx\.com/, 'workflow must pin the expected canonical host');
requireMatch(workflow, /QA_OUTPUT_DIR:\s*qa-artifacts\/staging2-rendered/, 'workflow must pin the QA output directory');

requireMatch(workflow, /uses: actions\/checkout@[0-9a-f]{40}/, 'checkout action must be pinned to a commit SHA');
requireMatch(workflow, /persist-credentials:\s*false/, 'checkout must not persist credentials');
requireMatch(workflow, /uses: actions\/setup-node@[0-9a-f]{40}/, 'setup-node action must be pinned to a commit SHA');
requireMatch(workflow, /node-version:\s*'22'/, 'workflow must use Node.js 22');

requireMatch(workflow, /node --check scripts\/staging2\/rendered-qa\.mjs/, 'workflow must syntax-check the audit script before running it');
requireMatch(
  workflow,
  /JSON\.parse\(require\('fs'\)\.readFileSync\('scripts\/staging2\/rendered-qa-routes\.json','utf8'\)\)/,
  'workflow must validate the routes JSON before running the audit',
);

requireMatch(
  workflow,
  /npm install playwright@1\.54\.1 --no-save/,
  'workflow must install a pinned Playwright version without persisting it to package.json',
);
requireMatch(workflow, /npx playwright install --with-deps chromium/, 'workflow must install Chromium and its OS dependencies');

requireMatch(workflow, /id:\s*audit/, 'the rendered QA step must expose an id for downstream steps');
requireMatch(workflow, /set \+e/, 'the rendered QA step must not abort the job on a non-zero audit exit code');
requireMatch(
  workflow,
  /echo "exit_code=\$exit_code" >> "\$GITHUB_OUTPUT"/,
  'the rendered QA step must capture its exit code as a step output',
);
requireMatch(workflow, /\n\s+exit 0\n/, 'the rendered QA step itself must always exit 0 so later steps still run');

requireMatch(
  workflow,
  /cat qa-artifacts\/staging2-rendered\/report\.md >> "\$GITHUB_STEP_SUMMARY"/,
  'workflow must publish the markdown report to the job summary',
);

requireMatch(workflow, /uses: actions\/upload-artifact@[0-9a-f]{40}/, 'upload-artifact action must be pinned to a commit SHA');
requireMatch(workflow, /if-no-files-found:\s*error/, 'missing QA artifacts must fail the job loudly');
requireMatch(workflow, /retention-days:\s*14/, 'workflow must set an explicit artifact retention period');

requireMatch(
  workflow,
  /AUDIT_EXIT_CODE:\s*\$\{\{ steps\.audit\.outputs\.exit_code \}\}/,
  'the enforcement step must read the captured audit exit code',
);
requireMatch(
  workflow,
  /test "\$AUDIT_EXIT_CODE" = "0" \|\| \{/,
  'the enforcement step must fail the job when the audit exit code is not 0',
);

// The summary, upload, and enforcement steps must all run even if an earlier
// step fails, otherwise a crashed audit could silently pass without evidence.
const alwaysSteps = workflow.match(/if:\s*always\(\)/g) || [];
assert.ok(
  alwaysSteps.length >= 3,
  `summary, upload, and enforcement steps must each run unconditionally with if: always() (found ${alwaysSteps.length})`,
);

if (failures.length) {
  console.error('Staging2 rendered QA contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: staging2 rendered QA contract (routes, script, and workflow)');