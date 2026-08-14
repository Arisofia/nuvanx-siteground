import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import { classifyHubSpotSubmissionRequest } from './hubspot-submission-classifier.mjs';

const portalId = '147416356';
const formId = '5042522a-0bc5-4381-ac3e-5aee8649b69c';

const classify = (method, url) => classifyHubSpotSubmissionRequest({ method, url, portalId, formId });

assert.equal(
  classify('POST', `https://api.hsforms.com/submissions/v3/integration/submit/${portalId}/${formId}`).isSubmission,
  true,
  'unauthenticated v3 submit endpoint must be blocked'
);
assert.equal(
  classify('POST', `https://api.hsforms.com/submissions/v3/integration/secure/submit/${portalId}/${formId}`).isSubmission,
  true,
  'secure v3 submit endpoint must be blocked'
);
assert.equal(
  classify('POST', `https://api-eu1.hsforms.com/submissions/v3/integration/submit/${portalId}/${formId}`).isSubmission,
  true,
  'regional hsforms submit endpoint must be blocked'
);
assert.equal(
  classify('GET', `https://api.hsforms.com/submissions/v3/integration/submit/${portalId}/${formId}`).isSubmission,
  false,
  'GET is not a submission'
);
assert.equal(
  classify('POST', `https://forms.hsforms.com/embed/v3/form/${portalId}/${formId}`).isSubmission,
  false,
  'form bootstrap URL containing the form ID must not be classified as a submission'
);
assert.equal(
  classify('POST', `https://forms.hsforms.com/telemetry?formId=${formId}`).isSubmission,
  false,
  'telemetry containing the form ID must not be classified as a submission'
);
assert.equal(
  classify('POST', `https://api.hsforms.com/submissions/v3/integration/submit/${portalId}/00000000-0000-0000-0000-000000000000`).isSubmission,
  false,
  'another form must not be classified as this form submission'
);
assert.equal(
  classify('POST', `https://example.com/submissions/v3/integration/submit/${portalId}/${formId}`).isSubmission,
  false,
  'lookalike endpoint on another host must not be blocked'
);

// Production release verification must never create a contact or synthetic
// attribution event. Keep this source-level regression beside the submission
// classifier so every production-eligible Staging acceptance executes it.
const productionProbe = await fs.readFile(new URL('./h1-hubspot-e2e.mjs', import.meta.url), 'utf8');
const forbiddenPatterns = [
  [/from\s+['"]playwright['"]/, 'production HubSpot probe must not launch a browser'],
  [/nvxqa-h1-/i, 'production HubSpot probe must not generate QA contact emails'],
  [/QA H1 Attribution/i, 'production HubSpot probe must not synthesize contact names'],
  [/wp_set_consent/i, 'production HubSpot probe must not grant marketing consent'],
  [/\?gclid=/i, 'production HubSpot probe must not generate synthetic paid-search attribution'],
  [/\.click\s*\(/, 'production HubSpot probe must not click a form submit control'],
  [/submissions\/v3/i, 'production HubSpot probe must not call or monitor submission endpoints'],
];

for (const [pattern, message] of forbiddenPatterns) {
  assert.doesNotMatch(productionProbe, pattern, message);
}
assert.match(
  productionProbe,
  /HUBSPOT_PRODUCTION_CONTRACT_MODE=ZERO_SUBMIT/,
  'production HubSpot probe must declare the zero-submit contract'
);
assert.match(
  productionProbe,
  /PRODUCTION_HUBSPOT_CONTRACT=PASS/,
  'production HubSpot probe must expose an auditable zero-submit PASS marker'
);

console.log('HUBSPOT_SUBMISSION_CLASSIFIER_TEST=PASS cases=8');
console.log('HUBSPOT_PRODUCTION_ZERO_SUBMIT_GUARD=PASS forbidden_patterns=7');
