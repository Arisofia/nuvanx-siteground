import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import {
  HUBSPOT_PORTAL_ID,
  HUBSPOT_FORM_ID,
  HUBSPOT_PRODUCTION_FORBIDDEN_PATTERNS,
} from './hubspot-config.mjs';
import { classifyHubSpotSubmissionRequest } from './hubspot-submission-classifier.mjs';

const portalId = HUBSPOT_PORTAL_ID;
const formId = HUBSPOT_FORM_ID;

const classify = (method, url) => classifyHubSpotSubmissionRequest({ method, url, portalId, formId });

// 1. Unauthenticated v3 submit endpoint
const res1 = classify('POST', `https://api.hsforms.com/submissions/v3/integration/submit/${portalId}/${formId}`);
assert.equal(res1.isSubmission, true, 'unauthenticated v3 submit endpoint must be blocked');
assert.equal(res1.reason, 'exact-v3-submit-endpoint');
assert.equal(res1.hostname, 'api.hsforms.com');
assert.equal(res1.pathname, `/submissions/v3/integration/submit/${portalId}/${formId}`);

// 2. Secure v3 submit endpoint
const res2 = classify('POST', `https://api.hsforms.com/submissions/v3/integration/secure/submit/${portalId}/${formId}`);
assert.equal(res2.isSubmission, true, 'secure v3 submit endpoint must be blocked');
assert.equal(res2.reason, 'exact-v3-submit-endpoint');

// 3. Async v3 submit endpoint
const res3 = classify('POST', `https://api.hsforms.com/submissions/v3/integration/async/submit/${portalId}/${formId}`);
assert.equal(res3.isSubmission, true, 'async v3 submit endpoint must be blocked');
assert.equal(res3.reason, 'exact-v3-submit-endpoint');

// 4. Forms Next multipart submit endpoint
const res4 = classify('POST', `https://api.hsforms.com/submissions/v3/public/submit/formsnext/multipart/${portalId}/${formId}`);
assert.equal(res4.isSubmission, true, 'formsnext multipart submit endpoint must be blocked');
assert.equal(res4.reason, 'exact-v3-submit-endpoint');

// 5. Regional hsforms.com submit endpoint
const res5 = classify('POST', `https://api-eu1.hsforms.com/submissions/v3/integration/submit/${portalId}/${formId}`);
assert.equal(res5.isSubmission, true, 'regional hsforms submit endpoint must be blocked');
assert.equal(res5.hostname, 'api-eu1.hsforms.com');

// 6. hsforms.net host variant
const res6 = classify('POST', `https://api.hsforms.net/submissions/v3/integration/submit/${portalId}/${formId}`);
assert.equal(res6.isSubmission, true, 'hsforms.net submit endpoint must be blocked');
assert.equal(res6.hostname, 'api.hsforms.net');

// 7. Case-insensitive method and path handling
const res7 = classify('post', `https://api.hsforms.com/Submissions/v3/Integration/Submit/${portalId}/${formId}`);
assert.equal(res7.isSubmission, true, 'classifier should be case-insensitive for method and path');

// 8. GET method is not a submission
const res8 = classify('GET', `https://api.hsforms.com/submissions/v3/integration/submit/${portalId}/${formId}`);
assert.equal(res8.isSubmission, false, 'GET is not a submission');
assert.equal(res8.reason, 'method');
assert.equal(res8.method, 'GET');

// 9. Malformed URL yields reason: 'url'
const res9 = classify('POST', 'not a valid url: // %%%');
assert.equal(res9.isSubmission, false, 'malformed URL must not be classified as a submission');
assert.equal(res9.reason, 'url');

// 10. Form bootstrap URL containing the form ID must not be classified as a submission
const bootstrapUrl = `https://forms.hsforms.com/embed/v3/form/${portalId}/${formId}`;
const res10 = classify('POST', bootstrapUrl);
assert.equal(res10.isSubmission, false, 'form bootstrap URL must not be classified as a submission');
assert.equal(res10.reason, 'path');
assert.equal(res10.hostname, 'forms.hsforms.com');
assert.equal(res10.pathname, `/embed/v3/form/${portalId}/${formId}`);

// 11. Telemetry containing the form ID must not be classified as a submission
const res11 = classify('POST', `https://forms.hsforms.com/telemetry?formId=${formId}`);
assert.equal(res11.isSubmission, false, 'telemetry containing the form ID must not be classified as a submission');
assert.equal(res11.reason, 'path');

// 12. Another form must not be classified as this form submission
const res12 = classify('POST', `https://api.hsforms.com/submissions/v3/integration/submit/${portalId}/00000000-0000-0000-0000-000000000000`);
assert.equal(res12.isSubmission, false, 'another form must not be classified as this form submission');
assert.equal(res12.reason, 'path');

// 13. Lookalike endpoint on another host must not be blocked
const res13 = classify('POST', `https://example.com/submissions/v3/integration/submit/${portalId}/${formId}`);
assert.equal(res13.isSubmission, false, 'lookalike endpoint on another host must not be blocked');
assert.equal(res13.reason, 'host');
assert.equal(res13.hostname, 'example.com');
assert.equal(res13.pathname, `/submissions/v3/integration/submit/${portalId}/${formId}`);

// Production release verification must never create a contact or synthetic
// attribution event. Keep this source-level regression beside the submission
// classifier so every production-eligible Staging acceptance executes it.
const productionProbe = await fs.readFile(new URL('./h1-hubspot-e2e.mjs', import.meta.url), 'utf8');

for (const [pattern, message] of HUBSPOT_PRODUCTION_FORBIDDEN_PATTERNS) {
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

console.log('HUBSPOT_SUBMISSION_CLASSIFIER_TEST=PASS cases=13');
console.log(`HUBSPOT_PRODUCTION_ZERO_SUBMIT_GUARD=PASS forbidden_patterns=${HUBSPOT_PRODUCTION_FORBIDDEN_PATTERNS.length}`);
