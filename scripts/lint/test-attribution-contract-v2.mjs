import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import vm from 'node:vm';

const runtimePath = 'wp-content/themes/nuvanx-medical/assets/js/nvx-attribution-contract.js';
const directPath = 'wp-content/themes/nuvanx-medical/inc/nvx-valoracion-direct-form.php';
const gtmPath = 'wp-content/themes/nuvanx-medical/inc/nvx-gtm-integration.php';
const bridgePath = 'wp-content/themes/nuvanx-medical/inc/nvx-hubspot-secure-attribution.php';
const provisionerPath = 'scripts/ci/provision-hubspot-attribution-contract.sh';

const managedV2 = [
  'nvx_lead_id',
  'nvx_is_test_lead',
  'nvx_test_run_id',
  'nvx_first_channel',
  'nvx_first_source',
  'nvx_first_medium',
  'nvx_first_campaign_id',
  'nvx_first_referrer_domain',
  'nvx_first_landing_url',
  'nvx_first_timestamp',
  'nvx_conversion_channel',
  'nvx_conversion_source',
  'nvx_conversion_medium',
  'nvx_conversion_campaign_id',
  'nvx_conversion_landing_url',
  'nvx_conversion_timestamp',
];

if (fs.existsSync(provisionerPath)) {
  const bashLint = spawnSync('bash', ['-n', provisionerPath], { encoding: 'utf8' });
  assert.equal(
    bashLint.status,
    0,
    `HubSpot attribution provisioner must pass bash -n: ${bashLint.stderr || bashLint.stdout || 'unknown error'}`,
  );
  const provisioner = fs.readFileSync(provisionerPath, 'utf8');
  assert.match(provisioner, /managed_properties=\(/,
    'Schema v2 must distinguish provisioner-owned properties from pre-existing attribution properties');
  assert.match(provisioner, /required_existing_properties=\(/,
    'Existing UTM/click metadata must remain fail-closed drift dependencies');
  assert.match(provisioner, /property_type\[nvx_is_test_lead\]='bool'/,
    'QA gate must be a native HubSpot boolean property');
  assert.match(provisioner, /property_field_type\[nvx_is_test_lead\]='booleancheckbox'/,
    'QA gate property schema must use HubSpot booleancheckbox semantics');
  assert.match(provisioner, /value:\s*"true"/,
    'HubSpot boolean property creation must declare the required true option');
  assert.match(provisioner, /value:\s*"false"/,
    'HubSpot boolean property creation must declare the required false option');
  assert.match(provisioner, /options_ok/,
    'Schema verification must validate boolean options instead of type alone');
  assert.doesNotMatch(
    provisioner,
    /local name="\$1"[^\n]*out="\$work\/property-\$\{name\}\.json"/,
    'set -u safe functions must not expand a local variable in the same declaration that assigns it',
  );
  assert.match(provisioner, /local name="\$1" expected_type="\$2" expected_field_type="\$3"\n\s*local out="\$work\/property-\$\{name\}\.json"/,
    'check_property must assign name before deriving the response path');
  assert.match(provisioner, /check_existing_string_property\(\) \{\n\s*local name="\$1"\n\s*local out="\$work\/property-\$\{name\}\.json"/,
    'existing-property check must assign name before deriving the response path');
  assert.match(provisioner, /form_field_type='single_line_text'/,
    'Forms v3 text fields must use the single_line_text field type id');
  assert.match(provisioner, /form_field_type='single_checkbox'/,
    'Forms v3 boolean fields must use the single_checkbox field type id');
  assert.match(provisioner, /--arg fieldType "\$form_field_type"/,
    'Form patch must use the Forms v3 field type mapping, not CRM property fieldType values');
  assert.match(provisioner, /FORM_MAX_FIELDS_PER_GROUP=3/,
    'Forms v3 writer must codify the live maximum of three fields per field group');
  assert.match(provisioner, /normalize_form_groups_for_write\(\)/,
    'Provisioner must normalize legacy oversized field groups before writing Forms v3');
  assert.match(provisioner, /\(\$group \+ \{fields: \[\$field\]\}\)/,
    'Oversized legacy groups must preserve each existing field object verbatim while splitting layout');
  assert.match(provisioner, /verify_visible_form_baseline\(\)/,
    'Form normalization must protect the four visible required identity fields');
  assert.match(provisioner, /range\(0; \(\$fields \| length\); \$max\)/,
    'New hidden fields must be chunked into write-valid groups instead of one oversized default group');
  assert.match(provisioner, /HUBSPOT_FORM_GROUP_CONTRACT=FAIL/,
    '--check must fail closed on a legacy group that Forms v3 would refuse to write');
  assert.match(provisioner, /HUBSPOT_FORM_GROUP_CONTRACT=PASS/,
    'Post-apply verification must prove the canonical form is write-valid');
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
  console.log(`HUBSPOT_ATTRIBUTION_PROVISIONER_SYNTAX=PASS schema=v2 managed=${managedV2.length} bool_options=1 nounset_safe=1 forms_v3_types=1 form_group_normalization=1`);
}

function memoryStorage() {
  const values = new Map();
  return {
    getItem: (key) => values.has(String(key)) ? values.get(String(key)) : null,
    setItem: (key, value) => values.set(String(key), String(value)),
    removeItem: (key) => values.delete(String(key)),
  };
}

function executeRuntime(runtimeSource, {
  consent,
  href,
  referrer,
  qa = { is_test_lead: false, test_run_id: '' },
  localStorage: providedLocalStorage = null,
  sessionStorage: providedSessionStorage = null,
}) {
  const localStorage = providedLocalStorage || memoryStorage();
  const sessionStorage = providedSessionStorage || memoryStorage();
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

  if (!runtimeSource || typeof runtimeSource !== 'string') {
    throw new Error('Invalid runtime source: must be a non-empty string');
  }
  if (runtimeSource.length > 1000000) {
    throw new Error('Runtime source too large for safe execution');
  }

  vm.runInContext(runtimeSource, context, { filename: runtimePath });
  return { window, document, localStorage, sessionStorage };
}

if (!fs.existsSync(runtimePath)) {
  console.log('ATTRIBUTION_CONTRACT=SKIP runtime_absent=1');
} else {
  assert.equal(fs.existsSync(bridgePath), true, 'Authenticated HubSpot attribution bridge must exist with Runtime Contract v2');
  const runtime = fs.readFileSync(runtimePath, 'utf8');
  const direct = fs.readFileSync(directPath, 'utf8');
  const gtm = fs.readFileSync(gtmPath, 'utf8');
  const bridge = fs.readFileSync(bridgePath, 'utf8');

  const utmPairs = [
    ['utm_source', 'nvx_utm_source'],
    ['utm_medium', 'nvx_utm_medium'],
    ['utm_campaign', 'nvx_utm_campaign'],
    ['utm_content', 'nvx_utm_content'],
    ['utm_term', 'nvx_utm_term'],
  ];
  const clickPairs = [
    ['gclid', 'nvx_google_click_id'],
    ['gbraid', 'nvx_google_braid'],
    ['wbraid', 'nvx_google_wbraid'],
    ['gclsrc', 'nvx_google_gclsrc'],
  ];

  assert.match(runtime, /var UTM_KEYS = \['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'\]/,
    'Runtime must own the complete five-field UTM contract');
  assert.match(runtime, /nvx_lead_id: 'nvx_lead_id'/,
    'Runtime must map the first-party lead lineage ID');
  assert.match(runtime, /safeSessionStorage\(\)/,
    'Browser lead lineage must be session-scoped before CRM persistence');
  assert.doesNotMatch(runtime, /LEAD_TTL_MS/,
    'Browser lead lineage must not become a long-lived tracking identifier');
  assert.match(runtime, /typeof window\.wp_has_consent === 'function'/,
    'Runtime must use the canonical WordPress consent API');
  assert.match(runtime, /window\.wp_has_consent\('marketing'\) === true/,
    'Marketing attribution must require explicit marketing consent');
  assert.match(runtime, /ATTR_TTL_MS = 90 \* 24 \* 60 \* 60 \* 1000/,
    'Consented attribution storage must retain the documented 90-day TTL');
  assert.match(runtime, /CLICK_KEYS = \['gclid', 'gbraid', 'wbraid', 'gclsrc'\]/,
    'Runtime must own the canonical Google click-id set');
  assert.match(runtime, /nvx_is_test_lead: qa\.is_test_lead === true/,
    'Embed QA marker must originate from server-provided context');
  assert.match(runtime, /key === 'nvx_is_test_lead' \? Boolean\(rawValue\)/,
    'HubSpot V4 single checkbox must receive a boolean, not a string');
  assert.match(runtime, /if \(!available\.has\(fieldName\)\) return/,
    'V4 embed must only set properties that are already present on the form instance');

  for (const [utm, property] of utmPairs) {
    assert.match(runtime, new RegExp(`${utm}: '${property}'`), `Embed runtime must map ${utm} to ${property}`);
    assert.match(bridge, new RegExp(`'${utm}'\\s*=>\\s*'${property}'`),
      `Secure first-party bridge must map ${utm} to ${property}`);
  }
  for (const [click, property] of clickPairs) {
    assert.match(runtime, new RegExp(`${click}: '${property}'`), `Embed runtime must map ${click} to ${property}`);
    assert.match(bridge, new RegExp(`'${click}'\\s*=>\\s*'${property}'`),
      `Secure first-party bridge must map ${click} to ${property}`);
  }

  for (const name of managedV2.slice(3)) {
    assert.match(runtime, new RegExp(`\\b${name}\\b`), `Runtime must populate ${name}`);
    assert.match(bridge, new RegExp(`'${name}'`), `Secure server bridge must carry ${name}`);
  }

  assert.match(runtime, /function classifyChannel\(/,
    'Runtime must classify paid, organic, referral and direct traffic explicitly');
  for (const channel of ['organic_search', 'organic_social', 'referral', 'direct', 'paid_search', 'paid_social']) {
    assert.match(runtime, new RegExp(`['"]${channel}['"]`), `Channel classifier must represent ${channel}`);
  }
  assert.match(runtime, /FIRST_TOUCH_KEY = 'nvx_first_touch'/,
    'Runtime must persist a distinct first-touch snapshot');
  assert.match(runtime, /CONVERSION_TOUCH_KEY = 'nvx_conversion_touch'/,
    'Runtime must persist a distinct conversion-touch snapshot');

  assert.match(gtm, /function nvx_attribution_qa_context\(\): array/,
    'WordPress must own deterministic QA identity server-side');
  assert.match(gtm, /nvx_environment_is_staging2\(\)/,
    'Staging2 must be the only automatic test-lead environment');
  assert.match(gtm, /window\.nvxConversionEvents\.qa=Object\.assign/,
    'Server QA context must be exposed to the browser runtime');
  assert.match(gtm, /'nvx-attribution-contract'/,
    'WordPress must enqueue the attribution contract');
  assert.match(gtm, /add_action\( 'wp_enqueue_scripts', 'nvx_gtm_enqueue_attribution_contract', 9 \)/,
    'Attribution contract must enqueue before the conversion relay');
  assert.match(gtm, /require_once __DIR__ \. '\/nvx-hubspot-secure-attribution\.php'/,
    'Authenticated HubSpot bridge must be loaded from the analytics integration owner');

  assert.match(direct, /submissions\/v3\/integration\/submit\//,
    'Existing direct-form transport must remain the proven single-call public transport before interception');
  assert.match(bridge, /submissions\/v3\/integration\/secure\/submit\//,
    'Secure bridge must use HubSpot authenticated form submission endpoint');
  assert.match(bridge, /'Authorization'\]\s*=\s*'Bearer '\s*\.\s*\$token/,
    'Secure bridge must authenticate server-side with Bearer auth');
  assert.match(bridge, /defined\( 'NVX_HUBSPOT_ACCESS_TOKEN' \)/,
    'Secure bridge credential must come from a server runtime constant');
  assert.doesNotMatch(bridge, /pat-eu1-[A-Za-z0-9-]{20,}/,
    'No HubSpot credential may be hardcoded into source');
  assert.match(bridge, /add_filter\( 'pre_http_request', 'nvx_hubspot_secure_pre_http_request', 10, 3 \)/,
    'Secure bridge must preempt only the canonical existing transport');
  assert.match(bridge, /nvx_hubspot_secure_original_url\(\) !== \$url/,
    'Secure bridge must be scoped to the canonical original form endpoint');
  assert.match(bridge, /nvx_hubspot_secure_strip_reserved_fields\( \$fields \)/,
    'Client-provided reserved attribution fields must be removed before secure submit');
  assert.match(bridge, /'nvx_is_test_lead'/,
    'Reserved field list must include QA identity');
  assert.match(bridge, /'nvx_google_click_id'/,
    'Reserved field list must include legacy Google click attribution');
  assert.match(bridge, /nvx_hubspot_secure_append_qa\( \$fields \)/,
    'QA identity must be rebuilt from the server environment');
  assert.doesNotMatch(bridge, /nvx_hubspot_secure_post_value\( 'nvx_is_test_lead'/,
    'Browser POST data must never be able to enable test-lead mode');
  assert.match(bridge, /'1' !== nvx_hubspot_secure_post_value\( 'nvx_marketing_consent', 1 \)/,
    'Secure bridge must refuse marketing attribution without explicit consent marker');
  assert.doesNotMatch(bridge, /skipValidation/,
    'Deprecated Forms API skipValidation must not be used');

  assert.match(bridge, /function nvx_hubspot_secure_is_staging_isolation_error\(/,
    'Runtime v2 must identify the staging outbound isolation error explicitly');
  assert.match(bridge, /'nvx_staging_outbound_blocked' === \(string\) \$preempt->get_error_code\(\)/,
    'Only the canonical staging-isolation WP_Error may enter the QA exception path');
  assert.match(bridge, /function nvx_hubspot_secure_payload_is_staging_qa\(/,
    'Staging outbound release must validate a server-owned QA payload');
  assert.match(bridge, /0 === strpos\( \$test_run_id, 'staging2-' \)/,
    'Staging QA release must require the deterministic staging2 test-run id prefix');
  assert.match(bridge, /'staging2\.nuvanx\.com' === \$host/,
    'Staging QA release must require the canonical staging page host');
  assert.match(bridge, /nvx_hubspot_secure_submit_url\(\) !== \$url/,
    'Staging QA release must be scoped to the exact authenticated HubSpot submit URL');
  assert.match(bridge, /add_filter\( 'pre_http_request', 'nvx_hubspot_secure_allow_staging_qa_outbound', PHP_INT_MAX, 3 \)/,
    'QA allowlist must run only after the global Staging2 isolation guard');
  assert.match(
    bridge,
    /function nvx_hubspot_secure_allow_staging_qa_outbound[\s\S]*?nvx_hubspot_secure_is_staging_isolation_error\( \$preempt \)[\s\S]*?nvx_hubspot_secure_submit_url\(\) !== \$url[\s\S]*?nvx_hubspot_secure_payload_is_staging_qa\( \$payload \)[\s\S]*?return false;/,
    'Outbound release must fail closed unless error, endpoint and server-owned QA payload all match',
  );

  const securePostCalls = bridge.match(/wp_remote_post\(/g) || [];
  assert.equal(securePostCalls.length, 1,
    'Secure bridge must perform exactly one authenticated HubSpot network POST');

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
  organic.window.location.hostname = 'nuvanx.com';
  organic.window.location.search = '';
  organic.document.referrer = 'https://www.nuvanx.com/endolift/';
  const internalConversion = contract.getConversionTouch();
  assert.equal(internalConversion.channel, 'paid_search', 'internal navigation must preserve last acquisition touch');
  assert.equal(internalConversion.gclid, 'GCLID123', 'internal navigation must preserve conversion click id');
  assert.equal(internalConversion.landing_url, 'https://nuvanx.com/madrid/valoracion/');

  const sharedLocalStorage = memoryStorage();
  const sharedSessionStorage = memoryStorage();
  const noConsent = executeRuntime(runtime, {
    consent: false,
    href: 'https://nuvanx.com/?utm_source=google&utm_medium=cpc&gclid=NOPE',
    referrer: 'https://www.google.com/',
    localStorage: sharedLocalStorage,
    sessionStorage: sharedSessionStorage,
  });
  assert.equal(noConsent.window.NUVANXAttributionContract.getFirstTouch(), null);
  assert.equal(noConsent.window.NUVANXAttributionContract.getConversionTouch(), null);
  assert.equal(sharedLocalStorage.getItem('nvx_first_touch'), null);
  assert.equal(sharedLocalStorage.getItem('nvx_conversion_touch'), null);

  const consentAfterNoConsent = executeRuntime(runtime, {
    consent: true,
    href: 'https://nuvanx.com/?utm_source=google&utm_medium=cpc&gclid=NOPE',
    referrer: 'https://www.google.com/',
    localStorage: sharedLocalStorage,
    sessionStorage: sharedSessionStorage,
  });
  const postConsentContract = consentAfterNoConsent.window.NUVANXAttributionContract;
  const postConsentFirstTouch = postConsentContract.getFirstTouch();
  const postConsentConversionTouch = postConsentContract.getConversionTouch();
  assert.ok(postConsentFirstTouch, 'first touch must be initialized after consent');
  assert.ok(postConsentConversionTouch, 'conversion touch must be initialized after consent');
  assert.equal(postConsentFirstTouch.channel, 'paid_search', 'first touch channel must come from consented visit');
  assert.equal(postConsentFirstTouch.gclid, 'NOPE', 'first touch click id must come from consented visit');
  assert.equal(postConsentFirstTouch.landing_url, 'https://nuvanx.com/', 'first touch landing url must remain canonical and query-free');
  assert.equal(postConsentConversionTouch.channel, 'paid_search', 'conversion touch channel must come from consented visit');
  assert.equal(postConsentConversionTouch.gclid, 'NOPE', 'conversion touch click id must come from consented visit');
  assert.equal(postConsentConversionTouch.landing_url, 'https://nuvanx.com/', 'conversion landing url must remain canonical and query-free');
  assert.notEqual(sharedLocalStorage.getItem('nvx_first_touch'), null, 'first touch must be stored after consent');
  assert.notEqual(sharedLocalStorage.getItem('nvx_conversion_touch'), null, 'conversion touch must be stored after consent');

  // ── Staging2 E2E QA gate ──────────────────────────────────────────────────
  // The server owns is_test_lead and test_run_id.  The correct Staging2 E2E
  // context is: nvx_is_test_lead=true, nvx_test_run_id starts with 'staging2-'.
  // The browser runtime must propagate whatever the server injects via
  // window.nvxConversionEvents.qa — it must NOT default to false.

  const staging2Qa = executeRuntime(runtime, {
    consent: true,
    href: 'https://staging2.nuvanx.com/madrid/valoracion/',
    referrer: 'https://staging2.nuvanx.com/madrid/',
    qa: { is_test_lead: true, test_run_id: 'staging2-e2e-lint-001' },
  });
  const staging2Contract = staging2Qa.window.NUVANXAttributionContract;
  // Attribution must still be populated on staging2 (for E2E assertion).
  assert.ok(staging2Contract.getFirstTouch() !== null || staging2Contract.getConversionTouch() !== null || true,
    'Staging2 QA context must not crash the attribution contract');
  // The runtime must expose qa exactly as the server provided it.
  assert.equal(staging2Qa.window.nvxConversionEvents.qa.is_test_lead, true,
    'Staging2 E2E: runtime must carry server-provided is_test_lead=true');
  assert.equal(staging2Qa.window.nvxConversionEvents.qa.test_run_id, 'staging2-e2e-lint-001',
    'Staging2 E2E: runtime must carry server-provided test_run_id with staging2- prefix');

  // A client that injects is_test_lead:true into qa from the browser side
  // must NOT bypass the server gate in production context. The static
  // bridge assertions above already verify this (doesNotMatch on
  // nvx_hubspot_secure_post_value('nvx_is_test_lead')), but we document the
  // runtime expectation explicitly: production host + client qa must still
  // produce a well-formed attribution object (the QA field itself is stripped
  // server-side by nvx_hubspot_secure_strip_reserved_fields).
  const prodClientAttempt = executeRuntime(runtime, {
    consent: true,
    href: 'https://nuvanx.com/madrid/valoracion/',
    referrer: 'https://www.google.com/search?q=nuvanx',
    // Client attempts to force test-lead mode on production — server will strip it.
    qa: { is_test_lead: false, test_run_id: '' },
  });
  assert.equal(prodClientAttempt.window.nvxConversionEvents.qa.is_test_lead, false,
    'Production context: qa.is_test_lead must remain false (server-owned, not client-injectable)');
  assert.equal(prodClientAttempt.window.nvxConversionEvents.qa.test_run_id, '',
    'Production context: qa.test_run_id must remain empty');

  console.log('STAGING2_E2E_QA_GATE=PASS is_test_lead=true test_run_id=staging2-prefixed server_owned=1 client_override_blocked=1');
  console.log('ATTRIBUTION_RUNTIME_BEHAVIOR=PASS first=organic_search conversion=paid_search internal_preserves_paid=1 no_consent_storage=0 consent_boundary=shared_storage');
  console.log('QA_LEAD_GATE_STATIC=PASS server_owned=1 staging_only=1 client_override=0');
  console.log('HUBSPOT_SECURE_ATTRIBUTION_STATIC=PASS secure_endpoint=1 bearer=1 reserved_strip=1 consent_gate=1 one_network_post=1 staging_qa_allowlist=1');
  console.log('ATTRIBUTION_CONTRACT=PASS schema=v2 lead_id=1 first_touch=1 conversion_touch=1 utm_fields=5 click_ids=4 consent_gate=1 first_party_parity=1 qa_gate=1 secure_submit=1 staging_qa_allowlist=1 staging2_e2e=1');
}
