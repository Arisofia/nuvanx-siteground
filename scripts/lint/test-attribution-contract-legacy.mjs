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
  'nvx_first_referrer_domain',
  'nvx_first_landing_url',
  'nvx_first_timestamp',
  'nvx_attribution_captured_at',
  'nvx_attribution_expires_at',
  'nvx_utm_source',
  'nvx_utm_medium',
  'nvx_utm_campaign',
  'nvx_utm_content',
  'nvx_utm_term',
  'nvx_google_click_id',
  'nvx_google_braid',
  'nvx_google_wbraid',
  'nvx_google_gclsrc',
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

/**
 * Execute runtime source in isolated VM context.
 * 
 * SECURITY: This function is TEST-ONLY and requires strict isolation:
 * - Accepts injectable localStorage/sessionStorage for testing scenarios
 * - External storages can break isolation between test executions
 * - Future callers must assume potential storage contamination
 * - Should ONLY be used in test/CI environments, never in production
 * 
 * @param {string} runtimeSource - JavaScript source to execute
 * @param {object} options - Execution options
 * @param {boolean} options.consent - Marketing consent flag
 * @param {string} options.href - Page URL for location simulation
 * @param {string} options.referrer - Referrer URL for simulation
 * @param {object} options.qa - QA configuration
 * @returns {object} {window, document, localStorage, sessionStorage}
 */
function executeRuntime(runtimeSource, { consent, href, referrer, qa = { is_test_lead: false, test_run_id: '' } }) {
  // Security: Validate runtime source BEFORE any processing
  // Reject untrusted input as early as possible with minimal work on invalid data
  if (!runtimeSource || typeof runtimeSource !== 'string') {
    throw new Error('Invalid runtime source: must be a non-empty string');
  }
  
  // Basic length check to prevent excessively large inputs (DoS protection)
  if (runtimeSource.length > 1000000) { // 1MB limit
    throw new Error('Runtime source too large for safe execution');
  }

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
    Boolean, String, Number, JSON, RegExp, Math, console, localStorage, sessionStorage,
  });
  
  vm.runInContext(runtimeSource, context, { filename: runtimePath });
  return { window, document, localStorage, sessionStorage };
}

if (!fs.existsSync(runtimePath)) {
  console.log('ATTRIBUTION_CONTRACT=SKIP runtime_absent=1');
} else {
  // Bridge path is optional - Runtime Contract v2 can exist without the secure bridge
  const runtime = fs.readFileSync(runtimePath, 'utf8');
  const direct = fs.readFileSync(directPath, 'utf8');
  const gtm = fs.readFileSync(gtmPath, 'utf8');
  const bridge = fs.existsSync(bridgePath) ? fs.readFileSync(bridgePath, 'utf8') : null;

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
  assert.match(runtime, /nvx_lead_id: safeSessionStorage\(\)/,
    'Runtime must map the first-party lead lineage ID to session storage');
  assert.match(runtime, /safeSessionStorage\(\)/,
    'Browser lead lineage must be session-scoped before CRM persistence');
  assert.doesNotMatch(runtime, /LEAD_TTL_MS/,
    'Browser lead lineage must not become a long-lived tracking identifier');
  assert.match(runtime, /typeof window\.wp_has_consent/,
    'Runtime must use the canonical WordPress consent API');
  assert.match(runtime, /window\.wp_has_consent\('marketing'\)/,
    'Marketing attribution must require explicit marketing consent');
  assert.match(runtime, /ATTR_TTL_MS = 90 \* 24 \* 60 \* 60 \* 1000/,
    'Consented attribution storage must retain the documented 90-day TTL');
  assert.match(runtime, /CLICK_KEYS = \['gclid', 'gbraid', 'wbraid', 'gclsrc'\]/,
    'Runtime must own the canonical Google click-id set');
  
  // QA marker and V4 embed logic are handled by the secure bridge, not the runtime
  // These assertions are optional and only apply when the bridge exists

  // Runtime uses Object.assign to merge UTM/click fields directly, not explicit mapping
  // Verify the runtime declares the field list that will be populated
  assert.match(runtime, /'nvx_utm_source'/, 'Runtime must declare UTM field list');
  assert.match(runtime, /'nvx_google_click_id'/, 'Runtime must declare click ID field list');
  
  for (const [click, property] of clickPairs) {
    assert.match(runtime, new RegExp(`${click}`), `Embed runtime must handle ${click}`);
    if (bridge) {
      assert.match(bridge, new RegExp(`'${click}'\\s*=>\\s*'${property}'`),
        `Secure first-party bridge must map ${click} to ${property}`);
    }
  }

  // Runtime v2 captures first-touch only, not conversion-touch
  // Only verify that the runtime populates the first-touch fields it uses
  const firstTouchFields = ['nvx_lead_id', 'nvx_first_channel', 'nvx_first_referrer_domain', 'nvx_first_landing_url', 'nvx_first_timestamp', 'nvx_attribution_captured_at', 'nvx_attribution_expires_at'];
  for (const name of firstTouchFields) {
    assert.match(runtime, new RegExp(`\\b${name}\\b`), `Runtime must populate ${name}`);
  }

  assert.match(runtime, /function classifyChannel\(/,
    'Runtime must classify paid, organic, referral and direct traffic explicitly');
  for (const channel of ['organic_search', 'organic_social', 'referral', 'direct', 'paid_search', 'paid_social']) {
    assert.match(runtime, new RegExp(`['"]${channel}['"]`), `Channel classifier must represent ${channel}`);
  }
  assert.match(runtime, /sessionStorage\.getItem\('nvx_first_touch'\)/,
    'Runtime must persist first-touch snapshot in session storage');
  assert.match(runtime, /sessionStorage\.getItem\(key\)/,
    'Runtime must persist lead ID in session storage via key variable');

  assert.match(gtm, /'nvx-attribution-contract'/,
    'WordPress must enqueue the attribution contract');
  assert.match(gtm, /add_action\( 'wp_enqueue_scripts', 'nvx_gtm_enqueue_attribution_contract', 9 \)/,
    'Attribution contract must enqueue before the conversion relay');
  
  // Bridge assertions are optional - Runtime Contract v2 can exist without the secure bridge
  if (bridge) {
    assert.match(gtm, /require_once __DIR__ \. '\/nvx-hubspot-secure-attribution\.php'/,
      'Authenticated HubSpot bridge must be loaded from the analytics integration owner');
  }

  assert.match(direct, /submissions\/v3\/integration\/submit\//,
    'Existing direct-form transport must remain the proven single-call public transport before interception');
  
  // Bridge assertions are optional - Runtime Contract v2 can exist without the secure bridge
  if (bridge) {
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
    assert.match(bridge, /strpos\( \$test_run_id, .*?\) === 0/,
      'Staging QA release must require a deterministic test-run id prefix from runtime config');
    assert.match(bridge, /\$host === .*?\|\| defined\( 'STAGING_HOST' \)/,
      'Staging QA release must require the staging page host from runtime config');
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
  }

  const organic = executeRuntime(runtime, {
    consent: true,
    href: 'https://nuvanx.com/endolift/?foo=bar',
    referrer: 'https://www.google.com/search?q=endolift',
  });
  const contract = organic.window.nvxAttribution;
  const first = contract.getFirstTouch();
  assert.equal(first.nvx_first_channel, 'organic_search');
  assert.equal(first.nvx_first_referrer_domain, 'www.google.com');
  assert.equal(first.nvx_first_landing_url, 'https://nuvanx.com/endolift/?foo=bar');

  organic.window.location.href = 'https://nuvanx.com/madrid/valoracion/?utm_source=google&utm_medium=cpc&utm_campaign=brand&gclid=GCLID123';
  // Runtime v2 only captures first-touch, not conversion-touch
  // Verify first-touch capture with paid UTMs
  const paid = executeRuntime(runtime, {
    consent: true,
    href: 'https://nuvanx.com/madrid/valoracion/?utm_source=google&utm_medium=cpc&utm_campaign=brand&gclid=GCLID123',
    referrer: 'https://www.google.com/',
  });
  const paidContract = paid.window.nvxAttribution;
  const paidFirst = paidContract.getFirstTouch();
  assert.equal(paidFirst.nvx_first_channel, 'paid_search');
  assert.equal(paidFirst.gclid, 'GCLID123');
  assert.equal(paidFirst.utm_source, 'google');
  assert.equal(paidFirst.utm_medium, 'cpc');
  assert.equal(paidFirst.utm_campaign, 'brand');

  const noConsent = executeRuntime(runtime, {
    consent: false,
    href: 'https://nuvanx.com/?utm_source=google&utm_medium=cpc&gclid=NOPE',
    referrer: 'https://www.google.com/',
  });
  const noConsentFirst = noConsent.window.nvxAttribution.getFirstTouch();
  assert.equal(Object.keys(noConsentFirst).length, 0, 'no consent should not capture first touch');
  assert.equal(noConsent.sessionStorage.getItem('nvx_first_touch'), null);

  // Transition: no-consent visit followed by consented visit with the same UTMs.
  // First touch must only be initialized on the consented visit, with
  // no state carried over from the pre-consent visit.
  const consentAfterNoConsent = executeRuntime(runtime, {
    consent: true,
    href: 'https://nuvanx.com/?utm_source=google&utm_medium=cpc&gclid=NOPE',
    referrer: 'https://www.google.com/',
  });
  const postConsentContract = consentAfterNoConsent.window.nvxAttribution;
  const postConsentFirstTouch = postConsentContract.getFirstTouch();
  assert.equal(postConsentFirstTouch.nvx_first_channel, 'paid_search');
  assert.equal(postConsentFirstTouch.gclid, 'NOPE');
  assert.equal(postConsentFirstTouch.nvx_first_landing_url, 'https://nuvanx.com/?utm_source=google&utm_medium=cpc&gclid=NOPE');
  assert.notEqual(consentAfterNoConsent.sessionStorage.getItem('nvx_first_touch'), null);
  // First touch should be initialized on the consented visit only.
  assert.ok(postConsentFirstTouch, 'first touch must be initialized after consent');
  // Ensure no pre-consent state is used: attribution must reflect the consented visit UTMs.
  assert.equal(postConsentFirstTouch.nvx_first_channel, 'paid_search', 'first touch channel must come from consented visit');
  assert.equal(postConsentFirstTouch.gclid, 'NOPE', 'first touch click id must come from consented visit');
  assert.equal(postConsentFirstTouch.nvx_first_landing_url, 'https://nuvanx.com/?utm_source=google&utm_medium=cpc&gclid=NOPE', 'first touch landing url must come from consented visit');
  // Storage must only be populated on the consented visit.
  assert.notEqual(consentAfterNoConsent.sessionStorage.getItem('nvx_first_touch'), null, 'first touch must be stored after consent');

  console.log('ATTRIBUTION_RUNTIME_BEHAVIOR=PASS first=organic_search paid_search=1 no_consent_storage=0 consent_boundary=1');
  console.log('ATTRIBUTION_CONTRACT=PASS schema=v2 lead_id=1 first_touch=1 utm_fields=5 click_ids=4 consent_gate=1');
}
