import { chromium } from 'playwright';

const baseUrl = (process.env.BASE_URL || 'https://staging2.nuvanx.com').replace(/\/$/, '');
const formId = (process.env.FORM_ID || '5042522a-0bc5-4381-ac3e-5aee8649b69c').trim().toLowerCase();
const expectedSha = (process.env.EXPECTED_SHA || '').trim(); // Optional pinning
if (expectedSha && !/^[0-9a-f]{40}$/.test(expectedSha)) throw new Error('EXPECTED_SHA must be a 40-character lowercase hex string');

const gclid = `NVXQA_RUNTIME_${Date.now()}`;
const target = `${baseUrl}/madrid/valoracion/?gclid=${encodeURIComponent(gclid)}`;

const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });
try {
  const context = await browser.newContext({
    userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',
  });
  const page = await context.newPage();
  let response = null;
  for (let attempt = 1; attempt <= 6; attempt += 1) {
    response = null;
    let status = 0;
    try {
      response = await page.goto(target, { waitUntil: 'domcontentloaded', timeout: 45000 });
      status = response?.status() || 0;
    } catch (err) {
      console.log(`Navigation failed: ${err.message}`);
    }
    const currentPath = new URL(page.url()).pathname;
    const targetPath = new URL(target).pathname;
    const isSuccess = status === 200 && currentPath === targetPath;
    if (status === 200 && currentPath !== targetPath) {
      console.log(`200 OK but redirected to ${currentPath}`);
    }
    const isRetriable =
      status === 0 || // network / no-response cases
      status === 202 ||
      status === 429 ||
      (status >= 300 && status < 400) || // redirects
      (status >= 500 && status < 600) || // server errors
      (status === 200 && currentPath !== targetPath); // anti-bot interstitial

    console.log(`PAGE_ATTEMPT=${attempt} STATUS=${status} SUCCESS=${isSuccess} RETRIABLE=${isRetriable}`);

    if (isSuccess) break;
    if (!isRetriable) throw new Error(`route HTTP ${status}`);
    if (attempt < 6) await page.waitForTimeout(3000);
  }
  if (!response || response.status() !== 200) throw new Error(`route remained challenged HTTP ${response?.status() || 0}`);
  
  // Verify SHA if provided
  if (expectedSha) {
    const pageSha = await page.evaluate(() => {
      const meta = document.querySelector('meta[name="nvx-deploy-sha"]');
      return meta ? meta.getAttribute('content') : null;
    });
    if (pageSha !== expectedSha) {
      throw new Error(`Deployed SHA mismatch: expected ${expectedSha}, got ${pageSha}`);
    }
  }

  const selectors = [
    `#nvx-hubspot-form iframe[data-test-id*="${formId}"]`,
    `#nvx-hubspot-form .hbspt-form`,
    `#nvx-hubspot-form form.hs-form`
  ];
  await page.locator(selectors.join(', ')).first().waitFor({ state: 'attached', timeout: 20000 });
  await page.waitForFunction(() => window.HubSpotFormsV4 && typeof window.HubSpotFormsV4.getForms === 'function' && window.HubSpotFormsV4.getForms().length > 0, { timeout: 20000 });
  
  const state = await page.evaluate(async ({ formId, gclid }) => {
    const out = {
      wpHasConsentType: typeof window.wp_has_consent,
      wpSetConsentType: typeof window.wp_set_consent,
      marketing: null,
      qa: window.NUVANXGoogleAttributionQA ? {
        eligiblePath: window.NUVANXGoogleAttributionQA.eligiblePath,
        hasClickId: window.NUVANXGoogleAttributionQA.hasClickId,
        clickTypes: window.NUVANXGoogleAttributionQA.clickTypes,
      } : null,
      formCount: 0,
      fieldsBefore: [],
      fieldsAfterAllow: [],
      requestedGclid: gclid,
    };
    if (typeof window.wp_has_consent === 'function') {
      try { out.marketing = window.wp_has_consent('marketing'); } catch (error) { out.marketing = `ERROR:${error.message}`; }
    }
    if (!window.HubSpotFormsV4 || typeof window.HubSpotFormsV4.getForms !== 'function') return out;
    const forms = window.HubSpotFormsV4.getForms() || [];
    out.formCount = forms.length;
    const form = forms.find((candidate) => String(candidate?.getFormId?.() || '').toLowerCase() === formId);
    if (!form || typeof form.getFormFieldValues !== 'function') return out;
    const project = (fields) => (fields || [])
      .filter((field) => /google|gclid|gbraid|wbraid|gclsrc/i.test(String(field?.name || '')))
      .map((field) => ({ name: field.name, value: field.value }));
    out.fieldsBefore = project(await form.getFormFieldValues());
    if (typeof window.wp_set_consent === 'function') {
      try {
        window.wp_set_consent('marketing', 'allow');
        document.dispatchEvent(new Event('wp_listen_for_consent_change'));
        await new Promise((resolve) => setTimeout(resolve, 1000));
        out.marketingAfterAllow = window.wp_has_consent('marketing');
        out.fieldsAfterAllow = project(await form.getFormFieldValues());
      } catch (error) { out.allowError = error.message; }
    }
    return out;
  }, { formId, gclid });
  
  console.log(JSON.stringify(state, null, 2));
  if (state.wpHasConsentType !== 'function') throw new Error('wp_has_consent is not available on live Staging2');
  if (!state.qa?.eligiblePath || !state.qa?.hasClickId) throw new Error('Google attribution QA helper not active for synthetic GCLID');
  if (state.formCount < 1) throw new Error('Canonical HubSpot V4 form instance not available');
  if (!state.fieldsBefore.some((field) => /nvx_google_click_id|hs_google_click_id/.test(field.name))) {
    throw new Error('HubSpot canonical form does not expose a GCLID target field');
  }
  
  if (state.marketing === true) {
    throw new Error('Marketing consent was already granted before the allow step; cannot verify pre-consent state');
  }
  
  const valuesBefore = state.fieldsBefore
    .filter((field) => field.name.startsWith('nvx_'))
    .flatMap((field) => Array.isArray(field.value) ? field.value : [field.value]).map(String);
  if (valuesBefore.includes(gclid)) {
    throw new Error('GCLID leaked to HubSpot field BEFORE marketing consent was allowed');
  }

  if (state.wpSetConsentType === 'function') {
    if (state.allowError) {
      throw new Error(`wp_set_consent threw error: ${state.allowError}`);
    }
    if (state.marketingAfterAllow !== true) {
      throw new Error(`Marketing consent did not become true after allow step (got: ${state.marketingAfterAllow})`);
    }
    const values = state.fieldsAfterAllow.flatMap((field) => Array.isArray(field.value) ? field.value : [field.value]).map(String);
    if (!values.includes(gclid)) throw new Error('GCLID was not populated after marketing consent allow');
    console.log('GOOGLE_ATTRIBUTION_CONSENT_POPULATION=PASS');
  } else {
    throw new Error('GOOGLE_ATTRIBUTION_CONSENT_POPULATION=INCONCLUSIVE wp_set_consent unavailable');
  }
} finally {
  await browser.close();
}
