import assert from 'node:assert/strict';
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

console.log('HUBSPOT_SUBMISSION_CLASSIFIER_TEST=PASS cases=8');
