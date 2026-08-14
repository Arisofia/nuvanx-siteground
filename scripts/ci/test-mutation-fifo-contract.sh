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
  # No older active mutation runs for the PASS fixture.
  exit 0
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
  'GITHUB_REPOSITORY=Arisofia/nuvanx-siteground'
  'GITHUB_RUN_ID=42'
  'GH_TOKEN=test-token'
  'MUTATION_WAIT_STABILIZE_SECONDS=1'
  'MUTATION_WAIT_POLL_SECONDS=1'
  'MUTATION_WAIT_MAX_SECONDS=60'
)

pass_log="$TMP/pass.log"
env "${common_env[@]}" GITHUB_RUN_ATTEMPT=1 bash "$SUBJECT" >"$pass_log" 2>&1
grep -Fq 'MUTATION_FIFO=PASS' "$pass_log"
grep -Fq 'attempt=1' "$pass_log"

after_rerun="$TMP/rerun.log"
set +e
env "${common_env[@]}" GITHUB_RUN_ATTEMPT=2 bash "$SUBJECT" >"$after_rerun" 2>&1
rerun_rc=$?
set -e
[[ "$rerun_rc" -ne 0 ]]
grep -Fq 'reason=rerun_forbidden' "$after_rerun"
grep -Fq 'action=start_new_run' "$after_rerun"

echo 'MUTATION_FIFO_CONTRACT_TEST=PASS cases=2'
