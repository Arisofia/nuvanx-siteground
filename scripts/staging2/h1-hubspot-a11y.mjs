import { chromium } from 'playwright';
import fs from 'node:fs/promises';
import path from 'node:path';
import { setTimeout as delay } from 'node:timers/promises';
import {
  EX_TEMPFAIL,
  SITEGROUND_CAPTCHA_PATH,
  isSiteGroundCaptchaInterruption,
  isSiteGroundTransientResponse,
} from './siteground-transient-classifier.mjs';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const expectedSha = (process.env.EXPECTED_SHA || '').trim();
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

async function grantConsent(page) {
  await page.evaluate(() => {
    if (typeof window.wp_set_consent === 'function') {
      window.wp_set_consent('marketing', 'allow');
      document.dispatchEvent(new Event('wp_listen_for_consent_change'));
      document.dispatchEvent(new Event('wp_consent_type_defined'));
    }
  }).catch(() => {});
}

async function findHubSpotFrame(page) {
  const iframe = page.locator(
    `#nvx-hubspot-form iframe[data-test-id*="${formId}"], #nvx-hubspot-form iframe[data-test-id^="embedded-form-"], #nvx-hubspot-form iframe`
  ).first();

  await iframe.waitFor({ state: 'attached', timeout: 20000 });

  // Runtime governance normalizes generic HubSpot iframe names asynchronously.
  await page.waitForFunction(
    (id) => {
      const frame = document.querySelector(
        `#nvx-hubspot-form iframe[data-test-id*="${id}"], #nvx-hubspot-form iframe[data-test-id^="embedded-form-"], #nvx-hubspot-form iframe`
      );
      if (!frame) return false;
      const title = (frame.getAttribute('title') || '').trim();
      return title.length > 0 && !/^(form|hubspot form|hs-form-iframe)$/i.test(title);
    },
    formId,
    { timeout: 5000 }
  ).catch(() => {});

  const title = normalizeText(await iframe.getAttribute('title').catch(() => ''));
  const ariaLabel = normalizeText(await iframe.getAttribute('aria-label').catch(() => ''));
  const handle = await iframe.elementHandle();
  const frame = handle ? await handle.contentFrame() : null;

  return { iframe, frame, title, ariaLabel };
}

async function collectControlSemantics(frame) {
  return frame.locator(
    'input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([type="reset"]):not([disabled]), textarea:not([disabled]), select:not([disabled])'
  ).evaluateAll((nodes) => {
    const visible = (node) => {
      const style = getComputedStyle(node);
      const rect = node.getBoundingClientRect();
      return style.display !== 'none' && style.visibility !== 'hidden' && Number.parseFloat(style.opacity || '1') > 0.01 && rect.width > 1 && rect.height > 1;
    };
    const text = (value) => String(value || '').replace(/\s+/g, ' ').trim();
    const referencedText = (value) => text(
      String(value || '')
        .split(/\s+/)
        .filter(Boolean)
        .map((id) => document.getElementById(id)?.textContent || '')
        .join(' ')
    );

    return nodes.filter(visible).map((node) => {
      const labels = Array.from(node.labels || []);
      const labelText = text(labels.map((label) => label.textContent || '').join(' '));
      const ariaLabel = text(node.getAttribute('aria-label'));
      const ariaLabelledby = text(node.getAttribute('aria-labelledby'));
      const ariaLabelledbyText = referencedText(ariaLabelledby);
      const title = text(node.getAttribute('title'));
      const placeholder = text(node.getAttribute('placeholder'));
      const wrapper = node.closest('.hs-form-field, .field, [class*="hs_"]');
      const requiredMarker = wrapper?.querySelector('.hs-form-required');
      const requiredMarkerVisible = Boolean(requiredMarker && visible(requiredMarker));
      const programmaticRequired = node.required || node.getAttribute('aria-required') === 'true';
      const accessibleName = labelText || ariaLabelledbyText || ariaLabel || title;
      const hasNativeLabelAssociation = labels.some((label) => {
        if (node.id && label.htmlFor === node.id) return true;
        return label.contains(node);
      });

      return {
        tag: node.tagName.toLowerCase(),
        type: String(node.getAttribute('type') || '').toLowerCase(),
        id: node.id || '',
        name: node.getAttribute('name') || '',
        labelText,
        ariaLabel,
        ariaLabelledby,
        ariaLabelledbyText,
        title,
        placeholder,
        accessibleName,
        hasNativeLabelAssociation,
        requiredMarkerVisible,
        programmaticRequired,
        requiredAttribute: node.required,
        ariaRequired: node.getAttribute('aria-required') || '',
      };
    });
  });
}

async function collectErrorSemantics(frame) {
  return frame.locator(
    'input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([type="reset"]):not([disabled]), textarea:not([disabled]), select:not([disabled])'
  ).evaluateAll((nodes) => {
    const visible = (node) => {
      const style = getComputedStyle(node);
      const rect = node.getBoundingClientRect();
      return style.display !== 'none' && style.visibility !== 'hidden' && Number.parseFloat(style.opacity || '1') > 0.01 && rect.width > 1 && rect.height > 1;
    };
    const text = (value) => String(value || '').replace(/\s+/g, ' ').trim();
    const referenced = (ids) => String(ids || '')
      .split(/\s+/)
      .filter(Boolean)
      .map((id) => ({ id, text: text(document.getElementById(id)?.textContent || '') }));

    return nodes.filter(visible).map((node) => {
      const required = node.required || node.getAttribute('aria-required') === 'true';
      const describedby = node.getAttribute('aria-describedby') || '';
      const errormessage = node.getAttribute('aria-errormessage') || '';
      const refs = [...referenced(describedby), ...referenced(errormessage)];
      const associatedErrorText = text(refs.map((item) => item.text).join(' '));
      const wrapper = node.closest('.hs-form-field, .field, [class*="hs_"]');
      const nearbyErrorText = text(wrapper?.querySelector('.hs-error-msgs, .hs-error-msg, [role="alert"]')?.textContent || '');
      const nativeInvalid = typeof node.matches === 'function' ? node.matches(':invalid') : false;
      const ariaInvalid = node.getAttribute('aria-invalid') === 'true';
      const validationMessage = text(node.validationMessage || '');

      return {
        id: node.id || '',
        name: node.getAttribute('name') || '',
        type: String(node.getAttribute('type') || '').toLowerCase(),
        required,
        nativeInvalid,
        ariaInvalid,
        describedby,
        errormessage,
        associatedRefs: refs,
        associatedErrorText,
        nearbyErrorText,
        validationMessage,
      };
    });
  });
}

async function auditOnce(browser, attempt) {
  const context = await browser.newContext({
    viewport: { width: 1440, height: 1100 },
    ignoreHTTPSErrors: true,
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/151 Safari/537.36 NUVANX-HubSpot-A11y-QA/1.0',
  });
  const page = await context.newPage();
  let submissionObserved = false;

  page.on('request', (request) => {
    const url = request.url();
    if (
      request.method() === 'POST' &&
      /\/submissions\/v3\//i.test(url) &&
      url.includes(formId)
    ) submissionObserved = true;
  });

  try {
    let response;
    try {
      response = await page.goto(target, { waitUntil: 'domcontentloaded', timeout: 45000 });
    } catch (error) {
      const currentUrl = page.url() || target;
      if (isSiteGroundCaptchaInterruption(error, currentUrl)) {
        return { transient: true, reason: `siteground_navigation_challenge:${error.message}`, attempt };
      }
      return { transient: false, realFailure: true, reason: `navigation_failed:${error instanceof Error ? error.message : String(error)}`, attempt };
    }

    const status = Number(response?.status() || 0);
    const headers = response?.headers() || {};
    const currentUrl = page.url() || target;
    if (isSiteGroundTransientResponse(status, headers, currentUrl) || currentUrl.includes(SITEGROUND_CAPTCHA_PATH)) {
      return { transient: true, reason: `siteground_http_challenge:${status}:${currentUrl}`, attempt };
    }
    if (status !== 200 || new URL(currentUrl).pathname !== '/madrid/valoracion/') {
      return { transient: false, realFailure: true, reason: `unexpected_document:status=${status}:url=${currentUrl}`, attempt };
    }

    const deploySha = normalizeText(await page.locator('meta[name="nvx-deploy-sha"]').first().getAttribute('content').catch(() => ''));
    if (deploySha !== expectedSha) {
      return { transient: false, realFailure: true, reason: `sha_mismatch:${deploySha || 'missing'}:${expectedSha}`, attempt };
    }

    await grantConsent(page);
    const host = page.locator('#nvx-hubspot-form');
    await host.scrollIntoViewIfNeeded().catch(() => {});
    await host.dispatchEvent('focusin').catch(() => {});
    await page.waitForLoadState('load', { timeout: 10000 }).catch(() => {});

    let hubspot;
    try {
      hubspot = await findHubSpotFrame(page);
    } catch (error) {
      return { transient: true, reason: `hubspot_frame_not_mounted:${error instanceof Error ? error.message : String(error)}`, attempt };
    }
    if (!hubspot.frame) {
      return { transient: true, reason: 'hubspot_content_frame_unavailable', attempt };
    }

    const issues = [];
    if (!hubspot.title || /^(form|hubspot form|hs-form-iframe)$/i.test(hubspot.title)) {
      issues.push(`iframe-accessible-name: generic or missing title (${JSON.stringify(hubspot.title)})`);
    }

    const frame = hubspot.frame;
    const form = frame.locator('form').first();
    if (!await form.count().catch(() => 0)) {
      return { transient: true, reason: 'hubspot_form_element_not_available', attempt };
    }

    const controls = await collectControlSemantics(frame);
    if (controls.length === 0) {
      return { transient: true, reason: 'hubspot_visible_controls_not_available', attempt };
    }

    for (const control of controls) {
      const identity = control.name || control.id || `${control.tag}:${control.type || 'unknown'}`;
      if (!control.accessibleName) {
        issues.push(`4.1.2 accessible-name missing: ${identity}`);
      }
      if (!control.labelText && !control.ariaLabelledbyText) {
        issues.push(`3.3.2 label/instruction association missing: ${identity}`);
      }
      if (control.requiredMarkerVisible && !control.programmaticRequired) {
        issues.push(`3.3.2/4.1.2 required state not programmatically exposed: ${identity}`);
      }
    }

    const programmaticRequired = controls.filter((control) => control.programmaticRequired);
    if (programmaticRequired.length === 0) {
      issues.push('3.3.2 no programmatically required controls detected; blank-submit error cycle cannot be validated safely');
    }

    let errorSemantics = [];
    let activeAfterValidation = null;
    let liveRegionCount = 0;

    if (programmaticRequired.length > 0) {
      const submit = frame.locator('button[type="submit"], input[type="submit"]').first();
      if (!await submit.count().catch(() => 0)) {
        issues.push('3.3.1 submit control missing; error-identification cycle cannot be exercised');
      } else {
        await submit.click({ timeout: 5000 }).catch(async () => {
          await submit.click({ force: true, timeout: 3000 });
        });
        await frame.waitForTimeout(750);
        errorSemantics = await collectErrorSemantics(frame);

        const requiredErrors = errorSemantics.filter((control) => control.required);
        for (const control of requiredErrors) {
          const identity = control.name || control.id || `required:${control.type || 'unknown'}`;
          const errorIdentified = control.nativeInvalid || control.ariaInvalid;
          const errorAssociated = Boolean(control.associatedErrorText || control.validationMessage);
          if (!errorIdentified) {
            issues.push(`3.3.1 invalid state not exposed after blank submit: ${identity}`);
          }
          if (!errorAssociated) {
            issues.push(`3.3.1 error message not programmatically associated after blank submit: ${identity}`);
          }
        }

        activeAfterValidation = await frame.evaluate(() => {
          const active = document.activeElement;
          return active ? {
            tag: active.tagName.toLowerCase(),
            id: active.id || '',
            name: active.getAttribute('name') || '',
            ariaInvalid: active.getAttribute('aria-invalid') || '',
          } : null;
        }).catch(() => null);
        liveRegionCount = await frame.locator('[role="alert"], [aria-live]').count().catch(() => 0);
      }
    }

    if (submissionObserved) {
      issues.push('safety: blank accessibility validation unexpectedly triggered a HubSpot submission POST');
    }

    return {
      transient: false,
      realFailure: issues.length > 0,
      attempt,
      url: page.url(),
      deploySha,
      iframe: { title: hubspot.title, ariaLabel: hubspot.ariaLabel, url: frame.url() },
      controls,
      programmaticRequiredCount: programmaticRequired.length,
      errorSemantics,
      activeAfterValidation,
      liveRegionCount,
      submissionObserved,
      issues,
    };
  } catch (error) {
    const currentUrl = page.url() || target;
    if (isSiteGroundCaptchaInterruption(error, currentUrl)) {
      return { transient: true, reason: `siteground_challenge_during_audit:${error instanceof Error ? error.message : String(error)}`, attempt };
    }
    return { transient: false, realFailure: true, reason: `unhandled_audit_error:${error instanceof Error ? error.message : String(error)}`, attempt };
  } finally {
    await context.close().catch(() => {});
  }
}

async function disarmRollbackAfterTransientExhaustion() {
  const envFile = (process.env.GITHUB_ENV || '').trim();
  if (envFile) {
    await fs.appendFile(envFile, 'STAGING_MUTATION_ARMED=0\n', 'utf8');
    console.error('HUBSPOT_A11Y_STAGING_ROLLBACK=DISARMED reason=third-party-or-siteground-transient-exhaustion');
  }
  const summary = (process.env.GITHUB_STEP_SUMMARY || '').trim();
  if (summary) {
    await fs.appendFile(
      summary,
      '\n### HubSpot accessibility gate transient exhaustion\n\nThe HubSpot accessibility audit could not obtain a stable embedded form after three bounded attempts. No semantic defect was established, so Staging rollback was disarmed; the run remains ineligible for Production acceptance.\n',
      'utf8'
    );
  }
}

const browser = await chromium.launch({ headless: true, args: ['--no-sandbox', '--disable-setuid-sandbox'] });
let finalResult = null;
let finalExit = 1;

try {
  for (let attempt = 1; attempt <= maxAttempts; attempt += 1) {
    console.log(`HUBSPOT_A11Y_ATTEMPT=${attempt}/${maxAttempts}`);
    const result = await auditOnce(browser, attempt);
    finalResult = result;

    if (!result.transient) {
      await fs.writeFile(outFile, `${JSON.stringify(result, null, 2)}\n`, 'utf8');
      if (result.realFailure) {
        console.error(`HUBSPOT_A11Y=FAIL_REAL issues=${Array.isArray(result.issues) ? result.issues.length : 1}`);
        for (const issue of result.issues || [result.reason]) console.error(`HUBSPOT_A11Y_ISSUE=${issue}`);
        finalExit = 1;
      } else {
        console.log(`HUBSPOT_A11Y=PASS controls=${result.controls.length} required=${result.programmaticRequiredCount} live_regions=${result.liveRegionCount}`);
        finalExit = 0;
      }
      break;
    }

    console.warn(`HUBSPOT_A11Y_TRANSIENT attempt=${attempt} reason=${result.reason}`);
    if (attempt === maxAttempts) {
      await fs.writeFile(outFile, `${JSON.stringify(result, null, 2)}\n`, 'utf8');
      await disarmRollbackAfterTransientExhaustion();
      console.error(`HUBSPOT_A11Y=FAIL_TRANSIENT_EXHAUSTED attempts=${maxAttempts}`);
      finalExit = EX_TEMPFAIL;
      break;
    }

    await delay(3500 * attempt);
  }
} finally {
  await browser.close().catch(() => {});
}

process.exit(finalExit);
