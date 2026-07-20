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

function fail(message) {
  failures.push(message);
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
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

const workflow = read(workflowPath);
const script = read(scriptPath);
const routesRaw = read(routesPath);

// --- rendered-qa-routes.json structural contract ---------------------------
let routesConfig;
try {
  routesConfig = JSON.parse(routesRaw);
} catch (error) {
  fail(`rendered-qa-routes.json must be valid JSON: ${error.message}`);
}

if (routesConfig) {
  if (typeof routesConfig.baseUrl !== 'string' || !/^https:\/\/staging2\.nuvanx\.com$/.test(routesConfig.baseUrl)) {
    fail('rendered-qa-routes.json baseUrl must be the exact staging2 origin without a trailing slash');
  }

  if (!Array.isArray(routesConfig.routes) || routesConfig.routes.length === 0) {
    fail('rendered-qa-routes.json routes must be a non-empty array');
  } else {
    const slugs = new Set();
    const paths = new Set();
    for (const entry of routesConfig.routes) {
      if (!Array.isArray(entry) || entry.length !== 2) {
        fail(`each route entry must be a [slug, path] tuple, got: ${JSON.stringify(entry)}`);
        continue;
      }
      const [slug, routePath] = entry;
      if (typeof slug !== 'string' || !/^[a-z0-9-]+$/.test(slug)) {
        fail(`route slug must be a lowercase kebab-case string, got: ${JSON.stringify(slug)}`);
      }
      if (typeof routePath !== 'string' || !routePath.startsWith('/') || !routePath.endsWith('/')) {
        fail(`route path must start and end with a slash, got: ${JSON.stringify(routePath)}`);
      }
      if (slugs.has(slug)) fail(`duplicate route slug: ${slug}`);
      slugs.add(slug);
      if (paths.has(routePath)) fail(`duplicate route path: ${routePath}`);
      paths.add(routePath);
    }

    for (const requiredSlug of ['home', 'contacto', 'clinicas']) {
      if (!slugs.has(requiredSlug)) fail(`rendered-qa-routes.json must include the required "${requiredSlug}" route`);
    }

    // Cross-file consistency: every slug referenced by the schema checks in
    // rendered-qa.mjs must exist in the routes config, otherwise the check
    // silently never fires.
    const schemaSlugPattern = /\[['"]([a-z0-9-]+)['"](?:,\s*['"][a-z0-9-]+['"])*\]\.includes\(slug\)/g;
    let match;
    let foundSchemaSlugGroup = false;
    while ((match = schemaSlugPattern.exec(script)) !== null) {
      foundSchemaSlugGroup = true;
      const group = match[0].match(/\[([^\]]+)\]/)[1];
      const referencedSlugs = [...group.matchAll(/['"]([a-z0-9-]+)['"]/g)].map((m) => m[1]);
      for (const referencedSlug of referencedSlugs) {
        if (!slugs.has(referencedSlug)) {
          fail(`rendered-qa.mjs references slug "${referencedSlug}" for a schema check, but it is missing from rendered-qa-routes.json`);
        }
      }
    }
    if (!foundSchemaSlugGroup) {
      fail('expected rendered-qa.mjs to contain slug-based schema check arrays, but none were found');
    }
  }

  if (Object.keys(routesConfig).some((key) => !['baseUrl', 'routes'].includes(key))) {
    fail('rendered-qa-routes.json must only contain baseUrl and routes keys');
  }
}

// --- rendered-qa.mjs behavioral contract ------------------------------------
requireMatch(script, /import\s*\{\s*chromium\s*\}\s*from\s*['"]playwright['"]/, 'script must import chromium from playwright');
requireMatch(script, /rendered-qa-routes\.json/, 'script must load the routes config file');
requireMatch(script, /process\.env\.BASE_URL/, 'script must allow BASE_URL override via environment');
requireMatch(script, /process\.env\.QA_OUTPUT_DIR/, 'script must allow QA_OUTPUT_DIR override via environment');
requireMatch(script, /process\.env\.EXPECTED_DEPLOY_SHA/, 'script must read EXPECTED_DEPLOY_SHA from environment');
requireMatch(script, /process\.env\.EXPECTED_CANONICAL_HOST/, 'script must read EXPECTED_CANONICAL_HOST from environment');
requireMatch(script, /\['desktop',\s*1440,\s*1100\]/, 'script must audit the desktop viewport at 1440x1100');
requireMatch(script, /\['mobile',\s*390,\s*844\]/, 'script must audit the mobile viewport at 390x844');
requireMatch(script, /fs\.mkdirSync\(out,\s*\{\s*recursive:\s*true\s*\}\)/, 'script must create the output directory');

// Critical console error classification must stay strict enough to catch
// unhandled runtime errors and third-party tracking regressions.
requireMatch(script, /ReferenceError/, 'critical JS pattern must flag ReferenceError');
requireMatch(script, /TypeError/, 'critical JS pattern must flag TypeError');
requireMatch(script, /Uncaught/, 'critical JS pattern must flag uncaught exceptions');
requireMatch(script, /FacebookSignal/, 'critical JS pattern must flag FacebookSignal regressions');

// Browser launch must be sandboxed for CI runners.
requireMatch(script, /chromium\.launch\(\{\s*headless:\s*true,\s*args:\s*\['--no-sandbox',\s*'--disable-setuid-sandbox'\]\s*\}\)/, 'browser must launch headless with sandbox flags for CI');

// requestfailed handling must exclude known third-party domains so the audit
// does not fail on expected ad/tracker blocking.
requireMatch(script, /google\|facebook\|doubleclick\|hubspot\|hs-scripts\|clarity/, 'request-failed filter must exclude known third-party domains');

// SEO / rendering assertions that must remain present in the audit.
requireMatch(script, /rendered\.captcha/, 'script must detect SiteGround bot-challenge captcha pages');
requireMatch(script, /missing-title/, 'script must flag an empty document title as critical');
requireMatch(script, /missing-description/, 'script must flag an empty meta description');
requireMatch(script, /h1-count/, 'script must enforce exactly one H1 per page');
requireMatch(script, /staging-indexable/, 'script must flag staging pages missing noindex');
requireMatch(script, /missing-canonical/, 'script must flag missing canonical links');
requireMatch(script, /canonical-host/, 'script must validate the canonical host');
requireMatch(script, /horizontal-overflow/, 'script must detect horizontal overflow/layout breakage');
requireMatch(script, /duplicate-ids/, 'script must detect duplicate DOM ids');
requireMatch(script, /missing-alt/, 'script must detect images missing alt text');
requireMatch(script, /Manrope/, 'script must validate the Manrope body font');
requireMatch(script, /Playfair Display/, 'script must validate the Playfair Display heading font');
requireMatch(script, /joinchat-frame-size/, 'script must validate the joinchat button touch target size');
requireMatch(script, /joinchat-icon-size/, 'script must validate the joinchat icon size');
requireMatch(script, /inline-whatsapp-size/, 'script must validate the inline WhatsApp icon size');
requireMatch(script, /invalid-jsonld/, 'script must flag invalid JSON-LD blocks');
requireMatch(script, /MedicalClinic/, 'script must require MedicalClinic schema on clinic-facing routes');
requireMatch(script, /MedicalProcedure/, 'script must require MedicalProcedure schema on procedure routes');

// Report output and exit-code contract consumed by the CI workflow.
requireMatch(script, /report\.json/, 'script must write report.json');
requireMatch(script, /report\.md/, 'script must write report.md');
requireMatch(script, /summary:\s*\{[\s\S]*result:\s*critical\.length\s*\?\s*'FAIL'\s*:\s*'PASS_WITH_WARNINGS'/, 'report summary must classify runs as FAIL or PASS_WITH_WARNINGS');
requireMatch(script, /if\s*\(critical\.length\)\s*process\.exit\(1\)/, 'script must exit non-zero only when critical findings exist');

// --- .github/workflows/staging2-rendered-qa.yml contract --------------------
requireMatch(workflow, /^\s{2}workflow_dispatch:/m, 'workflow must be manually triggered via workflow_dispatch');
requireAbsent(workflow, /^\s{2}push:/m, 'workflow must not run automatically on push');
requireAbsent(workflow, /^\s{2}pull_request:/m, 'workflow must not run automatically on pull_request');
requireAbsent(workflow, /^\s{2}schedule:/m, 'workflow must not run automatically on a schedule');

requireMatch(workflow, /base_url:[\s\S]*default:\s*'https:\/\/staging2\.nuvanx\.com'/, 'workflow must default base_url to the staging2 origin');
requireMatch(workflow, /expected_sha:[\s\S]*default:\s*'[0-9a-f]{40}'/, 'workflow must default expected_sha to a full 40-character SHA');

requireMatch(workflow, /permissions:\s*\n\s+contents:\s*read/, 'workflow must declare read-only contents permissions');
requireAbsent(workflow, /permissions:\s*write-all/, 'workflow must never request write-all permissions');

requireMatch(workflow, /concurrency:\s*\n\s+group:\s*staging2-rendered-qa-\$\{\{\s*github\.ref\s*\}\}/, 'workflow must scope concurrency per ref');
requireMatch(workflow, /cancel-in-progress:\s*true/, 'workflow must cancel superseded rendered QA runs');

requireMatch(workflow, /timeout-minutes:\s*30/, 'rendered-qa job must cap runtime at 30 minutes');

requireMatch(workflow, /BASE_URL:\s*\$\{\{\s*inputs\.base_url[\s\S]*?\}\}/, 'workflow must forward base_url input as BASE_URL');
requireMatch(workflow, /EXPECTED_DEPLOY_SHA:\s*\$\{\{\s*inputs\.expected_sha[\s\S]*?\}\}/, 'workflow must forward expected_sha input as EXPECTED_DEPLOY_SHA');
requireMatch(workflow, /EXPECTED_CANONICAL_HOST:\s*nuvanx\.com/, 'workflow must pin EXPECTED_CANONICAL_HOST to nuvanx.com');
requireMatch(workflow, /QA_OUTPUT_DIR:\s*qa-artifacts\/staging2-rendered/, 'workflow must pin the QA output directory');

requireMatch(workflow, /uses: actions\/checkout@[0-9a-f]{40}/, 'checkout action must be pinned to a full commit SHA');
requireMatch(workflow, /persist-credentials:\s*false/, 'checkout must not persist git credentials');
requireMatch(workflow, /uses: actions\/setup-node@[0-9a-f]{40}/, 'setup-node action must be pinned to a full commit SHA');
requireMatch(workflow, /node-version:\s*'22'/, 'workflow must use Node.js 22');

requireMatch(workflow, /node --check scripts\/staging2\/rendered-qa\.mjs/, 'workflow must syntax-check the audit script before running it');
requireMatch(workflow, /JSON\.parse\(require\('fs'\)\.readFileSync\('scripts\/staging2\/rendered-qa-routes\.json','utf8'\)\)/, 'workflow must validate the routes JSON before running the audit');

requireMatch(workflow, /npm install playwright@1\.54\.1 --no-save/, 'workflow must install a pinned, unsaved Playwright dependency');
requireMatch(workflow, /npx playwright install --with-deps chromium/, 'workflow must install the Chromium browser binary');

requireMatch(workflow, /id:\s*audit/, 'run step must expose an id for downstream steps to read its outcome');
requireMatch(workflow, /set \+e/, 'run step must not let a non-zero audit exit code abort the job early');
requireMatch(workflow, /echo "exit_code=\$exit_code" >> "\$GITHUB_OUTPUT"/, 'run step must publish the audit exit code as a step output');
requireMatch(workflow, /run: \|\s*\n\s*set \+e\s*\n\s*node scripts\/staging2\/rendered-qa\.mjs\s*\n\s*exit_code=\$\?\s*\n\s*echo "exit_code=\$exit_code" >> "\$GITHUB_OUTPUT"\s*\n\s*exit 0/, 'run step must always exit 0 itself so later steps still execute');

requireMatch(workflow, /Publish QA report in job summary[\s\S]*if:\s*always\(\)/, 'job summary publication must run even if the audit step failed');
requireMatch(workflow, /cat qa-artifacts\/staging2-rendered\/report\.md >> "\$GITHUB_STEP_SUMMARY"/, 'workflow must publish report.md to the job summary');

requireMatch(workflow, /Upload screenshots and reports[\s\S]*if:\s*always\(\)/, 'artifact upload must run even if the audit step failed');
requireMatch(workflow, /uses: actions\/upload-artifact@[0-9a-f]{40}/, 'upload-artifact action must be pinned to a full commit SHA');
requireMatch(workflow, /path:\s*qa-artifacts\/staging2-rendered/, 'workflow must upload the QA output directory');
requireMatch(workflow, /if-no-files-found:\s*error/, 'workflow must fail if no QA artifacts were produced');
requireMatch(workflow, /retention-days:\s*14/, 'workflow must retain QA artifacts for 14 days');

requireMatch(workflow, /Enforce critical findings[\s\S]*if:\s*always\(\)/, 'critical-findings enforcement must run even after prior step failures');
requireMatch(workflow, /AUDIT_EXIT_CODE:\s*\$\{\{\s*steps\.audit\.outputs\.exit_code\s*\}\}/, 'enforcement step must read the audit exit code output');
requireMatch(workflow, /test "\$AUDIT_EXIT_CODE" = "0" \|\|/, 'enforcement step must fail the job when the audit exit code is non-zero');
requireMatch(workflow, /exit 1/, 'enforcement step must exit non-zero to fail the job on critical findings');

if (failures.length) {
  console.error('Staging2 rendered QA contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: staging2 rendered QA audit, routes config, and workflow contract');