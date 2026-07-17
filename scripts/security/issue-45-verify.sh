#!/usr/bin/env bash
set -euo pipefail

usage() {
  cat <<'EOF'
Usage: issue-45-verify.sh REPOSITORY APPROVED_MANIFEST OUTPUT_DIRECTORY TOOLS_DIRECTORY

Run against the rewritten mirror before force-push and again against a fresh
post-push mirror clone. Gitleaks is mandatory. Evidence contains paths/counts,
not secret values.
EOF
}

if [[ $# -ne 4 ]]; then
  usage >&2
  exit 64
fi

repo_dir="$(cd "$1" && pwd)"
manifest="$(cd "$(dirname "$2")" && pwd)/$(basename "$2")"
output_dir="$(mkdir -p "$3" && cd "$3" && pwd)"
tools_dir="$(cd "$4" && pwd)"
builder="$tools_dir/issue-45-build-manifest.py"
old_commit='81dafb2e74b6bbfffe302979d67fe789c0075509'

if [[ ! -s "$manifest" ]]; then
  echo "ERROR: approved manifest is missing or empty: $manifest" >&2
  exit 66
fi
if [[ ! -f "$builder" ]]; then
  echo "ERROR: manifest builder not found: $builder" >&2
  exit 66
fi
if ! command -v gitleaks >/dev/null 2>&1; then
  echo 'ERROR: Gitleaks is mandatory for closure verification.' >&2
  exit 69
fi

: > "$output_dir/verification-summary.txt"
printf 'verified_utc=%s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" >> "$output_dir/verification-summary.txt"
printf 'repository=%s\n' "$repo_dir" >> "$output_dir/verification-summary.txt"

if git -C "$repo_dir" cat-file -e "${old_commit}^{commit}" 2>/dev/null; then
  echo "ERROR: incident commit remains reachable: $old_commit" >&2
  exit 1
fi
printf 'old_incident_commit_reachable=false\n' >> "$output_dir/verification-summary.txt"

git -C "$repo_dir" show-ref --head | sort > "$output_dir/refs-after.txt"
git -C "$repo_dir" -c core.quotePath=false rev-list --objects --all > "$output_dir/all-objects-after.txt"
git -C "$repo_dir" count-objects -vH > "$output_dir/repository-size-after.txt"

python3 "$builder" \
  --objects "$output_dir/all-objects-after.txt" \
  --manifest "$output_dir/residual-candidate-paths.txt" \
  --reasons "$output_dir/residual-candidate-reasons.tsv"

if [[ -s "$output_dir/residual-candidate-paths.txt" ]]; then
  echo 'ERROR: prohibited paths remain reachable after rewrite:' >&2
  cat "$output_dir/residual-candidate-paths.txt" >&2
  exit 1
fi
printf 'residual_candidate_paths=0\n' >> "$output_dir/verification-summary.txt"

sort -u "$manifest" > "$output_dir/approved-manifest-sorted.txt"
awk 'index($0, " ") {sub(/^[^ ]+ /, ""); print}' "$output_dir/all-objects-after.txt" | sort -u > "$output_dir/reachable-paths-after.txt"
comm -12 "$output_dir/approved-manifest-sorted.txt" "$output_dir/reachable-paths-after.txt" > "$output_dir/approved-paths-still-reachable.txt"
if [[ -s "$output_dir/approved-paths-still-reachable.txt" ]]; then
  echo 'ERROR: approved purge paths remain reachable:' >&2
  cat "$output_dir/approved-paths-still-reachable.txt" >&2
  exit 1
fi
printf 'approved_paths_still_reachable=0\n' >> "$output_dir/verification-summary.txt"

set +e
gitleaks git \
  --redact=100 \
  --report-format json \
  --report-path "$output_dir/gitleaks-after-redacted.json" \
  --log-opts='--all' \
  "$repo_dir"
gitleaks_status=$?
set -e
if [[ $gitleaks_status -ne 0 ]]; then
  echo 'ERROR: Gitleaks found one or more reachable findings. Review the redacted report.' >&2
  exit 1
fi
printf 'gitleaks_exit=0\n' >> "$output_dir/verification-summary.txt"

git -C "$repo_dir" fsck --full --no-reflogs > "$output_dir/git-fsck-after.txt" 2>&1
printf 'git_fsck=pass\n' >> "$output_dir/verification-summary.txt"
printf 'heads=%s\n' "$(git -C "$repo_dir" for-each-ref --format='%(refname)' refs/heads | wc -l | tr -d ' ')" >> "$output_dir/verification-summary.txt"
printf 'tags=%s\n' "$(git -C "$repo_dir" for-each-ref --format='%(refname)' refs/tags | wc -l | tr -d ' ')" >> "$output_dir/verification-summary.txt"
printf 'commits=%s\n' "$(git -C "$repo_dir" rev-list --count --all)" >> "$output_dir/verification-summary.txt"

(
  cd "$output_dir"
  sha256sum \
    refs-after.txt \
    all-objects-after.txt \
    approved-manifest-sorted.txt \
    repository-size-after.txt \
    verification-summary.txt \
    gitleaks-after-redacted.json \
    > evidence-sha256.txt
)

echo "Verification passed: $output_dir/verification-summary.txt"
