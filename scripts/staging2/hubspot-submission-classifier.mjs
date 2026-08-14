function escapeRegExp(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

export function classifyHubSpotSubmissionRequest({ method, url, portalId, formId }) {
  const normalizedMethod = String(method || '').toUpperCase();
  if (normalizedMethod !== 'POST') return { isSubmission: false, reason: 'method' };

  let parsed;
  try {
    parsed = new URL(String(url || ''));
  } catch {
    return { isSubmission: false, reason: 'url' };
  }

  const hostname = parsed.hostname.toLowerCase();
  if (!/(^|\.)hsforms\.(com|net)$/.test(hostname)) {
    return { isSubmission: false, reason: 'host', hostname, pathname: parsed.pathname };
  }

  const expectedPortal = escapeRegExp(portalId);
  const expectedForm = escapeRegExp(formId);
  const pathPattern = new RegExp(
    `^/submissions/v3/integration/(?:secure/)?submit/${expectedPortal}/${expectedForm}/?$`,
    'i'
  );
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
