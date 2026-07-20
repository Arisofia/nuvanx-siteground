#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { execFileSync } from 'node:child_process';
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
function requireText(source, text, message) {
  if (!source.includes(text)) failures.push(message);
}

const workflowPath = '.github/workflows/staging2-rendered-qa.yml';
const scriptPath = 'scripts/staging2/rendered-qa.mjs';
const routesPath = 'scripts/staging2/rendered-qa-routes.json';

for (const requiredPath of [workflowPath, scriptPath, routesPath]) {
  if (!fs.existsSync(path.join(root, requiredPath))) {
    failures.push(`missing required rendered QA contract file: ${requiredPath}`);
  }
}

if (failures.length) {
  console.error('Staging2 rendered QA contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

const workflow = read(workflowPath);
const script = read(scriptPath);
const routesRaw = read(routesPath);

// --- Syntax / JSON validity, mirroring the workflow's "Validate audit source" step ---
try {
  execFileSync(process.execPath, ['--check', path.join(root, scriptPath)], { stdio: 'pipe' });
} catch (error) {
  failures.push(`rendered-qa.mjs failed node --check: ${error.message}`);
}

let routes;
try {
  routes = JSON.parse(routesRaw);
} catch (error) {
  failures.push(`rendered-qa-routes.json is not valid JSON: ${error.message}`);
}

// --- Routes config contract ---
if (routes) {
  if (typeof routes.baseUrl !== 'string' || !/^https:\/\/staging2\.nuvanx\.com$/.test(routes.baseUrl)) {
    failures.push('routes config must pin baseUrl to https://staging2.nuvanx.com');
  }
  if (!Array.isArray(routes.routes) || routes.routes.length === 0) {
    failures.push('routes config must declare a non-empty routes array');
  } else {
    const slugs = new Set();
    const paths = new Set();
    for (const entry of routes.routes) {
      if (!Array.isArray(entry) || entry.length !== 2) {
        failures.push(`route entry must be a [slug, path] tuple: ${JSON.stringify(entry)}`);
        continue;
      }
      const [slug, route] = entry;
      if (typeof slug !== 'string' || !/^[a-z0-9-]+$/.test(slug)) {
        failures.push(`route slug must be a lowercase kebab-case string: ${JSON.stringify(slug)}`);
      }
      if (slugs.has(slug)) failures.push(`duplicate route slug: ${slug}`);
      slugs.add(slug);
      if (typeof route !== 'string' || !route.startsWith('/')) {
        failures.push(`route path must be absolute: ${JSON.stringify(route)}`);
      } else {
        if (route !== '/' && !route.endsWith('/')) {
          failures.push(`route path must end with a trailing slash: ${route}`);
        }
        if (paths.has(route)) failures.push(`duplicate route path: ${route}`);
        paths.add(route);
      }
    }

    const homeRoute = routes.routes.find(([slug]) => slug === 'home');
    if (!homeRoute || homeRoute[1] !== '/') failures.push('home route must map to the root path "/"');

    // rendered-qa.mjs branches its schema assertions on these exact slugs, so the
    // routes config must keep them present or the audit silently stops checking schema.
    for (const requiredSlug of ['home', 'contacto', 'clinicas', 'endolift', 'laser', 'medicina-estetica']) {
      if (!slugs.has(requiredSlug)) failures.push(`routes config must keep required slug: ${requiredSlug}`);
    }

    // The baseUrl declared in the routes config and the default base_url exposed by the
    // workflow's manual dispatch input must stay in sync, otherwise CI defaults silently
    // diverge from the checked-in fixture used for local/PR runs.
    const workflowDefaultBaseUrl = workflow.match(/default:\s*'(https:\/\/[^']+)'/)?.[1];
    if (workflowDefaultBaseUrl && workflowDefaultBaseUrl !== routes.baseUrl) {
      failures.push(`workflow default base_url (${workflowDefaultBaseUrl}) must match routes.baseUrl (${routes.baseUrl})`);
    }
  }
}

// --- rendered-qa.mjs behavioral contract ---
requireText(script, "import { chromium } from 'playwright';", 'script must import chromium from the playwright package');
requireText(script, "readFileSync(new URL('./rendered-qa-routes.json', import.meta.url), 'utf8')", 'script must load routes relative to its own module URL');
requireText(script, 'process.env.BASE_URL || config.baseUrl', 'script must allow BASE_URL to override the configured baseUrl');
requireText(script, ".replace(/\\/$/, '')", 'script must normalize a trailing slash off the base URL');
requireText(script, "const viewports = [['desktop', 1440, 1100], ['mobile', 390, 844]];", 'script must audit both a desktop (1440x1100) and a mobile (390x844) viewport');
requireText(script, '(ReferenceError|TypeError|SyntaxError|Uncaught|FacebookSignal|is not defined)', 'script must flag critical JS error signatures in console output');
requireText(script, "args: ['--no-sandbox', '--disable-setuid-sandbox']", 'browser launch must use CI-safe sandbox flags');
requireText(script, 'await browser.close();', 'script must close the browser after auditing all routes');

for (const [snippet, message] of [
  ["add(findings, 'critical', 'page-error', error.message)", "script must report uncaught page errors as 'page-error' findings"],
  ["!/(google|facebook|doubleclick|hubspot|hs-scripts|clarity)\\./i.test(url)", 'script must ignore known third-party request failures'],
  ["request.resourceType() !== 'media'", 'script must ignore failed media requests'],
  ["add(findings, 'critical', 'navigation-failed', error.message)", "script must report navigation failures as 'navigation-failed' findings"],
  ["'critical', 'http-status'", "script must report bad HTTP statuses as 'http-status' findings"],
  ["if (rendered.captcha) add(findings, 'critical', 'siteground-captcha'", 'script must fail when a bot-challenge page is rendered'],
  ["if (!rendered.title) add(findings, 'critical', 'missing-title'", 'script must require a non-empty title'],
  ["if (!rendered.description) add(findings, 'warning', 'missing-description'", 'script must warn on an empty meta description'],
  ["if (rendered.h1s.length !== 1) add(findings, 'critical', 'h1-count'", 'script must require exactly one H1'],
  ["if (!/noindex/i.test(rendered.robots) && !/noindex/i.test(headers['x-robots-tag'] || '')) add(findings, 'critical', 'staging-indexable'", 'script must fail staging pages that are missing noindex'],
  ["if (!rendered.canonical) add(findings, 'critical', 'missing-canonical'", 'script must require a canonical link'],
  ["add(findings, 'warning', 'canonical-host', host)", "script must warn when the canonical host doesn't match the expected canonical host"],
  ["add(findings, 'critical', 'invalid-canonical', rendered.canonical)", 'script must fail when the canonical URL cannot be parsed'],
  ["if (rendered.overflow > 2) add(findings, 'critical', 'horizontal-overflow'", 'script must detect horizontal overflow'],
  ["if (rendered.duplicateIds.length) add(findings, 'critical', 'duplicate-ids'", 'script must detect duplicate element ids'],
  ["if (rendered.missingAlt.length) add(findings, 'warning', 'missing-alt'", 'script must warn on images missing alt text'],
  ["if (!/Manrope/i.test(rendered.bodyFont)) add(findings, 'warning', 'body-font'", 'script must warn when the body font is not Manrope'],
  ["if (rendered.h1s.length && !/Playfair Display/i.test(rendered.h1Font)) add(findings, 'warning', 'heading-font'", 'script must warn when the heading font is not Playfair Display'],
  ["if (rendered.smallControls.length) add(findings, 'warning', 'small-controls'", 'script must warn on undersized interactive controls'],
  ["size(rendered.joinchatButton, 48, 'joinchat-frame-size'); size(rendered.joinchatIcon, 24, 'joinchat-icon-size'); size(rendered.inlineWhatsapp, 16, 'inline-whatsapp-size');", 'script must validate joinchat/whatsapp widget dimensions'],
  ["add(findings, 'critical', 'invalid-jsonld'", 'script must fail on invalid JSON-LD'],
  ["['home', 'contacto', 'clinicas'].includes(slug) && !rendered.schemaTypes.includes('MedicalClinic')) add(findings, 'warning', 'schema-medical-clinic'", 'script must require MedicalClinic schema on clinic-facing routes'],
  ["['endolift', 'laser', 'medicina-estetica'].includes(slug) && !rendered.schemaTypes.includes('MedicalProcedure')) add(findings, 'warning', 'schema-medical-procedure'", 'script must require MedicalProcedure schema on treatment routes'],
  ['await page.screenshot({ path: path.join(out, `${slug}-${viewport}.png`), fullPage: true, animations: \'disabled\' });', 'script must capture a full-page screenshot named by slug and viewport'],
  ["fs.writeFileSync(path.join(out, 'report.json')", 'script must write a machine-readable report.json artifact'],
  ["fs.writeFileSync(path.join(out, 'report.md'), md)", 'script must write a human-readable report.md artifact'],
  ['if (critical.length) process.exit(1);', 'script must exit non-zero whenever any critical finding is present'],
]) {
  requireText(script, snippet, message);
}

// --- Workflow trigger and safety contract ---
requireMatch(workflow, /^\s{2}pull_request:/m, 'workflow must run on pull requests touching the QA sources');
requireMatch(workflow, /^\s{2}workflow_dispatch:/m, 'workflow must support manual dispatch with an overridable base URL and SHA');
requireAbsent(workflow, /^\s{2}push:/m, 'workflow must not run rendered QA on every push');
requireAbsent(workflow, /^\s{2}schedule:/m, 'workflow must not run rendered QA on a schedule');

for (const triggerPath of [
  '.github/workflows/staging2-rendered-qa.yml',
  'scripts/staging2/rendered-qa.mjs',
  'scripts/staging2/rendered-qa-routes.json',
]) {
  requireText(workflow, `- '${triggerPath}'`, `workflow must trigger on changes to ${triggerPath}`);
}

requireText(workflow, "default: 'https://staging2.nuvanx.com'", 'workflow must default base_url to the staging2 origin');
requireMatch(workflow, /default:\s*'[0-9a-f]{40}'/, 'workflow must default expected_sha to a full 40-character commit SHA');
requireMatch(workflow, /permissions:\s*\n\s+contents: read/, 'workflow must use read-only permissions');
requireText(workflow, 'cancel-in-progress: true', 'concurrent QA runs for the same ref must cancel the stale run');
requireText(workflow, 'timeout-minutes: 30', 'workflow must bound the audit job with a timeout');
requireText(workflow, 'EXPECTED_CANONICAL_HOST: nuvanx.com', 'workflow must pin the canonical host to production');
requireMatch(workflow, /uses: actions\/checkout@[0-9a-f]{40}/, 'checkout action must be pinned to a commit SHA');
requireMatch(workflow, /uses: actions\/setup-node@[0-9a-f]{40}/, 'setup-node action must be pinned to a commit SHA');
requireMatch(workflow, /uses: actions\/upload-artifact@[0-9a-f]{40}/, 'upload-artifact action must be pinned to a commit SHA');
requireText(workflow, "node-version: '22'", 'workflow must pin the Node.js major version');
requireText(workflow, 'node --check scripts/staging2/rendered-qa.mjs', 'workflow must syntax-check the audit script before running it');
requireText(
  workflow,
  "JSON.parse(require('fs').readFileSync('scripts/staging2/rendered-qa-routes.json','utf8'))",
  'workflow must validate the routes JSON before running the audit',
);
requireText(workflow, 'npm install playwright@1.54.1 --no-save', 'workflow must install a pinned, unsaved playwright dependency');
requireText(workflow, 'npx playwright install --with-deps chromium', 'workflow must install the chromium browser binary');
requireText(workflow, 'set +e', 'the audit step must disable errexit so it can capture a non-zero exit code');
requireText(workflow, 'echo "exit_code=$exit_code" >> "$GITHUB_OUTPUT"', 'the audit step must record its exit code as a step output');
requireMatch(
  workflow,
  /run:\s*\|\s*\n\s+set \+e\s*\n\s+node scripts\/staging2\/rendered-qa\.mjs\s*\n\s+exit_code=\$\?\s*\n\s+echo "exit_code=\$exit_code" >> "\$GITHUB_OUTPUT"\s*\n\s+exit 0/,
  'the audit step must always exit 0 so later steps still publish the report and artifacts',
);
requireText(workflow, 'cat qa-artifacts/staging2-rendered/report.md >> "$GITHUB_STEP_SUMMARY"', 'workflow must publish the report to the job summary');
requireText(workflow, 'name: staging2-rendered-qa-${{ github.run_id }}', 'uploaded artifact must be named per run');
requireText(workflow, 'path: qa-artifacts/staging2-rendered', 'workflow must upload the QA output directory');
requireText(workflow, 'if-no-files-found: error', 'artifact upload must fail if no QA artifacts were produced');
requireText(workflow, 'retention-days: 14', 'QA artifacts must be retained for 14 days');
requireText(workflow, 'test "${{ steps.audit.outputs.exit_code }}" = "0"', 'workflow must gate the job on the recorded audit exit code');

// The two "always()" steps that publish the report and enforce findings must both run even
// when the audit step (which itself always exits 0) recorded a critical failure.
const alwaysSteps = workflow.match(/if:\s*always\(\)/g) ?? [];
if (alwaysSteps.length < 3) {
  failures.push('report publication, artifact upload and enforcement steps must all run with if: always()');
}

if (failures.length) {
  console.error('Staging2 rendered QA contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: staging2 rendered QA contract');