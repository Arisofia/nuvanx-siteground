export const SITEGROUND_CAPTCHA_PATH = '/.well-known/sgcaptcha/';
export const SITEGROUND_TRANSIENT_HTTP_STATUSES = new Set([202, 429, 503]);

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
