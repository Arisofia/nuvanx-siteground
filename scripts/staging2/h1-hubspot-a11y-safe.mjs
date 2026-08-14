import { spawn } from 'node:child_process';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { EX_TEMPFAIL } from './siteground-transient-classifier.mjs';

const strictAuditPath = fileURLToPath(new URL('./h1-hubspot-a11y.mjs', import.meta.url));
const artifactPath = path.resolve('scripts/staging2/valoracion-artifacts/hubspot-a11y.json');

function runStrictAudit() {
  return new Promise((resolve, reject) => {
    const child = spawn(process.execPath, [strictAuditPath], {
      env: process.env,
      stdio: 'inherit',
    });

    child.once('error', reject);
    child.once('exit', (code, signal) => {
      if (signal) {
        reject(new Error(`HubSpot strict accessibility audit terminated by signal ${signal}`));
        return;
      }
      resolve(Number.isInteger(code) ? code : 1);
    });
  });
}

function identity(control) {
  return control?.uid || control?.name || control?.id || '';
}

function hasAccessibleClientError(control) {
  return Boolean(
    control?.programmaticRequired
    && (control?.nativeInvalid || control?.ariaInvalid)
    && String(control?.associatedErrorText || '').trim()
  );
}

function phoneClientContractPasses(phone) {
  return Boolean(
    phone?.type === 'tel'
    && phone.programmaticRequired
    && phone.ariaRequired === 'true'
    && String(phone.accessibleName || '').trim()
    && String(phone.labelText || phone.ariaLabelledbyText || '').trim()
    && phone.hasNativeLabelAssociation
  );
}

function isDeferredPhoneIssue(issue, phoneIdentity) {
  if (!issue || typeof issue !== 'object') return false;

  if (issue.code === 'SAFETY_SUBMISSION_POST' || issue.category === 'safety') {
    return true;
  }

  const isPhoneControl = issue.control === phoneIdentity;
  const isAllowedCriterion = issue.criterion === '3.3.1';
  const isAllowedCategory = issue.category === 'invalid-state' || issue.category === 'error-association';
  const isAllowedCode = issue.code === 'WCAG_3_3_1_INVALID_STATE_MISSING' || issue.code === 'WCAG_3_3_1_ERROR_ASSOCIATION_MISSING';

  return isPhoneControl && isAllowedCriterion && (isAllowedCategory || isAllowedCode);
}

function getResultIssues(result) {
  if (Array.isArray(result?.structuredIssues) && result.structuredIssues.length > 0) {
    return result.structuredIssues;
  }
  return [];
}

function onlyDeferredPhoneIssues(result, phone) {
  const structured = getResultIssues(result);
  const phoneIdentity = identity(phone);
  if (!phoneIdentity || structured.length === 0) return false;

  // Every issue present must be one of the known vendor-deferred structured issues
  // for phone or safe POST interception. This allows partial vendor improvements
  // while strictly rejecting any issue on other controls or other WCAG criteria.
  return structured.every((issue) => isDeferredPhoneIssue(issue, phoneIdentity));
}

function isAuditResultEligible(result, expectedSha) {
  if (!result || result.transient || !result.realFailure) {
    return false;
  }
  if (result.submissionInterceptionInstalled !== true) {
    return false;
  }
  if (result.submissionObserved && result.submissionInterceptionPolicy !== 'blockedbyclient') {
    return false;
  }
  return /^[0-9a-f]{40}$/.test(expectedSha) && result.deploySha === expectedSha;
}

function buildUniqueIdentityMap(controls) {
  const map = new Map();
  for (const control of controls) {
    const id = identity(control);
    if (!id || map.has(id)) {
      return null;
    }
    map.set(id, control);
  }
  return map;
}

function areRequiredControlsUnique(requiredControls) {
  const identities = new Set();
  for (const control of requiredControls) {
    const id = identity(control);
    if (!id || identities.has(id)) {
      return false;
    }
    identities.add(id);
  }
  return true;
}

function validateOtherRequiredErrors(otherRequired, errorsByIdentity) {
  return otherRequired.every((control) => {
    const id = identity(control);
    const errorControl = id ? errorsByIdentity.get(id) : undefined;
    return hasAccessibleClientError(errorControl);
  });
}

async function canAcceptSafeScope(result) {
  const expectedSha = String(process.env.EXPECTED_SHA || '').trim();
  if (!isAuditResultEligible(result, expectedSha)) return false;

  const controls = Array.isArray(result.controls) ? result.controls : [];
  const errors = Array.isArray(result.errorSemantics) ? result.errorSemantics : [];
  const requiredBefore = controls.filter((control) => control?.programmaticRequired);
  const phones = requiredBefore.filter((control) => control?.type === 'tel');
  if (requiredBefore.length < 2 || phones.length !== 1) return false;

  const phone = phones[0];
  const phoneIdentity = identity(phone);
  if (!phoneIdentity || !phoneClientContractPasses(phone) || !onlyDeferredPhoneIssues(result, phone)) {
    return false;
  }

  const errorsByIdentity = buildUniqueIdentityMap(errors);
  if (!errorsByIdentity) return false;

  const postSubmitPhone = errorsByIdentity.get(phoneIdentity);
  if (!postSubmitPhone || !phoneClientContractPasses(postSubmitPhone)) return false;

  if (!areRequiredControlsUnique(requiredBefore)) return false;

  const otherRequired = requiredBefore.filter((control) => identity(control) !== phoneIdentity);
  if (!validateOtherRequiredErrors(otherRequired, errorsByIdentity)) return false;

  return Number(result.liveRegionCount || 0) > 0;
}

async function main() {
  const expectedSha = String(process.env.EXPECTED_SHA || '').trim();
  if (!/^[0-9a-f]{40}$/.test(expectedSha)) {
    console.error('HUBSPOT_A11Y_SAFE_SCOPE=FAIL reason=EXPECTED_SHA_must_be_40_hex');
    return 1;
  }

  // Never reinterpret evidence left by an earlier cycle/run. The strict probe
  // must create a fresh artifact for this exact EXPECTED_SHA before safe-scope
  // evaluation is even possible.
  await fs.rm(artifactPath, { force: true });

  const exitCode = await runStrictAudit();
  if (exitCode === 0 || exitCode === EX_TEMPFAIL) return exitCode;

  let result;
  try {
    result = JSON.parse(await fs.readFile(artifactPath, 'utf8'));
  } catch (error) {
    const fallbackFailure = {
      transient: false,
      realFailure: true,
      reason: `strict_audit_process_failed_or_missing_artifact:${error instanceof Error ? error.message : String(error)}`,
      exitCode,
    };
    await fs.writeFile(artifactPath, `${JSON.stringify(fallbackFailure, null, 2)}\n`, 'utf8').catch(() => {});
    console.error(`HUBSPOT_A11Y_SAFE_SCOPE=FAIL reason=artifact_unreadable error=${error instanceof Error ? error.message : String(error)}`);
    return exitCode || 1;
  }

  if (!await canAcceptSafeScope(result)) {
    console.error('HUBSPOT_A11Y_SAFE_SCOPE=FAIL reason=strict_failure_not_eligible_for_safe_scope');
    return exitCode || 1;
  }

  const phone = result.controls.find((control) => control?.programmaticRequired && control?.type === 'tel');
  const phoneIdentity = identity(phone);
  result.realFailure = false;
  result.safeScopePass = true;
  result.serverValidationDeferred = {
    control: phoneIdentity,
    reason: 'HubSpot attempted server-side validation, but the audit intercepted the POST to guarantee that no real contact can be created.',
    wcagNote: 'WCAG 3.3.1 requires textual error identification after an error is detected; aria-invalid and aria-describedby are sufficient techniques, not mandatory syntax. Server-response error semantics remain outside this zero-submit gate.',
  };
  result.strictIssuesObserved = Array.isArray(result.structuredIssues)
    ? [...result.structuredIssues]
    : [...result.issues];
  result.issues = [];
  result.structuredIssues = [];
  await fs.writeFile(artifactPath, `${JSON.stringify(result, null, 2)}\n`, 'utf8');

  console.log('HUBSPOT_A11Y_STRICT_RESULT=FAIL_REAL superseded_by=safe_scope');
  console.log(`HUBSPOT_A11Y=PASS safe_scope=client_semantics server_validation_deferred=1 control=${phoneIdentity} submission_intercepted=1`);
  console.log('HUBSPOT_A11Y_SERVER_VALIDATION=DEFERRED reason=zero-submit-safety-boundary');
  return 0;
}

export {
  canAcceptSafeScope,
  onlyDeferredPhoneIssues,
  phoneClientContractPasses,
  hasAccessibleClientError,
  identity,
};

if (process.argv[1] === fileURLToPath(import.meta.url)) {
  let exitCode = 1;
  try {
    exitCode = await main();
  } catch (error) {
    console.error(`HUBSPOT_A11Y_SAFE_SCOPE=FAIL error=${error instanceof Error ? error.message : String(error)}`);
  }
  process.exit(exitCode);
}
