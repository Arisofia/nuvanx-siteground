#!/usr/bin/env bash
set -Eeuo pipefail

: "${CANDIDATE_SHA:?Missing CANDIDATE_SHA}"
: "${GITHUB_REPOSITORY:?Missing GITHUB_REPOSITORY}"
: "${GH_TOKEN:?Missing GH_TOKEN}"

STAGING_ACCEPTANCE_BRANCH="${STAGING_ACCEPTANCE_BRANCH:-master}"
STAGING_ACCEPTANCE_WORKFLOW_PATH="${STAGING_ACCEPTANCE_WORKFLOW_PATH:-.github/workflows/staging.yml}"

[[ "$CANDIDATE_SHA" =~ ^[0-9a-f]{40}$ ]] || { echo 'STAGING_ACCEPTANCE=FAIL reason=invalid_candidate_sha' >&2; exit 1; }
command -v curl >/dev/null
command -v jq >/dev/null
command -v unzip >/dev/null

# Production candidates must carry the zero-submit HubSpot verification contract.
# This permanently rejects historical SHAs whose production QA filled and
# submitted the commercial HubSpot form, even if those SHAs once had successful
# Staging acceptance artifacts.
candidate_hubspot_probe="$(git show "${CANDIDATE_SHA}:scripts/staging2/h1-hubspot-e2e.mjs" 2>/dev/null || true)"
[[ -n "$candidate_hubspot_probe" ]] || {
  echo "STAGING_ACCEPTANCE=FAIL reason=missing_zero_submit_hubspot_probe sha=$CANDIDATE_SHA" >&2
  exit 1
}
printf '%s' "$candidate_hubspot_probe" | grep -Fq 'HUBSPOT_PRODUCTION_CONTRACT_MODE=ZERO_SUBMIT' || {
  echo "STAGING_ACCEPTANCE=FAIL reason=hubspot_probe_missing_zero_submit_marker sha=$CANDIDATE_SHA" >&2
  exit 1
}
printf '%s' "$candidate_hubspot_probe" | grep -Fq 'PRODUCTION_HUBSPOT_CONTRACT=PASS' || {
  echo "STAGING_ACCEPTANCE=FAIL reason=hubspot_probe_missing_contract_marker sha=$CANDIDATE_SHA" >&2
  exit 1
}
if printf '%s' "$candidate_hubspot_probe" | grep -Eqi 'nvxqa-h1-|QA H1 Attribution|wp_set_consent|\?gclid=|\.click[[:space:]]*\(|submissions/v3'; then
  echo "STAGING_ACCEPTANCE=FAIL reason=unsafe_live_hubspot_probe sha=$CANDIDATE_SHA" >&2
  exit 1
fi
echo "STAGING_ACCEPTANCE_HUBSPOT_SAFETY=PASS sha=$CANDIDATE_SHA zero_submit=1"

artifact_name="staging2-block-c-${CANDIDATE_SHA}"
if ! response="$(curl -fsSL --retry 3 --retry-all-errors --connect-timeout 10 --max-time 60 --proto '=https' --proto-redir '=https' "${api_headers[@]}" "https://api.github.com/repos/${GITHUB_REPOSITORY}/actions/artifacts?name=${artifact_name}&per_page=100")"; then
  echo "STAGING_ACCEPTANCE=FAIL reason=github_api_artifacts_query_failed sha=$CANDIDATE_SHA" >&2
  exit 1
fi
mapfile -t candidates < <(printf '%s' "$response" | jq -rc --arg name "$artifact_name" '[.artifacts[] | select(.name == $name and .expired == false)] | sort_by(.created_at) | reverse | .[] | [.id, .workflow_run.id, .created_at] | @tsv')
(( ${#candidates[@]} > 0 )) || { echo "STAGING_ACCEPTANCE=FAIL reason=no_artifact sha=$CANDIDATE_SHA" >&2; exit 1; }

for candidate in "${candidates[@]}"; do
  IFS=$'\t' read -r artifact_id run_id created_at <<< "$candidate"
  [[ "$artifact_id" =~ ^[0-9]{1,20}$ && "$run_id" =~ ^[0-9]{1,20}$ ]] || continue

  run="$(curl -fsSL --retry 3 --retry-all-errors --connect-timeout 10 --max-time 60 --proto '=https' --proto-redir '=https' "${api_headers[@]}" "https://api.github.com/repos/${GITHUB_REPOSITORY}/actions/runs/${run_id}")" || continue
  IFS=$'\t' read -r head_branch run_head_sha workflow_path run_event < <(printf '%s' "$run" | jq -r '[.head_branch // "",.head_sha // "",.path // "",.event // ""] | @tsv')

  [[ "$head_branch" == "$STAGING_ACCEPTANCE_BRANCH" ]] || continue
  workflow_prefix="${workflow_path:0:${#STAGING_ACCEPTANCE_WORKFLOW_PATH}}"
  workflow_suffix="${workflow_path:${#STAGING_ACCEPTANCE_WORKFLOW_PATH}}"
  [[ "$workflow_prefix" == "$STAGING_ACCEPTANCE_WORKFLOW_PATH" ]] || continue
  [[ -z "$workflow_suffix" || ( "${workflow_suffix:0:1}" == '@' && "$workflow_suffix" != '@' ) ]] || continue
  [[ "$run_head_sha" =~ ^[0-9a-f]{40}$ ]] || continue
  [[ "$run_event" == push || "$run_event" == workflow_dispatch ]] || continue

  if [[ "$run_event" == push ]]; then
    [[ "$run_head_sha" == "$CANDIDATE_SHA" ]] || continue
  else
    git cat-file -e "${run_head_sha}^{commit}" 2>/dev/null || continue
    git merge-base --is-ancestor "$run_head_sha" "origin/$STAGING_ACCEPTANCE_BRANCH" || continue
    git merge-base --is-ancestor "$CANDIDATE_SHA" "$run_head_sha" || continue
  fi

  artifact_zip="$RUNNER_TEMP/staging-acceptance-${artifact_id}.zip"
  rm -f "$artifact_zip"
  curl -LfsS --retry 3 --retry-all-errors --connect-timeout 10 --max-time 180 --max-filesize 157286400 --proto '=https' --proto-redir '=https' "${api_headers[@]}" "https://api.github.com/repos/${GITHUB_REPOSITORY}/actions/artifacts/${artifact_id}/zip" -o "$artifact_zip" || { rm -f "$artifact_zip"; continue; }
  unzip -tqq "$artifact_zip" >/dev/null 2>&1 || { rm -f "$artifact_zip"; continue; }

  manifest_path="$(unzip -Z1 "$artifact_zip" | grep -E '(^|/)acceptance-manifest\.json$' | head -n1 || true)"
  [[ -n "$manifest_path" ]] || { rm -f "$artifact_zip"; continue; }
  manifest="$(unzip -p "$artifact_zip" "$manifest_path" 2>/dev/null || true)"
  rm -f "$artifact_zip"

  manifest_fields="$(printf '%s' "$manifest" | jq -er '
    select(type == "object" and .schema == 1) |
    [.candidate_sha,.run_id,.run_attempt,.event,.head_sha,.head_branch,.workflow_path] |
    select(all(.[]; type == "string" and length > 0)) |
    @tsv
  ')" || continue
  IFS=$'\t' read -r manifest_candidate manifest_run_id manifest_run_attempt manifest_event manifest_head_sha manifest_head_branch manifest_workflow <<< "$manifest_fields"

  [[ "$manifest_candidate" == "$CANDIDATE_SHA" ]] || continue
  [[ "$manifest_run_id" == "$run_id" ]] || continue
  [[ "$manifest_run_attempt" =~ ^[0-9]{1,6}$ ]] || continue
  manifest_run_attempt=$((10#$manifest_run_attempt))
  (( manifest_run_attempt >= 1 )) || continue
  [[ "$manifest_event" == "$run_event" ]] || continue
  [[ "$manifest_head_sha" == "$run_head_sha" ]] || continue
  [[ "$manifest_head_branch" == "$STAGING_ACCEPTANCE_BRANCH" ]] || continue
  [[ "$manifest_workflow" == "$STAGING_ACCEPTANCE_WORKFLOW_PATH" ]] || continue

  exact_run="$(curl -fsSL --retry 3 --retry-all-errors --connect-timeout 10 --max-time 60 --proto '=https' --proto-redir '=https' "${api_headers[@]}" "https://api.github.com/repos/${GITHUB_REPOSITORY}/actions/runs/${run_id}/attempts/${manifest_run_attempt}")" || continue
  IFS=$'\t' read -r exact_status exact_conclusion exact_branch exact_head_sha exact_path exact_event exact_attempt < <(printf '%s' "$exact_run" | jq -r '[.status // "",.conclusion // "",.head_branch // "",.head_sha // "",.path // "",.event // "",(.run_attempt // "" | tostring)] | @tsv')

  [[ "$exact_status" == completed && "$exact_conclusion" == success ]] || continue
  [[ "$exact_attempt" == "$manifest_run_attempt" ]] || continue
  [[ "$exact_branch" == "$manifest_head_branch" && "$exact_head_sha" == "$manifest_head_sha" && "$exact_event" == "$manifest_event" ]] || continue
  exact_prefix="${exact_path:0:${#STAGING_ACCEPTANCE_WORKFLOW_PATH}}"
  exact_suffix="${exact_path:${#STAGING_ACCEPTANCE_WORKFLOW_PATH}}"
  [[ "$exact_prefix" == "$STAGING_ACCEPTANCE_WORKFLOW_PATH" ]] || continue
  [[ -z "$exact_suffix" || ( "${exact_suffix:0:1}" == '@' && "$exact_suffix" != '@' ) ]] || continue

  if [[ "$manifest_event" == push ]]; then
    [[ "$manifest_head_sha" == "$CANDIDATE_SHA" ]] || continue
  else
    git merge-base --is-ancestor "$CANDIDATE_SHA" "$manifest_head_sha" || continue
  fi

  if [[ -n "${GITHUB_OUTPUT:-}" ]]; then
    {
      echo "artifact_id=$artifact_id"
      echo "run_id=$run_id"
      echo "run_attempt=$manifest_run_attempt"
      echo "event=$manifest_event"
      echo "head_sha=$manifest_head_sha"
    } >> "$GITHUB_OUTPUT"
  fi
  echo "STAGING_ACCEPTANCE=PASS artifact=$artifact_name artifact_id=$artifact_id run_id=$run_id attempt=$manifest_run_attempt sha=$CANDIDATE_SHA event=$manifest_event run_head_sha=$manifest_head_sha conclusion=success"
  exit 0
done

echo "STAGING_ACCEPTANCE=FAIL reason=no_valid_successful_attempt sha=$CANDIDATE_SHA" >&2
exit 1
