import fs from 'node:fs';
import assert from 'node:assert/strict';

const gtmPath = 'wp-content/themes/nuvanx-medical/inc/nvx-gtm-integration.php';
const relayPath = 'wp-content/themes/nuvanx-medical/inc/nvx-lead-captured-relay.php';

assert.equal(fs.existsSync(relayPath), true, 'Canonical lead-captured relay must exist');
const gtm = fs.readFileSync(gtmPath, 'utf8');
const relay = fs.readFileSync(relayPath, 'utf8');

const secureRequire = gtm.indexOf("require_once __DIR__ . '/nvx-hubspot-secure-attribution.php';");
const relayRequire = gtm.indexOf("require_once __DIR__ . '/nvx-lead-captured-relay.php';");
assert.ok(secureRequire >= 0, 'Secure HubSpot bridge must remain loaded');
assert.ok(relayRequire > secureRequire, 'Lead-captured relay must load after the secure HubSpot bridge');

assert.match(relay, /add_filter\( 'http_response', 'nvx_lead_captured_on_http_response', 10, 3 \)/,
  'Relay must observe completed HTTP responses rather than browser events');
assert.match(relay, /nvx_hubspot_secure_submit_url\(\) !== \$url/,
  'Relay must scope itself to the authenticated HubSpot transport only');
assert.match(relay, /'' === \$endpoint \|\| \$url === \$endpoint/,
  'Relay must fail closed without config and never observe its own Supabase POST');
assert.match(relay, /getenv.*NVX_LEAD_CAPTURE_ENDPOINT/,
  'Capture endpoint may only come from server runtime configuration');
assert.doesNotMatch(
  relay,
  /getenv\(\s*'NVX_LEAD_CAPTURE_ENDPOINT'\s*\)\s*\?: ''[\s\S]{0,120}return 'https:/,
  'Unconfigured capture endpoint must not fall back to a hardcoded URL',
);
assert.match(relay, /\$status < 200 \|\| \$status >= 300/,
  'Relay must require a real 2xx HubSpot response before recording a capture');

assert.match(relay, /defined.*NUVANX_LEAD_CAPTURE_SECRET/,
  'Relay secret must come from server runtime configuration');
assert.doesNotMatch(relay, /NUVANX_LEAD_CAPTURE_SECRET[^\n]+['"][A-Za-z0-9_-]{16,}['"]/,
  'Relay must never contain a hardcoded secret fallback');
assert.match(relay, /'x-nvx-lead-capture-secret' => \$secret/,
  'Relay must authenticate to the canonical capture endpoint');

assert.match(relay, /\$email_hash\s*=\s*'' !== \$email \? hash\( 'sha256', \$email \) : null;/,
  'Relay must derive a one-way email hash before payload construction');
assert.match(relay, /unset\( \$email \);/,
  'Relay must discard the raw email variable before constructing the canonical payload');
assert.doesNotMatch(relay, /['"](?:treatment|condition|procedure|diagnosis|body_area)['"]/i,
  'Relay payload must contain no clinical-treatment semantics');

const payloadStart = relay.indexOf('$relay_payload = array(');
const postStart = relay.indexOf('$relay = wp_remote_post(', payloadStart);
assert.ok(payloadStart >= 0 && postStart > payloadStart, 'Canonical relay payload block must be parseable');
const payloadBlock = relay.slice(payloadStart, postStart);
assert.match(payloadBlock, /'email_hash'\s*=>\s*\$email_hash/,
  'Canonical payload may carry only the one-way email hash');
assert.doesNotMatch(payloadBlock, /['"](?:email|phone|phone_number|name|first_name|last_name|full_name)['"]\s*=>/i,
  'Canonical payload must not include direct email, phone or name fields');

// Transitional gate: current master predates explicit consent persistence. Once a
// candidate adds marketing_consent, the stricter server-provenance contract becomes
// mandatory and remains mandatory after that runtime is merged.
const consentAware = /'marketing_consent'\s*=>/.test(payloadBlock);
if (consentAware) {
  assert.match(
    relay,
    /function_exists.*nvx_hubspot_secure_post_value/,
    'Capture consent must be re-derived server-side from the validated first-party request',
  );
  assert.match(
    relay,
    /nvx_hubspot_secure_post_value.*nvx_marketing_consent/,
    'Capture consent must derive from the marketing_consent field',
  );
  assert.match(
    relay,
    /===.*['"]1['"]/,
    'Capture consent must use strict comparison for consent value',
  );
  assert.doesNotMatch(
    relay,
    /nvx_hubspot_secure_post_value.*nvx_marketing_consent.*1.*\)/,
    'Capture consent must default to false when field is absent, not true',
  );
  assert.match(payloadBlock, /'marketing_consent'\s*=>\s*\$marketing_consent/,
    'Explicit marketing consent must reach the canonical capture ledger');
  
  // Check for conditional attribution patterns (consent-aware mode)
  const hasConditionalAttribution = /\?.*nvx_lead_captured_attribution/.test(payloadBlock);
  if (hasConditionalAttribution) {
    assert.match(payloadBlock, /'first_attribution'\s*=>\s*\$marketing_consent\s*\?/,
      'First-touch attribution must be conditional on marketing consent');
    assert.match(payloadBlock, /'conversion_attribution'\s*=>\s*\$marketing_consent\s*\?/,
      'Conversion attribution must be conditional on marketing consent');
    assert.doesNotMatch(payloadBlock, /'first_attribution'\s*=>\s*nvx_lead_captured_attribution(?!\s*\?)/,
      'First-touch attribution must never be set unconditionally when marketing consent is conditional');
    assert.doesNotMatch(payloadBlock, /'conversion_attribution'\s*=>\s*nvx_lead_captured_attribution(?!\s*\?)/,
      'Conversion attribution must never be set unconditionally when marketing consent is conditional');
  }
  
  assert.match(relay, /\$relay_body\s*=\s*wp_json_encode/,
    'Capture payload must be encoded before transport');
  assert.match(relay, /false === \$relay_body/,
    'JSON encoding failure must fail closed before network transport');
  assert.match(relay, /'body'\s*=>\s*\$relay_body/,
    'Only a successfully encoded capture payload may be transmitted');
  assert.match(relay, /\$relay_identifier/,
    'JSON encoding failure logging must include an identifier');
  assert.match(relay, /json_error/,
    'JSON encoding failure logging must include json_last_error_msg');
} else {
  console.log('LEAD_CAPTURED_CONSENT_GATE=MIGRATION_PENDING');
}

assert.match(relay, /HubSpot response IDs unavailable; status=%d json_error=%d/,
  'Unexpected HubSpot response structure must be observable without logging response content');
assert.doesNotMatch(relay, /Snippet:|substr\(\s*\$body/,
  'Observability must not log HubSpot body fragments');
assert.doesNotMatch(relay, /json_last_error_msg\(\).*HubSpot/,
  'Observability must not log HubSpot verbose decode content');
assert.doesNotMatch(relay, /json_last_error_msg\(\).*response/,
  'Observability must not log HubSpot response verbose decode content');
assert.match(relay, /relay transport failure; wp_error_code=%s/,
  'Transport failures must expose a bounded machine-readable error code');
assert.match(relay, /relay HTTP failure; status=%d/,
  'HTTP failures must expose status without response body content');

assert.match(relay, /'nvx_is_test_lead'\s*=>\s*\$is_test/,
  'Server-owned QA identity must reach the capture ledger');
assert.match(relay, /'nvx_test_run_id'\s*=>/,
  'Server-owned QA run lineage must reach the capture ledger');
assert.match(relay, /'nvx_lead_id'\s*=>\s*\$lead_id/,
  'Canonical first-party lineage must reach the capture ledger');

assert.doesNotMatch(relay, /graph\.facebook\.com|functions\/v1\/web-events|googleads\.|crm\/v3\/objects\/deals/i,
  'Capture relay must not contain executable downstream advertising or Deal endpoints');

console.log(`LEAD_CAPTURED_SERVER_RELAY=PASS consent=${consentAware ? 'server-derived' : 'migration-pending'}`);
