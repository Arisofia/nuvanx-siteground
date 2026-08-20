import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import { randomUUID } from 'node:crypto';
import { chromium } from 'playwright';
import { EX_TEMPFAIL, getGitHubEventPath, EXECUTION_PATHS } from './siteground-transient-classifier.mjs';

const BASE_URL = (process.env.BASE_URL || '').replace(/\/+$/, '');
const EXPECTED_BASE = 'https://staging2.nuvanx.com';
const ARTIFACT_PATH = new URL('./valoracion-artifacts/attribution-lineage-e2e.json', import.meta.url);
const EVENT_NAME = process.env.GITHUB_EVENT_NAME || '';
const REF_NAME = process.env.GITHUB_REF_NAME || '';
const EVENT_PATH = getGitHubEventPath(EVENT_NAME, REF_NAME);

if (EVENT_PATH === EXECUTION_PATHS.UNSUPPORTED_EVENT) {
  console.log(`ATTRIBUTION_LINEAGE_E2E=SKIP reason=unsupported_event event=${EVENT_NAME} ref=${REF_NAME}`);
  process.exit(0);
}

console.log(`ATTRIBUTION_LINEAGE_E2E=PATH path=${EVENT_PATH} event=${EVENT_NAME} ref=${REF_NAME}`);
assert.equal(BASE_URL, EXPECTED_BASE, 'Real lineage E2E is allowed only against canonical Staging2');

const qaToken = randomUUID().replaceAll('-', '');
const email = `qa-attribution-${qaToken.slice(0, 16)}@example.org`;
const gclid = `NVXQA${qaToken}`;
const target = `${BASE_URL}/madrid/valoracion/?utm_source=google&utm_medium=cpc&utm_campaign=nvx_lineage_e2e&gclid=${encodeURIComponent(gclid)}`;

await fs.mkdir(new URL('./valoracion-artifacts/', import.meta.url), { recursive: true });
await fs.rm(ARTIFACT_PATH, { force: true });

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext();
const page = await context.newPage();

function isTransient(error) {
  const message = error instanceof Error ? error.message : String(error);
  return /ERR_(?:CONNECTION|NAME|TIMED_OUT)|Timeout|net::|502|503|504|temporar/i.test(message);
}

function scalar(value) {
  if (Array.isArray(value)) return value.length ? String(value[0] ?? '') : '';
  return String(value ?? '');
}

try {
  const response = await page.goto(target, { waitUntil: 'domcontentloaded', timeout: 30_000 });
  if (!response || response.status() >= 500) {
    throw new Error(`Staging2 navigation unavailable: status=${response ? response.status() : 'none'}`);
  }

  await page.waitForSelector('[data-nvx-direct-form]', { timeout: 15_000 });
  await page.waitForFunction(() => Boolean(window.NUVANXAttributionContract?.getLeadId), null, { timeout: 15_000 });

  const consentState = await page.evaluate(() => {
    window.wp_has_consent = (type) => type === 'marketing' || type === 'statistics';
    window.cmplz_has_consent = (type) => type === 'marketing' || type === 'statistics';
    document.dispatchEvent(new Event('wp_listen_for_consent_change'));
    document.dispatchEvent(new Event('cmplz_enable_category'));
    window.NUVANXAttributionContract?.getFirstTouch?.();
    document.dispatchEvent(new Event('wp_listen_for_consent_change'));
    window.NUVANXHubSpotAttributionSync?.syncExistingForms?.();
    return {
      env: window.nvxConversionEvents?.env || '',
      qa: window.nvxConversionEvents?.qa || {},
      formId: String(window.nvxConversionEvents?.forms?.valoracion || '').toLowerCase(),
    };
  });

  assert.equal(consentState.env, 'staging2', 'E2E must execute with server-owned staging2 context');
  assert.match(consentState.formId, /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/,
    'Browser form identity must be a server-provided canonical UUID');
  assert.equal(consentState.qa?.is_test_lead, true, 'Staging2 QA identity must be server-owned and enabled');
  assert.match(String(consentState.qa?.test_run_id || ''), /^staging2-sha-[0-9a-f]{12}$/,
    'Staging2 QA run id must be deterministic and SHA-scoped');

  await page.waitForFunction((formId) => {
    const api = window.HubSpotFormsV4;
    return Boolean(api?.getForms?.().some((candidate) => String(candidate?.getFormId?.() || '').toLowerCase() === formId));
  }, consentState.formId, { timeout: 20_000 });

  const nativeSnapshot = await page.evaluate(async (formId) => {
    const form = (window.HubSpotFormsV4?.getForms?.() || [])
      .find((candidate) => String(candidate?.getFormId?.() || '').toLowerCase() === formId);
    if (!form) return null;
    const syncChanged = await window.NUVANXHubSpotAttributionSync?.syncForm?.(form);
    await new Promise((resolve) => setTimeout(resolve, 250));
    const values = {};
    for (const field of await form.getFormFieldValues() || []) {
      const actual = String(field?.name || '');
      const canonical = actual.replace(/^\d+-\d+\//, '');
      if (canonical) values[canonical] = field?.value;
    }
    return { values, syncChanged: Boolean(syncChanged) };
  }, consentState.formId);

  assert.ok(nativeSnapshot?.values, 'Canonical HubSpot V4 form must be discoverable after marketing consent');
  const nativeFields = nativeSnapshot.values;
  const fieldNames = Object.keys(nativeFields).sort();
  console.log(
    `ATTRIBUTION_LINEAGE_DIAGNOSTIC sync_changed=${nativeSnapshot.syncChanged ? 'true' : 'false'} lead_field_present=${fieldNames.includes('nvx_lead_id') ? 'true' : 'false'} managed_fields_present=${['nvx_utm_source', 'nvx_google_click_id'].filter((name) => fieldNames.includes(name)).join(',') || 'none'} owner=first_party`
  );
  assert.equal(scalar(nativeFields.nvx_utm_source), 'google',
    'Live V4 form must receive the supported UTM source field');
  assert.equal(scalar(nativeFields.nvx_google_click_id), gclid,
    'Live V4 form must receive the supported Google click id');

  const directState = await page.evaluate(async ({ email: qaEmail, gclid: qaGclid }) => {
    const form = document.querySelector('[data-nvx-direct-form]');
    if (!form) return null;
    const set = (name, value) => {
      const input = form.querySelector(`[name="${name}"]`);
      if (input) input.value = value;
    };
    set('firstname', 'QA');
    set('lastname', 'Attribution');
    set('phone', '+34910000000');
    set('email', qaEmail);
    set('message', 'Automated Staging2 attribution lineage QA. No clinical request.');
    set('nvx_marketing_consent', '1');
    set('gclid', qaGclid);
    set('utm_source', 'google');
    set('utm_medium', 'cpc');
    set('utm_campaign', 'nvx_lineage_e2e');
    const privacy = form.querySelector('[name="privacy"]');
    if (privacy) privacy.checked = true;
    document.dispatchEvent(new Event('wp_listen_for_consent_change'));
    await new Promise((resolve) => setTimeout(resolve, 100));
    set('nvx_marketing_consent', '1');
    set('gclid', qaGclid);
    return {
      leadId: String(form.querySelector('[name="nvx_lead_id"]')?.value || '').toLowerCase(),
      marketing: String(form.querySelector('[name="nvx_marketing_consent"]')?.value || ''),
    };
  }, { email, gclid });

  assert.ok(directState, 'First-party direct form must remain present');
  assert.match(directState.leadId, /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/,
    'First-party form must own one browser-session UUID v4');
  assert.equal(directState.marketing, '1');

  await Promise.all([
    page.waitForURL((url) => url.origin === BASE_URL && url.pathname === '/gracias/', { timeout: 25_000 }),
    page.evaluate(() => document.querySelector('[data-nvx-direct-form]')?.requestSubmit()),
  ]);

  const evidence = {
    schema: 2,
    environment: 'staging2',
    source: EVENT_NAME,
    form_id: consentState.formId,
    nvx_lead_id: directState.leadId,
    email,
    gclid,
    test_run_id: String(consentState.qa.test_run_id),
    success_url: page.url(),
    submitted_at: new Date().toISOString(),
  };
  await fs.writeFile(ARTIFACT_PATH, `${JSON.stringify(evidence, null, 2)}\n`, 'utf8');
  console.log(`ATTRIBUTION_LINEAGE_E2E=PASS nvx_lead_id=${directState.leadId} test_run_id=${evidence.test_run_id} owner=first_party`);
} catch (error) {
  const message = error instanceof Error ? error.message : String(error);
  console.error(`ATTRIBUTION_LINEAGE_E2E=${isTransient(error) ? 'TRANSIENT' : 'FAIL'} reason=${message}`);
  process.exitCode = isTransient(error) ? EX_TEMPFAIL : 1;
} finally {
  await context.close().catch(() => {});
  await browser.close().catch(() => {});
}
