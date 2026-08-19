import assert from 'node:assert/strict';
import fs from 'node:fs';

const integrationPath = 'wp-content/themes/nuvanx-medical/inc/nvx-attribution-integration.php';
const metaPath = 'wp-content/themes/nuvanx-medical/inc/nvx-meta-capi.php';

for (const path of [integrationPath, metaPath]) {
  assert.equal(fs.existsSync(path), true, `Missing Meta CAPI dependency: ${path}`);
}

const integration = fs.readFileSync(integrationPath, 'utf8');
const meta = fs.readFileSync(metaPath, 'utf8');

assert.match(integration, /\$hubspot_status < 200 \|\| \$hubspot_status >= 300/,
  'Canonical lifecycle must remain downstream of verified HubSpot 2xx');
assert.match(integration, /nvx_attribution_emit_lead_captured\( \$fields, \$marketing_consent \);/,
  'Integration owner must emit canonical lead_captured');
assert.match(integration, /do_action\(\s*'nvx_lead_captured'/,
  'Commercial lifecycle must be a WordPress event rather than Contact creation coupling');
assert.match(integration, /nvx_attribution_qa_context\(\)/,
  'Lifecycle QA identity must come from server context');
assert.match(integration, /'nvx_lead_id'\s*=>\s*\$lead_id/,
  'Lifecycle must preserve the HubSpot-bound lineage UUID');
assert.match(integration, /'marketing_consent'\s*=>\s*\$marketing_consent/,
  'Lifecycle must carry consent state for downstream purpose limitation');
assert.match(integration, /require_once __DIR__ \. '\/nvx-meta-capi\.php';/,
  'Attribution integration owner must load the optional Meta consumer');

const emitPos = integration.indexOf('nvx_attribution_emit_lead_captured( $fields, $marketing_consent );');
const consentReturnPos = integration.indexOf('if ( ! $marketing_consent || ! is_email( $email ) )');
assert.ok(emitPos >= 0 && consentReturnPos > emitPos,
  'lead_captured must exist independently of marketing consent; only marketing relays are consent-gated');

assert.match(meta, /function nvx_meta_capi_enabled\(\): bool/,
  'Meta delivery must be separately feature-gated');
assert.match(meta, /getenv\( 'NVX_META_CAPI_ENABLED' \)/,
  'Meta enablement may only come from server runtime');
assert.doesNotMatch(meta, /NVX_META_CAPI_ENABLED[^\n]*(?:=|=>)\s*(?:true|1)/,
  'Meta must not be enabled by a source-code default');
assert.match(meta, /getenv\( 'NVX_WEB_EVENT_SECRET' \)/,
  'Relay secret must come from server runtime');
assert.doesNotMatch(meta, /NVX_WEB_EVENT_SECRET[^\n]+[A-Za-z0-9_-]{32,}/,
  'Relay secret must never be hardcoded');
assert.match(meta, /return 'https:\/\/ssvvuuysgxyqvmovrlvk\.supabase\.co\/functions\/v1\/web-events';/,
  'Meta endpoint must be pinned and non-overrideable');

const listenerPos = meta.indexOf('function nvx_meta_capi_on_lead_captured');
const outboundPos = meta.indexOf('$response = wp_remote_post(', listenerPos);
assert.ok(listenerPos >= 0 && outboundPos > listenerPos, 'Meta listener must be parseable');
const beforeOutbound = meta.slice(listenerPos, outboundPos);
assert.match(beforeOutbound, /if\s*\(\s*!\s*nvx_meta_capi_enabled\(\)\s*\)\s*\{\s*return;\s*\}/,
  'Meta delivery must be explicitly gated and fail closed');
assert.match(beforeOutbound, /! empty\( \$event\['nvx_is_test_lead'\] \)/,
  'QA must be suppressed before any outbound Meta HTTP');
assert.match(beforeOutbound, /empty\( \$event\['marketing_consent'\] \)/,
  'Meta delivery must require explicit marketing consent');
assert.match(beforeOutbound, /'' === \$secret/,
  'Missing rotated relay secret must fail closed');

assert.match(meta, /'event_name'\s*=>\s*'Lead'/,
  'Canonical capture must map to one Meta Lead event');
assert.match(meta, /'event_id'\s*=>\s*'nvx-lead-' \. \$lead_id/,
  'Meta event_id must be deterministic from nvx_lead_id');
assert.doesNotMatch(meta, /['"](?:treatment|condition|procedure|diagnosis|message|page_url|page_title|landing_url|body_area)['"]/i,
  'Meta consumer must not propagate clinical or page-level semantics');
assert.doesNotMatch(meta, /window\.|document\.|fetch\(/,
  'Meta transport must remain server-side');

console.log('META_CAPI_OWNER_CONTRACT=PASS lifecycle=hubspot_2xx consent_independent=1 feature_flag=fail_closed qa=suppressed medical_data=minimized');
