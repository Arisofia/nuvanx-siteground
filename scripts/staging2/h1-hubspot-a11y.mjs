import { chromium } from 'playwright';
import fs from 'node:fs/promises';
import path from 'node:path';
import { setTimeout as delay } from 'node:timers/promises';
import { classifyHubSpotSubmissionRequest } from './hubspot-submission-classifier.mjs';
import {
  EX_TEMPFAIL,
  SITEGROUND_CAPTCHA_PATH,
  isSiteGroundCaptchaInterruption,
  isSiteGroundTransientResponse,
} from './siteground-transient-classifier.mjs';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedSha = (process.env.EXPECTED_SHA || '').trim();
const portalId = '147416356';
const formId = '5042522a-0bc5-4381-ac3e-5aee8649b69c';
const target = `${baseUrl}/madrid/valoracion/`;
const maxAttempts = 3;
const outDir = path.resolve('scripts/staging2/valoracion-artifacts');
const outFile = path.join(outDir, 'hubspot-a11y.json');

if (!/^[0-9a-f]{40}$/.test(expectedSha)) {
  console.error('HUBSPOT_A11Y=FAIL_REAL reason=EXPECTED_SHA_must_be_40_hex');
  process.exit(1);
}

await fs.mkdir(outDir, { recursive: true });

const normalizeText = (value) => String(value || '').replace(/\s+/g, ' ').trim();
const transientResult = (attempt, reason) => ({ transient: true, attempt, reason });
const realFailureResult = (attempt, reason, details = {}) => ({
  transient: false,
  realFailure: true,
  attempt,
  reason,
  ...details,
});

function controlIdentity(control) {
  return control?.uid || control?.name || control?.id || `${control?.tag || 'control'}:${control?.type || 'unknown'}`;
}

async function grantConsent(page) {
  await page.evaluate(() => {
    if (typeof window.wp_set_consent !== 'function') return;
    window.wp_set_consent('marketing', 'allow');
    document.dispatchEvent(new Event('wp_listen_for_consent_change'));
    document.dispatchEvent(new Event('wp_consent_type_defined'));
  }).catch(() => {});
}

async function navigateExpectedDocument(page, attempt) {
  let response;
  try {
    response = await page.goto(target, { waitUntil: 'domcontentloaded', timeout: 45000 });
  } catch (error) {
    const currentUrl = page.url() || target;
    if (isSiteGroundCaptchaInterruption(error, currentUrl)) {
      return transientResult(attempt, `siteground_navigation_challenge:${error.message}`);
    }
    return realFailureResult(attempt, `navigation_failed:${error instanceof Error ? error.message : String(error)}`);
  }

  const status = Number(response?.status() || 0);
  const headers = response?.headers() || {};
  const currentUrl = page.url() || target;
  if (isSiteGroundTransientResponse(status, headers, currentUrl) || currentUrl.includes(SITEGROUND_CAPTCHA_PATH)) {
    return transientResult(attempt, `siteground_http_challenge:${status}:${currentUrl}`);
  }
  if (status !== 200 || new URL(currentUrl).pathname !== '/madrid/valoracion/') {
    return realFailureResult(attempt, `unexpected_document:status=${status}:url=${currentUrl}`);
  }

  const deploySha = normalizeText(
    await page.locator('meta[name="nvx-deploy-sha"]').first().getAttribute('content').catch(() => '')
  );
  if (deploySha !== expectedSha) {
    return realFailureResult(attempt, `sha_mismatch:${deploySha || 'missing'}:${expectedSha}`);
  }

  return { transient: false, realFailure: false, attempt, deploySha };
}

async function waitForMeaningfulIframeTitle(iframe) {
  let title = '';
  for (let poll = 0; poll < 10; poll += 1) {
    title = normalizeText(await iframe.getAttribute('title').catch(() => ''));
    if (title && !/^(form|hubspot form|hs-form-iframe)$/i.test(title)) return title;
    await delay(500);
  }
  return title;
}

async function resolveHubSpot(page, attempt) {
  await grantConsent(page);
  const host = page.locator('#nvx-hubspot-form');
  await host.scrollIntoViewIfNeeded().catch(() => {});
  await host.dispatchEvent('focusin').catch(() => {});
  await page.waitForLoadState('load', { timeout: 10000 }).catch(() => {});

  const iframe = page.locator(
    `#nvx-hubspot-form iframe[data-test-id*="${formId}"], #nvx-hubspot-form iframe[data-test-id^="embedded-form-"], #nvx-hubspot-form iframe`
  ).first();

  try {
    await iframe.waitFor({ state: 'attached', timeout: 20000 });
  } catch (error) {
    return transientResult(attempt, `hubspot_frame_not_mounted:${error instanceof Error ? error.message : String(error)}`);
  }

  const title = await waitForMeaningfulIframeTitle(iframe);
  const ariaLabel = normalizeText(await iframe.getAttribute('aria-label').catch(() => ''));
  const handle = await iframe.elementHandle();
  const frame = handle ? await handle.contentFrame() : null;
  if (!frame) return transientResult(attempt, 'hubspot_content_frame_unavailable');

  // HubSpot attaches the iframe before its form subtree is ready. Wait for the
  // actual form DOM before auditing semantics so vendor bootstrap latency is not
  // misclassified as a deterministic accessibility failure.
  try {
    await frame.locator('form').first().waitFor({ state: 'attached', timeout: 30000 });
  } catch (error) {
    return transientResult(
      attempt,
      `hubspot_form_element_not_available_after_wait:${error instanceof Error ? error.message : String(error)}`
    );
  }

  return {
    transient: false,
    realFailure: false,
    attempt,
    frame,
    iframe: { title, ariaLabel, url: frame.url() },
  };
}

async function collectControls(frame) {
  return frame.locator(
    'input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([type="reset"]):not([disabled]), textarea:not([disabled]), select:not([disabled])'
  ).evaluateAll((nodes) => {
    const isVisible = (node) => {
      const style = getComputedStyle(node);
      const rect = node.getBoundingClientRect();
      return style.display !== 'none' && style.visibility !== 'hidden' && Number.parseFloat(style.opacity || '1') > 0.01 && rect.width > 1 && rect.height > 1;
    };
    const text = (value) => String(value || '').replace(/\s+/g, ' ').trim();
    const referenced = (ids) => String(ids || '')
      .split(/\s+/)
      .filter(Boolean)
      .map((id) => ({ id, text: text(document.getElementById(id)?.textContent || '') }));

    return nodes.filter(isVisible).map((node) => {
      const labels = Array.from(node.labels || []);
      const labelText = text(labels.map((label) => label.textContent || '').join(' '));
      const ariaLabel = text(node.getAttribute('aria-label'));
      const ariaLabelledby = node.getAttribute('aria-labelledby') || '';
      const labelledRefs = referenced(ariaLabelledby);
      const ariaLabelledbyText = text(labelledRefs.map((item) => item.text).join(' '));
      const title = text(node.getAttribute('title'));
      const wrapper = node.closest('.hs-form-field, .field, [class*="hs_"]');
      const requiredMarker = wrapper?.querySelector('.hs-form-required');
      const requiredMarkerVisible = Boolean(requiredMarker && isVisible(requiredMarker));
      const programmaticRequired = node.required || node.getAttribute('aria-required') === 'true';
      const describedby = node.getAttribute('aria-describedby') || '';
      const errormessage = node.getAttribute('aria-errormessage') || '';
      const errorRefs = [...referenced(describedby), ...referenced(errormessage)];
      const associatedErrorText = text(errorRefs.map((item) => item.text).join(' '));
      const id = node.id || '';
      const name = node.getAttribute('name') || '';
      const tag = node.tagName.toLowerCase();
      const type = String(node.getAttribute('type') || '').toLowerCase();
      const uid = id || name || `${tag}:${type || 'unknown'}`;

      return {
        uid,
        tag,
        type,
        id,
        name,
        labelText,
        ariaLabel,
        ariaLabelledby,
        ariaLabelledbyText,
        title,
        placeholder: text(node.getAttribute('placeholder')),
        accessibleName: labelText || ariaLabelledbyText || ariaLabel || title,
        hasNativeLabelAssociation: labels.some((label) => (id && label.htmlFor === id) || label.contains(node)),
        requiredMarkerVisible,
        programmaticRequired,
        requiredAttribute: node.required,
        ariaRequired: node.getAttribute('aria-required') || '',
        nativeInvalid: typeof node.matches === 'function' ? node.matches(':invalid') : false,
        ariaInvalid: node.getAttribute('aria-invalid') === 'true',
        describedby,
        errormessage,
        associatedErrorText,
        nearbyErrorText: text(wrapper?.querySelector('.hs-error-msgs, .hs-error-msg, [role="alert"]')?.textContent || ''),
        validationMessage: text(node.validationMessage || ''),
      };
    });
  });
}

function auditLabelsAndRequiredState(controls) {
  const issues = [];
  for (const control of controls) {
    const identity = controlIdentity(control);
    if (!control.accessibleName) {
      issues.push({
        code: 'WCAG_4_1_2_NAME_MISSING',
        criterion: '4.1.2',
        category: 'name',
        control: identity,
        message: `4.1.2 accessible-name missing: ${identity}`,
      });
    }
    if (!control.labelText && !control.ariaLabelledbyText) {
      issues.push({
        code: 'WCAG_3_3_2_LABEL_MISSING',
        criterion: '3.3.2',
        category: 'label',
        control: identity,
        message: `3.3.2 label/instruction association missing: ${identity}`,
      });
    }
    if (control.requiredMarkerVisible && !control.programmaticRequired) {
      issues.push({
        code: 'WCAG_3_3_2_REQUIRED_NOT_EXPOSED',
        criterion: '3.3.2',
        category: 'required-state',
        control: identity,
        message: `3.3.2/4.1.2 required state not programmatically exposed: ${identity}`,
      });
    }
  }
  return issues;
}

function auditErrorState(controls) {
  const issues = [];
  for (const control of controls.filter((item) => item.programmaticRequired)) {
    const identity = controlIdentity(control);
    if (!control.nativeInvalid && !control.ariaInvalid) {
      issues.push({
        code: 'WCAG_3_3_1_INVALID_STATE_MISSING',
        criterion: '3.3.1',
        category: 'invalid-state',
        control: identity,
        message: `3.3.1 invalid state not exposed after blank submit: ${identity}`,
      });
    }
    // The browser's native validationMessage exists for every invalid required
    // control, even when no error is exposed to assistive technology. Require
    // an explicit programmatic relationship to rendered error text instead.
    if (!control.associatedErrorText) {
      issues.push({
        code: 'WCAG_3_3_1_ERROR_ASSOCIATION_MISSING',
        criterion: '3.3.1',
        category: 'error-association',
        control: identity,
        message: `3.3.1 error message not programmatically associated after blank submit: ${identity}`,
      });
    }
  }
  return issues;
}

async function exerciseBlankValidation(frame, expectedRequiredControls, submissionState) {
  const submit = frame.locator('button[type="submit"], input[type="submit"]').first();
  if (!await submit.count().catch(() => 0)) {
    return {
      issues: [{
        code: 'WCAG_3_3_1_SUBMIT_MISSING',
        criterion: '3.3.1',
        category: 'submit-control',
        message: '3.3.1 submit control missing; error-identification cycle cannot be exercised',
      }],
      controls: [],
      active: null,
      liveRegionCount: 0,
    };
  }

  // HubSpot performs bootstrap/telemetry POSTs while the form is loading. Only
  // requests after this point can be attributed to the blank-submit validation
  // exercise. Actual submission endpoints are still blocked at all times.
  submissionState.validationStarted = true;
  submissionState.observed = false;
  submissionState.requests = [];

  await submit.click({ timeout: 5000 }).catch(async () => {
    await submit.click({ force: true, timeout: 3000 });
  });
  await delay(750);

  const controls = await collectControls(frame);
  const actualRequiredIdentities = new Set(
    controls.filter((control) => control.programmaticRequired).map(controlIdentity)
  );
  const missingRequiredControls = expectedRequiredControls
    .map(controlIdentity)
    .filter((identity) => !actualRequiredIdentities.has(identity));
  const active = await frame.evaluate(() => {
    const node = document.activeElement;
    if (!node) return null;
    return {
      tag: node.tagName.toLowerCase(),
      id: node.id || '',
      name: node.getAttribute('name') || '',
      ariaInvalid: node.getAttribute('aria-invalid') || '',
    };
  }).catch(() => null);
  const liveRegionCount = await frame.locator('[role="alert"], [aria-live]').count().catch(() => 0);

  const issues = auditErrorState(controls);
  if (missingRequiredControls.length > 0) {
    issues.push({
      code: 'WCAG_3_3_1_CONTROLS_UNAVAILABLE',
      criterion: '3.3.1',
      category: 'missing-controls',
      controls: missingRequiredControls,
      message: `3.3.1 required controls unavailable after blank submit: ${missingRequiredControls.join(', ')}`,
    });
  }

  return { issues, controls, active, liveRegionCount };
}

function formatIssueMessage(issue) {
  if (typeof issue === 'string') return issue;
  if (issue && typeof issue.message === 'string') return issue.message;
  return JSON.stringify(issue);
}

async function auditForm(page, hubspot, documentState, attempt, submissionState) {
  const controlsBefore = await collectControls(hubspot.frame);
  if (controlsBefore.length === 0) return transientResult(attempt, 'hubspot_visible_controls_not_available');

  const issues = auditLabelsAndRequiredState(controlsBefore);
  if (!hubspot.iframe.title || /^(form|hubspot form|hs-form-iframe)$/i.test(hubspot.iframe.title)) {
    issues.push({
      code: 'IFRAME_TITLE_GENERIC',
      category: 'iframe-accessible-name',
      message: `iframe-accessible-name: generic or missing title (${JSON.stringify(hubspot.iframe.title)})`,
    });
  }

  const requiredCount = controlsBefore.filter((control) => control.programmaticRequired).length;
  let validation = { issues: [], controls: [], active: null, liveRegionCount: 0 };
  if (requiredCount === 0) {
    issues.push({
      code: 'WCAG_3_3_2_NO_REQUIRED_CONTROLS',
      criterion: '3.3.2',
      category: 'required-controls',
      message: '3.3.2 no programmatically required controls detected; blank-submit error cycle cannot be validated safely',
    });
  } else {
    validation = await exerciseBlankValidation(
      hubspot.frame,
      controlsBefore.filter((control) => control.programmaticRequired),
      submissionState
    );
    issues.push(...validation.issues);
  }

  if (submissionState.preValidationRequests.length > 0) {
    issues.push({
      code: 'SAFETY_PREVALIDATION_SUBMISSION',
      category: 'safety',
      message: 'safety: HubSpot attempted a real form submission before the blank-validation exercise started',
    });
  }
  if (submissionState.observed) {
    issues.push({
      code: 'SAFETY_SUBMISSION_POST',
      category: 'safety',
      message: 'safety: blank accessibility validation unexpectedly triggered a HubSpot submission POST',
    });
  }

  const rawIssueMessages = issues.map(formatIssueMessage);
  const structuredIssues = issues.map((issue) => (typeof issue === 'string' ? { message: issue } : issue));

  return {
    transient: false,
    realFailure: issues.length > 0,
    attempt,
    url: page.url(),
    deploySha: documentState.deploySha,
    iframe: hubspot.iframe,
    controls: controlsBefore,
    programmaticRequiredCount: requiredCount,
    errorSemantics: validation.controls,
    activeAfterValidation: validation.active,
    liveRegionCount: validation.liveRegionCount,
    submissionObserved: submissionState.observed,
    submissionInterceptionInstalled: submissionState.interceptionInstalled === true,
    submissionInterceptionPolicy: submissionState.policy || (submissionState.observed ? 'blockedbyclient' : 'none'),
    submissionRequests: submissionState.requests,
    preValidationSubmissionRequests: submissionState.preValidationRequests,
    issues: rawIssueMessages,
    structuredIssues,
  };
}

async function auditOnce(browser, attempt) {
  const context = await browser.newContext({
    viewport: { width: 1440, height: 1100 },
    ignoreHTTPSErrors: true,
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/151 Safari/537.36 NUVANX-HubSpot-A11y-QA/1.0',
  });
  const page = await context.newPage();
  const submissionState = {
    validationStarted: false,
    observed: false,
    interceptionInstalled: false,
    policy: null,
    requests: [],
    preValidationRequests: [],
  };

  await page.route('**/*', async (route) => {
    const request = route.request();
    const classification = classifyHubSpotSubmissionRequest({
      method: request.method(),
      url: request.url(),
      portalId,
      formId,
    });

    if (classification.isSubmission) {
      const evidence = {
        hostname: classification.hostname,
        pathname: classification.pathname,
        phase: submissionState.validationStarted ? 'blank-validation' : 'pre-validation',
      };
      if (submissionState.validationStarted) {
        submissionState.observed = true;
        submissionState.policy = 'blockedbyclient';
        submissionState.requests.push(evidence);
      } else {
        submissionState.preValidationRequests.push(evidence);
      }
      // Safety invariant: the accessibility audit never permits a real contact
      // submission, regardless of whether it happened before or after the test.
      await route.abort('blockedbyclient');
      return;
    }
    await route.continue();
  });
  submissionState.interceptionInstalled = true;

  try {
    const documentState = await navigateExpectedDocument(page, attempt);
    if (documentState.transient || documentState.realFailure) return documentState;

    const hubspot = await resolveHubSpot(page, attempt);
    if (hubspot.transient || hubspot.realFailure) return hubspot;

    return await auditForm(page, hubspot, documentState, attempt, submissionState);
  } catch (error) {
    const currentUrl = page.url() || target;
    if (isSiteGroundCaptchaInterruption(error, currentUrl)) {
      return transientResult(attempt, `siteground_challenge_during_audit:${error instanceof Error ? error.message : String(error)}`);
    }
    return realFailureResult(attempt, `unhandled_audit_error:${error instanceof Error ? error.message : String(error)}`);
  } finally {
    await context.close().catch(() => {});
  }
}

async function disarmRollbackAfterTransientExhaustion() {
  const envFile = (process.env.GITHUB_ENV || '').trim();
  if (envFile) {
    try {
      await fs.appendFile(envFile, 'STAGING_MUTATION_ARMED=0\n', 'utf8');
      console.error('HUBSPOT_A11Y_STAGING_ROLLBACK=DISARMED reason=third-party-or-siteground-transient-exhaustion');
    } catch (error) {
      console.warn(`HUBSPOT_A11Y_STAGING_ROLLBACK=DISARM_FAILED error=${error instanceof Error ? error.message : String(error)}`);
    }
  }

  const summary = (process.env.GITHUB_STEP_SUMMARY || '').trim();
  if (!summary) return;
  try {
    await fs.appendFile(
      summary,
      '\n### HubSpot accessibility gate transient exhaustion\n\nThe HubSpot accessibility audit could not obtain a stable embedded form after three bounded attempts. No semantic defect was established, so Staging rollback was disarmed; the run remains ineligible for Production acceptance.\n',
      'utf8'
    );
  } catch (error) {
    console.warn(`HUBSPOT_A11Y_SUMMARY=WRITE_FAILED error=${error instanceof Error ? error.message : String(error)}`);
  }
}

async function persistResult(result) {
  await fs.writeFile(outFile, `${JSON.stringify(result, null, 2)}\n`, 'utf8');
}

function reportRealFailure(result) {
  const issues = result.issues || [result.reason || 'unknown failure'];
  console.error(`HUBSPOT_A11Y=FAIL_REAL issues=${issues.length}`);
  for (const issue of issues) {
    console.error(`HUBSPOT_A11Y_ISSUE=${formatIssueMessage(issue)}`);
  }
}

async function runAudit(browser) {
  for (let attempt = 1; attempt <= maxAttempts; attempt += 1) {
    console.log(`HUBSPOT_A11Y_ATTEMPT=${attempt}/${maxAttempts}`);
    const result = await auditOnce(browser, attempt);

    if (!result.transient) {
      await persistResult(result);
      if (result.realFailure) {
        reportRealFailure(result);
        return 1;
      }
      console.log(`HUBSPOT_A11Y=PASS controls=${result.controls.length} required=${result.programmaticRequiredCount} live_regions=${result.liveRegionCount}`);
      return 0;
    }

    console.warn(`HUBSPOT_A11Y_TRANSIENT attempt=${attempt} reason=${result.reason}`);
    if (attempt === maxAttempts) {
      await persistResult(result);
      await disarmRollbackAfterTransientExhaustion();
      console.error(`HUBSPOT_A11Y=FAIL_TRANSIENT_EXHAUSTED attempts=${maxAttempts}`);
      return EX_TEMPFAIL;
    }
    await delay(3500 * attempt);
  }

  return 1;
}

const browser = await chromium.launch({ headless: true, args: ['--no-sandbox', '--disable-setuid-sandbox'] });
let exitCode;
try {
  exitCode = await runAudit(browser);
} finally {
  await browser.close().catch(() => {});
}
process.exit(exitCode);
