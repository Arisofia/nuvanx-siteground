import assert from 'node:assert/strict';
import { isExpectedClientResourceAbort } from './browser-request-failure-classifier.mjs';

assert.equal(isExpectedClientResourceAbort('image', 'net::ERR_ABORTED'), true);
assert.equal(isExpectedClientResourceAbort('image', 'net::ERR_ABORTED; maybe frame was detached?'), true);
assert.equal(isExpectedClientResourceAbort('media', 'net::ERR_ABORTED'), true);

assert.equal(isExpectedClientResourceAbort('image', 'net::ERR_NAME_NOT_RESOLVED'), false);
assert.equal(isExpectedClientResourceAbort('image', 'net::ERR_CONNECTION_RESET'), false);
assert.equal(isExpectedClientResourceAbort('script', 'net::ERR_ABORTED'), false);
assert.equal(isExpectedClientResourceAbort('document', 'net::ERR_ABORTED'), false);
assert.equal(isExpectedClientResourceAbort('', 'net::ERR_ABORTED'), false);

console.log('BROWSER_REQUEST_FAILURE_CLASSIFIER=PASS expected_client_aborts=3 reportable_failures=5');
