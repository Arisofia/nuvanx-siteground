import assert from 'node:assert/strict';
import {
  canAcceptSafeScope,
  onlyDeferredPhoneIssues,
  phoneClientContractPasses,
  hasAccessibleClientError,
  identity,
} from './h1-hubspot-a11y-safe.mjs';

const testSha = '1234567890abcdef1234567890abcdef12345678';
process.env.EXPECTED_SHA = testSha;

function makeSamplePayload(overrides = {}) {
  const phoneControl = {
    uid: 'phone',
    name: 'phone',
    id: 'phone-input',
    type: 'tel',
    tag: 'input',
    programmaticRequired: true,
    ariaRequired: 'true',
    accessibleName: 'Teléfono de contacto',
    labelText: 'Teléfono de contacto',
    hasNativeLabelAssociation: true,
  };

  const emailControl = {
    uid: 'email',
    name: 'email',
    id: 'email-input',
    type: 'email',
    tag: 'input',
    programmaticRequired: true,
    ariaRequired: 'true',
    accessibleName: 'Correo electrónico',
    labelText: 'Correo electrónico',
    hasNativeLabelAssociation: true,
  };

  const emailError = {
    uid: 'email',
    name: 'email',
    id: 'email-input',
    type: 'email',
    tag: 'input',
    programmaticRequired: true,
    nativeInvalid: true,
    ariaInvalid: false,
    associatedErrorText: 'Por favor, completa este campo obligatorio.',
  };

  const phoneError = {
    uid: 'phone',
    name: 'phone',
    id: 'phone-input',
    type: 'tel',
    tag: 'input',
    programmaticRequired: true,
    ariaRequired: 'true',
    accessibleName: 'Teléfono de contacto',
    labelText: 'Teléfono de contacto',
    hasNativeLabelAssociation: true,
    nativeInvalid: false,
    ariaInvalid: false,
    associatedErrorText: '',
  };

  const base = {
    transient: false,
    realFailure: true,
    submissionObserved: true,
    submissionInterceptionInstalled: true,
    submissionInterceptionPolicy: 'blockedbyclient',
    deploySha: testSha,
    liveRegionCount: 1,
    controls: [emailControl, phoneControl],
    errorSemantics: [emailError, phoneError],
    structuredIssues: [
      {
        code: 'WCAG_3_3_1_INVALID_STATE_MISSING',
        criterion: '3.3.1',
        category: 'invalid-state',
        control: 'phone',
        message: '3.3.1 invalid state not exposed after blank submit: phone',
      },
      {
        code: 'WCAG_3_3_1_ERROR_ASSOCIATION_MISSING',
        criterion: '3.3.1',
        category: 'error-association',
        control: 'phone',
        message: '3.3.1 error message not programmatically associated after blank submit: phone',
      },
      {
        code: 'SAFETY_SUBMISSION_POST',
        category: 'safety',
        message: 'safety: blank accessibility validation unexpectedly triggered a HubSpot submission POST',
      },
    ],
    issues: [
      '3.3.1 invalid state not exposed after blank submit: phone',
      '3.3.1 error message not programmatically associated after blank submit: phone',
      'safety: blank accessibility validation unexpectedly triggered a HubSpot submission POST',
    ],
  };

  return { ...base, ...overrides };
}

async function runTests() {
  console.log('Testing identity helper...');
  assert.equal(identity({ uid: 'u1', id: 'i1', name: 'n1' }), 'u1');
  assert.equal(identity({ id: 'i1', name: 'n1' }), 'i1');
  assert.equal(identity({ name: 'n1' }), 'n1');
  assert.equal(identity({}), '');
  assert.equal(identity(null), '');

  console.log('Testing phoneClientContractPasses...');
  assert.equal(phoneClientContractPasses(makeSamplePayload().controls[1]), true);
  assert.equal(phoneClientContractPasses({ ...makeSamplePayload().controls[1], hasNativeLabelAssociation: false }), false);
  assert.equal(phoneClientContractPasses({ ...makeSamplePayload().controls[1], type: 'text' }), false);

  console.log('Testing hasAccessibleClientError...');
  assert.equal(hasAccessibleClientError(makeSamplePayload().errorSemantics[0]), true);
  assert.equal(hasAccessibleClientError({ ...makeSamplePayload().errorSemantics[0], associatedErrorText: '' }), false);
  assert.equal(hasAccessibleClientError({ ...makeSamplePayload().errorSemantics[0], nativeInvalid: false, ariaInvalid: false }), false);

  console.log('Testing onlyDeferredPhoneIssues with structured issues...');
  const sample = makeSamplePayload();
  const phone = sample.controls[1];
  assert.equal(onlyDeferredPhoneIssues(sample, phone), true);

  console.log('Testing onlyDeferredPhoneIssues accepts partial vendor improvements...');
  const partialVendorImprovementPayload = makeSamplePayload({
    structuredIssues: [
      {
        code: 'WCAG_3_3_1_ERROR_ASSOCIATION_MISSING',
        criterion: '3.3.1',
        category: 'error-association',
        control: 'phone',
        message: '3.3.1 error message not programmatically associated after blank submit: phone',
      },
      {
        code: 'SAFETY_SUBMISSION_POST',
        category: 'safety',
        message: 'safety: blank accessibility validation unexpectedly triggered a HubSpot submission POST',
      },
    ],
  });
  assert.equal(onlyDeferredPhoneIssues(partialVendorImprovementPayload, phone), true);

  console.log('Testing onlyDeferredPhoneIssues accepts phone issues without safety POST...');
  const withoutSafetyPostPayload = makeSamplePayload({
    structuredIssues: sample.structuredIssues.slice(0, 2),
  });
  assert.equal(onlyDeferredPhoneIssues(withoutSafetyPostPayload, phone), true);

  console.log('Testing onlyDeferredPhoneIssues rejection on foreign issue...');
  const foreignIssuePayload = makeSamplePayload({
    structuredIssues: [
      ...sample.structuredIssues,
      {
        code: 'WCAG_3_3_1_INVALID_STATE_MISSING',
        criterion: '3.3.1',
        category: 'invalid-state',
        control: 'email',
        message: '3.3.1 invalid state not exposed after blank submit: email',
      },
    ],
  });
  assert.equal(onlyDeferredPhoneIssues(foreignIssuePayload, phone), false);

  console.log('Testing canAcceptSafeScope happy path...');
  assert.equal(await canAcceptSafeScope(makeSamplePayload()), true);

  console.log('Testing canAcceptSafeScope happy path without submission POST (interception installed)...');
  assert.equal(await canAcceptSafeScope(makeSamplePayload({ submissionObserved: false, submissionInterceptionPolicy: 'none' })), true);

  console.log('Testing canAcceptSafeScope rejects missing interception installed...');
  assert.equal(await canAcceptSafeScope(makeSamplePayload({ submissionInterceptionInstalled: false })), false);

  console.log('Testing canAcceptSafeScope rejects submission observed without blockedbyclient policy...');
  assert.equal(await canAcceptSafeScope(makeSamplePayload({ submissionObserved: true, submissionInterceptionPolicy: 'allow' })), false);

  console.log('Testing canAcceptSafeScope rejects duplicate control identity...');
  const duplicateControlsPayload = makeSamplePayload({
    controls: [
      { ...sample.controls[0], uid: 'dup' },
      { ...sample.controls[1], uid: 'dup' },
    ],
  });
  assert.equal(await canAcceptSafeScope(duplicateControlsPayload), false);

  console.log('Testing canAcceptSafeScope rejects duplicate error semantics identity...');
  const duplicateErrorsPayload = makeSamplePayload({
    errorSemantics: [
      { ...sample.errorSemantics[0], uid: 'dup' },
      { ...sample.errorSemantics[1], uid: 'dup' },
    ],
  });
  assert.equal(await canAcceptSafeScope(duplicateErrorsPayload), false);

  console.log('Testing canAcceptSafeScope rejects missing error semantics for required field...');
  const missingErrorPayload = makeSamplePayload({
    errorSemantics: [sample.errorSemantics[1]], // only phone, email error missing
  });
  assert.equal(await canAcceptSafeScope(missingErrorPayload), false);

  console.log('Testing canAcceptSafeScope rejects when post-submit phone loses label association...');
  const brokenPostSubmitPhonePayload = makeSamplePayload({
    errorSemantics: [
      sample.errorSemantics[0],
      { ...sample.errorSemantics[1], hasNativeLabelAssociation: false },
    ],
  });
  assert.equal(await canAcceptSafeScope(brokenPostSubmitPhonePayload), false);

  console.log('Testing canAcceptSafeScope rejects when email client error is inaccessible...');
  const inaccessibleErrorPayload = makeSamplePayload({
    errorSemantics: [
      { ...sample.errorSemantics[0], associatedErrorText: '' },
      sample.errorSemantics[1],
    ],
  });
  assert.equal(await canAcceptSafeScope(inaccessibleErrorPayload), false);

  console.log('Testing canAcceptSafeScope rejects sha mismatch...');
  assert.equal(await canAcceptSafeScope(makeSamplePayload({ deploySha: '0000000000000000000000000000000000000000' })), false);

  console.log('Testing canAcceptSafeScope rejects when liveRegionCount is 0...');
  assert.equal(await canAcceptSafeScope(makeSamplePayload({ liveRegionCount: 0 })), false);

  console.log('\nALL HUBSPOT A11Y SAFE SCOPE TESTS PASSED ✅');
}

await runTests();
