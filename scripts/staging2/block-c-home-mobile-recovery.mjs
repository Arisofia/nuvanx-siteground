import { chromium } from 'playwright';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import {
  EX_NOT_APPLICABLE,
  EX_TEMPFAIL,
  SITEGROUND_CAPTCHA_PATH,
  isSiteGroundTransientResponse,
} from './siteground-transient-classifier.mjs';
import { isIgnorableExternalConsoleError } from './console-error-classifier.mjs';
import {
  BLOCK_C_BROWSER_CONFIG,
  BLOCK_C_BROWSER_UA,
  BLOCK_C_RECOVERY_TARGETS,
  BLOCK_C_VIEWPORTS,
  getCanonicalViewport,
} from './block-c-browser-config.mjs';
import {
  activateLazyImages,
  collectHomeGeometry,
  evaluateHomeVisualContract,
  handleCookieConsent,
  waitForVisualStability,
} from './block-c-browser-visual-contract.mjs';

const moduleDir = path.dirname(fileURLToPath(import.meta.url));
const artifactsDir = path.join(moduleDir, 'block-c-artifacts');
const screenshotDir = path.join(artifactsDir, 'screenshots');
const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedHost = process.env.EXPECTED_HOST || new URL(baseUrl).hostname;
const expectedSha = (process.env.EXPECTED_SHA || '').trim();
const resultsPath = path.join(artifactsDir, 'block-c-results.json');
const recoveryPath = path.join(artifactsDir, 'block-c-home-mobile-recovery.json');
const matrixPath = path.join(artifactsDir, 'block-c-matrix.md');
const summaryPath = path.join(artifactsDir, 'block-c-summary.md');
const csvPath = path.join(artifactsDir, 'block-c-results.csv');
const targetConfig = BLOCK_C_RECOVERY_TARGETS.homeMobile;
const targetViewport = getCanonicalViewport(targetConfig.viewportKey);
const targetRoute = targetConfig.route;
const targetUrl = `${baseUrl}${targetRoute}`;
const screenshotPath = path.join(screenshotDir, `home--${targetViewport.key}--public-recovery.jpg`);

function sanitizeLogValue(value) {
  return String(value ?? '').replace(/\s+/g, '_').slice(0, 500);
}

function logNotApplicable(reason) {
  console.error(`BLOCK_C_HOME_MOBILE_RECOVERY=NOT_APPLICABLE reason=${sanitizeLogValue(reason)}`);
  return EX_NOT_APPLICABLE;
}

async function writeTextAtomic(filePath, content) {
  const tmpPath = `${filePath}.tmp-${process.pid}`;
  await fs.writeFile(tmpPath, content, 'utf8');
  await fs.rename(tmpPath, filePath);
}

async function writeJsonAtomic(filePath, payload) {
  await writeTextAtomic(filePath, `${JSON.stringify(payload, null, 2)}\n`);
}

function isAllowedSiteGroundAbort(message, documentUrl) {
  const normalized = String(message || '').trim();
  if (!/net::ERR_ABORTED/i.test(normalized)) return false;
  const captchaPrefix = `${baseUrl}${SITEGROUND_CAPTCHA_PATH}`;
  return normalized.startsWith(documentUrl) || normalized.startsWith(captchaPrefix);
}

function splitNetworkErrors(networkErrors, documentUrl) {
  const transient = [];
  const real = [];
  for (const message of networkErrors) {
    if (isAllowedSiteGroundAbort(message, documentUrl)) transient.push(message);
    else real.push(message);
  }
  return { transient, real };
}

async function backoff(baseMs, attempt) {
  await new Promise((resolve) => setTimeout(resolve, baseMs * attempt));
}

function buildMatrix(results) {
  const pageOrder = [];
  const pages = new Map();
  const viewportOrder = BLOCK_C_VIEWPORTS.map((viewport) => viewport.key);

  for (const result of results) {
    const route = String(result.route || '');
    if (!pages.has(route)) {
      pages.set(route, {
        pageId: result.pageId,
        route,
        statuses: {},
      });
      pageOrder.push(route);
    }
    pages.get(route).statuses[result.viewport?.key || 'unknown'] = result.status;
  }

  const viewportHeaders = BLOCK_C_VIEWPORTS.map((viewport) => `${viewport.width}×${viewport.height}`);
  const rows = [
    `| # | WP ID | URL | ${viewportHeaders.join(' | ')} |`,
    `|---:|---:|---|${viewportHeaders.map(() => '---:').join('|')}|`,
  ];
  pageOrder.forEach((route, index) => {
    const page = pages.get(route);
    rows.push(`| ${index + 1} | ${page.pageId} | \`${route}\` | ${viewportOrder.map((key) => page.statuses[key] || '—').join(' | ')} |`);
  });
  return rows;
}

async function refreshDerivedEvidence(results) {
  const matrixRows = buildMatrix(results);
  const passCount = results.filter((item) => item.status === 'PASS').length;
  const fixCount = results.filter((item) => item.status === 'FIX').length;
  const blockedCount = results.filter((item) => item.status === 'BLOCKED').length;
  const inconclusive = results.filter((item) => item.externalInconclusive === true);
  const pageCount = new Set(results.map((item) => item.route)).size;
  const viewportLabels = [...new Set(results.map((item) => item.viewport?.label).filter(Boolean))];
  const findings = results.filter((item) => item.status !== 'PASS');

  const summary = [
    '# NUVANX Staging2 — Block C Visual QA',
    '',
    `Expected staging SHA: \`${expectedSha}\``,
    `Published WordPress pages: ${pageCount}`,
    `Viewports: ${viewportLabels.join(', ')}`,
    `Total cases: ${results.length}`,
    `PASS: ${passCount}`,
    `FIX: ${fixCount}`,
    `BLOCKED: ${blockedCount}`,
    `Origin-verified edge-inconclusive cases: ${inconclusive.length}`,
    '',
    '## Matrix',
    '',
    ...matrixRows,
    '',
    '## Findings',
    '',
    '| WP ID | URL | Viewport | Status | Finding | Screenshot |',
    '|---:|---|---|---|---|---|',
    ...(findings.length
      ? findings.map((item) => `| ${item.pageId} | \`${item.route}\` | ${item.viewport?.label || 'unknown'} | ${item.status} | ${[...(item.blockers || []), ...(item.issues || [])].join('; ').replaceAll('|', '\\|')} | \`${item.screenshot || ''}\` |`)
      : ['| — | — | — | PASS | No findings | — |']),
    '',
    '## Public browser recovery',
    '',
    `Home mobile recovery completed with public HTTP 200 and exact deploy SHA \`${expectedSha}\`.`,
    'The recovered case was revalidated for responsive geometry, H1, header/footer, CTAs, images, fonts, mobile navigation, runtime diagnostics and home hero video.',
    '',
  ].join('\n');

  const csvHeader = ['wp_id', 'title', 'route', 'viewport', 'width', 'height', 'status', 'expected_http_status', 'http_status', 'edge_http_status', 'final_url', 'meta_sha', 'external_inconclusive', 'origin_verified', 'origin_status', 'origin_sha', 'origin_robots', 'visual_validation', 'horizontal_overflow_px', 'h1', 'issues', 'notes', 'screenshot'];
  const csvEscape = (value) => `"${String(value ?? '').replaceAll('"', '""').replaceAll('\n', ' ')}"`;
  const csvRows = [csvHeader.map(csvEscape).join(',')];
  for (const item of results) {
    csvRows.push([
      item.pageId,
      item.title,
      item.route,
      item.viewport?.label || '',
      item.viewport?.width || '',
      item.viewport?.height || '',
      item.status,
      item.expectedHttpStatus,
      item.httpStatus,
      item.edgeHttpStatus,
      item.finalUrl,
      item.metaSha,
      item.externalInconclusive,
      item.originVerified,
      item.originStatus ?? '',
      item.originDeploySha,
      item.originRobots,
      item.visualValidation,
      item.geometry?.horizontalOverflowPx ?? '',
      item.geometry?.h1Text ?? '',
      [...(item.blockers || []), ...(item.issues || [])].join('; '),
      (item.notes || []).join('; '),
      item.screenshot,
    ].map(csvEscape).join(','));
  }

  await writeTextAtomic(matrixPath, `${matrixRows.join('\n')}\n`);
  await writeTextAtomic(summaryPath, `${summary}\n`);
  await writeTextAtomic(csvPath, `${csvRows.join('\n')}\n`);
}

async function main() {
  if (expectedHost !== 'staging2.nuvanx.com' || !/^[0-9a-f]{40}$/.test(expectedSha)) {
    return logNotApplicable(`invalid_recovery_identity host=${expectedHost} sha=${expectedSha || 'missing'}`);
  }

  let results;
  try {
    results = JSON.parse(await fs.readFile(resultsPath, 'utf8'));
  } catch (error) {
    return logNotApplicable(`results_unreadable ${error instanceof Error ? error.message : String(error)}`);
  }

  if (!Array.isArray(results) || results.length === 0) {
    return logNotApplicable('results_empty_or_invalid');
  }

  const nonPass = results.filter((result) => result?.status !== 'PASS');
  const inconclusive = results.filter((result) => result?.externalInconclusive === true);
  if (nonPass.length > 0 || inconclusive.length !== 1) {
    return logNotApplicable(`non_pass=${nonPass.length} inconclusive=${inconclusive.length}`);
  }

  const target = inconclusive[0];
  const targetIsExact = target?.route === targetRoute
    && target?.viewport?.key === targetViewport.key
    && target?.visualValidation === 'inconclusive-siteground-antibot'
    && target?.originVerified === true
    && Number(target?.originStatus || 0) === 200
    && String(target?.originDeploySha || '') === expectedSha;

  if (!targetIsExact) {
    return logNotApplicable(`route=${target?.route || 'unknown'} viewport=${target?.viewport?.key || 'unknown'}`);
  }

  await fs.mkdir(screenshotDir, { recursive: true });

  let browser;
  try {
    browser = await chromium.launch({ headless: true, args: ['--no-sandbox', '--disable-setuid-sandbox'] });
  } catch (error) {
    console.error(`BLOCK_C_HOME_MOBILE_RECOVERY=FAIL_TRANSIENT reason=browser_launch error=${sanitizeLogValue(error instanceof Error ? error.message : error)}`);
    return EX_TEMPFAIL;
  }

  const attempts = [];
  let recovered = null;
  let lastVisualFailure = null;

  try {
    for (let attempt = 1; attempt <= BLOCK_C_BROWSER_CONFIG.maxAttempts; attempt += 1) {
      let context = null;
      try {
        context = await browser.newContext({
          viewport: { width: targetViewport.width, height: targetViewport.height },
          screen: { width: targetViewport.width, height: targetViewport.height },
          deviceScaleFactor: 1,
          ignoreHTTPSErrors: true,
          userAgent: BLOCK_C_BROWSER_UA,
          locale: 'es-ES',
          extraHTTPHeaders: {
            'Cache-Control': 'no-cache',
            Pragma: 'no-cache',
            'Accept-Language': 'es-ES,es;q=0.9,en;q=0.8',
          },
        });
        await context.addCookies([{ name: 'wpSGCacheBypass', value: '1', url: targetUrl }]);
        const page = await context.newPage();
        const consoleErrors = [];
        const networkErrors = [];
        const imageHttpErrors = [];
        const productionMediaLeaks = [];

        page.on('console', (msg) => {
          if (msg.type() !== 'error') return;
          const text = msg.text();
          if (!isIgnorableExternalConsoleError(text) && !/Failed to load resource/i.test(text)) consoleErrors.push(text);
        });
        page.on('pageerror', (error) => consoleErrors.push(error.message));
        page.on('requestfailed', (request) => {
          const requestUrl = request.url();
          const failureText = request.failure()?.errorText || 'request failed';
          const resourceType = request.resourceType();
          const expectedMediaAbort = resourceType === 'media' && /ERR_ABORTED/i.test(failureText);
          if (requestUrl.startsWith(baseUrl) && !expectedMediaAbort) networkErrors.push(`${requestUrl}: ${failureText}`);
          if (resourceType === 'image' && requestUrl.startsWith(baseUrl)) imageHttpErrors.push(`${requestUrl}: ${failureText}`);
        });
        page.on('response', (resourceResponse) => {
          const resourceType = resourceResponse.request().resourceType();
          if (resourceType !== 'image' && resourceType !== 'media') return;
          const resourceUrl = resourceResponse.url();
          let parsed;
          try {
            parsed = new URL(resourceUrl);
          } catch {
            return;
          }
          if (parsed.hostname === expectedHost && resourceResponse.status() >= 400) {
            const message = `${resourceUrl}: HTTP ${resourceResponse.status()}`;
            if (resourceType === 'image') imageHttpErrors.push(message);
            else networkErrors.push(message);
          }
          if (
            resourceType === 'image'
            && (parsed.hostname === 'nuvanx.com' || parsed.hostname === 'www.nuvanx.com')
            && parsed.pathname.includes('/wp-content/uploads/')
          ) {
            productionMediaLeaks.push(resourceUrl);
          }
        });

        let response;
        try {
          response = await page.goto(targetUrl, {
            waitUntil: 'domcontentloaded',
            timeout: BLOCK_C_BROWSER_CONFIG.navigationTimeoutMs,
          });
        } catch (error) {
          attempts.push({ attempt, outcome: 'navigation-error', error: error instanceof Error ? error.message : String(error) });
          if (attempt < BLOCK_C_BROWSER_CONFIG.maxAttempts) {
            await backoff(BLOCK_C_BROWSER_CONFIG.navigationErrorBackoffBaseMs, attempt);
          }
          continue;
        }

        const finalUrl = page.url();
        const edgeStatus = response?.status() || 0;
        const responseHeaders = response ? response.headers() : {};
        if (!response || isSiteGroundTransientResponse(edgeStatus, responseHeaders, finalUrl)) {
          attempts.push({ attempt, outcome: 'siteground-transient', edgeHttpStatus: edgeStatus, finalUrl });
          await page.screenshot({
            path: screenshotPath.replace('.jpg', `--challenge-${attempt}.jpg`),
            type: 'jpeg',
            quality: BLOCK_C_BROWSER_CONFIG.screenshotQuality,
            fullPage: true,
          }).catch(() => {});
          if (attempt < BLOCK_C_BROWSER_CONFIG.maxAttempts) {
            await backoff(BLOCK_C_BROWSER_CONFIG.transientBackoffBaseMs, attempt);
          }
          continue;
        }

        const blockers = [];
        if (edgeStatus !== 200) blockers.push(`Expected public HTTP 200, got ${edgeStatus}`);
        if (new URL(finalUrl).hostname !== expectedHost) blockers.push(`Final hostname ${new URL(finalUrl).hostname} != ${expectedHost}`);

        await handleCookieConsent(page);
        await waitForVisualStability(page);
        await activateLazyImages(page);

        const metaSha = (await page.locator('meta[name="nvx-deploy-sha"]').getAttribute('content').catch(() => '')) || '';
        if (metaSha !== expectedSha) blockers.push(`Deployment SHA mismatch: ${metaSha || 'missing'} != ${expectedSha}`);
        const robots = (await page.locator('meta[name="robots"]').getAttribute('content').catch(() => '')) || '';
        const xRobots = responseHeaders['x-robots-tag'] || '';
        if (!robots.toLowerCase().includes('noindex') && !xRobots.toLowerCase().includes('noindex')) {
          blockers.push('Staging noindex protection missing');
        }

        const geometry = await collectHomeGeometry(page);
        const splitErrors = splitNetworkErrors(networkErrors, targetUrl);
        const issues = await evaluateHomeVisualContract({
          page,
          geometry,
          viewport: targetViewport,
          consoleErrors,
          networkErrors: splitErrors.real,
          imageHttpErrors,
        });
        if (productionMediaLeaks.length > 0) {
          issues.push(`Staging media leaked to production host: ${[...new Set(productionMediaLeaks)].slice(0, 8).join(' | ')}`);
        }

        if (splitErrors.transient.length > 0 && blockers.length === 0 && issues.length === 0) {
          attempts.push({
            attempt,
            outcome: 'siteground-network-abort',
            edgeHttpStatus: edgeStatus,
            finalUrl,
            transientNetworkErrors: splitErrors.transient,
          });
          if (attempt < BLOCK_C_BROWSER_CONFIG.maxAttempts) {
            await backoff(BLOCK_C_BROWSER_CONFIG.transientBackoffBaseMs, attempt);
          }
          continue;
        }

        let screenshotError = '';
        try {
          await page.screenshot({
            path: screenshotPath,
            type: 'jpeg',
            quality: BLOCK_C_BROWSER_CONFIG.screenshotQuality,
            fullPage: true,
          });
        } catch (error) {
          screenshotError = error instanceof Error ? error.message : String(error);
        }

        if (screenshotError) {
          attempts.push({ attempt, outcome: 'screenshot-error', edgeHttpStatus: edgeStatus, finalUrl, error: screenshotError });
          if (attempt < BLOCK_C_BROWSER_CONFIG.maxAttempts) {
            await backoff(BLOCK_C_BROWSER_CONFIG.visualRetryBackoffBaseMs, attempt);
          }
          continue;
        }

        attempts.push({
          attempt,
          outcome: blockers.length || issues.length ? 'visual-failure' : 'pass',
          edgeHttpStatus: edgeStatus,
          finalUrl,
          metaSha,
          blockers,
          issues,
          consoleErrors,
          networkErrors: splitErrors.real,
          transientNetworkErrors: splitErrors.transient,
          imageHttpErrors,
          productionMediaLeaks,
        });

        if (blockers.length > 0 || issues.length > 0) {
          lastVisualFailure = {
            attempt,
            edgeHttpStatus: edgeStatus,
            finalUrl,
            metaSha,
            blockers,
            issues,
            geometry,
            consoleErrors,
            networkErrors: splitErrors.real,
            transientNetworkErrors: splitErrors.transient,
            imageHttpErrors,
            productionMediaLeaks,
            screenshot: path.relative(artifactsDir, screenshotPath),
          };
          if (attempt < BLOCK_C_BROWSER_CONFIG.maxAttempts) {
            await backoff(BLOCK_C_BROWSER_CONFIG.visualRetryBackoffBaseMs, attempt);
            continue;
          }
          break;
        }

        recovered = {
          attempt,
          edgeHttpStatus: edgeStatus,
          finalUrl,
          metaSha,
          geometry,
          consoleErrors: [...consoleErrors],
          networkErrors: [...splitErrors.real],
          transientNetworkErrors: [...splitErrors.transient],
          imageHttpErrors: [...imageHttpErrors],
          productionMediaLeaks: [...productionMediaLeaks],
          screenshot: path.relative(artifactsDir, screenshotPath),
        };
        break;
      } catch (error) {
        attempts.push({
          attempt,
          outcome: 'attempt-exception',
          error: error instanceof Error ? error.message : String(error),
        });
        if (attempt < BLOCK_C_BROWSER_CONFIG.maxAttempts) {
          await backoff(BLOCK_C_BROWSER_CONFIG.navigationErrorBackoffBaseMs, attempt);
        }
      } finally {
        if (context) await context.close().catch(() => {});
      }
    }
  } finally {
    await browser.close().catch(() => {});
  }

  if (!recovered) {
    if (lastVisualFailure) {
      await writeJsonAtomic(recoveryPath, {
        schema: 2,
        expectedSha,
        status: 'visual-failure-exhausted',
        target: { route: targetRoute, viewport: targetViewport },
        attempts,
        lastVisualFailure,
      });
      console.error(`BLOCK_C_HOME_MOBILE_RECOVERY=FAIL_REAL reason=${sanitizeLogValue([...(lastVisualFailure.blockers || []), ...(lastVisualFailure.issues || [])].join('; '))}`);
      return 1;
    }

    await writeJsonAtomic(recoveryPath, {
      schema: 2,
      expectedSha,
      status: 'transient-exhausted',
      target: { route: targetRoute, viewport: targetViewport },
      attempts,
    });
    console.error(`BLOCK_C_HOME_MOBILE_RECOVERY=FAIL_TRANSIENT_EXHAUSTED attempts=${attempts.length}`);
    return EX_TEMPFAIL;
  }

  const priorAntibotEvidence = {
    edgeHttpStatus: target.edgeHttpStatus,
    finalUrl: target.finalUrl,
    visualValidation: target.visualValidation,
    notes: Array.isArray(target.notes) ? target.notes : [],
    blockers: Array.isArray(target.blockers) ? target.blockers : [],
    consoleErrors: Array.isArray(target.consoleErrors) ? target.consoleErrors : [],
    networkErrors: Array.isArray(target.networkErrors) ? target.networkErrors : [],
    screenshot: target.screenshot || '',
  };

  const recoveredResults = results.map((result) => {
    if (result !== target) return result;
    return {
      ...result,
      status: 'PASS',
      httpStatus: 200,
      edgeHttpStatus: 200,
      finalUrl: recovered.finalUrl,
      metaSha: recovered.metaSha,
      externalInconclusive: false,
      visualValidation: 'complete-public-browser-recovery',
      validationTransport: 'public-browser-recovery-after-siteground-antibot',
      recoveredFromExternalInconclusive: true,
      recoveryAttempt: recovered.attempt,
      recoveryScreenshot: recovered.screenshot,
      priorAntibotEvidence,
      geometry: recovered.geometry,
      blockers: [],
      issues: [],
      consoleErrors: recovered.consoleErrors,
      networkErrors: recovered.networkErrors,
      transientNetworkErrors: recovered.transientNetworkErrors,
      imageHttpErrors: recovered.imageHttpErrors,
      productionMediaLeaks: recovered.productionMediaLeaks,
      screenshot: recovered.screenshot,
      notes: [`Public browser recovery completed the missing ${targetViewport.label} visual contract after SiteGround Antibot.`],
    };
  });

  await writeJsonAtomic(resultsPath, recoveredResults);
  await refreshDerivedEvidence(recoveredResults);
  await writeJsonAtomic(recoveryPath, {
    schema: 2,
    expectedSha,
    status: 'pass',
    target: { route: targetRoute, viewport: targetViewport },
    attempts,
    recovered,
    priorAntibotEvidence,
  });

  console.log(`BLOCK_C_HOME_MOBILE_RECOVERY=PASS attempt=${recovered.attempt} sha=${expectedSha} visual_contract=complete edge_http=200`);
  return 0;
}

let exitCode = 1;
try {
  exitCode = await main();
} catch (error) {
  console.error(`BLOCK_C_HOME_MOBILE_RECOVERY=FAIL_TRANSIENT reason=unexpected_top_level error=${sanitizeLogValue(error instanceof Error ? error.message : error)}`);
  exitCode = EX_TEMPFAIL;
}
process.exitCode = exitCode;
