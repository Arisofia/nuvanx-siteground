const CLIENT_ABORT_PATTERN = /net::ERR_ABORTED/i;

/**
 * Chromium may cancel an in-flight responsive image candidate after srcset,
 * lazy-loading, viewport or layout selection chooses a different candidate.
 * The final DOM image contract remains authoritative for rendered-image health.
 *
 * Media aborts were already treated as client-side cancellations by Block C;
 * image aborts follow the same transport rule. Other resource types and other
 * failure modes remain reportable.
 *
 * @param {string} resourceType Playwright request.resourceType().
 * @param {string} failureText Playwright request.failure().errorText.
 * @returns {boolean} Whether the failed request is an expected client abort.
 */
export function isExpectedClientResourceAbort(resourceType, failureText) {
  const type = String(resourceType || '').toLowerCase();
  if (type !== 'image' && type !== 'media') return false;
  return CLIENT_ABORT_PATTERN.test(String(failureText || ''));
}
