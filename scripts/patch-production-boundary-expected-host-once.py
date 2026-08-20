#!/usr/bin/env python3
from pathlib import Path

boundary = Path('scripts/production/verify-production-boundary.mjs')
text = boundary.read_text(encoding='utf-8')
old = "`PROD_ROOT=${prodRoot} BASE_URL=${baseUrl} EXPECTED_SHA=${expectedSha} EXPECTED_RUN_ID=${expectedRunId} SITEGROUND_CAPTCHA_PATH=${SITEGROUND_CAPTCHA_PATH} PROD_DB_NAME=${prodDbName} bash -se`,"
new = "`PROD_ROOT=${prodRoot} BASE_URL=${baseUrl} EXPECTED_HOST=${expectedHost} EXPECTED_SHA=${expectedSha} EXPECTED_RUN_ID=${expectedRunId} SITEGROUND_CAPTCHA_PATH=${SITEGROUND_CAPTCHA_PATH} PROD_DB_NAME=${prodDbName} bash -se`,"
if text.count(old) != 1:
    raise SystemExit(f'expected exactly one origin SSH env string, found {text.count(old)}')
text = text.replace(old, new, 1)
if text.count('EXPECTED_HOST=${expectedHost}') != 1:
    raise SystemExit('EXPECTED_HOST origin wiring missing or duplicated')
boundary.write_text(text, encoding='utf-8')

contract = Path('scripts/ci/test-release-regression-contract.sh')
ctext = contract.read_text(encoding='utf-8')
needle = "grep -Fq \"process.env.EXPECTED_RUN_ID || ''\" \"$BOUNDARY\" || fail 'boundary_expected_run_id_not_explicit'\n"
insert = needle + "grep -Fq 'EXPECTED_HOST=${expectedHost}' \"$BOUNDARY\" || fail 'boundary_origin_expected_host_not_wired'\n"
if ctext.count(needle) != 1:
    raise SystemExit(f'expected exactly one boundary contract insertion point, found {ctext.count(needle)}')
ctext = ctext.replace(needle, insert, 1)
contract.write_text(ctext, encoding='utf-8')

print('PRODUCTION_BOUNDARY_EXPECTED_HOST_PATCH=PASS files=2')
