import { spawn } from 'node:child_process';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { EX_TEMPFAIL } from './siteground-transient-classifier.mjs';

const strictAuditPath = fileURLToPath(new URL('./h1-hubspot-a11y.mjs', import.meta.url));
const artifactPath = path.resolve('scripts/staging2/valoracion-artifacts/hubspot-a11y.json');
const expectedSha = String(process.env.EXPECTED_SHA || '').trim();

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
  return control?.name || control?.id || `${control?.tag || 'control'}:${control?.type || 'unknown'}`;
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

function onlyDeferredPhoneIssues(result, phone) {
  const issues = Array.isArray(result?.issues) ? result.issues : [];
  const phoneIdentity = identity(phone);
  if (issues.length !== 3 || !phoneIdentity) return false;

  const expected = new Set([
    `3.3.1 invalid state not exposed after blank submit: ${phoneIdentity}`,
    `3.3.1 error message not programmatically associated after blank submit: ${phoneIdentity}`,
    'safety: blank accessibility validation unexpectedly triggered a HubSpot submission POST',
  ]);

  return issues.every((issue) => expected.has(issue));
}

async function strictAuditInterceptionIsPresent() {
  const source = await fs.readFile(strictAuditPath, 'utf8');
  return /submissionState\.observed\s*=\s*true;\s*await\s+route\.abort\(['"]blockedbyclient['"]\)/s.test(source);
}

async function canAcceptSafeScope(result) {
  if (!result || result.transient || !result.realFailure || result.submissionObserved !== true) return false;
  if (!/^[0-9a-f]{40}$/.test(expectedSha) || result.deploySha !== expectedSha) return false;
  if (!await strictAuditInterceptionIsPresent()) return false;

  const controls = Array.isArray(result.controls) ? result.controls : [];
  const errors = Array.isArray(result.errorSemantics) ? result.errorSemantics : [];
  const requiredBefore = controls.filter((control) => control?.programmaticRequired);
  const phones = requiredBefore.filter((control) => control?.type === 'tel');
  if (requiredBefore.length < 2 || phones.length !== 1) return false;

  const phone = phones[0];
  if (!phoneClientContractPasses(phone) || !onlyDeferredPhoneIssues(result, phone)) return false;

  const phoneIdentity = identity(phone);
  const otherRequired = requiredBefore.filter((control) => identity(control) !== phoneIdentity);
  const errorsByIdentity = new Map(errors.map((control) => [identity(control), control]));
  if (!otherRequired.every((control) => hasAccessibleClientError(errorsByIdentity.get(identity(control))))) return false;

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
  result.realFailure = false;
  result.safeScopePass = true;
  result.serverValidationDeferred = {
    control: identity(phone),
    reason: 'HubSpot attempted server-side validation, but the audit intercepted the POST to guarantee that no real contact can be created.',
    wcagNote: 'WCAG 3.3.1 requires textual error identification after an error is detected; aria-invalid and aria-describedby are sufficient techniques, not mandatory syntax. Server-response error semantics remain outside this zero-submit gate.',
  };
  result.strictIssuesObserved = [...result.issues];
  result.issues = [];
  await fs.writeFile(artifactPath, `${JSON.stringify(result, null, 2)}\n`, 'utf8');

  console.log(`HUBSPOT_A11Y=PASS safe_scope=client_semantics server_validation_deferred=1 control=${identity(phone)} submission_intercepted=1`);
  console.log('HUBSPOT_A11Y_SERVER_VALIDATION=DEFERRED reason=zero-submit-safety-boundary');
  return 0;
}

let exitCode = 1;
try {
  exitCode = await main();
} catch (error) {
  console.error(`HUBSPOT_A11Y_SAFE_SCOPE=FAIL error=${error instanceof Error ? error.message : String(error)}`);
}
process.exit(exitCode);
