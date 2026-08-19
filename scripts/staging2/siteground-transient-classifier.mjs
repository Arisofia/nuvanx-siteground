export const SITEGROUND_CAPTCHA_PATH = '/.well-known/sgcaptcha/';
export const SITEGROUND_TRANSIENT_HTTP_STATUSES = new Set([202, 429, 503]);
/** BSD sysexits: EX_USAGE (64) - recovery path is unavailable or not applicable. */
export const EX_NOT_APPLICABLE = 64;
/** BSD sysexits: EX_TEMPFAIL (75) - temporary failure / transient infrastructure challenge (retryable). */
export const EX_TEMPFAIL = 75;
/** BSD sysexits: EX_CONFIG (78) - invalid recovery configuration/identity. */
export const EX_CONFIG = 78;

/** GitHub event names that trigger pipeline execution. */
export const GITHUB_EVENT_NAMES = Object.freeze({
  PULL_REQUEST_TARGET: 'pull_request_target',
  PUSH: 'push',
  WORKFLOW_DISPATCH: 'workflow_dispatch',
});

/** Git ref names for protected branches. */
export const GIT_REF_NAMES = Object.freeze({
  MASTER: 'master',
});

/** Execution path identifiers for GitHub Actions. */
export const EXECUTION_PATHS = Object.freeze({
  PULL_REQUEST: 'pull_request',
  ONE_SHOT_MASTER_PUSH: 'one_shot_master_push',
  TRUSTED_WORKFLOW_DISPATCH: 'trusted_workflow_dispatch',
  UNSUPPORTED_EVENT: 'unsupported_event',
});

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

export function isOneShotMasterPush(eventName = '', refName = '') {
  return eventName === GITHUB_EVENT_NAMES.PUSH && refName === GIT_REF_NAMES.MASTER;
}

export function getGitHubEventPath(eventName = '', refName = '') {
  if (eventName === GITHUB_EVENT_NAMES.PULL_REQUEST_TARGET) {
    return EXECUTION_PATHS.PULL_REQUEST;
  }
  if (isOneShotMasterPush(eventName, refName)) {
    return EXECUTION_PATHS.ONE_SHOT_MASTER_PUSH;
  }
  if (eventName === 'workflow_dispatch' && refName === GIT_REF_NAMES.MASTER) {
    return EXECUTION_PATHS.TRUSTED_WORKFLOW_DISPATCH;
  }
  return EXECUTION_PATHS.UNSUPPORTED_EVENT;
}
