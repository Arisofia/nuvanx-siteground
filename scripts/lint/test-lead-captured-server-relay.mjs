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

assert.match(relay, /defined\( 'NUVANX_LEAD_CAPTURE_SECRET' \)/,
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

assert.match(relay, /HubSpot response IDs unavailable; status=%d json_error=%d/,
  'Unexpected HubSpot response structure must be observable without logging response content');
assert.doesNotMatch(relay, /Snippet:|substr\(\s*\$body|json_last_error_msg\(\)/,
  'Observability must not log HubSpot body fragments or verbose decode content');
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

console.log('LEAD_CAPTURED_SERVER_RELAY=PASS');
