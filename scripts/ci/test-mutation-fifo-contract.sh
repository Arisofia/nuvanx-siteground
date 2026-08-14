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
    printf '%s\n' '{"path":".github/workflows/staging.yml","event":"push","status":"in_progress","run_attempt":1}'
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

echo 'MUTATION_FIFO_CONTRACT_TEST=PASS cases=4'
