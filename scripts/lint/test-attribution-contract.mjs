import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';

const runtimePath = 'wp-content/themes/nuvanx-medical/assets/js/nvx-attribution-contract.js';
const directPath = 'wp-content/themes/nuvanx-medical/inc/nvx-valoracion-direct-form.php';
const gtmPath = 'wp-content/themes/nuvanx-medical/inc/nvx-gtm-integration.php';
const provisionerPath = 'scripts/ci/provision-hubspot-attribution-contract.sh';

if (fs.existsSync(provisionerPath)) {
  const bashLint = spawnSync('bash', ['-n', provisionerPath], { encoding: 'utf8' });
  assert.equal(
    bashLint.status,
    0,
    `HubSpot attribution provisioner must pass bash -n: ${bashLint.stderr || bashLint.stdout || 'unknown error'}`,
  );
  const provisioner = fs.readFileSync(provisionerPath, 'utf8');
  assert.match(
    provisioner,
    /HUBSPOT_PROPERTY_CONTRACT=FAIL property=\$name/,
    'Property contract mismatches must emit an explicit FAIL diagnostic',
  );
  assert.match(
    provisioner,
    /required_form_fields=\("\$\{required_properties\[@\]\}"\)/,
    'Form field contract must derive from the property list',
  );
  assert.doesNotMatch(
    provisioner,
    /\bhs_google_click_id\b/,
    'Native HubSpot Google click property must remain opportunistic, not a provisioning dependency',
  );
  console.log('HUBSPOT_ATTRIBUTION_PROVISIONER_SYNTAX=PASS');
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
    'First-touch marketing snapshot must have the documented 90-day TTL');
  assert.match(runtime, /nvx_landing_url/,
    'Runtime must carry the landing URL');
  assert.match(runtime, /nvx_attribution_captured_at/,
    'Runtime must carry attribution captured timestamp');
  assert.match(runtime, /nvx_attribution_expires_at/,
    'Runtime must carry attribution expiry timestamp');
  assert.match(runtime, /CLICK_KEYS = \['gclid', 'gbraid', 'wbraid', 'gclsrc'\]/,
    'Runtime must own the canonical Google click-id set');
  assert.match(runtime, /UTM_KEYS\.concat\(CLICK_KEYS\)/,
    'Stored first-touch attribution must retain click identifiers even when UTMs are absent');
  assert.match(runtime, /fieldCandidates\(propertyName\)/,
    'Embed population must check canonical HubSpot field candidates');
  assert.match(runtime, /available\.has\(fieldName\)/,
    'Embed must not write fields absent from the canonical form definition');

  for (const [utm, property] of utmPairs) {
    assert.match(runtime, new RegExp(`${utm}: '${property}'`), `Embed runtime must map ${utm} to ${property}`);
    assert.match(direct, new RegExp(`'${utm}'\\s*=>\\s*array\\( '${property}' \\)`),
      `First-party server bridge must map ${utm} to ${property}`);
    assert.match(direct, new RegExp(`'${utm}'`), `Direct form must render hidden ${utm}`);
  }

  for (const [click, property] of clickPairs) {
    assert.match(runtime, new RegExp(`${click}: '${property}'`), `Embed runtime must map ${click} to ${property}`);
    assert.match(direct, new RegExp(`'${click}'\\s*=>\\s*array\\( '${property}' \\)`),
      `First-party server bridge must map ${click} to ${property}`);
  }

  for (const name of [
    'nvx_lead_id',
    'nvx_marketing_consent',
    'gclid',
    'gbraid',
    'wbraid',
    'gclsrc',
    'nvx_landing_url',
    'nvx_attribution_captured_at',
    'nvx_attribution_expires_at',
  ]) {
    assert.match(direct, new RegExp(`'${name}'`), `Direct form contract must include ${name}`);
  }

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

  console.log('ATTRIBUTION_CONTRACT=PASS lead_id=1 utm_fields=5 click_ids=4 metadata=3 consent_gate=1 first_party_parity=1');
}
