import assert from 'node:assert/strict';
import {
  SITEGROUND_CAPTCHA_PATH,
  isSiteGroundCaptchaInterruption,
  isSiteGroundTransientResponse,
} from './siteground-transient-classifier.mjs';

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
