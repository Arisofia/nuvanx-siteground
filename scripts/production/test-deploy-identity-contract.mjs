import assert from 'node:assert/strict';
import {
  extractMetaContent,
  validDeployTimestamp,
  validGitHubRunId,
  validReleaseId,
  validateDeployIdentity,
} from './deploy-identity-contract.mjs';

const sha = '0123456789abcdef0123456789abcdef01234567';

assert.equal(validDeployTimestamp('2026-08-16T17:45:30Z'), true);
for (const invalid of [
  '2026-08-16T17:45:30.123Z',
  '2026-08-16T19:45:30+02:00',
  '2026-08-16T17:45:30',
  '2026-02-31T17:45:30Z',
  'not-a-timestamp',
]) {
  assert.equal(validDeployTimestamp(invalid), false, invalid);
}

assert.equal(validGitHubRunId('31962589500'), true);
assert.equal(validGitHubRunId('manual'), false);
assert.equal(validGitHubRunId('319-1'), false);
assert.equal(validReleaseId('012345abcdef'), true);
assert.equal(validReleaseId('release_20260816-1'), true);
assert.equal(validReleaseId('release 1'), false);

const htmlVariants = [
  '<meta name="nvx-deploy-run-id" content="31962589500">',
  "<meta content='31962589500' name='nvx-deploy-run-id'>",
  '<meta content = "31962589500" data-x="1" name = "nvx-deploy-run-id">',
];
for (const html of htmlVariants) {
  assert.equal(extractMetaContent(html, 'nvx-deploy-run-id'), '31962589500');
}

const validIdentity = {
  DEPLOY_SHA: sha,
  DEPLOY_RUN_ID: '31962589500',
  DEPLOY_TIMESTAMP: '2026-08-16T17:45:30Z',
  RELEASE_ID: '012345abcdef',
};
assert.deepEqual(validateDeployIdentity(validIdentity, { expectedSha: sha }), []);
assert.deepEqual(validateDeployIdentity(validIdentity, { expectedSha: sha, expectedRunId: '31962589500' }), []);
assert.match(
  validateDeployIdentity(validIdentity, { expectedSha: sha, expectedRunId: '999' }).join('\n'),
  /DEPLOY_RUN_ID mismatch/,
);
assert.match(
  validateDeployIdentity({ ...validIdentity, DEPLOY_RUN_ID: 'manual' }, { expectedSha: sha }).join('\n'),
  /non-numeric/,
);

console.log('DEPLOY_IDENTITY_CONTRACT_TEST=PASS cases=17');
