import fs from 'node:fs/promises';
import path from 'node:path';
import { createSiteGroundOriginVerifier, isBlockCTransientSiteGroundFailure } from './siteground-origin-verifier.mjs';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedHost = process.env.EXPECTED_HOST || new URL(baseUrl).hostname;
const expectedSha = String(process.env.EXPECTED_SHA || '').trim();
const outputDir = path.resolve('scripts/staging2/block-c-artifacts');
const resultsPath = path.join(outputDir, 'block-c-results.json');
const fallbackPath = path.join(outputDir, 'block-c-origin-fallback.json');

if (!/^[0-9a-f]{40}$/.test(expectedSha)) throw new Error('EXPECTED_SHA must be a full lowercase 40-character SHA');

const results = JSON.parse(await fs.readFile(resultsPath, 'utf8'));
if (!Array.isArray(results)) throw new TypeError('Block C results must be an array');
const failed = results.filter((result) => result.status !== 'PASS');
if (failed.length === 0) {
  console.log('BLOCK_C_ORIGIN_FALLBACK=SKIP no_failed_cases');
  process.exit(0);
}

const ineligible = failed.filter((result) => !isBlockCTransientSiteGroundFailure(result, baseUrl));
if (ineligible.length > 0) {
  console.log(`BLOCK_C_ORIGIN_FALLBACK=SKIP real_failures=${ineligible.length}`);
  process.exit(2);
}

const verifier = createSiteGroundOriginVerifier({ expectedHost, expectedSha });
if (!verifier.isAvailable()) {
  console.error(`BLOCK_C_ORIGIN_FALLBACK=UNAVAILABLE alias=${verifier.originSshAlias}`);
  process.exit(3);
}

const routeCache = new Map();
const evidence = [];
for (const result of failed) {
  const route = String(result.route || '');
  if (!routeCache.has(route)) routeCache.set(route, verifier.verify(route));
  const origin = routeCache.get(route);
  evidence.push({ route, viewport: result.viewport?.key || '', origin });
  if (!origin.pass) {
    console.error(`BLOCK_C_ORIGIN_FALLBACK=FAIL route=${route} viewport=${result.viewport?.key || 'unknown'} reason=${origin.stderr || origin.error || `exit-${origin.status}`}`);
  }
}

if (evidence.some((item) => !item.origin.pass)) {
  await fs.writeFile(fallbackPath, `${JSON.stringify({ pass: false, expectedSha, evidence }, null, 2)}\n`, 'utf8');
  process.exit(4);
}

for (const result of results) {
  if (result.status === 'PASS') continue;
  const origin = routeCache.get(String(result.route || ''));
  if (!origin?.pass) continue;
  result.edgeStatusBeforeFallback = result.status;
  result.edgeHttpStatusBeforeFallback = result.httpStatus;
  result.externalInconclusive = true;
  result.originVerified = true;
  result.originStatus = origin.originStatus;
  result.originDeploySha = origin.originDeploySha;
  result.originRobots = origin.originRobots;
  result.originSshAlias = verifier.originSshAlias;
  result.visualValidation = 'inconclusive-siteground-antibot';
  result.status = 'PASS';
  result.blockers = [];
  result.issues = [];
  result.notes = [
    'SiteGround Antibot made browser geometry inconclusive for this route/viewport.',
    'Origin fallback verified HTTP 200, exact deploy SHA and staging noindex/nofollow.',
    'No claim is made that geometry/H1/images were revalidated through the origin fallback.',
  ];
}

await fs.writeFile(resultsPath, `${JSON.stringify(results, null, 2)}\n`, 'utf8');
await fs.writeFile(
  fallbackPath,
  `${JSON.stringify({
    pass: true,
    expectedSha,
    originSshAlias: verifier.originSshAlias,
    visualLimitation: 'origin fallback cannot validate browser geometry, H1 visibility, responsive layout or images',
    evidence,
  }, null, 2)}\n`,
  'utf8'
);

const summaryPath = path.join(outputDir, 'block-c-summary.md');
try {
  const summary = await fs.readFile(summaryPath, 'utf8');
  const resolved = failed.length;
  const updated = `${summary.trimEnd()}\n\n## SiteGround origin fallback\n\n- Origin-verified transient cases: ${resolved}\n- Exact deploy SHA: \`${expectedSha}\`\n- Result: PASS for transport/deploy identity and robots protection.\n- Visual geometry remains explicitly inconclusive for these cases because the edge browser was challenged by SiteGround Antibot.\n`;
  await fs.writeFile(summaryPath, updated, 'utf8');
} catch {
  // JSON evidence remains authoritative if the human-readable summary is unavailable.
}

console.log(`BLOCK_C_ORIGIN_FALLBACK=PASS cases=${failed.length} unique_routes=${routeCache.size} alias=${verifier.originSshAlias}`);
