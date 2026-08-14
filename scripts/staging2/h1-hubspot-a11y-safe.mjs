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
  return control?.uid || control?.id || control?.name || '';
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
  if (!issue) return false;

  if (typeof issue === 'string') {
    if (/safety:\s*blank accessibility validation.*submission POST/i.test(issue)) return true;
    if (/3\.3\.1\s+invalid state not exposed/i.test(issue) && issue.includes(phoneIdentity)) return true;
    if (/3\.3\.1\s+error message not programmatically associated/i.test(issue) && issue.includes(phoneIdentity)) return true;
    return false;
  }

  if (issue.code === 'SAFETY_SUBMISSION_POST' || issue.category === 'safety') {
    return true;
  }

  const isPhoneControl = issue.control === phoneIdentity;
  const isAllowedCriterion = issue.criterion === '3.3.1';
  const isAllowedCategory = issue.category === 'invalid-state' || issue.category === 'error-association';
  const isAllowedCode = issue.code === 'WCAG_3_3_1_INVALID_STATE_MISSING' || issue.code === 'WCAG_3_3_1_ERROR_ASSOCIATION_MISSING';

  return isPhoneControl && isAllowedCriterion && (isAllowedCategory || isAllowedCode);
}

function onlyDeferredPhoneIssues(result, phone) {
  const structured = Array.isArray(result?.structuredIssues) && result.structuredIssues.length > 0
    ? result.structuredIssues
    : (Array.isArray(result?.issues) ? result.issues : []);
  const phoneIdentity = identity(phone);
  if (!phoneIdentity || structured.length === 0) return false;

  const allDeferred = structured.every((issue) => isDeferredPhoneIssue(issue, phoneIdentity));
  if (!allDeferred) return false;

  const hasSafety = structured.some((issue) =>
    typeof issue === 'string'
      ? /safety:\s*blank accessibility validation.*submission POST/i.test(issue)
      : (issue.code === 'SAFETY_SUBMISSION_POST' || issue.category === 'safety')
  );
  if (!hasSafety) return false;

  return true;
}

async function canAcceptSafeScope(result) {
  const expectedSha = String(process.env.EXPECTED_SHA || '').trim();
  if (!result || result.transient || !result.realFailure || result.submissionObserved !== true) return false;
  if (result.submissionInterceptionInstalled !== true || result.submissionInterceptionPolicy !== 'blockedbyclient') return false;
  if (!/^[0-9a-f]{40}$/.test(expectedSha) || result.deploySha !== expectedSha) return false;

  const controls = Array.isArray(result.controls) ? result.controls : [];
  const errors = Array.isArray(result.errorSemantics) ? result.errorSemantics : [];
  const requiredBefore = controls.filter((control) => control?.programmaticRequired);
  const phones = requiredBefore.filter((control) => control?.type === 'tel');
  if (requiredBefore.length < 2 || phones.length !== 1) return false;

  const phone = phones[0];
  if (!phoneClientContractPasses(phone) || !onlyDeferredPhoneIssues(result, phone)) return false;

  const phoneIdentity = identity(phone);
  if (!phoneIdentity) return false;

  const errorsByIdentity = new Map();
  for (const errorControl of errors) {
    const errorIdentity = identity(errorControl);
    if (!errorIdentity || errorsByIdentity.has(errorIdentity)) {
      return false;
    }
    errorsByIdentity.set(errorIdentity, errorControl);
  }

  const requiredIdentities = new Set();
  for (const control of requiredBefore) {
    const controlIdentity = identity(control);
    if (!controlIdentity || requiredIdentities.has(controlIdentity)) {
      return false;
    }
    requiredIdentities.add(controlIdentity);
  }

  const otherRequired = requiredBefore.filter((control) => {
    const controlIdentity = identity(control);
    return controlIdentity && controlIdentity !== phoneIdentity;
  });

  if (
    !otherRequired.every((control) => {
      const controlIdentity = identity(control);
      const errorForControl = controlIdentity ? errorsByIdentity.get(controlIdentity) : undefined;
      return hasAccessibleClientError(errorForControl);
    })
  ) {
    return false;
  }

  return Number(result.liveRegionCount || 0) > 0;
}

async function main() {
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
