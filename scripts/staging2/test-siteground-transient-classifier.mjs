import assert from 'node:assert/strict';
import {
  SITEGROUND_CAPTCHA_PATH,
  SITEGROUND_TRANSIENT_HTTP_STATUSES,
  EX_TEMPFAIL,
  getProtectedBranch,
  isOneShotMasterPush,
  getGitHubEventPath,
  isSiteGroundCaptchaInterruption,
  isSiteGroundTransientResponse,
} from './siteground-transient-classifier.mjs';

assert.equal(EX_TEMPFAIL, 75);
assert.equal(SITEGROUND_TRANSIENT_HTTP_STATUSES.has(202), true);
assert.equal(SITEGROUND_TRANSIENT_HTTP_STATUSES.has(429), true);
assert.equal(SITEGROUND_TRANSIENT_HTTP_STATUSES.has(503), true);

const defaultBranch = getProtectedBranch();
assert.equal(defaultBranch, 'master');

assert.equal(isOneShotMasterPush('push', defaultBranch), true);
assert.equal(isOneShotMasterPush('push', 'feature'), false);
assert.equal(getGitHubEventPath('pull_request_target', ''), 'pull_request');
assert.equal(getGitHubEventPath('push', defaultBranch), 'one_shot_master_push');
assert.equal(getGitHubEventPath('workflow_dispatch', defaultBranch), 'trusted_workflow_dispatch');
assert.equal(getGitHubEventPath('workflow_dispatch', 'feature'), 'unsupported_event');
assert.equal(getGitHubEventPath('pull_request', defaultBranch), 'unsupported_event');

// default / empty / undefined eventName/refName behaviors
assert.equal(getGitHubEventPath(), 'unsupported_event');
assert.equal(getGitHubEventPath('', ''), 'unsupported_event');
assert.equal(getGitHubEventPath(undefined, undefined), 'unsupported_event');
assert.equal(getGitHubEventPath('', defaultBranch), 'unsupported_event');
assert.equal(getGitHubEventPath('push', ''), 'unsupported_event');

// test branch rename support (main as alternative protected branch)
process.env.NUVANX_PROTECTED_BRANCH = 'main';
assert.equal(getProtectedBranch(), 'main');
assert.equal(isOneShotMasterPush('push', 'main'), true);
assert.equal(getGitHubEventPath('push', 'main'), 'one_shot_master_push');
assert.equal(getGitHubEventPath('workflow_dispatch', 'main'), 'trusted_workflow_dispatch');
delete process.env.NUVANX_PROTECTED_BRANCH;

const captchaUrl = `https://staging2.nuvanx.com${SITEGROUND_CAPTCHA_PATH}?rid=qa`;
const interrupted = new Error(
  `page.goto: Navigation to "https://staging2.nuvanx.com/madrid/valoracion/" is interrupted by another navigation to "${captchaUrl}"`
);

assert.equal(isSiteGroundCaptchaInterruption(interrupted, captchaUrl), true);
assert.equal(isSiteGroundCaptchaInterruption(interrupted, 'https://staging2.nuvanx.com/madrid/valoracion/'), true);
assert.equal(
  isSiteGroundCaptchaInterruption(
    new Error('page.goto: Navigation interrupted by another navigation to https://staging2.nuvanx.com/contacto/'),
    'https://staging2.nuvanx.com/madrid/valoracion/'
  ),
  false
);
assert.equal(isSiteGroundCaptchaInterruption(new Error('net::ERR_CONNECTION_RESET'), ''), false);

for (const status of [202, 429, 503]) {
  assert.equal(isSiteGroundTransientResponse(status, {}, 'https://staging2.nuvanx.com/madrid/valoracion/'), true);
}
assert.equal(isSiteGroundTransientResponse(200, { 'sg-captcha': 'challenge' }, 'https://staging2.nuvanx.com/madrid/valoracion/'), true);
assert.equal(isSiteGroundTransientResponse(200, {}, captchaUrl), true);
assert.equal(isSiteGroundTransientResponse(500, {}, 'https://staging2.nuvanx.com/madrid/valoracion/'), false);
assert.equal(isSiteGroundTransientResponse(404, {}, 'https://staging2.nuvanx.com/madrid/valoracion/'), false);

console.log('SITEGROUND_TRANSIENT_CLASSIFIER_TEST=PASS');
