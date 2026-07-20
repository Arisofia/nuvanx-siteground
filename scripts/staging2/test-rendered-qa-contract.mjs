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
  assert.ok(fs.existsSync(path.join(root, requiredPath)), `missing rendered QA contract file: ${requiredPath}`);
}

const config = JSON.parse(read(routesPath));
assert.equal(config.baseUrl, 'https://staging2.nuvanx.com');
assert.ok(Array.isArray(config.routes));

const expectedSlugs = [
  'home',
  'contacto',
  'clinicas',
  'tratamientos',
  'endolift',
  'laser',
  'medicina-estetica',
  'equipo',
  'blog',
  'por-que-nuvanx',
  'inversion',
  'liposculpt-review',
  'v-lift-review',
];
assert.deepEqual(config.routes.map(([slug]) => slug), expectedSlugs, 'rendered QA route order and coverage changed unexpectedly');

const paths = new Set();
for (const [slug, route] of config.routes) {
  assert.match(slug, /^[a-z0-9-]+$/);
  assert.match(route, /^\//);
  if (route !== '/') assert.match(route, /\/$/);
  assert.ok(!paths.has(route), `duplicate route: ${route}`);
  paths.add(route);
}

const script = read(scriptPath);
const workflow = read(workflowPath);
const failures = [];
const requireMatch = (source, pattern, message) => { if (!pattern.test(source)) failures.push(message); };
const requireAbsent = (source, pattern, message) => { if (pattern.test(source)) failures.push(message); };

requireMatch(script, /import \{ chromium \} from 'playwright'/, 'Playwright Chromium is required');
requireMatch(script, /\['desktop', 1440, 1100\]/, 'desktop viewport must remain 1440x1100');
requireMatch(script, /\['mobile', 390, 844\]/, 'mobile viewport must remain 390x844');
requireMatch(script, /protectedReviewSlugs = new Set\(\['liposculpt-review', 'v-lift-review'\]\)/, 'protected review routes must be explicit');

requireMatch(script, /captureAndDismissConsent/, 'consent evidence helper is required');
requireMatch(script, /name: \/denegar\/i/, 'clean QA must reject optional cookies');
requireMatch(script, /`\$\{slug\}-consent-\$\{viewport\}\.png`/, 'consent screenshot must be separate');
requireMatch(script, /fullPage: false/, 'consent evidence must capture the viewport, not duplicate a full-page baseline');
requireMatch(script, /`\$\{slug\}-\$\{viewport\}\.png`/, 'clean full-page screenshot naming changed');
requireMatch(script, /fullPage: true/, 'clean visual baseline must remain full-page');

requireMatch(script, /async function hydratePage/, 'lazy asset hydration helper is required');
requireMatch(script, /window\.scrollTo\(0, y\)/, 'QA must scroll through the document');
requireMatch(script, /image\.decode\(\)/, 'QA must decode lazy images before the clean screenshot');
requireMatch(script, /window\.scrollTo\(0, 0\)/, 'QA must return to the page top before evaluation');

for (const code of [
  'missing-title',
  'h1-count',
  'staging-indexable',
  'missing-canonical',
  'horizontal-overflow',
  'duplicate-ids',
  'siteground-captcha',
  'invalid-jsonld',
  'navigation-failed',
  'http-status',
]) {
  requireMatch(script, new RegExp(`'critical', '${code}'`), `missing critical finding: ${code}`);
}

requireMatch(script, /Expected 1 H1/, 'exactly one H1 must be enforced');
requireMatch(script, /!protectedReview && !rendered\.description/, 'protected review pages must be exempt from public meta-description expectations');
requireMatch(script, /if \(protectedReview\)/, 'protected review canonical branch is required');
requireMatch(script, /protected-review-canonical/, 'unexpected canonical on a protected review route must be visible');
requireMatch(script, /\[canonicalHost, `www\.\$\{canonicalHost\}`\]/, 'approved canonicals must target the public host');

requireMatch(script, /\['home', 'contacto', 'clinicas'\]\.includes\(slug\)[\s\S]{0,180}MedicalClinic/, 'home/contacto/clinicas must require MedicalClinic');
requireMatch(script, /slug === 'endolift'[\s\S]{0,180}MedicalProcedure/, 'Endolift detail must require MedicalProcedure');
requireAbsent(script, /\['endolift', 'laser', 'medicina-estetica'\]\.includes\(slug\)/, 'collection hubs must not be misclassified as MedicalProcedure details');

requireMatch(script, /box\.width < 44 \|\| box\.height < 44/, '44px interaction target contract is required');
requireMatch(script, /size\(rendered\.joinchatButton, 48, 'joinchat-frame-size'\)/, 'Joinchat frame must be 48px');
requireMatch(script, /size\(rendered\.joinchatIcon, 24, 'joinchat-icon-size'\)/, 'Joinchat icon must be 24px');
requireMatch(script, /size\(rendered\.inlineWhatsapp, 16, 'inline-whatsapp-size'\)/, 'inline WhatsApp icon must be 16px');
requireMatch(script, /fs\.writeFileSync\(path\.join\(out, 'report\.json'\)/, 'report.json is required');
requireMatch(script, /fs\.writeFileSync\(path\.join\(out, 'report\.md'\)/, 'report.md is required');
requireMatch(script, /if \(critical\.length\) process\.exit\(1\)/, 'critical findings must fail the process');
requireAbsent(script, /process\.exit\(0\)/, 'the auditor must not mask failures');

requireMatch(workflow, /^\s{2}workflow_dispatch:/m, 'workflow_dispatch is required');
requireAbsent(workflow, /^\s{2}(push|pull_request|schedule):/m, 'rendered QA must stay manual');
requireMatch(workflow, /base_url:[\s\S]{0,140}required: true/, 'base_url must be required');
requireMatch(workflow, /default: 'https:\/\/staging2\.nuvanx\.com'/, 'base_url must default to Staging2');
requireMatch(workflow, /expected_sha:[\s\S]{0,140}required: true/, 'expected_sha must be required');
requireAbsent(workflow, /expected_sha:[\s\S]{0,180}default:/, 'expected_sha must not silently default to an obsolete deployment');
requireMatch(workflow, /contents: read/, 'workflow permissions must remain read-only');
requireMatch(workflow, /persist-credentials: false/, 'checkout credentials must not persist');
requireMatch(workflow, /node-version: '22'/, 'Node.js 22 is required');
requireMatch(workflow, /npm install playwright@1\.54\.1 --no-save/, 'Playwright must be pinned and ephemeral');
requireMatch(workflow, /npx playwright install --with-deps chromium/, 'Chromium installation is required');
requireMatch(workflow, /node scripts\/staging2\/test-rendered-qa-contract\.mjs/, 'workflow must run this contract before browser QA');
requireMatch(workflow, /if-no-files-found: error/, 'missing artifacts must fail loudly');
requireMatch(workflow, /retention-days: 14/, 'artifact retention must remain explicit');
requireMatch(workflow, /AUDIT_EXIT_CODE: \$\{\{ steps\.audit\.outputs\.exit_code \}\}/, 'audit exit code must be enforced');

if (failures.length) {
  console.error('Staging2 rendered QA contract failed:');
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log('PASS: staging2 rendered QA contract (clean capture, SEO hierarchy and workflow)');
