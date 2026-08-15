import { chromium } from 'playwright';
import AxeBuilder from '@axe-core/playwright';
import fs from 'node:fs/promises';
import path from 'node:path';
import {
  SITEGROUND_CAPTCHA_PATH,
  EX_TEMPFAIL,
  isSiteGroundTransientResponse,
} from './siteground-transient-classifier.mjs';
import { createSiteGroundOriginVerifier } from './siteground-origin-verifier.mjs';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedSha = (process.env.EXPECTED_SHA || '').trim();
const expectedHost = new URL(baseUrl).hostname;
const maxAttempts = 3;

if (!/^[0-9a-f]{40}$/.test(expectedSha)) {
  console.error('BLOCK_A11Y=FAIL_REAL reason=EXPECTED_SHA_must_be_40_hex');
  process.exit(1);
}
if (expectedHost !== 'staging2.nuvanx.com') {
  console.error(`BLOCK_A11Y=FAIL_REAL reason=unexpected_host host=${expectedHost}`);
  process.exit(1);
}

const originVerifier = createSiteGroundOriginVerifier({ expectedHost, expectedSha });

const viewports = [
  { key: 'desktop', width: 1440, height: 1100 },
  { key: 'tablet', width: 1024, height: 768 },
  { key: 'mobile', width: 390, height: 844 },
];

const outDir = path.resolve('scripts/staging2/block-a11y-artifacts');
await fs.mkdir(outDir, { recursive: true });

const criticalRoutes = [
  '/',
  '/politica-privacidad/',
  '/politica-de-cookies/',
  '/aviso-legal/',
  '/contacto/',
  '/blog/',
  '/medicina-estetica/',
  '/madrid/valoracion/',
];

const wcagTags = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'];
const disabledRules = [
  'color-contrast',
  'skip-link',
  'region',
];

async function handleCookieConsent(page) {
  const selectors = [
    'button:has-text("Aceptar todo")',
    'button:has-text("Aceptar")',
    'button:has-text("Accept all")',
    'button:has-text("Accept")',
    '.cmplz-accept',
    '#cmplz-cookiebanner-container button.cmplz-accept',
  ];
  for (const selector of selectors) {
    try {
      const button = page.locator(selector).first();
      if (await button.isVisible({ timeout: 350 })) {
        await button.click({ timeout: 1500 });
        await page.waitForTimeout(150);
        return;
      }
    } catch {
      // Continue.
    }
  }
}

async function waitForVisualStability(page) {
  await page.evaluate(async () => {
    if (document.fonts) await document.fonts.ready;
  }).catch(() => {});
  await page.waitForTimeout(400);
}

async function checkComplianzAnchors(page) {
  const complianzAnchors = await page.locator('.cmplz-link, a[href*="complianz"]').all();
  const issues = [];

  for (const anchor of complianzAnchors) {
    const text = await anchor.textContent().catch(() => '');
    const accessibleName = await anchor.getAttribute('aria-label').catch(() => null);
    const href = await anchor.getAttribute('href').catch(() => '');

    if (text.includes('{title}') || text.includes('{url}')) {
      issues.push(`Complianz anchor contains unreplaced template token: ${text.trim()}`);
    }
    if (!accessibleName && !text.trim()) {
      issues.push(`Complianz anchor has no accessible name or text: ${href}`);
    }
  }

  return issues;
}

async function runA11yAudit(page, route, viewport) {
  const results = await new AxeBuilder({ page })
    .withTags(wcagTags)
    .disableRules(disabledRules)
    .analyze();

  const violations = results.violations || [];
  const criticalViolations = violations.filter((v) => v.impact === 'critical' || v.impact === 'serious');
  const moderateViolations = violations.filter((v) => v.impact === 'moderate');

  return {
    route,
    viewport,
    totalViolations: violations.length,
    criticalViolations: criticalViolations.length,
    moderateViolations: moderateViolations.length,
    violations: violations.map((v) => ({
      id: v.id,
      impact: v.impact,
      description: v.description,
      help: v.help,
      helpUrl: v.helpUrl,
      nodes: v.nodes.slice(0, 3).map((n) => ({
        target: n.target,
        failureSummary: n.failureSummary,
      })),
    })),
  };
}

async function installOriginDocumentFallback(page, route) {
  if (!originVerifier.isAvailable()) {
    return { pass: false, transient: true, reason: 'SiteGround origin SSH unavailable' };
  }

  const origin = originVerifier.fetchHtml(route);
  if (!origin.pass) {
    const details = origin.stderr || origin.error || `origin status ${origin.originStatus ?? 0}`;
    return {
      pass: false,
      transient: origin.transportFailure === true,
      reason: `Origin HTML verification failed: ${details}`,
    };
  }
  if (origin.originStatus !== 200 || origin.originDeploySha !== expectedSha || !origin.html) {
    return {
      pass: false,
      transient: false,
      reason: `Origin HTML contract mismatch: status=${origin.originStatus ?? 0} sha=${origin.originDeploySha || 'missing'} bytes=${Buffer.byteLength(origin.html || '', 'utf8')}`,
    };
  }

  const targetUrl = `${baseUrl}${route}`;
  await page.route(targetUrl, async (routeHandle) => {
    if (!routeHandle.request().isNavigationRequest()) {
      await routeHandle.continue();
      return;
    }
    await routeHandle.fulfill({
      status: 200,
      contentType: 'text/html; charset=utf-8',
      headers: {
        'cache-control': 'no-store',
        'x-nvx-validation-transport': 'siteground-origin-document',
      },
      body: origin.html,
    });
  }, { times: 1 });

  return { pass: true, transient: false, origin };
}

async function validateRoute(context, route, viewport, attempt, { useOriginDocument = false } = {}) {
  const page = await context.newPage();
  const url = `${baseUrl}${route}`;
  let originFallback = null;

  try {
    if (useOriginDocument) {
      originFallback = await installOriginDocumentFallback(page, route);
      if (!originFallback.pass) {
        return {
          transient: originFallback.transient,
          route,
          viewport,
          attempt,
          validationTransport: 'siteground-origin-document',
          reason: originFallback.reason,
        };
      }
    }

    let response = null;
    let navError = null;

    try {
      response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 40000 });
    } catch (error) {
      navError = error;
    }

    const currentUrl = page.url() || '';

    if (navError) {
      const navMessage = navError instanceof Error ? navError.message : String(navError);
      if (!useOriginDocument && currentUrl.includes(SITEGROUND_CAPTCHA_PATH)) {
        return {
          transient: true,
          route,
          viewport,
          attempt,
          reason: `SiteGround captcha challenge: ${currentUrl}`,
        };
      }
      return {
        transient: useOriginDocument ? false : true,
        route,
        viewport,
        attempt,
        validationTransport: useOriginDocument ? 'siteground-origin-document' : 'public-edge',
        reason: `Navigation failed: ${navMessage}`,
      };
    }

    const headers = response ? await response.allHeaders() : {};
    const status = response?.status() || 0;
    const isTransientStatus = !useOriginDocument && isSiteGroundTransientResponse(status, headers, currentUrl);

    if (!useOriginDocument && (currentUrl.includes(SITEGROUND_CAPTCHA_PATH) || isTransientStatus)) {
      return {
        transient: true,
        route,
        viewport,
        attempt,
        validationTransport: 'public-edge',
        reason: `SiteGround transient response: HTTP ${status}`,
      };
    }

    if (status !== 200) {
      return {
        transient: false,
        route,
        viewport,
        attempt,
        validationTransport: useOriginDocument ? 'siteground-origin-document' : 'public-edge',
        reason: `Expected HTTP 200, got ${status}`,
      };
    }

    const metaSha = (await page.locator('meta[name="nvx-deploy-sha"]').getAttribute('content').catch(() => '')) || '';
    if (metaSha !== expectedSha) {
      return {
        transient: false,
        route,
        viewport,
        attempt,
        validationTransport: useOriginDocument ? 'siteground-origin-document' : 'public-edge',
        reason: `SHA mismatch: ${metaSha || 'missing'} != ${expectedSha}`,
      };
    }

    await handleCookieConsent(page);
    await waitForVisualStability(page);

    const complianzIssues = await checkComplianzAnchors(page);
    const a11yResults = await runA11yAudit(page, route, viewport);

    return {
      transient: false,
      route,
      viewport,
      attempt,
      validationTransport: useOriginDocument ? 'siteground-origin-document' : 'public-edge',
      originVerified: useOriginDocument,
      originStatus: useOriginDocument ? originFallback?.origin?.originStatus : undefined,
      originDeploySha: useOriginDocument ? originFallback?.origin?.originDeploySha : undefined,
      complianzIssues,
      ...a11yResults,
    };
  } finally {
    await page.close().catch(() => {});
  }
}

async function runViewport(browser, viewport) {
  const context = await browser.newContext({
    viewport: { width: viewport.width, height: viewport.height },
    ignoreHTTPSErrors: true,
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/151 Safari/537.36 NUVANX-A11Y-QA/1.0',
  });

  try {
    const results = [];

    for (const route of criticalRoutes) {
      let finalResult = null;

      for (let attempt = 1; attempt <= maxAttempts; attempt += 1) {
        console.log(`BLOCK_A11Y_AUDIT route=${route} viewport=${viewport.key} attempt=${attempt}/${maxAttempts}`);
        const result = await validateRoute(context, route, viewport, attempt);

        if (result.transient) {
          console.warn(`BLOCK_A11Y_TRANSIENT route=${route} viewport=${viewport.key} attempt=${attempt} reason=${result.reason}`);
          if (attempt < maxAttempts) {
            const backoff = 2500 * attempt;
            console.log(`BLOCK_A11Y_BACKOFF route=${route} viewport=${viewport.key} delay_ms=${backoff}`);
            await new Promise((resolve) => setTimeout(resolve, backoff));
            continue;
          }

          console.warn(`BLOCK_A11Y_ORIGIN_FALLBACK=ATTEMPT route=${route} viewport=${viewport.key} public_attempts=${maxAttempts}`);
          const originResult = await validateRoute(context, route, viewport, attempt, { useOriginDocument: true });
          if (!originResult.transient && !originResult.reason) {
            console.log(`BLOCK_A11Y_ORIGIN_FALLBACK=PASS route=${route} viewport=${viewport.key} sha=${originResult.originDeploySha}`);
          } else {
            console.warn(`BLOCK_A11Y_ORIGIN_FALLBACK=${originResult.transient ? 'TRANSIENT' : 'FAIL_REAL'} route=${route} viewport=${viewport.key} reason=${originResult.reason || 'unknown'}`);
          }
          finalResult = originResult;
          break;
        }

        finalResult = result;
        break;
      }

      if (finalResult) results.push(finalResult);
    }

    return results;
  } finally {
    await context.close();
  }
}

const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });
const allResults = [];
let realFailure = false;
let transientExhausted = false;

try {
  for (const viewport of viewports) {
    const viewportResults = await runViewport(browser, viewport);
    allResults.push(...viewportResults);

    for (const result of viewportResults) {
      if (result.transient) {
        transientExhausted = true;
      } else if (result.reason && result.totalViolations === undefined) {
        realFailure = true;
      } else if (result.criticalViolations > 0 || result.moderateViolations > 5 || result.complianzIssues?.length > 0) {
        realFailure = true;
      }
    }
  }
} finally {
  await browser.close().catch(() => {});
  try {
    await fs.writeFile(path.join(outDir, 'results.json'), `${JSON.stringify(allResults, null, 2)}\n`, 'utf8');
  } catch (writeErr) {
    console.error(`Failed to write results.json: ${writeErr instanceof Error ? writeErr.message : String(writeErr)}`);
  }
}

for (const result of allResults) {
  if (result.transient) {
    console.warn(`TRANSIENT route=${result.route} viewport=${result.viewport.key} reason=${result.reason}`);
  } else if (result.reason) {
    console.error(`FAIL route=${result.route} viewport=${result.viewport.key} reason=${result.reason}`);
  } else {
    if (result.criticalViolations > 0) {
      console.error(`CRITICAL route=${result.route} viewport=${result.viewport.key} violations=${result.criticalViolations}`);
      for (const v of result.violations.filter((v) => v.impact === 'critical' || v.impact === 'serious')) {
        console.error(`  ${v.id}: ${v.help}`);
        for (const node of v.nodes) console.error(`    Target: ${node.target.join(', ')}`);
      }
    }
    if (result.moderateViolations > 5) {
      console.warn(`MODERATE route=${result.route} viewport=${result.viewport.key} violations=${result.moderateViolations}`);
    }
    if (result.complianzIssues?.length > 0) {
      console.error(`COMPLIANZ route=${result.route} viewport=${result.viewport.key} issues=${result.complianzIssues.length}`);
      for (const issue of result.complianzIssues) console.error(`  ${issue}`);
    }
    if (result.criticalViolations === 0 && result.moderateViolations <= 5 && (!result.complianzIssues || result.complianzIssues.length === 0)) {
      console.log(`PASS route=${result.route} viewport=${result.viewport.key} violations=${result.totalViolations} transport=${result.validationTransport || 'public-edge'}`);
    }
  }
}

if (realFailure) {
  if (process.env.GITHUB_STEP_SUMMARY) {
    const summary = [
      '',
      '### ❌ Block A11y — Real Failure',
      '> **Accessibility audit found a real application failure:**',
      ...allResults
        .filter((r) => !r.transient && (r.reason || r.criticalViolations > 0 || r.moderateViolations > 5 || r.complianzIssues?.length > 0))
        .flatMap((r) => [
          `- **Route:** \`${r.route}\` | **Viewport:** \`${r.viewport.key}\` | **Transport:** \`${r.validationTransport || 'public-edge'}\``,
          ...(r.reason ? [`  - 🔴 ${r.reason}`] : []),
          ...(r.criticalViolations > 0 ? [`  - 🔴 Critical violations: ${r.criticalViolations}`] : []),
          ...(r.moderateViolations > 5 ? [`  - 🟡 Moderate violations: ${r.moderateViolations}`] : []),
          ...(r.complianzIssues?.length > 0 ? [`  - 🔴 Complianz issues: ${r.complianzIssues.length}`] : []),
        ]),
      '',
    ].join('\n');
    await fs.appendFile(process.env.GITHUB_STEP_SUMMARY, summary, 'utf8').catch(() => {});
  }
  console.error('BLOCK_A11Y=FAIL_REAL');
  process.exit(1);
}

if (transientExhausted) {
  if (process.env.GITHUB_ENV) {
    await fs.appendFile(process.env.GITHUB_ENV, 'BLOCK_A11Y_TRANSIENT=1\n', 'utf8').catch(() => {});
  }
  if (process.env.GITHUB_STEP_SUMMARY) {
    const summary = [
      '',
      '### ⚠️ Block A11y — Transient Exhausted',
      '> **Both the public edge and the exact-SHA SiteGround origin were unavailable for at least one accessibility case.**',
      '- No production-eligible completion marker is allowed.',
      '',
    ].join('\n');
    await fs.appendFile(process.env.GITHUB_STEP_SUMMARY, summary, 'utf8').catch(() => {});
  }
  console.error('BLOCK_A11Y=TRANSIENT_ONLY');
  process.exit(EX_TEMPFAIL);
}

console.log('BLOCK_A11Y=PASS');
