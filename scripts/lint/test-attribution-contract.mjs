import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import vm from 'node:vm';

const runtimePath = 'wp-content/themes/nuvanx-medical/assets/js/nvx-attribution-contract.js';
const directPath = 'wp-content/themes/nuvanx-medical/inc/nvx-valoracion-direct-form.php';
const gtmPath = 'wp-content/themes/nuvanx-medical/inc/nvx-gtm-integration.php';
const provisionerPath = 'scripts/ci/provision-hubspot-attribution-contract.sh';

const managedV2 = [
  'nvx_lead_id', 'nvx_is_test_lead', 'nvx_test_run_id',
  'nvx_first_channel', 'nvx_first_source', 'nvx_first_medium', 'nvx_first_campaign_id',
  'nvx_first_referrer_domain', 'nvx_first_landing_url', 'nvx_first_timestamp',
  'nvx_conversion_channel', 'nvx_conversion_source', 'nvx_conversion_medium',
  'nvx_conversion_campaign_id', 'nvx_conversion_landing_url', 'nvx_conversion_timestamp',
];

if (fs.existsSync(provisionerPath)) {
  const bashLint = spawnSync('bash', ['-n', provisionerPath], { encoding: 'utf8' });
  assert.equal(bashLint.status, 0,
    `HubSpot attribution provisioner must pass bash -n: ${bashLint.stderr || bashLint.stdout || 'unknown error'}`);
  const provisioner = fs.readFileSync(provisionerPath, 'utf8');
  assert.match(provisioner, /managed_properties=\(/,
    'Schema v2 must distinguish provisioner-owned properties from pre-existing attribution properties');
  assert.match(provisioner, /required_existing_properties=\(/,
    'Existing UTM/click metadata must remain fail-closed drift dependencies');
  assert.match(provisioner, /property_type\[nvx_is_test_lead\]='bool'/,
    'QA gate must be a native HubSpot boolean property');
  assert.match(provisioner, /property_field_type\[nvx_is_test_lead\]='booleancheckbox'/,
    'QA gate must use HubSpot booleancheckbox form semantics');
  assert.match(provisioner, /NUVANX_CONFIRM:-.*yes/,
    'HubSpot mutation must continue requiring explicit NUVANX_CONFIRM=yes');
  assert.match(provisioner, /HUBSPOT_MANAGED_PROPERTY_CONTRACT=FAIL missing=/,
    '--check must fail explicitly when managed schema is absent');
  assert.match(provisioner, /HUBSPOT_ATTRIBUTION_CONTRACT=PASS .*schema=v2/,
    'Successful reconciliation must identify schema v2');
  assert.doesNotMatch(provisioner, /\bhs_google_click_id\b/,
    'Native HubSpot Google click property must remain opportunistic, not a provisioning dependency');
  for (const name of managedV2) {
    assert.match(provisioner, new RegExp(`\\b${name}\\b`), `Schema v2 provisioner must own ${name}`);
  }
  console.log(`HUBSPOT_ATTRIBUTION_PROVISIONER_SYNTAX=PASS schema=v2 managed=${managedV2.length}`);
}

function memoryStorage() {
  const values = new Map();
  return {
    getItem: (key) => values.has(key) ? values.get(key) : null,
    setItem: (key, value) => values.set(String(key), String(value)),
    removeItem: (key) => values.delete(String(key)),
    dump: () => Object.fromEntries(values.entries()),
  };
}

function executeRuntime(runtimeSource, { consent, href, referrer, qa = { is_test_lead: false, test_run_id: '' } }) {
  const localStorage = memoryStorage();
  const sessionStorage = memoryStorage();
  const location = new URL(href);
  const window = {
    nvxConversionEvents: {
      forms: { valoracion: '5042522a-0bc5-4381-ac3e-5aee8649b69c' },
      qa,
    },
    location: {
      href: location.href,
      hostname: location.hostname,
      search: location.search,
    },
    localStorage,
    sessionStorage,
    wp_has_consent: () => consent,
    crypto: { randomUUID: () => '11111111-1111-4111-8111-111111111111' },
    addEventListener: () => {},
  };
  const document = {
    referrer,
    readyState: 'complete',
    querySelector: () => null,
    createElement: () => ({ type: '', name: '', value: '' }),
    addEventListener: () => {},
  };
  const context = vm.createContext({
    window, document, URL, URLSearchParams, Date, Uint8Array, Array, Object, Set,
    Boolean, String, Number, JSON, RegExp, Math, console,
  });
  vm.runInContext(runtimeSource, context, { filename: runtimePath });
  return { window, document, localStorage, sessionStorage };
}

if (!fs.existsSync(runtimePath)) {
  console.log('ATTRIBUTION_CONTRACT=SKIP runtime_absent=1');
} else {
  const runtime = fs.readFileSync(runtimePath, 'utf8');
  const direct = fs.readFileSync(directPath, 'utf8');
  const gtm = fs.readFileSync(gtmPath, 'utf8');

  const utmPairs = [
    ['utm_source', 'nvx_utm_source'], ['utm_medium', 'nvx_utm_medium'],
    ['utm_campaign', 'nvx_utm_campaign'], ['utm_content', 'nvx_utm_content'], ['utm_term', 'nvx_utm_term'],
  ];
  const clickPairs = [
    ['gclid', 'nvx_google_click_id'], ['gbraid', 'nvx_google_braid'],
    ['wbraid', 'nvx_google_wbraid'], ['gclsrc', 'nvx_google_gclsrc'],
  ];

  assert.match(runtime, /var UTM_KEYS = \['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'\]/,
    'Runtime must own the complete five-field UTM contract');
  assert.match(runtime, /nvx_lead_id: 'nvx_lead_id'/, 'Runtime must map the first-party lead lineage ID');
  assert.match(runtime, /safeSessionStorage\(\)/, 'Browser lead lineage must be session-scoped before CRM persistence');
  assert.doesNotMatch(runtime, /LEAD_TTL_MS/, 'Browser lead lineage must not become a long-lived tracking identifier');
  assert.match(runtime, /typeof window\.wp_has_consent === 'function'/, 'Runtime must use the canonical WordPress consent API');
  assert.match(runtime, /window\.wp_has_consent\('marketing'\) === true/, 'Marketing attribution must require explicit marketing consent');
  assert.match(runtime, /ATTR_TTL_MS = 90 \* 24 \* 60 \* 60 \* 1000/, 'Consented attribution storage must retain the documented 90-day TTL');
  assert.match(runtime, /CLICK_KEYS = \['gclid', 'gbraid', 'wbraid', 'gclsrc'\]/, 'Runtime must own the canonical Google click-id set');
  assert.match(runtime, /nvx_is_test_lead: qa\.is_test_lead === true/, 'Embed QA marker must originate from server-provided context');
  assert.match(runtime, /key === 'nvx_is_test_lead' \? Boolean\(rawValue\)/,
    'HubSpot V4 single checkbox must receive a boolean, not a string');

  for (const [utm, property] of utmPairs) {
    assert.match(runtime, new RegExp(`${utm}: '${property}'`), `Embed runtime must map ${utm} to ${property}`);
    assert.match(direct, new RegExp(`'${utm}'\\s*=>\\s*array\\( '${property}' \\)`), `First-party server bridge must map ${utm} to ${property}`);
  }
  for (const [click, property] of clickPairs) {
    assert.match(runtime, new RegExp(`${click}: '${property}'`), `Embed runtime must map ${click} to ${property}`);
    assert.match(direct, new RegExp(`'${click}'\\s*=>\\s*array\\( '${property}' \\)`), `First-party server bridge must map ${click} to ${property}`);
  }

  for (const name of managedV2.slice(3)) {
    assert.match(runtime, new RegExp(`\\b${name}\\b`), `Runtime must populate ${name}`);
    assert.match(direct, new RegExp(`'${name}'`), `Direct form contract must carry ${name}`);
  }

  assert.match(runtime, /function classifyChannel\(/, 'Runtime must classify paid, organic, referral and direct traffic explicitly');
  for (const channel of ['organic_search', 'organic_social', 'referral', 'direct', 'paid_search', 'paid_social']) {
    assert.match(runtime, new RegExp(`['"]${channel}['"]`), `Channel classifier must represent ${channel}`);
  }
  assert.match(runtime, /FIRST_TOUCH_KEY = 'nvx_first_touch'/, 'Runtime must persist a distinct first-touch snapshot');
  assert.match(runtime, /CONVERSION_TOUCH_KEY = 'nvx_conversion_touch'/, 'Runtime must persist a distinct conversion-touch snapshot');

  assert.match(direct, /'1' !== nvx_valoracion_attribution_value\( 'nvx_marketing_consent', 1 \)/,
    'Server bridge must refuse marketing attribution without the consent marker');
  assert.doesNotMatch(direct, /\bhs_google_click_id\b/, 'First-party submission must not depend on a native HubSpot system field');
  assert.match(direct, /nvx_valoracion_append_field\( \$fields, 'nvx_lead_id', nvx_valoracion_lead_id\(\) \)/,
    'Lead lineage ID must be appended independently from marketing attribution');
  assert.match(direct, /nvx_valoracion_append_qa_context\( \$fields \)/,
    'Direct server submission must append the server-owned QA context');
  assert.doesNotMatch(direct, /nvx_valoracion_attribution_value\( 'nvx_is_test_lead'/,
    'Direct server submission must never trust a client-posted QA marker');
  assert.doesNotMatch(direct, /new URLSearchParams\(/, 'PHP markup must not bypass consent by copying marketing query params inline');

  assert.match(gtm, /function nvx_attribution_qa_context\(\): array/,
    'WordPress must own deterministic QA identity server-side');
  assert.match(gtm, /nvx_environment_is_staging2\(\)/,
    'Staging2 must be the only automatic test-lead environment');
  assert.match(gtm, /window\.nvxConversionEvents\.qa=Object\.assign/,
    'Server QA context must be exposed to the HubSpot embed runtime');
  assert.match(gtm, /'nvx-attribution-contract'/, 'WordPress must enqueue the attribution contract');
  assert.match(gtm, /add_action\( 'wp_enqueue_scripts', 'nvx_gtm_enqueue_attribution_contract', 9 \)/,
    'Attribution contract must enqueue before the conversion relay');

  const organic = executeRuntime(runtime, {
    consent: true,
    href: 'https://nuvanx.com/endolift/?foo=bar',
    referrer: 'https://www.google.com/search?q=endolift',
  });
  const contract = organic.window.NUVANXAttributionContract;
  const first = contract.getFirstTouch();
  assert.equal(first.channel, 'organic_search');
  assert.equal(first.source, 'google');
  assert.equal(first.medium, 'organic');
  assert.equal(first.landing_url, 'https://nuvanx.com/endolift/');
  assert.equal(first.referrer_domain, 'www.google.com');

  organic.window.location.href = 'https://nuvanx.com/madrid/valoracion/?utm_source=google&utm_medium=cpc&utm_campaign=brand&gclid=GCLID123';
  organic.window.location.hostname = 'nuvanx.com';
  organic.window.location.search = '?utm_source=google&utm_medium=cpc&utm_campaign=brand&gclid=GCLID123';
  organic.document.referrer = 'https://www.google.com/';
  const paidConversion = contract.getConversionTouch();
  assert.equal(contract.getFirstTouch().channel, 'organic_search', 'paid return must not overwrite first touch');
  assert.equal(paidConversion.channel, 'paid_search');
  assert.equal(paidConversion.source, 'google');
  assert.equal(paidConversion.gclid, 'GCLID123');
  assert.equal(paidConversion.campaign_id, 'brand');

  organic.window.location.href = 'https://nuvanx.com/madrid/valoracion/';
  organic.window.location.search = '';
  organic.document.referrer = 'https://www.nuvanx.com/endolift/';
  const internalConversion = contract.getConversionTouch();
  assert.equal(internalConversion.channel, 'paid_search', 'internal navigation must preserve last acquisition touch');
  assert.equal(internalConversion.gclid, 'GCLID123', 'internal navigation must preserve the conversion click id');
  assert.equal(internalConversion.landing_url, 'https://nuvanx.com/madrid/valoracion/');

  assert.equal(contract.classifyChannel({}, '').channel, 'direct');
  assert.equal(contract.classifyChannel({}, 'https://example.org').channel, 'referral');
  assert.equal(contract.classifyChannel({}, 'www.instagram.com').channel, 'organic_social');

  const noConsent = executeRuntime(runtime, {
    consent: false,
    href: 'https://nuvanx.com/?utm_source=google&utm_medium=cpc&gclid=NOPE',
    referrer: 'https://www.google.com/',
  });
  assert.equal(noConsent.window.NUVANXAttributionContract.getFirstTouch(), null);
  assert.equal(noConsent.window.NUVANXAttributionContract.getConversionTouch(), null);
  assert.equal(noConsent.localStorage.getItem('nvx_first_touch'), null);
  assert.equal(noConsent.localStorage.getItem('nvx_conversion_touch'), null);

  console.log('ATTRIBUTION_RUNTIME_BEHAVIOR=PASS first=organic_search conversion=paid_search internal_preserves_paid=1 no_consent_storage=0');
  console.log('QA_LEAD_GATE_STATIC=PASS server_owned=1 staging_only=1 client_override=0');
  console.log('ATTRIBUTION_CONTRACT=PASS schema=v2 lead_id=1 first_touch=1 conversion_touch=1 utm_fields=5 click_ids=4 consent_gate=1 first_party_parity=1 qa_gate=1');
}
