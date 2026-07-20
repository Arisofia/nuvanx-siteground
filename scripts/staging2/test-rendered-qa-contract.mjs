#!/usr/bin/env node
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

function assertTrue(condition, message) {
  if (!condition) failures.push(message);
}

function assertEqual(actual, expected, message) {
  if (actual !== expected) failures.push(`${message} (expected ${JSON.stringify(expected)}, got ${JSON.stringify(actual)})`);
}

const routesPath = 'scripts/staging2/rendered-qa-routes.json';
const scriptPath = 'scripts/staging2/rendered-qa.mjs';
const workflowPath = '.github/workflows/staging2-rendered-qa.yml';

for (const requiredPath of [routesPath, scriptPath, workflowPath]) {
  if (!fs.existsSync(path.join(root, requiredPath))) {
    failures.push(`missing required rendered QA contract file: ${requiredPath}`);
  }
}

if (failures.length) {
  console.error('Rendered QA contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

const routesRaw = read(routesPath);
const script = read(scriptPath);
const workflow = read(workflowPath);

// ---- rendered-qa-routes.json: structural contract ----
let routesConfig;
try {
  routesConfig = JSON.parse(routesRaw);
} catch (error) {
  failures.push(`rendered-qa-routes.json is not valid JSON: ${error.message}`);
}

if (routesConfig) {
  assertTrue(typeof routesConfig.baseUrl === 'string' && /^https:\/\//.test(routesConfig.baseUrl), 'baseUrl must be an https URL');
  // Regression guard: the audit must never default to the production origin.
  assertEqual(routesConfig.baseUrl, 'https://staging2.nuvanx.com', 'baseUrl must default to the staging2 origin, not production');
  assertTrue(Array.isArray(routesConfig.routes) && routesConfig.routes.length > 0, 'routes must be a non-empty array');

  const slugs = [];
  const routePaths = [];
  for (const entry of routesConfig.routes ?? []) {
    assertTrue(Array.isArray(entry) && entry.length === 2, `each route entry must be a [slug, path] tuple, got ${JSON.stringify(entry)}`);
    const [slug, routePath] = entry;
    assertTrue(typeof slug === 'string' && /^[a-z0-9-]+$/.test(slug), `route slug must be kebab-case, got ${JSON.stringify(slug)}`);
    assertTrue(
      typeof routePath === 'string' && routePath.startsWith('/') && routePath.endsWith('/'),
      `route path must start and end with '/', got ${JSON.stringify(routePath)}`,
    );
    slugs.push(slug);
    routePaths.push(routePath);
  }

  const duplicateSlugs = slugs.filter((slug, index) => slugs.indexOf(slug) !== index);
  assertTrue(duplicateSlugs.length === 0, `route slugs must be unique, duplicates: ${duplicateSlugs.join(', ')}`);
  const duplicatePaths = routePaths.filter((routePath, index) => routePaths.indexOf(routePath) !== index);
  assertTrue(duplicatePaths.length === 0, `route paths must be unique, duplicates: ${duplicatePaths.join(', ')}`);

  // The audit script only checks for MedicalClinic/MedicalProcedure schema on these
  // slugs; if they are ever removed from the route list the checks silently stop running.
  for (const requiredSlug of ['home', 'contacto', 'clinicas', 'endolift', 'laser', 'medicina-estetica']) {
    assertTrue(slugs.includes(requiredSlug), `routes must include the "${requiredSlug}" slug expected by rendered-qa.mjs schema checks`);
  }
}

// ---- .github/workflows/staging2-rendered-qa.yml: trigger and permission contract ----
requireMatch(workflow, /^\s{2}workflow_dispatch:/m, 'workflow must be manually triggered via workflow_dispatch');
requireAbsent(workflow, /^\s{2}push:/m, 'workflow must not run automatically on push');
requireAbsent(workflow, /^\s{2}pull_request:/m, 'workflow must not run automatically on pull_request');
requireAbsent(workflow, /^\s{2}schedule:/m, 'workflow must not run on a schedule');
requireMatch(workflow, /base_url:[\s\S]*?default: 'https:\/\/staging2\.nuvanx\.com'/, 'base_url input must default to the staging2 URL');
requireMatch(workflow, /expected_sha:[\s\S]*?required: true/, 'expected_sha input must be required');
requireMatch(workflow, /default: '[0-9a-f]{40}'/, 'expected_sha input default must be a full 40-character commit SHA');
requireMatch(workflow, /permissions:\s*\n\s+contents: read/, 'workflow must use least-privilege read-only permissions');
requireAbsent(workflow, /contents: write/, 'workflow must never request write access to repository contents');
requireMatch(
  workflow,
  /concurrency:\s*\n\s+group: staging2-rendered-qa-\$\{\{ github\.ref \}\}\s*\n\s+cancel-in-progress: true/,
  'workflow must cancel superseded QA runs for the same ref',
);
requireMatch(workflow, /timeout-minutes:\s*30/, 'job must have a timeout to bound a hung browser run');
requireMatch(workflow, /node-version:\s*'22'/, 'workflow must pin Node.js 22');

requireMatch(workflow, /uses: actions\/checkout@[0-9a-f]{40}/, 'checkout action must be pinned to a commit SHA');
requireMatch(workflow, /uses: actions\/setup-node@[0-9a-f]{40}/, 'setup-node action must be pinned to a commit SHA');
requireMatch(workflow, /uses: actions\/upload-artifact@[0-9a-f]{40}/, 'upload-artifact action must be pinned to a commit SHA');

// ---- steps: syntax validation, browser install, execution ----
requireMatch(workflow, /node --check scripts\/staging2\/rendered-qa\.mjs/, 'workflow must syntax-check the audit script before running it');
requireMatch(
  workflow,
  /JSON\.parse\(require\('fs'\)\.readFileSync\('scripts\/staging2\/rendered-qa-routes\.json','utf8'\)\)/,
  'workflow must validate the routes JSON before running it',
);
requireMatch(workflow, /npm install playwright@1\.54\.1 --no-save/, 'workflow must install Playwright without persisting it to package.json');
requireMatch(workflow, /npx playwright install --with-deps chromium/, 'workflow must install the Chromium browser binary');
requireMatch(workflow, /id: audit/, 'audit step must expose an id so downstream steps can read its outputs');
requireMatch(workflow, /set \+e/, 'audit step must disable errexit so later reporting steps still run on failure');
requireMatch(workflow, /echo "exit_code=\$exit_code" >> "\$GITHUB_OUTPUT"/, 'audit step must capture its exit code as a step output');

// ---- reporting must always run, even after a failed audit ----
requireMatch(workflow, /run: cat qa-artifacts\/staging2-rendered\/report\.md >> "\$GITHUB_STEP_SUMMARY"/, 'workflow must publish the QA report to the job summary');
requireMatch(workflow, /path: qa-artifacts\/staging2-rendered/, 'workflow must upload the QA output directory as an artifact');
requireMatch(workflow, /if-no-files-found: error/, 'artifact upload must fail loudly if the QA output is missing');
requireMatch(workflow, /retention-days: 14/, 'workflow must retain QA artifacts for 14 days');
requireMatch(workflow, /AUDIT_EXIT_CODE: \$\{\{ steps\.audit\.outputs\.exit_code \}\}/, 'enforcement step must read the audit exit code from step outputs');
requireMatch(workflow, /test "\$AUDIT_EXIT_CODE" = "0" \|\|/, 'workflow must fail the job when the audit reports a non-zero exit code');

const alwaysSteps = [...workflow.matchAll(/- name: ([^\n]+)\n\s+if: always\(\)/g)].map((match) => match[1].trim());
for (const name of ['Publish QA report in job summary', 'Upload screenshots and reports', 'Enforce critical findings']) {
  assertTrue(alwaysSteps.includes(name), `step "${name}" must run with if: always() so it still executes after a failed audit`);
}
// Negative case: the audit step itself must NOT be an always()-guarded step, otherwise
// a prior failure could re-run the browser instead of moving on to reporting.
assertTrue(!alwaysSteps.includes('Run rendered QA'), 'the audit step must run exactly once, not be repeated via if: always()');

// ---- scripts/staging2/rendered-qa.mjs: configuration contract ----
requireMatch(script, /import \{ chromium \} from 'playwright';/, 'script must use Playwright chromium');
requireMatch(script, /process\.env\.BASE_URL \|\| config\.baseUrl/, 'script must allow BASE_URL to override the routes config default');
requireMatch(script, /process\.env\.QA_OUTPUT_DIR \|\| 'qa-artifacts\/staging2-rendered'/, 'script default output dir must match the workflow QA_OUTPUT_DIR default');
requireMatch(script, /process\.env\.EXPECTED_DEPLOY_SHA \|\| ''/, 'script must read EXPECTED_DEPLOY_SHA from the environment');
requireMatch(script, /process\.env\.EXPECTED_CANONICAL_HOST \|\| 'nuvanx\.com'/, 'script must default the canonical host to nuvanx.com');
requireMatch(script, /\[\['desktop', 1440, 1100\], \['mobile', 390, 844\]\]/, 'script must audit both a desktop and a mobile viewport');
requireMatch(
  script,
  /ReferenceError\|TypeError\|SyntaxError\|Uncaught\|FacebookSignal\|is not defined/,
  'critical console-error detector must cover the documented JS error classes',
);
requireAbsent(script, /https:\/\/nuvanx\.com/, 'script must not hardcode the production origin');
requireAbsent(script, /ignoreHTTPSErrors/, 'script must not disable TLS certificate validation');

// ---- browser lifecycle and resource cleanup ----
requireMatch(script, /headless: true, args: \['--no-sandbox', '--disable-setuid-sandbox'\]/, 'browser must launch headless with CI-safe sandbox flags');
requireMatch(script, /finally \{ await browser\.close\(\); \}/, 'the browser must be closed even if an audit run throws');
requireMatch(script, /await context\.close\(\);/, 'each route context must be closed to avoid leaking resources across routes');
requireMatch(
  script,
  /locale: 'es-ES', reducedMotion: 'reduce', colorScheme: 'light'/,
  'browser context must use a deterministic locale, reduced motion and colour scheme',
);

// ---- finding codes: every documented check must still emit its code ----
for (const code of [
  'siteground-captcha', 'missing-title', 'missing-description', 'h1-count', 'staging-indexable',
  'missing-canonical', 'canonical-host', 'invalid-canonical', 'horizontal-overflow', 'duplicate-ids',
  'missing-alt', 'body-font', 'heading-font', 'small-controls', 'joinchat-frame-size',
  'joinchat-icon-size', 'inline-whatsapp-size', 'invalid-jsonld', 'schema-medical-clinic',
  'schema-medical-procedure', 'request-failed', 'console-error', 'page-error', 'navigation-failed', 'http-status',
]) {
  requireMatch(script, new RegExp(`'${code}'`), `script must emit the "${code}" finding code`);
}

requireMatch(
  script,
  /\(google\|facebook\|doubleclick\|hubspot\|hs-scripts\|clarity\)/,
  'request-failed detector must ignore known third-party tracking domains',
);

// ---- report artifacts and exit behaviour ----
requireMatch(script, /if \(critical\.length\) process\.exit\(1\);/, 'script must exit non-zero when critical findings exist');
requireMatch(script, /critical\.length \? 'FAIL' : 'PASS_WITH_WARNINGS'/, 'report summary must reflect FAIL vs PASS_WITH_WARNINGS based on critical findings');
requireMatch(script, /fs\.writeFileSync\(path\.join\(out, 'report\.json'\)/, 'script must persist a machine-readable report.json');
requireMatch(script, /fs\.writeFileSync\(path\.join\(out, 'report\.md'\)/, 'script must persist a human-readable report.md');
requireMatch(script, /fullPage: true, animations: 'disabled'/, 'screenshots must capture the full page with animations disabled for stability');
requireMatch(script, /path\.join\(out, `\$\{slug\}-\$\{viewport\}\.png`\)/, 'screenshot file names must encode both the route slug and the viewport');

// ---- cross-file contracts: workflow env must agree with script defaults ----
requireMatch(workflow, /QA_OUTPUT_DIR: qa-artifacts\/staging2-rendered/, 'workflow QA_OUTPUT_DIR must match the script default output directory');
requireMatch(workflow, /EXPECTED_CANONICAL_HOST: nuvanx\.com/, 'workflow EXPECTED_CANONICAL_HOST must match the script default canonical host');

if (failures.length) {
  console.error('Rendered QA contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: staging2 rendered QA workflow, routes config and audit script contract');