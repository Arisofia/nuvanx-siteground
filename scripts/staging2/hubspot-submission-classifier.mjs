import {
  HUBSPOT_PORTAL_ID,
  HUBSPOT_FORM_ID,
  HUBSPOT_SUBMISSION_HOST_PATTERN,
} from './hubspot-config.mjs';

function escapeRegExp(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

export function buildHubSpotSubmissionPathPattern(portalId = HUBSPOT_PORTAL_ID, formId = HUBSPOT_FORM_ID) {
  const expectedPortal = escapeRegExp(portalId);
  const expectedForm = escapeRegExp(formId);
  return new RegExp(
    `^/submissions/v3/(?:integration/(?:(?:secure|async)/)?submit|public/submit/formsnext/(?:multipart|json))/${expectedPortal}/${expectedForm}/?$`,
    'i'
  );
}

export function classifyHubSpotSubmissionRequest({
  method,
  url,
  portalId = HUBSPOT_PORTAL_ID,
  formId = HUBSPOT_FORM_ID,
}) {
  const normalizedMethod = String(method || '').toUpperCase();
  if (normalizedMethod !== 'POST') return { isSubmission: false, reason: 'method', method: normalizedMethod };

  let parsed;
  try {
    parsed = new URL(String(url || ''));
  } catch {
    return { isSubmission: false, reason: 'url' };
  }

  const hostname = parsed.hostname.toLowerCase();
  if (!HUBSPOT_SUBMISSION_HOST_PATTERN.test(hostname)) {
    return { isSubmission: false, reason: 'host', hostname, pathname: parsed.pathname };
  }

  const pathPattern = buildHubSpotSubmissionPathPattern(portalId, formId);
  if (!pathPattern.test(parsed.pathname)) {
    return { isSubmission: false, reason: 'path', hostname, pathname: parsed.pathname };
  }

  return {
    isSubmission: true,
    reason: 'exact-v3-submit-endpoint',
    hostname,
    pathname: parsed.pathname,
  };
}
