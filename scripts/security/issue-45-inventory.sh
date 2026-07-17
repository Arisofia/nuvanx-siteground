#!/usr/bin/env bash
set -euo pipefail

usage() {
  cat <<'EOF'
Usage: issue-45-inventory.sh MIRROR_REPOSITORY OUTPUT_DIRECTORY TOOLS_DIRECTORY

MIRROR_REPOSITORY must be an isolated --mirror clone.
TOOLS_DIRECTORY must contain issue-45-build-manifest.py.
The output contains paths and counts only; Gitleaks findings are redacted.
EOF
}

if [[ $# -ne 3 ]]; then
  usage >&2
  exit 64
fi

repo_dir="$(cd "$1" && pwd)"
output_dir="$(mkdir -p "$2" && cd "$2" && pwd)"
tools_dir="$(cd "$3" && pwd)"
builder="$tools_dir/issue-45-build-manifest.py"

if [[ ! -f "$builder" ]]; then
  echo "ERROR: manifest builder not found: $builder" >&2
  exit 66
fi

if [[ "$(git -C "$repo_dir" rev-parse --is-bare-repository)" != "true" ]]; then
  echo 'ERROR: use an isolated mirror/bare clone, not a working clone.' >&2
  exit 65
fi

if ! command -v git-filter-repo >/dev/null 2>&1 && ! git filter-repo --version >/dev/null 2>&1; then
  echo 'ERROR: git-filter-repo is required.' >&2
  exit 69
fi

printf 'repository=%s\n' "$repo_dir" > "$output_dir/inventory-metadata.txt"
printf 'generated_utc=%s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" >> "$output_dir/inventory-metadata.txt"
printf 'old_incident_commit=%s\n' '81dafb2e74b6bbfffe302979d67fe789c0075509' >> "$output_dir/inventory-metadata.txt"

git -C "$repo_dir" show-ref --head | sort > "$output_dir/refs-before.txt"
git -C "$repo_dir" -c core.quotePath=false rev-list --objects --all > "$output_dir/all-objects-before.txt"
git -C "$repo_dir" count-objects -vH > "$output_dir/repository-size-before.txt"
git -C "$repo_dir" for-each-ref --format='%(refname)' refs/heads refs/tags refs/pull | sort > "$output_dir/reachable-refs-before.txt"

(
  cd "$repo_dir"
  git filter-repo --analyze --force
)

git_dir="$(git -C "$repo_dir" rev-parse --git-dir)"
analysis_dir="$repo_dir/$git_dir/filter-repo/analysis"
if [[ -d "$analysis_dir" ]]; then
  rm -rf "$output_dir/filter-repo-analysis"
  cp -R "$analysis_dir" "$output_dir/filter-repo-analysis"
fi

gitleaks_report="$output_dir/gitleaks-before-redacted.json"
: > "$gitleaks_report"
if command -v gitleaks >/dev/null 2>&1; then
  set +e
  gitleaks git \
    --redact=100 \
    --report-format json \
    --report-path "$gitleaks_report" \
    --log-opts='--all' \
    "$repo_dir"
  gitleaks_status=$?
  set -e
  printf 'gitleaks_exit=%s\n' "$gitleaks_status" >> "$output_dir/inventory-metadata.txt"
else
  printf 'gitleaks_exit=NOT_INSTALLED\n' >> "$output_dir/inventory-metadata.txt"
fi

python3 "$builder" \
  --objects "$output_dir/all-objects-before.txt" \
  --gitleaks-report "$gitleaks_report" \
  --manifest "$output_dir/candidate-purge-paths.txt" \
  --reasons "$output_dir/candidate-purge-reasons.tsv"

printf 'refs=%s\n' "$(wc -l < "$output_dir/reachable-refs-before.txt" | tr -d ' ')" >> "$output_dir/inventory-metadata.txt"
printf 'objects_with_paths=%s\n' "$(wc -l < "$output_dir/all-objects-before.txt" | tr -d ' ')" >> "$output_dir/inventory-metadata.txt"
printf 'candidate_paths=%s\n' "$(wc -l < "$output_dir/candidate-purge-paths.txt" | tr -d ' ')" >> "$output_dir/inventory-metadata.txt"

(
  cd "$output_dir"
  sha256sum \
    refs-before.txt \
    all-objects-before.txt \
    candidate-purge-paths.txt \
    candidate-purge-reasons.tsv \
    repository-size-before.txt \
    > evidence-sha256.txt
)

cat <<EOF
Inventory complete.
Review and approve this exact path list before rewriting:
  $output_dir/candidate-purge-paths.txt
Reasons:
  $output_dir/candidate-purge-reasons.tsv
EOF
