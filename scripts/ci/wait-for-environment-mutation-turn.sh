#!/usr/bin/env bash
set -Eeuo pipefail

: "${GITHUB_REPOSITORY:?Missing GITHUB_REPOSITORY}"
: "${GITHUB_RUN_ID:?Missing GITHUB_RUN_ID}"
: "${GITHUB_RUN_ATTEMPT:?Missing GITHUB_RUN_ATTEMPT}"
: "${GH_TOKEN:?Missing GH_TOKEN}"

command -v gh >/dev/null || { echo 'MUTATION_FIFO=FAIL reason=missing_gh' >&2; exit 1; }
command -v sort >/dev/null || { echo 'MUTATION_FIFO=FAIL reason=missing_sort' >&2; exit 1; }

CURRENT_RUN_ID="$(printf '%s' "$GITHUB_RUN_ID" | tr -d '[:space:]')"
CURRENT_RUN_ATTEMPT="$(printf '%s' "$GITHUB_RUN_ATTEMPT" | tr -d '[:space:]')"
[[ "$CURRENT_RUN_ID" =~ ^[0-9]{1,20}$ ]] || { echo "MUTATION_FIFO=FAIL reason=invalid_run_id value=$CURRENT_RUN_ID" >&2; exit 1; }
[[ "$CURRENT_RUN_ATTEMPT" =~ ^[0-9]{1,6}$ && "$CURRENT_RUN_ATTEMPT" -ge 1 ]] || { echo "MUTATION_FIFO=FAIL reason=invalid_run_attempt value=$CURRENT_RUN_ATTEMPT" >&2; exit 1; }

# GitHub re-runs keep the original run_id while incrementing run_attempt. A
# re-run of an old mutation could therefore be older by run_id but newer in
# wall-clock time, which destroys a total FIFO ordering. Mutation retries must
# be new workflow_dispatch/push runs so they receive a fresh monotonic run_id.
if (( CURRENT_RUN_ATTEMPT > 1 )); then
  echo "MUTATION_FIFO=FAIL reason=rerun_forbidden run_id=$CURRENT_RUN_ID attempt=$CURRENT_RUN_ATTEMPT action=start_new_run" >&2
  exit 1
fi

ROLE="${MUTATION_ROLE:-environment-mutation}"
POLL_SECONDS="${MUTATION_WAIT_POLL_SECONDS:-15}"
STABILIZE_SECONDS="${MUTATION_WAIT_STABILIZE_SECONDS:-5}"
MAX_WAIT_SECONDS="${MUTATION_WAIT_MAX_SECONDS:-3600}"

[[ "$POLL_SECONDS" =~ ^[0-9]{1,5}$ && "$POLL_SECONDS" -ge 1 ]] || { echo 'MUTATION_FIFO=FAIL reason=invalid_poll_seconds' >&2; exit 1; }
[[ "$STABILIZE_SECONDS" =~ ^[0-9]{1,5}$ && "$STABILIZE_SECONDS" -ge 1 ]] || { echo 'MUTATION_FIFO=FAIL reason=invalid_stabilize_seconds' >&2; exit 1; }
[[ "$MAX_WAIT_SECONDS" =~ ^[0-9]{1,6}$ && "$MAX_WAIT_SECONDS" -ge 60 ]] || { echo 'MUTATION_FIFO=FAIL reason=invalid_max_wait_seconds' >&2; exit 1; }

is_mutation_workflow_path() {
  case "$1" in
    .github/workflows/staging.yml|.github/workflows/staging.yml@*|.github/workflows/production.yml|.github/workflows/production.yml@*) return 0 ;;
    *) return 1 ;;
  esac
}

is_mutation_event() {
  case "$1" in
    push|workflow_dispatch|pull_request_target) return 0 ;;
    *) return 1 ;;
  esac
}

current_meta="$(gh api "/repos/${GITHUB_REPOSITORY}/actions/runs/${CURRENT_RUN_ID}")"
current_path="$(printf '%s' "$current_meta" | jq -r '.path // ""')"
current_event="$(printf '%s' "$current_meta" | jq -r '.event // ""')"
current_status="$(printf '%s' "$current_meta" | jq -r '.status // ""')"
api_attempt="$(printf '%s' "$current_meta" | jq -r '(.run_attempt // 0) | tostring')"

is_mutation_workflow_path "$current_path" || {
  echo "MUTATION_FIFO=FAIL reason=current_workflow_not_canonical path=$current_path" >&2
  exit 1
}
is_mutation_event "$current_event" || {
  echo "MUTATION_FIFO=FAIL reason=current_event_not_mutating event=$current_event" >&2
  exit 1
}
[[ "$api_attempt" == "$CURRENT_RUN_ATTEMPT" ]] || {
  echo "MUTATION_FIFO=FAIL reason=run_attempt_identity_mismatch env=$CURRENT_RUN_ATTEMPT api=$api_attempt" >&2
  exit 1
}
[[ "$current_status" != 'completed' ]] || {
  echo "MUTATION_FIFO=FAIL reason=current_run_already_completed run_id=$CURRENT_RUN_ID" >&2
  exit 1
}

started_epoch="$(date +%s)"
clear_scans=0

while :; do
  declare -A blockers=()

  for status in queued in_progress waiting requested pending; do
    while IFS=$'\t' read -r run_id run_status run_event run_path run_sha; do
      [[ "$run_id" =~ ^[0-9]{1,20}$ ]] || continue
      (( run_id < CURRENT_RUN_ID )) || continue
      is_mutation_workflow_path "$run_path" || continue
      is_mutation_event "$run_event" || continue
      blockers["$run_id"]="$run_status|$run_event|$run_path|$run_sha"
    done < <(
      gh api --paginate "/repos/${GITHUB_REPOSITORY}/actions/runs?status=${status}&per_page=100" \
        --jq '.workflow_runs[] | [(.id|tostring),(.status // ""),(.event // ""),(.path // ""),(.head_sha // "")] | @tsv'
    )
  done

  if (( ${#blockers[@]} == 0 )); then
    clear_scans=$((clear_scans + 1))
    if (( clear_scans >= 2 )); then
      waited=$(( $(date +%s) - started_epoch ))
      echo "MUTATION_FIFO=PASS role=$ROLE run_id=$CURRENT_RUN_ID attempt=$CURRENT_RUN_ATTEMPT waited_seconds=$waited stable_scans=$clear_scans"
      exit 0
    fi
    echo "MUTATION_FIFO=CLEAR_STABILIZING role=$ROLE run_id=$CURRENT_RUN_ID attempt=$CURRENT_RUN_ATTEMPT scan=$clear_scans/2"
    sleep "$STABILIZE_SECONDS"
    continue
  fi

  clear_scans=0
  now_epoch="$(date +%s)"
  waited=$(( now_epoch - started_epoch ))
  if (( waited >= MAX_WAIT_SECONDS )); then
    echo "MUTATION_FIFO=FAIL reason=wait_timeout role=$ROLE run_id=$CURRENT_RUN_ID waited_seconds=$waited blockers=${#blockers[@]}" >&2
    for run_id in $(printf '%s\n' "${!blockers[@]}" | sort -n); do
      echo "MUTATION_FIFO_BLOCKER run_id=$run_id meta=${blockers[$run_id]}" >&2
    done
    exit 1
  fi

  echo "MUTATION_FIFO=WAIT role=$ROLE run_id=$CURRENT_RUN_ID waited_seconds=$waited blockers=${#blockers[@]}"
  for run_id in $(printf '%s\n' "${!blockers[@]}" | sort -n); do
    echo "MUTATION_FIFO_BLOCKER run_id=$run_id meta=${blockers[$run_id]}"
  done
  sleep "$POLL_SECONDS"
done
