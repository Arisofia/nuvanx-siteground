#!/usr/bin/env bash
set -Eeuo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SUBJECT="$ROOT/scripts/ci/wait-for-environment-mutation-turn.sh"
test -s "$SUBJECT"

TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
mkdir -p "$TMP/bin"

cat > "$TMP/bin/gh" <<'STUB'
#!/usr/bin/env bash
set -euo pipefail
if [[ "${1:-}" != api ]]; then
  echo "unexpected gh command: $*" >&2
  exit 2
fi
shift
if [[ "${1:-}" == --paginate ]]; then
  scenario="${TEST_SCENARIO:-pass}"
  case "$scenario" in
    pass)
      # No older active mutation runs
      exit 0
      ;;
    blocked)
      # Older run 41 in_progress on staging
      printf '%s\t%s\t%s\t%s\t%s\n' '41' 'in_progress' 'push' '.github/workflows/staging.yml' '0123456789abcdef0123456789abcdef01234567'
      exit 0
      ;;
    transient_api_fail)
      fail_flag="${TMP_DIR:-/tmp}/failed_once"
      if [[ ! -f "$fail_flag" ]]; then
        touch "$fail_flag"
        echo "simulated 502 Bad Gateway" >&2
        exit 1
      fi
      exit 0
      ;;
    *)
      exit 0
      ;;
  esac
fi
case "${1:-}" in
  */actions/runs/42)
    printf '%s\n' '{"path":".github/workflows/staging.yml","event":"push","status":"in_progress","run_attempt":1,"head_sha":"0123456789abcdef0123456789abcdef01234567"}'
    ;;
  */actions/workflows/staging.yml/runs*)
    branch_scenario="${TEST_BRANCH_SCENARIO:-none}"
    case "$branch_scenario" in
      superseded)
        printf '%s\n' '{"workflow_runs":[{"id":43,"head_sha":"9999999999abcdef0123456789abcdef01234567","head_branch":"master","event":"push"}]}'
        ;;
      none|*)
        printf '%s\n' '{"workflow_runs":[{"id":42,"head_sha":"0123456789abcdef0123456789abcdef01234567","head_branch":"master","event":"push"}]}'
        ;;
    esac
    ;;
  *)
    echo "unexpected gh api target: ${1:-missing}" >&2
    exit 2
    ;;
esac
STUB
chmod +x "$TMP/bin/gh"

common_env=(
  "PATH=$TMP/bin:$PATH"
  "TMP_DIR=$TMP"
  'GITHUB_REPOSITORY=Arisofia/nuvanx-siteground'
  'GITHUB_RUN_ID=42'
  'GH_TOKEN=test-token'
  'MUTATION_WAIT_STABILIZE_SECONDS=1'
  'MUTATION_WAIT_POLL_SECONDS=1'
  'MUTATION_WAIT_MAX_SECONDS=60'
)

# Case 1: Happy path
pass_log="$TMP/pass.log"
env "${common_env[@]}" GITHUB_RUN_ATTEMPT=1 TEST_SCENARIO=pass bash "$SUBJECT" >"$pass_log" 2>&1
grep -Fq 'MUTATION_FIFO=PASS' "$pass_log"
grep -Fq 'attempt=1' "$pass_log"

# Case 2: Re-run rejection
after_rerun="$TMP/rerun.log"
set +e
env "${common_env[@]}" GITHUB_RUN_ATTEMPT=2 TEST_SCENARIO=pass bash "$SUBJECT" >"$after_rerun" 2>&1
rerun_rc=$?
set -e
[[ "$rerun_rc" -ne 0 ]]
grep -Fq 'reason=rerun_forbidden' "$after_rerun"
grep -Fq 'action=start_new_run' "$after_rerun"

# Case 3: Blocker timeout detection
blocked_log="$TMP/blocked.log"
set +e
env "${common_env[@]}" GITHUB_RUN_ATTEMPT=1 MUTATION_WAIT_MAX_SECONDS=1 TEST_SCENARIO=blocked bash "$SUBJECT" >"$blocked_log" 2>&1
blocked_rc=$?
set -e
[[ "$blocked_rc" -ne 0 ]]
grep -Fq 'MUTATION_FIFO=FAIL reason=wait_timeout' "$blocked_log"
grep -Fq 'MUTATION_FIFO_BLOCKER run_id=41' "$blocked_log"

# Case 4: API failure recovery (retries rather than failing open)
transient_log="$TMP/transient.log"
rm -f "$TMP/failed_once"
env "${common_env[@]}" GITHUB_RUN_ATTEMPT=1 TEST_SCENARIO=transient_api_fail bash "$SUBJECT" >"$transient_log" 2>&1
grep -Fq 'MUTATION_FIFO=WARN reason=api_query_failed retrying=true' "$transient_log"
grep -Fq 'MUTATION_FIFO=PASS' "$transient_log"

# Case 5: Superseded push run rejection (exit 78)
superseded_log="$TMP/superseded.log"
set +e
env "${common_env[@]}" GITHUB_RUN_ATTEMPT=1 MUTATION_ROLE=staging TEST_BRANCH_SCENARIO=superseded bash "$SUBJECT" >"$superseded_log" 2>&1
superseded_rc=$?
set -e
[[ "$superseded_rc" -eq 78 ]]
grep -Fq 'MUTATION_FIFO=SUPERSEDED' "$superseded_log"
grep -Fq 'mutation=forbidden' "$superseded_log"

# Case 6: Bridal seed retirement must require BOTH the historical meta key and
# the temporary seed marker. OR logic re-drafts the real editorial page after
# production-to-staging parity sync.
BRIDAL="$ROOT/wp-content/themes/nuvanx-medical/inc/nvx-catalog-json.php"
grep -Eq '\$is_seed[[:space:]]*=[[:space:]]*\$has_meta_key[[:space:]]*&&[[:space:]]*\$has_seed_marker;' "$BRIDAL"
! grep -Eq '\$is_seed[[:space:]]*=[[:space:]]*\$has_meta_key[[:space:]]*\|\|[[:space:]]*\$has_seed_marker;' "$BRIDAL"

# Case 7: Production verifier must require a numeric deployed GitHub Actions run
# ID and compare it to the expected run when one is provided.
IDENTITY="$ROOT/scripts/production/verify-production-identity.mjs"
grep -Fq 'if (!/^\d+$/.test(deployStamp.DEPLOY_RUN_ID))' "$IDENTITY"
grep -Fq 'deployStamp.DEPLOY_RUN_ID !== expectedRunId' "$IDENTITY"

# Case 8: The atomic production payload must carry all four immutable identity
# fields in the exact stamp file consumed by the active theme.
DEPLOY="$ROOT/tools/deploy/deploy-to-prod.sh"
grep -Fq 'cat > "$STAGED_THEME/.nvx-deploy-stamp.json"' "$DEPLOY"
grep -Fq '"DEPLOY_SHA": "$SHA"' "$DEPLOY"
grep -Fq '"DEPLOY_RUN_ID": "$DEPLOY_RUN_ID"' "$DEPLOY"
grep -Fq '"DEPLOY_TIMESTAMP": "$DEPLOY_TIMESTAMP"' "$DEPLOY"
grep -Fq '"RELEASE_ID": "$RELEASE_ID"' "$DEPLOY"
STAMP_READER="$ROOT/wp-content/themes/nuvanx-medical/inc/nvx-deploy-stamp.php"
grep -Fq "get_template_directory() . '/.nvx-deploy-stamp.json'" "$STAMP_READER"

# Case 9: The canonical production workflow must propagate GITHUB_RUN_ID through
# SSH and run the dedicated 4-field verifier against that same run.
PRODUCTION_WORKFLOW="$ROOT/.github/workflows/production.yml"
grep -Fq "GITHUB_RUN_ID='\${GITHUB_RUN_ID}'" "$PRODUCTION_WORKFLOW"
grep -Fq 'Verify full production identity chain (4-field verification)' "$PRODUCTION_WORKFLOW"
grep -Fq 'EXPECTED_RUN_ID="$GITHUB_RUN_ID" EXPECTED_SHA="$CANDIDATE_SHA"' "$PRODUCTION_WORKFLOW"

# Case 10: The rollback-protected production boundary itself must enforce all
# four fields, so identity drift fails before the compensating rollback gate.
BOUNDARY="$ROOT/scripts/production/verify-production-boundary.mjs"
grep -Fq "extractMetaContent(html, 'nvx-deploy-sha')" "$BOUNDARY"
grep -Fq "extractMetaContent(html, 'nvx-deploy-run-id')" "$BOUNDARY"
grep -Fq "extractMetaContent(html, 'nvx-deploy-timestamp')" "$BOUNDARY"
grep -Fq "extractMetaContent(html, 'nvx-release-id')" "$BOUNDARY"
grep -Fq 'deployRunId !== expectedRunId' "$BOUNDARY"
grep -Fq 'validIsoTimestamp(deployTimestamp)' "$BOUNDARY"

echo 'MUTATION_FIFO_CONTRACT_TEST=PASS cases=10'
