import assert from 'node:assert/strict';
import fs from 'node:fs';

const runtimePath = 'wp-content/themes/nuvanx-medical/assets/js/nvx-attribution-contract.js';
const directPath = 'wp-content/themes/nuvanx-medical/inc/nvx-valoracion-direct-form.php';
const gtmPath = 'wp-content/themes/nuvanx-medical/inc/nvx-gtm-integration.php';
const bridgePath = 'wp-content/themes/nuvanx-medical/inc/nvx-hubspot-secure-attribution.php';

for (const path of [runtimePath, directPath, gtmPath, bridgePath]) {
  assert.equal(fs.existsSync(path), true, `Runtime Contract v3 dependency missing: ${path}`);
}

const runtime = fs.readFileSync(runtimePath, 'utf8');
const direct = fs.readFileSync(directPath, 'utf8');
const gtm = fs.readFileSync(gtmPath, 'utf8');
const bridge = fs.readFileSync(bridgePath, 'utf8');

assert.match(bridge, /Runtime Contract v3\./,
  'Bridge must declare Runtime Contract v3 explicitly');

assert.match(runtime, /var LEAD_SESSION_KEY = 'nvx_lead_id'/,
  'Browser lineage must remain session-scoped');
assert.match(runtime, /function isUuidV4\(/,
  'Browser runtime must validate UUID v4 lineage');
assert.match(runtime, /nvx_is_test_lead:\s*qa\.is_test_lead === true/,
  'Browser QA projection must originate from server-provided context');
assert.match(runtime, /if \(!available\.has\(fieldName\)\) return;/,
  'Browser payload construction must skip fields absent from the live V4 form');

assert.match(gtm, /function nvx_attribution_qa_context\(\): array/,
  'WordPress must remain the canonical QA owner');
assert.match(gtm, /nvx_environment_is_staging2\(\)/,
  'Only Staging2 may become an automatic QA environment');
assert.match(gtm, /window\.nvxConversionEvents\.qa=Object\.assign/,
  'Server-owned QA context must be exposed to browser code read-only by contract');
assert.match(gtm, /require_once __DIR__ \. '\/nvx-hubspot-secure-attribution\.php'/,
  'Runtime Contract v3 bridge must remain wired by the analytics owner');

assert.match(direct, /name=\\?"nvx_lead_id\\?"/,
  'First-party direct form must carry nvx_lead_id');
assert.match(direct, /function nvx_valoracion_is_uuid_v4\(/,
  'Direct form must validate UUID v4 lineage server-side');
assert.match(direct, /\$_POST\['nvx_lead_id'\]/,
  'Direct form must prefer browser-session lineage when valid');
assert.match(direct, /wp_generate_uuid4\(\)/,
  'No-JS requests must still receive a fresh UUID v4');
assert.match(direct, /name=\\?"nvx_marketing_consent\\?"/,
  'Marketing consent must remain independent from processing consent');
assert.match(direct, /function nvx_valoracion_has_marketing_consent\(\): bool/,
  'Direct form must own an explicit marketing-consent check');
assert.doesNotMatch(direct, /get_transient\(\s*'nvx_valoracion_lead_id'/,
  'Lead lineage must never be site-global');

assert.match(bridge, /https:\/\/api\.hsforms\.com\/submissions\/v3\/integration\/submit\//,
  'Bridge must intercept the canonical public Forms transport');
assert.match(bridge, /https:\/\/api\.hsforms\.com\/submissions\/v3\/integration\/secure\/submit\//,
  'Bridge must submit the form through the authenticated Forms endpoint');
assert.match(bridge, /https:\/\/api\.hubapi\.com\/crm\/v3\/objects\/contacts/,
  'Extended lineage must be enriched through the HubSpot Contacts CRM API');

const serverOwned = bridge.match(/function nvx_hubspot_secure_server_owned_fields\(\): array \{([\s\S]*?)\n\}/)?.[1] || '';
assert.ok(serverOwned, 'Server-owned QA field function must be parseable');
assert.match(serverOwned, /'nvx_is_test_lead'/,
  'QA marker must be privileged server-owned state');
assert.match(serverOwned, /'nvx_test_run_id'/,
  'QA run id must be privileged server-owned state');
assert.doesNotMatch(serverOwned, /'nvx_lead_id'/,
  'First-party lead lineage must not be discarded as a privileged QA field');

assert.match(bridge, /function nvx_hubspot_secure_qa_context\(\): array[\s\S]*?nvx_attribution_qa_context\(\)/,
  'CRM QA identity must be rebuilt from the server QA context');
assert.match(bridge, /\$out\['nvx_is_test_lead'\] = \$qa\['is_test_lead'\] \? 'true' : 'false';/,
  'CRM QA marker must be derived from server-owned QA state');
assert.match(bridge, /\$out\['nvx_test_run_id'\] = \$qa\['test_run_id'\];/,
  'CRM QA run id must be derived from server-owned QA state');
assert.doesNotMatch(bridge, /nvx_hubspot_secure_post_value\( 'nvx_is_test_lead'/,
  'Browser POST data must never enable QA mode');
assert.doesNotMatch(bridge, /nvx_hubspot_secure_post_value\( 'nvx_test_run_id'/,
  'Browser POST data must never choose the QA run id');

assert.match(bridge, /'nvx_lead_id' === \$name[\s\S]*?nvx_hubspot_secure_is_uuid_v4/,
  'First-party nvx_lead_id must cross the bridge only after UUID v4 validation');
assert.match(bridge, /foreach \( array\( 'firstname', 'lastname', 'email', 'phone', 'message', 'nvx_lead_id' \) as \$name \)/,
  'CRM enrichment must preserve message and first-party lineage outside the V4 form schema');

const formFields = bridge.match(/function nvx_hubspot_secure_form_field_names\(\): array \{([\s\S]*?)\n\}/)?.[1] || '';
assert.ok(formFields, 'Live Forms whitelist must be parseable');
const liveFormFields = [
  'firstname', 'lastname', 'email', 'phone',
  'nvx_attribution_captured_at', 'nvx_attribution_expires_at',
  'nvx_google_braid', 'nvx_google_click_id', 'nvx_google_gclsrc', 'nvx_google_wbraid',
  'nvx_landing_url', 'nvx_utm_campaign', 'nvx_utm_content', 'nvx_utm_medium',
  'nvx_utm_source', 'nvx_utm_term',
];
for (const name of liveFormFields) {
  assert.match(formFields, new RegExp(`'${name}'`), `Live Forms whitelist must include ${name}`);
}
for (const forbidden of ['message', 'nvx_lead_id', 'nvx_is_test_lead', 'nvx_test_run_id', 'nvx_first_source', 'nvx_conversion_source']) {
  assert.doesNotMatch(formFields, new RegExp(`'${forbidden}'`),
    `Live Forms whitelist must exclude unsupported field ${forbidden}`);
}
const literalFieldNames = [...formFields.matchAll(/'([^']+)'/g)].map((match) => match[1]);
assert.equal(literalFieldNames.length, 16,
  'Canonical V4 Forms transport must remain pinned to the verified 16-field live schema');
assert.equal(new Set(literalFieldNames).size, 16,
  'Canonical V4 Forms whitelist must not contain duplicates');

assert.match(bridge, /function nvx_hubspot_secure_filter_form_fields\( array \$fields \): array/,
  'Forms payload must pass through the live-schema whitelist');
assert.match(bridge, /\$payload\['fields'\] = nvx_hubspot_secure_filter_form_fields\( \$fields \);/,
  'Secure Forms transport must receive only whitelisted fields');

assert.match(bridge, /\$marketing_consent = '1' === nvx_hubspot_secure_post_value\( 'nvx_marketing_consent', 1 \)/,
  'Marketing consent must be re-derived server-side');
assert.match(bridge, /nvx_hubspot_secure_filter_fields\( \$fields, \$marketing_consent \)/,
  'Browser QA and non-consented marketing fields must be filtered before CRM enrichment');
assert.match(bridge, /if \( \$marketing_consent \) \{[\s\S]*?nvx_hubspot_secure_marketing_fields\(\)/,
  'Extended marketing properties may reach CRM only with explicit consent');

assert.match(bridge, /function nvx_hubspot_secure_payload_is_staging_qa\( array \$payload \): bool[\s\S]*?nvx_hubspot_secure_qa_context\(\)/,
  'Staging QA authorization must depend on server-owned QA context');
assert.match(bridge, /'staging2\.nuvanx\.com' === \$host[\s\S]*?true === \$qa\['is_test_lead'\][\s\S]*?strpos\( \$qa\['test_run_id'\], 'staging2-' \)/,
  'Staging outbound traffic must be bound to canonical host and deterministic server QA lineage');
assert.match(bridge, /nvx_environment_is_staging2\(\) && ! nvx_hubspot_secure_payload_is_staging_qa\( \$payload \)/,
  'Staging2 must fail closed before any HubSpot mutation unless server QA is valid');

assert.match(bridge, /nvx_hubspot_secure_find_contact\( \$email, \$token \)/,
  'CRM enrichment must resolve an existing contact before deciding create vs update');
assert.match(bridge, /'method'\s*=>\s*'PATCH'/,
  'Existing HubSpot contacts must be updated through PATCH');
assert.match(bridge, /nvx_hubspot_secure_contacts_url\(\),[\s\S]*?'Authorization' => 'Bearer ' \. \$token/,
  'New contact creation must use the server-only HubSpot credential');
assert.match(bridge, /if \( ! \$crm\['ok'\] \) \{[\s\S]*?nvx_hubspot_crm_enrichment_failed/,
  'Forms submission must fail closed when CRM enrichment fails');

const stagingGuard = bridge.indexOf("nvx_environment_is_staging2() && ! nvx_hubspot_secure_payload_is_staging_qa( $payload )");
const crmEnrichment = bridge.indexOf('$crm = nvx_hubspot_secure_enrich_contact(');
const formFilter = bridge.indexOf("$payload['fields'] = nvx_hubspot_secure_filter_form_fields( $fields );");
const secureSubmit = bridge.indexOf('nvx_hubspot_secure_submit_url(),', formFilter);
assert.ok(stagingGuard >= 0 && crmEnrichment > stagingGuard && formFilter > crmEnrichment && secureSubmit > formFilter,
  'Transport order must remain Staging guard → CRM enrichment → Forms whitelist → secure Forms submit');

assert.doesNotMatch(bridge, /skipValidation/,
  'Deprecated Forms skipValidation must not be used');
assert.doesNotMatch(bridge, /pat-eu1-[A-Za-z0-9-]{20,}/,
  'HubSpot credentials must never be hardcoded');
assert.doesNotMatch(bridge, /graph\.facebook\.com|googleads\.|crm\/v3\/objects\/deals/i,
  'HubSpot bridge must not own downstream ads or Deal creation');

console.log('ATTRIBUTION_CONTRACT_V3=PASS form_schema=16 first_party_lineage=1 qa_owner=server_crm consent=server_derived staging=fail_closed transport=crm_then_forms');
