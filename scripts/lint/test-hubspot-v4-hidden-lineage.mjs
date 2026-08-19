import assert from 'node:assert/strict';

const FORM_ID = '5042522a-0bc5-4381-ac3e-5aee8649b69c';
const LEAD_ID = '11111111-1111-4111-8111-111111111111';
const TEST_RUN_ID = 'staging2-sha-abcdef123456';

const listeners = new Map();
const calls = [];
const values = new Map([
  ['0-1/nvx_lead_id', []],
  ['0-1/nvx_is_test_lead', false],
  ['0-1/nvx_test_run_id', []],
  ['0-1/nvx_utm_source', []],
  ['0-1/nvx_google_click_id', []],
]);

function isBooleanField(name) {
  return name === '0-1/nvx_is_test_lead';
}

const form = {
  getFormId: () => FORM_ID,
  getFormFieldValues: async () => Array.from(values.entries()).map(([name, value]) => ({ name, value })),
  getFieldValue: async (name) => values.get(name),
  setFieldValue: async (name, value) => {
    calls.push({ name, value });
    if (isBooleanField(name)) {
      if (typeof value !== 'boolean') throw new TypeError('single checkbox requires boolean');
      values.set(name, value);
      return;
    }
    // Reproduce the updated HubSpot Forms V4 Hidden-field contract: hidden
    // fields reject scalar strings and accept string arrays.
    if (!Array.isArray(value)) throw new TypeError('hidden field requires string[]');
    values.set(name, value);
  },
};

globalThis.window = {
  nvxConversionEvents: { forms: { valoracion: FORM_ID } },
  wp_has_consent: () => true,
  setTimeout,
  NUVANXAttributionContract: {
    buildFormPayload: () => ({
      nvx_lead_id: LEAD_ID,
      nvx_is_test_lead: true,
      nvx_test_run_id: TEST_RUN_ID,
      nvx_utm_source: 'google',
      nvx_google_click_id: 'GCLID-HIDDEN-V4',
    }),
  },
  HubSpotFormsV4: {
    getForms: () => [form],
    getFormFromEvent: () => form,
  },
  addEventListener: (name, callback) => listeners.set(name, callback),
};

globalThis.document = {
  readyState: 'loading',
  addEventListener: (name, callback) => listeners.set(name, callback),
};

const syncUrl = new URL('../../wp-content/themes/nuvanx-medical/assets/js/nvx-hubspot-attribution-sync.js', import.meta.url);
syncUrl.searchParams.set('test', 'hidden-v4-readback');
await import(syncUrl.href);

const api = globalThis.window.NUVANXHubSpotAttributionSync;
assert.ok(api, 'HubSpot attribution sync API must be exposed');
assert.equal(await api.syncForm(form), true, 'V4 hidden fields must synchronize successfully after typed fallback');

assert.deepEqual(values.get('0-1/nvx_lead_id'), [LEAD_ID]);
assert.equal(values.get('0-1/nvx_is_test_lead'), true);
assert.deepEqual(values.get('0-1/nvx_test_run_id'), [TEST_RUN_ID]);
assert.deepEqual(values.get('0-1/nvx_utm_source'), ['google']);
assert.deepEqual(values.get('0-1/nvx_google_click_id'), ['GCLID-HIDDEN-V4']);

const leadWrites = calls.filter((call) => call.name === '0-1/nvx_lead_id');
assert.equal(leadWrites.length, 2, 'Hidden lead id must attempt scalar then documented string[] fallback');
assert.equal(leadWrites[0].value, LEAD_ID);
assert.deepEqual(leadWrites[1].value, [LEAD_ID]);

const qaWrites = calls.filter((call) => call.name === '0-1/nvx_is_test_lead');
assert.equal(qaWrites.length, 1, 'Single checkbox must remain native boolean and must not use hidden fallback');
assert.equal(qaWrites[0].value, true);

console.log('HUBSPOT_V4_HIDDEN_LINEAGE=PASS scalar_rejected=1 hidden_array_fallback=1 readback_verified=1 qa_boolean=1');
