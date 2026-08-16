export const SITEGROUND_CAPTCHA_PATH = '/.well-known/sgcaptcha/';
export const SITEGROUND_TRANSIENT_HTTP_STATUSES = new Set([202, 429, 503]);
/** BSD sysexits: EX_USAGE (64) - recovery path is unavailable or not applicable. */
export const EX_NOT_APPLICABLE = 64;
/** BSD sysexits: EX_TEMPFAIL (75) - temporary failure / transient infrastructure challenge (retryable). */
export const EX_TEMPFAIL = 75;
/** BSD sysexits: EX_CONFIG (78) - invalid recovery configuration/identity. */
export const EX_CONFIG = 78;

export function isSiteGroundCaptchaInterruption(error, currentUrl = '') {
  const message = error instanceof Error ? error.message : String(error || '');
  return String(currentUrl).includes(SITEGROUND_CAPTCHA_PATH)
    || (/interrupted by another navigation/i.test(message) && message.includes(SITEGROUND_CAPTCHA_PATH));
}

export function isSiteGroundTransientResponse(status, headers = {}, currentUrl = '') {
  const normalizedStatus = Number(status || 0);
  return SITEGROUND_TRANSIENT_HTTP_STATUSES.has(normalizedStatus)
    || Boolean(headers['sg-captcha'])
    || String(currentUrl).includes(SITEGROUND_CAPTCHA_PATH);
}
