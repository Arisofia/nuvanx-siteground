#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const failures = [];

/**
 * Records a failure message when a pattern does not match the source.
 * @param {string} source - The content to test.
 * @param {RegExp} pattern - The pattern that must match the source.
 * @param {string} message - The failure message to record.
 */
function requireMatch(source, pattern, message) {
  if (!pattern.test(source)) failures.push(message);
}
/**
 * Records a failure when a pattern is found in the source.
 * @param {string} source - The content to inspect.
 * @param {RegExp} pattern - The pattern that must be absent.
 * @param {string} message - The failure message to record.
 */
function requireAbsent(source, pattern, message) {
  if (pattern.test(source)) failures.push(message);
}
/**
 * Records a failure when a condition is falsy.
 * @param {boolean} condition - The condition expected to be true.
 * @param {string} message - The failure message to record.
 */
function requireTrue(condition, message) {
  if (!condition) failures.push(message);
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

// ---------------------------------------------------------------------------
// Workflow contract: manual-only trigger, least-privilege permissions, pinned
// actions, and a resilient audit/report/upload/enforce pipeline.
// ---------------------------------------------------------------------------

requireMatch(workflow, /^name: Staging2 Rendered QA$/m, 'workflow must be named Staging2 Rendered QA');
requireMatch(workflow, /^\s{2}workflow_dispatch:/m, 'workflow must be manually triggerable');
requireAbsent(workflow, /^\s{2}push:/m, 'rendered QA workflow must not run automatically on push');
requireAbsent(workflow, /^\s{2}pull_request:/m, 'rendered QA workflow must not run automatically on pull_request');
requireAbsent(workflow, /^\s{2}schedule:/m, 'rendered QA workflow must not run on a schedule');

requireMatch(
  workflow,
  /base_url:\s*\n\s+description: 'Public Staging2 URL'\s*\n\s+required: true\s*\n\s+default: 'https:\/\/staging2\.nuvanx\.com'\s*\n\s+type: string/,
  'workflow must expose a required base_url input with a safe default',
);
requireMatch(
  workflow,
  /expected_sha:\s*\n\s+description: 'SHA expected to be deployed in Staging2'\s*\n\s+required: true\s*\n\s+default: '[0-9a-f]{40}'\s*\n\s+type: string/,
  'workflow must expose a required expected_sha input defaulting to a 40-character SHA',
);

requireMatch(workflow, /^permissions:\s*\n\s+contents: read$/m, 'workflow must request read-only contents permission');

requireMatch(
  workflow,
  /concurrency:\s*\n\s+group: staging2-rendered-qa-\$\{\{ github\.ref \}\}\s*\n\s+cancel-in-progress: true/,
  'workflow must serialize runs per ref and cancel superseded runs',
);

requireMatch(workflow, /runs-on: ubuntu-latest/, 'job must run on ubuntu-latest');
requireMatch(workflow, /timeout-minutes: 30/, 'job must bound execution with a 30 minute timeout');

requireMatch(
  workflow,
  /BASE_URL:\s*\$\{\{\s*inputs\.base_url\s*\|\|\s*'https:\/\/staging2\.nuvanx\.com'\s*\}\}/,
  'workflow must fall back to the staging2 URL when base_url is not supplied',
);
requireMatch(
  workflow,
  /EXPECTED_DEPLOY_SHA:\s*\$\{\{\s*inputs\.expected_sha\s*\|\|\s*'[0-9a-f]{40}'\s*\}\}/,
  'workflow must fall back to a pinned SHA when expected_sha is not supplied',
);
requireMatch(workflow, /EXPECTED_CANONICAL_HOST:\s*nuvanx\.com/, 'workflow must pin the expected canonical host');
requireMatch(workflow, /QA_OUTPUT_DIR:\s*qa-artifacts\/staging2-rendered/, 'workflow must pin the QA output directory');

requireMatch(workflow, /uses: actions\/checkout@[0-9a-f]{40} # v4/, 'checkout action must be pinned to a commit SHA');
requireMatch(workflow, /persist-credentials: false/, 'checkout must not persist git credentials');
requireMatch(workflow, /uses: actions\/setup-node@[0-9a-f]{40} # v4/, 'setup-node action must be pinned to a commit SHA');
requireMatch(workflow, /node-version: '22'/, 'workflow must pin the Node.js major version');

requireMatch(
  workflow,
  new RegExp(`node --check ${scriptPath.replace(/\//g, '\\/')}`),
  'workflow must syntax-check the audit script before running it',
);
requireMatch(
  workflow,
  new RegExp(`JSON\\.parse\\(require\\('fs'\\)\\.readFileSync\\('${routesPath.replace(/\//g, '\\/')}','utf8'\\)\\)`),
  'workflow must validate the routes JSON before running the audit',
);

requireMatch(workflow, /npm install playwright@1\.54\.1 --no-save/, 'workflow must install a pinned Playwright version without mutating package.json');
requireMatch(workflow, /npx playwright install --with-deps chromium/, 'workflow must install the chromium browser with its OS dependencies');

requireMatch(workflow, /id: audit/, 'the audit step must expose an id so its outcome can be enforced later');
requireMatch(workflow, /set \+e/, 'audit step must disable errexit so later reporting steps still run');
requireMatch(
  workflow,
  new RegExp(`node ${scriptPath.replace(/\//g, '\\/')}\\n\\s+exit_code=\\$\\?`),
  'audit step must capture the script exit code',
);
requireMatch(workflow, /echo "exit_code=\$exit_code" >> "\$GITHUB_OUTPUT"/, 'audit step must publish its exit code as a step output');
requireMatch(workflow, /run: \|\s*\n\s+set \+e[\s\S]*?exit 0/, 'audit step must always exit 0 so subsequent steps run under if: always()');

requireMatch(
  workflow,
  /Publish QA report in job summary\s*\n\s+if: always\(\)\s*\n\s+run: cat qa-artifacts\/staging2-rendered\/report\.md >> "\$GITHUB_STEP_SUMMARY"/,
  'workflow must publish report.md to the job summary regardless of audit outcome',
);

requireMatch(
  workflow,
  /Upload screenshots and reports\s*\n\s+if: always\(\)\s*\n\s+uses: actions\/upload-artifact@[0-9a-f]{40} # v4\.6\.2/,
  'artifact upload must run unconditionally and use a pinned upload-artifact action',
);
requireMatch(workflow, /name: staging2-rendered-qa-\$\{\{ github\.run_id \}\}/, 'artifact name must be unique per run');
requireMatch(workflow, /path: qa-artifacts\/staging2-rendered/, 'artifact upload must target the QA output directory');
requireMatch(workflow, /if-no-files-found: error/, 'artifact upload must fail loudly if no report/screenshots were produced');
requireMatch(workflow, /retention-days: 14/, 'artifact retention must be bounded');

requireMatch(
  workflow,
  /Enforce critical findings\s*\n\s+if: always\(\)\s*\n\s+env:\s*\n\s+AUDIT_EXIT_CODE: \$\{\{ steps\.audit\.outputs\.exit_code \}\}/,
  'final gating step must always run and consume the captured audit exit code',
);
requireMatch(
  workflow,
  /test "\$AUDIT_EXIT_CODE" = "0" \|\| \{\s*\n\s+echo 'Rendered QA found critical failures\. Review report\.md and screenshots\.' >&2\s*\n\s+exit 1\s*\n\s+\}/,
  'workflow must fail the job when the audit exit code is non-zero',
);

// ---------------------------------------------------------------------------
// Routes JSON contract: structurally valid and consistent with the slugs the
// audit script depends on for schema assertions.
// ---------------------------------------------------------------------------

let routesConfig;
try {
  routesConfig = JSON.parse(routesRaw);
} catch (error) {
  failures.push(`rendered-qa-routes.json must be valid JSON: ${error.message}`);
  routesConfig = null;
}

if (routesConfig) {
  requireTrue(typeof routesConfig.baseUrl === 'string' && /^https:\/\//.test(routesConfig.baseUrl), 'routes config must declare an https baseUrl');
  requireTrue(Array.isArray(routesConfig.routes), 'routes config must declare a routes array');

  if (Array.isArray(routesConfig.routes)) {
    requireTrue(routesConfig.routes.length > 0, 'routes config must declare at least one route');

    const slugs = new Set();
    const paths = new Set();
    for (const entry of routesConfig.routes) {
      requireTrue(Array.isArray(entry) && entry.length === 2, `each route entry must be a [slug, path] tuple: ${JSON.stringify(entry)}`);
      const [slug, routePath] = Array.isArray(entry) ? entry : [undefined, undefined];
      requireTrue(typeof slug === 'string' && slug.length > 0, `route slug must be a non-empty string: ${JSON.stringify(entry)}`);
      requireTrue(typeof routePath === 'string' && routePath.startsWith('/'), `route path must start with a leading slash: ${JSON.stringify(entry)}`);
      if (typeof slug === 'string') {
        requireTrue(!slugs.has(slug), `route slug must be unique: ${slug}`);
        slugs.add(slug);
      }
      if (typeof routePath === 'string') {
        requireTrue(!paths.has(routePath), `route path must be unique: ${routePath}`);
        paths.add(routePath);
      }
    }

    // The audit script gates schema.org assertions on these specific slugs;
    // if they are renamed or removed here, that logic silently stops firing.
    for (const requiredSlug of ['home', 'contacto', 'clinicas', 'endolift', 'laser', 'medicina-estetica']) {
      requireTrue(slugs.has(requiredSlug), `routes config must retain the slug relied on by schema assertions: ${requiredSlug}`);
    }

    const home = routesConfig.routes.find(([slug]) => slug === 'home');
    requireTrue(Boolean(home) && home[1] === '/', 'the home route must map to the site root');
  }
}

// ---------------------------------------------------------------------------
// Audit script contract: safety-relevant behaviors and finding rules that
// govern PASS/FAIL classification for the staging rendered QA gate.
// ---------------------------------------------------------------------------

requireMatch(script, /^#!\/usr\/bin\/env node$/m, 'audit script must be directly executable');
requireMatch(script, /import \{ chromium \} from 'playwright';/, 'audit script must drive rendering with Playwright chromium');
requireMatch(script, /new URL\('\.\/rendered-qa-routes\.json', import\.meta\.url\)/, 'audit script must resolve routes relative to its own module location');
requireMatch(
  script,
  /const baseUrl = \(process\.env\.BASE_URL \|\| config\.baseUrl\)\.replace\(\/\\\/\$\/, ''\);/,
  'audit script must allow BASE_URL override and normalize a trailing slash',
);
requireMatch(script, /const out = path\.resolve\(process\.env\.QA_OUTPUT_DIR \|\| 'qa-artifacts\/staging2-rendered'\);/, 'audit script must allow QA_OUTPUT_DIR override with a sane default');
requireMatch(script, /const canonicalHost = process\.env\.EXPECTED_CANONICAL_HOST \|\| 'nuvanx\.com';/, 'audit script must allow the canonical host to be overridden');
requireMatch(script, /fs\.mkdirSync\(out, \{ recursive: true \}\);/, 'audit script must ensure the output directory exists before writing artifacts');

requireMatch(
  script,
  /const viewports = \[\['desktop', 1440, 1100\], \['mobile', 390, 844\]\];/,
  'audit script must check both desktop and mobile viewports',
);
requireMatch(
  script,
  /const criticalJs = \/\(ReferenceError\|TypeError\|SyntaxError\|Uncaught\|FacebookSignal\|is not defined\)\/i;/,
  'audit script must classify common fatal JS error signatures as critical',
);

requireMatch(script, /function add\(list, severity, code, message, details = \{\}\) \{ list\.push\(\{ severity, code, message, \.\.\.details \}\); \}/, 'add() must record severity, code, message and any extra details');
requireMatch(script, /function collectTypes\(value, set = new Set\(\)\)/, 'collectTypes() must recursively collect JSON-LD @type values');
requireMatch(script, /const type = value\['@type'\];/, 'collectTypes() must read the @type property off each node');

requireMatch(script, /async function inspect\(browser, slug, route, viewport, width, height\)/, 'inspect() must accept per-route/per-viewport parameters');
requireMatch(script, /locale: 'es-ES', reducedMotion: 'reduce', colorScheme: 'light'/, 'browser context must use deterministic locale/motion/color settings');

requireMatch(
  script,
  /if \(msg\.type\(\) === 'error'\) \{ consoleErrors\.push\(msg\.text\(\)\); if \(criticalJs\.test\(msg\.text\(\)\)\) add\(findings, 'critical', 'console-error', msg\.text\(\)\); \}/,
  'console errors must be recorded, and only escalated to critical when they match the fatal JS signature list',
);
requireMatch(script, /page\.on\('pageerror', \(error\) => add\(findings, 'critical', 'page-error', error\.message\)\);/, 'uncaught page errors must always be critical');
requireMatch(
  script,
  /if \(!\/\(google\|facebook\|doubleclick\|hubspot\|hs-scripts\|clarity\)\\\.\/i\.test\(url\) && request\.resourceType\(\) !== 'media'\)/,
  'failed request tracking must ignore known third-party/analytics domains and media requests',
);
requireMatch(script, /failedRequests\.push\(url\); add\(findings, 'warning', 'request-failed', url\);/, 'non-ignored failed requests must be recorded as warnings');

requireMatch(script, /waitUntil: 'domcontentloaded', timeout: 45000/, 'navigation must use a bounded timeout');
requireMatch(script, /await page\.waitForTimeout\(1500\);/, 'audit script must allow time for client-side rendering to settle');
requireMatch(script, /catch \(error\) \{ add\(findings, 'critical', 'navigation-failed', error\.message\); \}/, 'navigation failures must be recorded as critical findings');
requireMatch(script, /if \(!status \|\| status >= 400\) add\(findings, 'critical', 'http-status', `HTTP \$\{status \?\? 'none'\}`\);/, 'missing or error HTTP statuses must be critical');

requireMatch(script, /if \(rendered\.captcha\) add\(findings, 'critical', 'siteground-captcha', 'Bot challenge rendered\.'\);/, 'a rendered bot-challenge page must be treated as critical');
requireMatch(script, /if \(!rendered\.title\) add\(findings, 'critical', 'missing-title', 'Empty title\.'\);/, 'an empty <title> must be critical');
requireMatch(script, /if \(!rendered\.description\) add\(findings, 'warning', 'missing-description', 'Empty meta description\.'\);/, 'an empty meta description must be a warning, not critical');
requireMatch(script, /if \(rendered\.h1s\.length !== 1\) add\(findings, 'critical', 'h1-count',/, 'a page without exactly one H1 must be critical');
requireMatch(
  script,
  /if \(!\/noindex\/i\.test\(rendered\.robots\) && !\/noindex\/i\.test\(headers\['x-robots-tag'\] \|\| ''\)\) add\(findings, 'critical', 'staging-indexable',/,
  'staging pages missing a noindex directive (meta or header) must be flagged critical to prevent duplicate content indexing',
);
requireMatch(script, /if \(!rendered\.canonical\) add\(findings, 'critical', 'missing-canonical',/, 'a missing canonical link must be critical');
requireMatch(
  script,
  /if \(!\[canonicalHost, `www\.\$\{canonicalHost\}`\]\.includes\(host\)\) add\(findings, 'warning', 'canonical-host', host\);/,
  'a canonical host outside the expected production domain must be flagged (warning)',
);
requireMatch(script, /catch \{ add\(findings, 'critical', 'invalid-canonical', rendered\.canonical\); \}/, 'an unparsable canonical URL must be critical');
requireMatch(script, /if \(rendered\.overflow > 2\) add\(findings, 'critical', 'horizontal-overflow',/, 'horizontal overflow beyond a small tolerance must be critical');
requireMatch(script, /if \(rendered\.duplicateIds\.length\) add\(findings, 'critical', 'duplicate-ids',/, 'duplicate DOM ids must be critical');
requireMatch(script, /if \(rendered\.missingAlt\.length\) add\(findings, 'warning', 'missing-alt',/, 'images missing alt text must be a warning');
requireMatch(script, /if \(!\/Manrope\/i\.test\(rendered\.bodyFont\)\) add\(findings, 'warning', 'body-font',/, 'body font drift from Manrope must be a warning');
requireMatch(script, /if \(rendered\.h1s\.length && !\/Playfair Display\/i\.test\(rendered\.h1Font\)\) add\(findings, 'warning', 'heading-font',/, 'heading font drift from Playfair Display must be a warning when an H1 exists');
requireMatch(script, /if \(rendered\.smallControls\.length\) add\(findings, 'warning', 'small-controls',/, 'undersized tap targets must be a warning');

requireMatch(
  script,
  /const size = \(actual, expected, code\) => \{ if \(actual && \(Math\.abs\(actual\[0\] - expected\) > 1 \|\| Math\.abs\(actual\[1\] - expected\) > 1\)\) add\(findings, 'warning', code,/,
  'widget size checks must tolerate at most 1px of drift before warning',
);
requireMatch(script, /size\(rendered\.joinchatButton, 48, 'joinchat-frame-size'\); size\(rendered\.joinchatIcon, 24, 'joinchat-icon-size'\); size\(rendered\.inlineWhatsapp, 16, 'inline-whatsapp-size'\);/, 'audit script must check joinchat button, joinchat icon and inline WhatsApp icon sizes');

requireMatch(script, /catch \(error\) \{ add\(findings, 'critical', 'invalid-jsonld', `Block \$\{index\}: \$\{error\.message\}`\); \}/, 'unparsable JSON-LD blocks must be critical');
requireMatch(
  script,
  /if \(\['home', 'contacto', 'clinicas'\]\.includes\(slug\) && !rendered\.schemaTypes\.includes\('MedicalClinic'\)\) add\(findings, 'warning', 'schema-medical-clinic',/,
  'clinic-facing pages missing MedicalClinic schema must be flagged',
);
requireMatch(
  script,
  /if \(\['endolift', 'laser', 'medicina-estetica'\]\.includes\(slug\) && !rendered\.schemaTypes\.includes\('MedicalProcedure'\)\) add\(findings, 'warning', 'schema-medical-procedure',/,
  'procedure pages missing MedicalProcedure schema must be flagged',
);

requireMatch(script, /await page\.screenshot\(\{ path: path\.join\(out, `\$\{slug\}-\$\{viewport\}\.png`\), fullPage: true, animations: 'disabled' \}\);/, 'audit script must capture a deterministic, full-page screenshot per slug/viewport');
requireMatch(script, /await context\.close\(\);/, 'audit script must close the browser context after each inspection to avoid leaking resources');

requireMatch(script, /const browser = await chromium\.launch\(\{ headless: true, args: \['--no-sandbox', '--disable-setuid-sandbox'\] \}\);/, 'audit script must launch a headless, CI-safe browser');
requireMatch(script, /try \{ for \(const \[slug, route\] of config\.routes\) for \(const \[viewport, width, height\] of viewports\)/, 'audit script must iterate every configured route across every viewport');
requireMatch(script, /finally \{ await browser\.close\(\); \}/, 'audit script must close the browser even if an inspection throws');

requireMatch(script, /const critical = findings\.filter\(\(item\) => item\.severity === 'critical'\);/, 'audit script must separate critical findings from warnings');
requireMatch(
  script,
  /result: critical\.length \? 'FAIL' : 'PASS_WITH_WARNINGS'/,
  'summary result must be FAIL only when critical findings exist',
);
requireMatch(script, /fs\.writeFileSync\(path\.join\(out, 'report\.json'\)/, 'audit script must persist a machine-readable report.json');
requireMatch(script, /fs\.writeFileSync\(path\.join\(out, 'report\.md'\)/, 'audit script must persist a human-readable report.md');
requireMatch(script, /if \(critical\.length\) process\.exit\(1\);/, 'audit script must exit non-zero when any critical finding was recorded');

if (failures.length) {
  console.error('Staging2 rendered QA contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: staging2 rendered QA workflow, routes config and audit script contract');