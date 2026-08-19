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
assert.match(relay, /\$status < 200 \|\| \$status >= 300/,
  'Relay must require a real 2xx HubSpot response before recording a capture');

assert.match(relay, /https:\/\/[a-z0-9-]+\.supabase\.co\/functions\/v1\/runtime-bootstrap/,
  'Relay must use the pinned Supabase runtime bootstrap endpoint');
assert.match(relay, /https:\/\/[a-z0-9-]+\.supabase\.co\/functions\/v1\/lead-captured/,
  'Relay must use the pinned Supabase canonical capture endpoint');
assert.match(relay, /defined\( 'NVX_HUBSPOT_ACCESS_TOKEN' \)/,
  'Relay must reuse the existing server-only HubSpot credential mechanism');
assert.doesNotMatch(relay, /NUVANX_LEAD_CAPTURE_SECRET/,
  'Relay must not require a second manually provisioned capture secret');

assert.match(relay, /'Authorization'\s*=>\s*'Bearer '\s*\.\s*\$token/,
  'Bootstrap must authenticate using the existing HubSpot server credential');
assert.match(relay, /nvx_lead_captured_derive_hmac_key/,
  'Capture must use a derived HMAC key instead of the raw token');
assert.match(relay, /\$hmac_key\s*=\s*nvx_lead_captured_derive_hmac_key/,
  'HMAC key must be derived from the token');
assert.match(relay, /hash_hmac\(\s*'sha256',\s*\$timestamp\s*\.\s*'\.'\s*\.\s*\$body,\s*\$hmac_key\s*\)/,
  'Capture body must be timestamped and HMAC-signed with derived key');
assert.match(relay, /'x-nvx-timestamp'\s*=>\s*\$timestamp/,
  'Signed capture must send its timestamp');
assert.match(relay, /'x-nvx-signature'\s*=>\s*\$signature/,
  'Signed capture must send only the HMAC signature, not the token');
assert.match(relay, /401 === \$relay_status \|\| 503 === \$relay_status/,
  'Authentication/bootstrap failures must force exactly one re-bootstrap path');
assert.match(relay, /nvx_lead_captured_bootstrap_runtime\( \$token, true \)/,
  'Stale Vault/bootstrap state must be recoverable once');

const bootstrapRuntimeMatches =
  relay.match(/nvx_lead_captured_bootstrap_runtime\( \$token, true \)/g) || [];
assert.equal(
  bootstrapRuntimeMatches.length,
  1,
  'Bootstrap runtime must be invoked exactly once for stale Vault/bootstrap recovery',
);

const bootstrapRuntimeMatches =
  relay.match(/nvx_lead_captured_bootstrap_runtime\( \$token, true \)/g) || [];
assert.equal(
  bootstrapRuntimeMatches.length,
  1,
  'Bootstrap runtime must be invoked exactly once for stale Vault/bootstrap recovery',
);

assert.match(relay, /\$email_hash\s*=\s*'' !== \$email \? hash\( 'sha256', \$email \) : null;/,
  'Relay must derive a one-way email hash before payload construction');
assert.match(relay, /unset\( \$email \);/,
  'Relay must discard the raw email variable before constructing the canonical payload');
assert.doesNotMatch(relay, /['"](?:treatment|condition|procedure|diagnosis|body_area)['"]/i,
  'Relay payload must contain no clinical-treatment semantics');

const payloadStart = relay.indexOf('$relay_payload = array(');
const encodeStart = relay.indexOf('$relay_body = wp_json_encode(', payloadStart);
assert.ok(payloadStart >= 0 && encodeStart > payloadStart, 'Canonical relay payload block must be parseable');
const payloadBlock = relay.slice(payloadStart, encodeStart);
assert.match(payloadBlock, /'email_hash'\s*=>\s*\$email_hash/,
  'Canonical payload may carry only the one-way email hash');
assert.doesNotMatch(payloadBlock, /['"](?:email|phone|phone_number|name|first_name|last_name|full_name|token|authorization)['"]\s*=>/i,
  'Canonical payload must not include direct PII or credentials');

// Transitional gate: current master predates explicit consent persistence. Once a
// candidate adds marketing_consent, the stricter server-provenance contract becomes
// mandatory and remains mandatory after that runtime is merged.
const consentAware = /'marketing_consent'\s*=>/.test(payloadBlock);
if (consentAware) {
  assert.match(
    relay,
    /\$marketing_consent\s*=\s*function_exists\( 'nvx_hubspot_secure_post_value' \)[\s\S]{0,180}'1' === nvx_hubspot_secure_post_value\( 'nvx_marketing_consent', 1 \)/,
    'Capture consent must be re-derived server-side from the validated first-party request',
  );
  assert.match(payloadBlock, /'marketing_consent'\s*=>\s*\$marketing_consent/,
    'Explicit marketing consent must reach the canonical capture ledger');
  assert.match(relay, /\$relay_body\s*=\s*wp_json_encode\( \$relay_payload \);/,
    'Capture payload must be encoded before transport');
  assert.match(relay, /false === \$relay_body/,
    'JSON encoding failure must fail closed before network transport');
  assert.match(relay, /'body'\s*=>\s*\$relay_body/,
    'Only a successfully encoded capture payload may be transmitted');
} else {
  console.log('LEAD_CAPTURED_CONSENT_GATE=MIGRATION_PENDING');
}

assert.match(relay, /HubSpot response IDs unavailable; status=%d json_error=%d/,
  'Unexpected HubSpot response structure must be observable without logging response content');
assert.doesNotMatch(relay, /Snippet:|substr\(\s*\$body|json_last_error_msg\(\)/,
  'Observability must not log HubSpot body fragments or verbose decode content');
assert.match(relay, /runtime bootstrap HTTP failure; status=%d/,
  'Bootstrap failure logs may expose status only');
assert.match(relay, /relay transport failure; wp_error_code=%s/,
  'Transport failures must expose a bounded machine-readable error code');
assert.match(relay, /relay HTTP failure; status=%d/,
  'HTTP failures must expose status without response body content');

const logLines = relay.split('\n').filter((line) => line.includes('error_log') || line.includes('sprintf('));
assert.doesNotMatch(logLines.join('\n'), /\$token|Authorization|x-nvx-signature/,
  'Credential material must never be referenced in log statements');

assert.match(relay, /'nvx_is_test_lead'\s*=>\s*\$is_test/,
  'Server-owned QA identity must reach the capture ledger');
assert.match(relay, /'nvx_test_run_id'\s*=>/,
  'Server-owned QA run lineage must reach the capture ledger');
assert.match(relay, /'nvx_lead_id'\s*=>\s*\$lead_id/,
  'Canonical first-party lineage must reach the capture ledger');

assert.doesNotMatch(relay, /graph\.facebook\.com|functions\/v1\/web-events|googleads\.|crm\/v3\/objects\/deals/i,
  'Capture relay must not contain executable downstream advertising or Deal endpoints');

console.log(`LEAD_CAPTURED_SERVER_RELAY=PASS consent=${consentAware ? 'server-derived' : 'migration-pending'}`);
