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

assert.match(relay, /hash\( 'sha256', \$email \)/,
  'Relay may send only a one-way email hash to the capture ledger');
assert.doesNotMatch(relay, /['"](?:treatment|condition|procedure|diagnosis|body_area)['"]/i,
  'Relay payload must contain no clinical-treatment semantics');

assert.match(relay, /'nvx_is_test_lead'\s*=>\s*\$is_test/,
  'Server-owned QA identity must reach the capture ledger');
assert.match(relay, /'nvx_test_run_id'\s*=>/,
  'Server-owned QA run lineage must reach the capture ledger');
assert.match(relay, /'nvx_lead_id'\s*=>\s*\$lead_id/,
  'Canonical first-party lineage must reach the capture ledger');

assert.doesNotMatch(relay, /graph\.facebook|web-events|googleads|google ads|deal factory|create.*deal/i,
  'Capture relay must not create downstream advertising or Deal side effects');

console.log('LEAD_CAPTURED_SERVER_RELAY=PASS');
