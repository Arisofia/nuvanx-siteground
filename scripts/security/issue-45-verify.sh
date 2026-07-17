#!/usr/bin/env bash
set -euo pipefail

usage() {
  cat <<'EOF'
Usage: issue-45-verify.sh REPOSITORY APPROVED_MANIFEST OUTPUT_DIRECTORY TOOLS_DIRECTORY

Run against the rewritten mirror before force-push and again against a fresh
post-push mirror clone. Verification scopes to mutable refs (refs/heads/* and
refs/tags/*). GitHub-managed refs/pull/* are reported separately and do not
fail closure checks — they require GitHub Support cleanup.

Gitleaks is mandatory. Evidence contains paths/counts, not secret values.
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
printf 'scope=refs/heads+refs/tags\n' >> "$output_dir/verification-summary.txt"

# Mutable-ref reachability only. GitHub keeps refs/pull/* with pre-rewrite tips
# until Support purges them; those must not fail the mutable-ref closure check.
if git -C "$repo_dir" rev-list --branches --tags | grep -qx "$old_commit"; then
  echo "ERROR: incident commit remains reachable from branches/tags: $old_commit" >&2
  git -C "$repo_dir" for-each-ref --contains "$old_commit" refs/heads refs/tags >&2 || true
  exit 1
fi
printf 'old_incident_commit_reachable_from_mutable_refs=false\n' >> "$output_dir/verification-summary.txt"

# Still record whether the object exists at all (usually via pull refs on GitHub).
if git -C "$repo_dir" cat-file -e "${old_commit}^{commit}" 2>/dev/null; then
  printf 'old_incident_commit_object_present=true\n' >> "$output_dir/verification-summary.txt"
  git -C "$repo_dir" for-each-ref --contains "$old_commit" --format='%(refname)' \
    > "$output_dir/refs-still-containing-incident.txt" || true
  pull_hit_count="$(grep -c '^refs/pull/' "$output_dir/refs-still-containing-incident.txt" 2>/dev/null || true)"
  printf 'pull_refs_containing_incident=%s\n' "${pull_hit_count:-0}" >> "$output_dir/verification-summary.txt"
else
  printf 'old_incident_commit_object_present=false\n' >> "$output_dir/verification-summary.txt"
  : > "$output_dir/refs-still-containing-incident.txt"
  printf 'pull_refs_containing_incident=0\n' >> "$output_dir/verification-summary.txt"
fi

git -C "$repo_dir" show-ref --head | sort > "$output_dir/refs-after.txt"
# Scope object inventory to mutable refs only (exclude GitHub pull refs).
git -C "$repo_dir" -c core.quotePath=false rev-list --objects --branches --tags \
  > "$output_dir/all-objects-after.txt"
git -C "$repo_dir" count-objects -vH > "$output_dir/repository-size-after.txt"

python3 "$builder" \
  --objects "$output_dir/all-objects-after.txt" \
  --manifest "$output_dir/residual-candidate-paths.txt" \
  --reasons "$output_dir/residual-candidate-reasons.tsv"

if [[ -s "$output_dir/residual-candidate-paths.txt" ]]; then
  echo 'ERROR: prohibited paths remain reachable from branches/tags after rewrite:' >&2
  cat "$output_dir/residual-candidate-paths.txt" >&2
  exit 1
fi
printf 'residual_candidate_paths=0\n' >> "$output_dir/verification-summary.txt"

sort -u "$manifest" > "$output_dir/approved-manifest-sorted.txt"
awk 'index($0, " ") {sub(/^[^ ]+ /, ""); print}' "$output_dir/all-objects-after.txt" | sort -u > "$output_dir/reachable-paths-after.txt"
comm -12 "$output_dir/approved-manifest-sorted.txt" "$output_dir/reachable-paths-after.txt" > "$output_dir/approved-paths-still-reachable.txt"
if [[ -s "$output_dir/approved-paths-still-reachable.txt" ]]; then
  echo 'ERROR: approved purge paths remain reachable from branches/tags:' >&2
  cat "$output_dir/approved-paths-still-reachable.txt" >&2
  exit 1
fi
printf 'approved_paths_still_reachable=0\n' >> "$output_dir/verification-summary.txt"

gitleaks_config="$tools_dir/gitleaks.toml"
gitleaks_args=(
  git
  --redact=100
  --report-format json
  --report-path "$output_dir/gitleaks-after-redacted.json"
  "--log-opts=--branches --tags"
)
if [[ -f "$gitleaks_config" ]]; then
  gitleaks_args+=(--config "$gitleaks_config")
fi
gitleaks_args+=("$repo_dir")

set +e
gitleaks "${gitleaks_args[@]}"
gitleaks_status=$?
set -e
if [[ $gitleaks_status -ne 0 ]]; then
  echo 'ERROR: Gitleaks found one or more reachable findings on branches/tags. Path-only summary:' >&2
  if [[ -s "$output_dir/gitleaks-after-redacted.json" ]]; then
    python3 - "$output_dir/gitleaks-after-redacted.json" <<'PY' >&2
import json
import sys
from pathlib import Path

report = Path(sys.argv[1])
try:
    findings = json.loads(report.read_text(encoding="utf-8"))
except (OSError, json.JSONDecodeError) as exc:
    print(f"unable to parse redacted report: {exc}")
    raise SystemExit(0)

if not isinstance(findings, list):
    print("unexpected gitleaks report shape")
    raise SystemExit(0)

for finding in findings:
    if not isinstance(finding, dict):
        continue
    rule = finding.get("RuleID") or finding.get("Rule") or "unknown-rule"
    path = finding.get("File") or finding.get("file") or "(no-file)"
    commit = (finding.get("Commit") or "")[:12]
    line = finding.get("StartLine") or ""
    print(f"- rule={rule} file={path} commit={commit} line={line}")
print(f"gitleaks_findings={len(findings)}")
PY
  fi
  exit 1
fi
printf 'gitleaks_exit=0\n' >> "$output_dir/verification-summary.txt"

git -C "$repo_dir" fsck --full --no-reflogs > "$output_dir/git-fsck-after.txt" 2>&1
printf 'git_fsck=pass\n' >> "$output_dir/verification-summary.txt"
printf 'heads=%s\n' "$(git -C "$repo_dir" for-each-ref --format='%(refname)' refs/heads | wc -l | tr -d ' ')" >> "$output_dir/verification-summary.txt"
printf 'tags=%s\n' "$(git -C "$repo_dir" for-each-ref --format='%(refname)' refs/tags | wc -l | tr -d ' ')" >> "$output_dir/verification-summary.txt"
printf 'commits_mutable=%s\n' "$(git -C "$repo_dir" rev-list --count --branches --tags)" >> "$output_dir/verification-summary.txt"

(
  cd "$output_dir"
  sha256sum \
    refs-after.txt \
    all-objects-after.txt \
    approved-manifest-sorted.txt \
    repository-size-after.txt \
    verification-summary.txt \
    gitleaks-after-redacted.json \
    refs-still-containing-incident.txt \
    > evidence-sha256.txt
)

echo "Verification passed: $output_dir/verification-summary.txt"
