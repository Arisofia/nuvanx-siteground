import assert from 'node:assert/strict';

const FORM_ID = '5042522a-0bc5-4381-ac3e-5aee8649b69c';
const api = globalThis.window?.NUVANXHubSpotAttributionSync;
assert.ok(api, 'HubSpot attribution sync API must already be exposed by integration wiring');

const liveNames = [
  'firstname', 'lastname', 'email', 'phone',
  'nvx_attribution_captured_at', 'nvx_attribution_expires_at',
  'nvx_google_braid', 'nvx_google_click_id', 'nvx_google_gclsrc', 'nvx_google_wbraid',
  'nvx_landing_url', 'nvx_utm_campaign', 'nvx_utm_content', 'nvx_utm_medium',
  'nvx_utm_source', 'nvx_utm_term',
];
const values = new Map(liveNames.map((name) => [`0-1/${name}`, []]));
const writes = [];
let available = null;

const form = {
  getFormId: () => FORM_ID,
  getFormFieldValues: async () => Array.from(values.entries()).map(([name, value]) => ({ name, value })),
  getFieldValue: async (name) => values.get(name),
  setFieldValue: async (name, value) => {
    writes.push({ name, value });
    values.set(name, value);
  },
};

globalThis.window.wp_has_consent = () => true;
globalThis.window.setTimeout = setTimeout;
globalThis.window.NUVANXAttributionContract = {
  buildFormPayload: (fields) => {
    available = new Set(fields);
    return {
      nvx_lead_id: '11111111-1111-4111-8111-111111111111',
      nvx_is_test_lead: true,
      nvx_test_run_id: 'staging2-sha-abcdef123456',
      nvx_utm_source: 'google',
      nvx_google_click_id: 'GCLID-LIVE-SCHEMA-V3',
    };
  },
};
globalThis.window.HubSpotFormsV4 = {
  getForms: () => [form],
  getFormFromEvent: () => form,
};

assert.equal(await api.syncForm(form), true,
  'Live V4 schema must synchronize supported attribution fields');
assert.ok(available, 'Runtime must receive the live field availability set');
assert.equal(available.size, 16, 'Canonical live form must expose exactly 16 supported fields in this regression');
assert.equal(available.has('nvx_lead_id'), false, 'Live V4 form must not pretend to own nvx_lead_id');
assert.equal(available.has('nvx_is_test_lead'), false, 'Live V4 form must not pretend to own QA marker');
assert.equal(available.has('nvx_test_run_id'), false, 'Live V4 form must not pretend to own QA run id');

const canonicalWrites = writes.map(({ name }) => api.canonicalPropertyName(name));
assert.equal(canonicalWrites.includes('nvx_lead_id'), false,
  'Browser sync must never write first-party lineage into an absent V4 field');
assert.equal(canonicalWrites.includes('nvx_is_test_lead'), false,
  'Browser sync must never write QA identity into an absent V4 field');
assert.equal(canonicalWrites.includes('nvx_test_run_id'), false,
  'Browser sync must never write QA run lineage into an absent V4 field');
assert.ok(canonicalWrites.includes('nvx_utm_source'),
  'Supported UTM source must still synchronize to the V4 form');
assert.ok(canonicalWrites.includes('nvx_google_click_id'),
  'Supported Google click id must still synchronize to the V4 form');
assert.deepEqual(values.get('0-1/nvx_utm_source'), ['google']);
assert.deepEqual(values.get('0-1/nvx_google_click_id'), ['GCLID-LIVE-SCHEMA-V3']);

console.log('HUBSPOT_V4_LIVE_SCHEMA_V3=PASS fields=16 lead_owner=first_party qa_owner=server_crm supported_attribution_sync=1');
