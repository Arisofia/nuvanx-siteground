import { chromium } from 'playwright';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import {
  EX_CONFIG,
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
  BlockCConfigError,
  getCanonicalViewport,
} from './block-c-browser-config.mjs';
import {
  activateLazyImages,
  collectHomeGeometry,
  evaluateHomeVisualContract,
  handleCookieConsent,
  waitForVisualStability,
} from './block-c-browser-visual-contract.mjs';
import { renderBlockCEvidence, writeEvidenceBundle } from './block-c-evidence.mjs';

const moduleDir = path.dirname(fileURLToPath(import.meta.url));
const artifactsDir = path.join(moduleDir, 'block-c-artifacts');
const screenshotDir = path.join(artifactsDir, 'screenshots');
const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const baseOrigin = new URL(baseUrl).origin;
const expectedHost = process.env.EXPECTED_HOST || new URL(baseUrl).hostname;
const expectedSha = (process.env.EXPECTED_SHA || '').trim();
const resultsPath = path.join(artifactsDir, 'block-c-results.json');
const recoveryPath = path.join(artifactsDir, 'block-c-home-mobile-recovery.json');
const matrixPath = path.join(artifactsDir, 'block-c-matrix.md');
const summaryPath = path.join(artifactsDir, 'block-c-summary.md');
const csvPath = path.join(artifactsDir, 'block-c-results.csv');
const targetConfig = BLOCK_C_RECOVERY_TARGETS.homeMobile;
const TRANSIENT_RUNNER_ERROR_CODES = new Set([
  'EAGAIN',
  'EBUSY',
  'EMFILE',
  'ENFILE',
  'ENOSPC',
  'EIO',
  'ETIMEDOUT',
  'ECONNRESET',
]);

let targetViewport = null;
let targetViewportConfigError = null;
try {
  targetViewport = getCanonicalViewport(targetConfig.viewportKey);
} catch (error) {
  targetViewportConfigError = error instanceof BlockCConfigError
    ? error
    : new BlockCConfigError(error instanceof Error ? error.message : String(error));
}

const targetRoute = targetConfig.route;
const targetUrl = `${baseUrl}${targetRoute}`;
const screenshotPath = path.join(screenshotDir, `${targetConfig.screenshotStem}.jpg`);

function sanitizeLogValue(value) {
  return String(value ?? '').replace(/\s+/g, '_').slice(0, 500);
}

function logNotApplicable(reason) {
  console.error(`BLOCK_C_HOME_MOBILE_RECOVERY=NOT_APPLICABLE reason=${sanitizeLogValue(reason)}`);
  return EX_NOT_APPLICABLE;
}

function logConfigFailure(reason) {
  console.error(`BLOCK_C_HOME_MOBILE_RECOVERY=FAIL_CONFIG reason=${sanitizeLogValue(reason)}`);
  return EX_CONFIG;
}

function logTransient(reason) {
  console.error(`BLOCK_C_HOME_MOBILE_RECOVERY=FAIL_TRANSIENT reason=${sanitizeLogValue(reason)}`);
  return EX_TEMPFAIL;
}

async function writeJsonAtomic(filePath, payload) {
  const tmpPath = `${filePath}.tmp-${process.pid}`;
  try {
    await fs.writeFile(tmpPath, `${JSON.stringify(payload, null, 2)}\n`, 'utf8');
    await fs.rename(tmpPath, filePath);
  } finally {
    await fs.rm(tmpPath, { force: true }).catch(() => {});
  }
}

function requestUrlFromErrorMessage(message) {
  const normalized = String(message || '').trim();
  const separator = normalized.lastIndexOf(': ');
  return separator >= 0 ? normalized.slice(0, separator) : normalized;
}

function isSameDocumentPath(requestUrl, documentUrl) {
  try {
    const request = new URL(requestUrl);
    const document = new URL(documentUrl);
    return request.origin === document.origin && request.pathname === document.pathname;
  } catch {
    return false;
  }
}

function isAllowedSiteGroundAbort(message, documentUrl) {
  const normalized = String(message || '').trim();
  if (!/net::ERR_ABORTED/i.test(normalized)) return false;
  const requestUrl = requestUrlFromErrorMessage(normalized);
  const captchaPrefix = `${baseUrl}${SITEGROUND_CAPTCHA_PATH}`;
  return isSameDocumentPath(requestUrl, documentUrl) || requestUrl.startsWith(captchaPrefix);
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

function isStagingOriginUrl(value) {
  try {
    return new URL(value).origin === baseOrigin;
  } catch {
    return false;
  }
}

function isLikelyTransientRunnerError(error) {
  const code = String(error?.code || '');
  return TRANSIENT_RUNNER_ERROR_CODES.has(code);
}

async function backoff(baseMs, attempt) {
  await new Promise((resolve) => setTimeout(resolve, baseMs * attempt));
}

async function main() {
  if (targetViewportConfigError || !targetViewport) {
    return logConfigFailure(`invalid_recovery_viewport key=${targetConfig.viewportKey} error=${targetViewportConfigError?.message || 'unresolved'}`);
  }
  if (expectedHost !== 'staging2.nuvanx.com' || !/^[0-9a-f]{40}$/.test(expectedSha)) {
    return logConfigFailure(`invalid_recovery_identity host=${expectedHost} sha=${expectedSha || 'missing'}`);
  }

  let results;
  try {
    results = JSON.parse(await fs.readFile(resultsPath, 'utf8'));
  } catch (error) {
    return logTransient(`results_unreadable ${error instanceof Error ? error.message : String(error)}`);
  }
  if (!Array.isArray(results) || results.length === 0) return logTransient('results_empty_or_invalid');

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
    return logTransient(`browser_launch ${error instanceof Error ? error.message : error}`);
  }

  const attempts = [];
  let recovered = null;
  let lastVisualFailure = null;
  let lastAttemptOutcome = '';

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
          if (isStagingOriginUrl(requestUrl) && !expectedMediaAbort) networkErrors.push(`${requestUrl}: ${failureText}`);
          if (resourceType === 'image' && isStagingOriginUrl(requestUrl)) imageHttpErrors.push(`${requestUrl}: ${failureText}`);
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
          if (isStagingOriginUrl(resourceUrl) && resourceResponse.status() >= 400) {
            const message = `${resourceUrl}: HTTP ${resourceResponse.status()}`;
            if (resourceType === 'image') imageHttpErrors.push(message);
            else networkErrors.push(message);
          }
          if (
            (resourceType === 'image' || resourceType === 'media')
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
          lastAttemptOutcome = 'navigation-error';
          attempts.push({ attempt, outcome: lastAttemptOutcome, error: error instanceof Error ? error.message : String(error) });
          if (attempt < BLOCK_C_BROWSER_CONFIG.maxAttempts) {
            await backoff(BLOCK_C_BROWSER_CONFIG.navigationErrorBackoffBaseMs, attempt);
          }
          continue;
        }

        const finalUrl = page.url();
        const edgeStatus = response?.status() || 0;
        const responseHeaders = response ? response.headers() : {};
        if (!response || isSiteGroundTransientResponse(edgeStatus, responseHeaders, finalUrl)) {
          lastAttemptOutcome = 'siteground-transient';
          attempts.push({ attempt, outcome: lastAttemptOutcome, edgeHttpStatus: edgeStatus, finalUrl });
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

        await handleCookieConsent(page, BLOCK_C_BROWSER_CONFIG);
        await waitForVisualStability(page, BLOCK_C_BROWSER_CONFIG);
        await activateLazyImages(page, BLOCK_C_BROWSER_CONFIG);

        const metaSha = (await page.locator('meta[name="nvx-deploy-sha"]').getAttribute('content').catch(() => '')) || '';
        if (metaSha !== expectedSha) blockers.push(`Deployment SHA mismatch: ${metaSha || 'missing'} != ${expectedSha}`);
        const robots = (await page.locator('meta[name="robots"]').getAttribute('content').catch(() => '')) || '';
        const xRobots = responseHeaders['x-robots-tag'] || '';
        if (!robots.toLowerCase().includes('noindex') && !xRobots.toLowerCase().includes('noindex')) {
          blockers.push('Staging noindex protection missing');
        }

        const geometry = await collectHomeGeometry(page, BLOCK_C_BROWSER_CONFIG);
        const splitErrors = splitNetworkErrors(networkErrors, targetUrl);
        const issues = await evaluateHomeVisualContract({
          page,
          geometry,
          viewport: targetViewport,
          consoleErrors,
          networkErrors: splitErrors.real,
          imageHttpErrors,
          config: BLOCK_C_BROWSER_CONFIG,
        });
        if (productionMediaLeaks.length > 0) {
          issues.push(`Staging media leaked to production host: ${[...new Set(productionMediaLeaks)].slice(0, BLOCK_C_BROWSER_CONFIG.errorPreviewLimit).join(' | ')}`);
        }

        if (splitErrors.transient.length > 0 && blockers.length === 0 && issues.length === 0) {
          lastAttemptOutcome = 'siteground-network-abort';
          attempts.push({
            attempt,
            outcome: lastAttemptOutcome,
            edgeHttpStatus: edgeStatus,
            finalUrl,
            transientNetworkErrors: [...splitErrors.transient],
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
          lastAttemptOutcome = 'screenshot-error';
          attempts.push({ attempt, outcome: lastAttemptOutcome, edgeHttpStatus: edgeStatus, finalUrl, error: screenshotError });
          if (attempt < BLOCK_C_BROWSER_CONFIG.maxAttempts) {
            await backoff(BLOCK_C_BROWSER_CONFIG.visualRetryBackoffBaseMs, attempt);
          }
          continue;
        }

        lastAttemptOutcome = blockers.length || issues.length ? 'visual-failure' : 'pass';
        attempts.push({
          attempt,
          outcome: lastAttemptOutcome,
          edgeHttpStatus: edgeStatus,
          finalUrl,
          metaSha,
          blockers: [...blockers],
          issues: [...issues],
          consoleErrors: [...consoleErrors],
          networkErrors: [...splitErrors.real],
          transientNetworkErrors: [...splitErrors.transient],
          imageHttpErrors: [...imageHttpErrors],
          productionMediaLeaks: [...productionMediaLeaks],
        });

        if (blockers.length > 0 || issues.length > 0) {
          lastVisualFailure = {
            attempt,
            edgeHttpStatus: edgeStatus,
            finalUrl,
            metaSha,
            blockers: [...blockers],
            issues: [...issues],
            geometry,
            consoleErrors: [...consoleErrors],
            networkErrors: [...splitErrors.real],
            transientNetworkErrors: [...splitErrors.transient],
            imageHttpErrors: [...imageHttpErrors],
            productionMediaLeaks: [...productionMediaLeaks],
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
        lastAttemptOutcome = 'attempt-exception';
        attempts.push({
          attempt,
          outcome: lastAttemptOutcome,
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
        schema: 4,
        expectedSha,
        status: 'visual-failure-exhausted',
        target: { route: targetRoute, viewport: targetViewport },
        attempts,
        lastVisualFailure,
        finalAttemptOutcome: lastAttemptOutcome,
      });
      console.error(`BLOCK_C_HOME_MOBILE_RECOVERY=FAIL_REAL reason=${sanitizeLogValue([...(lastVisualFailure.blockers || []), ...(lastVisualFailure.issues || [])].join('; '))}`);
      return 1;
    }

    await writeJsonAtomic(recoveryPath, {
      schema: 4,
      expectedSha,
      status: 'transient-exhausted',
      target: { route: targetRoute, viewport: targetViewport },
      attempts,
      finalAttemptOutcome: lastAttemptOutcome,
    });
    console.error(`BLOCK_C_HOME_MOBILE_RECOVERY=FAIL_TRANSIENT_EXHAUSTED attempts=${attempts.length} final_outcome=${lastAttemptOutcome || 'unknown'}`);
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
    imageHttpErrors: Array.isArray(target.imageHttpErrors) ? target.imageHttpErrors : [],
    productionMediaLeaks: Array.isArray(target.productionMediaLeaks) ? target.productionMediaLeaks : [],
    screenshot: target.screenshot || '',
  };

  const recoveryNote = `Public browser recovery completed the missing ${targetViewport.label} visual contract after SiteGround Antibot.`;
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
      notes: [...(Array.isArray(result.notes) ? result.notes : []), recoveryNote],
    };
  });

  const derived = renderBlockCEvidence(recoveredResults, {
    expectedSha,
    recoverySummary: [
      `Home mobile recovery completed with public HTTP 200 and exact deploy SHA \`${expectedSha}\`.`,
      'The recovered case was revalidated for responsive geometry, H1, header/footer, CTAs, images, fonts, mobile navigation, runtime diagnostics and home hero video.',
    ],
  });
  const recoveryEvidence = {
    schema: 4,
    expectedSha,
    status: 'pass',
    target: { route: targetRoute, viewport: targetViewport },
    attempts,
    recovered,
    priorAntibotEvidence,
  };

  await writeEvidenceBundle([
    [matrixPath, derived.matrix],
    [summaryPath, derived.summary],
    [csvPath, derived.csv],
    [resultsPath, `${JSON.stringify(recoveredResults, null, 2)}\n`],
    [recoveryPath, `${JSON.stringify(recoveryEvidence, null, 2)}\n`],
  ]);

  console.log(`BLOCK_C_HOME_MOBILE_RECOVERY=PASS attempt=${recovered.attempt} sha=${expectedSha} visual_contract=complete edge_http=200`);
  return 0;
}

let exitCode = 1;
try {
  exitCode = await main();
} catch (error) {
  if (isLikelyTransientRunnerError(error)) {
    console.error(`BLOCK_C_HOME_MOBILE_RECOVERY=FAIL_TRANSIENT reason=unexpected_runner_io error=${sanitizeLogValue(error instanceof Error ? error.message : error)}`);
    exitCode = EX_TEMPFAIL;
  } else {
    console.error(`BLOCK_C_HOME_MOBILE_RECOVERY=FAIL_REAL reason=unexpected_top_level error=${sanitizeLogValue(error instanceof Error ? error.message : error)}`);
    exitCode = 1;
  }
}
process.exitCode = exitCode;
