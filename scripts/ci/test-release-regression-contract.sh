#!/usr/bin/env bash
set -Eeuo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
fail() { echo "RELEASE_REGRESSION_CONTRACT=FAIL reason=$1" >&2; exit 1; }

BRIDAL="$ROOT/wp-content/themes/nuvanx-medical/inc/nvx-catalog-json.php"
IDENTITY_CONTRACT="$ROOT/scripts/production/test-deploy-identity-contract.mjs"
DEPLOY="$ROOT/tools/deploy/deploy-to-prod.sh"
WORKFLOW="$ROOT/.github/workflows/production.yml"
BOUNDARY="$ROOT/scripts/production/verify-production-boundary.mjs"

for required in "$BRIDAL" "$IDENTITY_CONTRACT" "$DEPLOY" "$WORKFLOW" "$BOUNDARY"; do
  [[ -s "$required" ]] || fail "missing_file:$required"
done

# Bridal retirement must remain an AND condition. This assertion is textual
# because the source depends on WordPress runtime state, but it tolerates
# formatting changes and produces an explicit diagnostic.
grep -Eq '\$is_seed[[:space:]]*=[[:space:]]*\$has_meta_key[[:space:]]*&&[[:space:]]*\$has_seed_marker' "$BRIDAL" \
  || fail 'bridal_seed_requires_meta_and_marker'
if grep -Eq '\$is_seed[[:space:]]*=[[:space:]]*\$has_meta_key[[:space:]]*\|\|[[:space:]]*\$has_seed_marker' "$BRIDAL"; then
  fail 'bridal_seed_or_logic_forbidden'
fi
echo 'RELEASE_REGRESSION_ASSERT=PASS name=bridal-seed-and-contract'

node "$IDENTITY_CONTRACT" || fail 'deploy_identity_behavior'
echo 'RELEASE_REGRESSION_ASSERT=PASS name=deploy-identity-behavior'

# Production deploys must refuse anonymous/manual identity before cutover.
grep -Eq 'DEPLOY_RUN_ID=.*GITHUB_RUN_ID' "$DEPLOY" || fail 'deploy_run_id_not_sourced_from_github'
grep -Eq 'DEPLOY_RUN_ID.*\^\[0-9\].*\$' "$DEPLOY" || fail 'deploy_run_id_numeric_guard_missing'
! grep -Eq 'DEPLOY_RUN_ID=.*manual' "$DEPLOY" || fail 'manual_deploy_identity_still_allowed'
echo 'RELEASE_REGRESSION_ASSERT=PASS name=deploy-run-id-enforcement'

# Validate semantic workflow wiring without depending on step display names or
# environment assignment ordering.
grep -Eq 'EXPECTED_RUN_ID=.*GITHUB_RUN_ID' "$WORKFLOW" || fail 'release_expected_run_id_not_wired'
grep -Fq 'ORIGIN_SSH_ALIAS=nvx-prod-audit' "$WORKFLOW" || fail 'audit_origin_alias_not_wired'
grep -Fq 'ORIGIN_SSH_ALIAS=nvx-prod-hubspot' "$WORKFLOW" || fail 'hubspot_origin_alias_not_wired'
grep -Fq 'steps.production_identity.outcome' "$WORKFLOW" || fail 'identity_failure_not_compensated'
echo 'RELEASE_REGRESSION_ASSERT=PASS name=workflow-identity-wiring'

# Boundary must use the shared semantic parser/validator and explicit run ID.
grep -Fq "from './deploy-identity-contract.mjs'" "$BOUNDARY" || fail 'boundary_shared_contract_missing'
grep -Fq "process.env.EXPECTED_RUN_ID || ''" "$BOUNDARY" || fail 'boundary_expected_run_id_not_explicit'
! grep -Fq 'process.env.EXPECTED_RUN_ID || process.env.GITHUB_RUN_ID' "$BOUNDARY" || fail 'boundary_current_audit_run_fallback_forbidden'
echo 'RELEASE_REGRESSION_ASSERT=PASS name=boundary-identity-semantics'

echo 'RELEASE_REGRESSION_CONTRACT=PASS assertions=5'
