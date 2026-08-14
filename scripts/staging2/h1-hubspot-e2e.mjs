import assert from 'node:assert/strict';
import fs from 'node:fs/promises';

const formId = '5042522a-0bc5-4381-ac3e-5aee8649b69c';
const portalId = '147416356';
const expectedSha = (process.env.EXPECTED_SHA || '').trim();

if (!/^[0-9a-f]{40}$/.test(expectedSha)) {
  throw new Error(`EXPECTED_SHA must be a full lowercase 40-hex commit SHA; received=${JSON.stringify(expectedSha)}`);
}

// Historical note: this filename is retained temporarily because production.yml
// still references it. It is intentionally NOT a browser E2E anymore. The prior
// implementation submitted the live HubSpot form and created QA contacts in the
// commercial portal. Production verification must be zero-submit and zero-tracking.
// The workflow verifies the live production SHA immediately before invoking this
// script; this script validates the exact candidate's form/attribution contract
// without executing page JavaScript, granting consent, creating a synthetic GCLID,
// or calling any HubSpot submission endpoint.
const managedPageUrl = new URL(
  '../../wp-content/themes/nuvanx-medical/inc/nvx-valoracion-managed-page.php',
  import.meta.url
);
const conversionEventsUrl = new URL(
  '../../wp-content/themes/nuvanx-medical/assets/js/nvx-conversion-events.js',
  import.meta.url
);
const runtimeGovernanceUrl = new URL(
  '../../wp-content/themes/nuvanx-medical/assets/js/nvx-runtime-governance.js',
  import.meta.url
);

const [managedPage, conversionEvents, runtimeGovernance] = await Promise.all([
  fs.readFile(managedPageUrl, 'utf8'),
  fs.readFile(conversionEventsUrl, 'utf8'),
  fs.readFile(runtimeGovernanceUrl, 'utf8'),
]);

const escapedFormId = formId.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
const escapedPortalId = portalId.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

assert.match(
  managedPage,
  new RegExp(`data-form-id=\\"[^\\"]*${escapedFormId}[^\\"]*\\"`),
  'managed valoración page must render the canonical HubSpot form ID'
);
assert.match(
  managedPage,
  new RegExp(`data-portal-id=\\"[^\\"]*${escapedPortalId}[^\\"]*\\"`),
  'managed valoración page must render the canonical HubSpot portal ID'
);
assert.match(
  managedPage,
  /id=\\"nvx-hubspot-form\\"/,
  'managed valoración page must retain the canonical HubSpot section mount'
);
assert.match(
  managedPage,
  /id=\\"nvx-hubspot-native-form\\"/,
  'managed valoración page must retain the canonical native HubSpot host'
);
assert.match(
  managedPage,
  /data-nvx-hubspot-native=\\"1\\"/,
  'managed valoración page must identify the canonical HubSpot runtime mount'
);
assert.doesNotMatch(
  managedPage,
  /nvx-hs-lead-form/,
  'managed valoración page must not reintroduce the legacy captured non-HubSpot form'
);

assert.match(
  conversionEvents,
  /nvx_google_click_id/,
  'attribution runtime must retain the custom Google click ID field'
);
assert.match(
  conversionEvents,
  new RegExp(escapedFormId),
  'attribution runtime must remain scoped to the canonical HubSpot form'
);
assert.match(
  conversionEvents,
  /normalizedPath\s*===\s*['"]\/madrid\/valoracion['"]/,
  'Google attribution must remain scoped to /madrid/valoracion'
);
assert.match(
  conversionEvents,
  /hasMarketingConsent/,
  'Google attribution must remain consent-gated'
);

assert.match(
  runtimeGovernance,
  /nvx-hubspot-native-form/,
  'runtime governance must retain the canonical HubSpot mount selector'
);
assert.match(
  runtimeGovernance,
  /Formulario de valoración médica/,
  'runtime governance must retain an accessible HubSpot iframe name'
);

console.log(`EXPECTED_SHA=${expectedSha}`);
console.log(`HUBSPOT_FORM_ID=${formId}`);
console.log(`HUBSPOT_PORTAL_ID=${portalId}`);
console.log('HUBSPOT_PRODUCTION_CONTRACT_MODE=ZERO_SUBMIT');
console.log('H1_BROWSER_E2E=PASS mode=zero-submit-static-contract');
console.log('PRODUCTION_HUBSPOT_CONTRACT=PASS zero_submit=1 javascript_executed=0 contact_created=0');
