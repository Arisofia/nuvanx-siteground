import {
  HUBSPOT_PORTAL_ID,
  HUBSPOT_FORM_ID,
  HUBSPOT_SUBMISSION_HOST_PATTERN,
} from './hubspot-config.mjs';

function escapeRegExp(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Exact submission endpoint pattern for the specific form and portal.
 */
export function buildHubSpotSubmissionPathPattern(portalId = HUBSPOT_PORTAL_ID, formId = HUBSPOT_FORM_ID) {
  const expectedPortal = escapeRegExp(portalId);
  const expectedForm = escapeRegExp(formId);
  return new RegExp(
    `^/(?:submissions/v3/(?:integration/(?:(?:secure|async)/)?submit|public/submit/formsnext/(?:multipart|json))|uploads/form/v2)/${expectedPortal}/${expectedForm}/?$`,
    'i'
  );
}

/**
 * Broad safety predicate: aborts ANY plausible HubSpot submission request
 * (including legacy v2 uploads, v3/v4 submissions, collected-forms, and other forms)
 * to guarantee that no real contact record can ever be created during testing.
 */
export function shouldBlockHubSpotRequest({
  method,
  url,
}) {
  const normalizedMethod = String(method || '').toUpperCase();
  if (normalizedMethod !== 'POST') return { shouldBlock: false, reason: 'method', method: normalizedMethod };

  let parsed;
  try {
    parsed = new URL(String(url || ''));
  } catch {
    return { shouldBlock: false, reason: 'url' };
  }

  const hostname = parsed.hostname.toLowerCase();
  if (!HUBSPOT_SUBMISSION_HOST_PATTERN.test(hostname)) {
    return { shouldBlock: false, reason: 'host', hostname, pathname: parsed.pathname };
  }

  const pathname = parsed.pathname.toLowerCase();

  // Known safe non-submission requests (bootstrap, telemetry, tracking scripts, graphql)
  if (
    pathname.startsWith('/embed/v3/form/') ||
    pathname === '/telemetry' ||
    pathname.startsWith('/events/v3/') ||
    pathname.startsWith('/graphql') ||
    pathname.endsWith('.js') ||
    pathname.endsWith('.css')
  ) {
    return { shouldBlock: false, reason: 'path', hostname, pathname: parsed.pathname };
  }

  // Any plausible submission route on HubSpot hosts must be blocked for safety
  if (
    /\/submissions\/v\d+\//i.test(pathname) ||
    /\/uploads\/form\/v\d+\//i.test(pathname) ||
    /\/collected-forms\//i.test(pathname) ||
    /\/submit\//i.test(pathname)
  ) {
    return { shouldBlock: true, reason: 'safety-submission-block', hostname, pathname: parsed.pathname };
  }

  return { shouldBlock: false, reason: 'path', hostname, pathname: parsed.pathname };
}

/**
 * Full classifier returning both shouldBlock (safety action) and
 * isConfirmedSubmission (reporting / evidence for the audited form).
 */
export function classifyHubSpotSubmissionRequest({
  method,
  url,
  portalId = HUBSPOT_PORTAL_ID,
  formId = HUBSPOT_FORM_ID,
}) {
  const safetyCheck = shouldBlockHubSpotRequest({ method, url });

  let parsed;
  try {
    parsed = new URL(String(url || ''));
  } catch {
    return {
      isSubmission: false,
      isConfirmedSubmission: false,
      shouldBlock: false,
      reason: 'url',
    };
  }

  const hostname = parsed.hostname.toLowerCase();
  const pathname = parsed.pathname;

  if (!safetyCheck.shouldBlock) {
    return {
      isSubmission: false,
      isConfirmedSubmission: false,
      shouldBlock: false,
      reason: safetyCheck.reason,
      hostname,
      pathname,
      ...(safetyCheck.method ? { method: safetyCheck.method } : {}),
    };
  }

  const exactPattern = buildHubSpotSubmissionPathPattern(portalId, formId);
  const isExactFormSubmission = exactPattern.test(pathname);

  if (isExactFormSubmission) {
    return {
      isSubmission: true,
      isConfirmedSubmission: true,
      shouldBlock: true,
      reason: 'exact-v3-submit-endpoint',
      hostname,
      pathname,
    };
  }

  return {
    isSubmission: false,
    isConfirmedSubmission: false,
    shouldBlock: true,
    reason: 'unconfirmed-submission-blocked-for-safety',
    hostname,
    pathname,
  };
}
