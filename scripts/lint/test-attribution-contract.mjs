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
