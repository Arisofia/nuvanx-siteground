import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';

const runtimePath = 'wp-content/themes/nuvanx-medical/assets/js/nvx-attribution-contract.js';
const directPath = 'wp-content/themes/nuvanx-medical/inc/nvx-valoracion-direct-form.php';
const gtmPath = 'wp-content/themes/nuvanx-medical/inc/nvx-gtm-integration.php';
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
  assert.match(provisioner, /FORMS_API_BASE="\$\{HUBSPOT_FORMS_API_BASE:-https:\/\/api\.hubapi\.com\/marketing\/forms\/2026-09-beta\}"/,
    'Canonical form reads and writes must use the versioned Forms API endpoint that supports the live HubSpot V4 form');
  assert.doesNotMatch(provisioner, /marketing\/v3\/forms/,
    'Legacy marketing/v3/forms must not re-enter the canonical provisioner for this V4 form');
  assert.match(provisioner, /request GET "\$FORMS_API_BASE\/\$FORM_ID"/,
    'Canonical form read must use the versioned Forms API base');
  assert.match(provisioner, /request PATCH "\$FORMS_API_BASE\/\$FORM_ID"/,
    'Canonical form patch must use the versioned Forms API base');
  assert.match(provisioner, /form_field_type='single_line_text'/,
    'Versioned Forms API text fields must use the single_line_text field type id');
  assert.match(provisioner, /form_field_type='single_checkbox'/,
    'Versioned Forms API boolean fields must use the single_checkbox field type id');
  assert.match(provisioner, /--arg fieldType "\$form_field_type"/,
    'Form patch must use the Forms field type mapping, not CRM property fieldType values');
  assert.match(provisioner, /FORM_MAX_FIELDS_PER_GROUP=3/,
    'Forms writer must codify the live maximum of three fields per field group');
  assert.match(provisioner, /normalize_form_groups_for_write\(\)/,
    'Provisioner must normalize legacy oversized field groups before writing the form');
  assert.match(provisioner, /\(\$group \+ \{fields: \[\$field\]\}\)/,
    'Oversized legacy groups must preserve each existing field object verbatim while splitting layout');
  assert.match(provisioner, /verify_visible_form_baseline\(\)/,
    'Form normalization must protect the four visible required identity fields');
  assert.match(provisioner, /range\(0; \(\$fields \| length\); \$max\)/,
    'New hidden fields must be chunked into write-valid groups instead of one oversized default group');
  assert.match(provisioner, /HUBSPOT_FORM_GROUP_CONTRACT=FAIL/,
    '--check must fail closed on a legacy group that the current Forms API would refuse to write');
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
  console.log(`HUBSPOT_ATTRIBUTION_PROVISIONER_SYNTAX=PASS schema=v2 managed=${managedV2.length} bool_options=1 nounset_safe=1 forms_versioned_api=1 forms_field_types=1 form_group_normalization=1`);
}

if (!fs.existsSync(runtimePath)) {
  console.log('ATTRIBUTION_CONTRACT=SKIP runtime_absent=1');
} else {
  const runtime = fs.readFileSync(runtimePath, 'utf8');
  const direct = fs.readFileSync(directPath, 'utf8');
  const gtm = fs.readFileSync(gtmPath, 'utf8');

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

  for (const [utm, property] of utmPairs) {
    assert.match(runtime, new RegExp(`${utm}: '${property}'`), `Embed runtime must map ${utm} to ${property}`);
    assert.match(direct, new RegExp(`'${utm}'\\s*=>\\s*array\\( '${property}' \\)`),
      `First-party server bridge must map ${utm} to ${property}`);
  }
  for (const [click, property] of clickPairs) {
    assert.match(runtime, new RegExp(`${click}: '${property}'`), `Embed runtime must map ${click} to ${property}`);
    assert.match(direct, new RegExp(`'${click}'\\s*=>\\s*array\\( '${property}' \\)`),
      `First-party server bridge must map ${click} to ${property}`);
  }

  for (const name of [
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
  ]) {
    assert.match(runtime, new RegExp(`\\b${name}\\b`), `Runtime must populate ${name}`);
    assert.match(direct, new RegExp(`'${name}'`), `Direct form contract must carry ${name}`);
  }

  assert.match(runtime, /function classifyChannel\(/,
    'Runtime must classify paid, organic, referral and direct traffic explicitly');
  assert.match(runtime, /organic_search/,
    'First-touch classifier must represent organic search without requiring UTMs');
  assert.match(runtime, /organic_social/,
    'First-touch classifier must represent organic social without requiring UTMs');
  assert.match(runtime, /referral/,
    'First-touch classifier must represent external referral traffic');
  assert.match(runtime, /direct/,
    'First-touch classifier must represent direct traffic');
  assert.match(runtime, /nvx_first_touch/,
    'Runtime must persist a distinct first-touch snapshot');
  assert.match(runtime, /nvx_conversion_touch/,
    'Runtime must maintain a distinct conversion/last-touch snapshot');

  assert.match(direct, /'1' !== nvx_valoracion_attribution_value\( 'nvx_marketing_consent', 1 \)/,
    'Server bridge must refuse marketing attribution without the consent marker');
  assert.doesNotMatch(direct, /\bhs_google_click_id\b/,
    'First-party submission must not depend on a native HubSpot system field');
  assert.match(direct, /nvx_valoracion_append_field\( \$fields, 'nvx_lead_id', nvx_valoracion_lead_id\(\) \)/,
    'Lead lineage ID must be appended independently from marketing attribution');
  assert.doesNotMatch(direct, /new URLSearchParams\(/,
    'PHP markup must not bypass consent by copying marketing query params inline');
  assert.match(gtm, /'nvx-attribution-contract'/,
    'WordPress must enqueue the attribution contract');
  assert.match(gtm, /add_action\( 'wp_enqueue_scripts', 'nvx_gtm_enqueue_attribution_contract', 9 \)/,
    'Attribution contract must enqueue before the conversion relay');

  console.log('ATTRIBUTION_CONTRACT=PASS schema=v2 lead_id=1 first_touch=1 conversion_touch=1 utm_fields=5 click_ids=4 consent_gate=1 first_party_parity=1');
}
