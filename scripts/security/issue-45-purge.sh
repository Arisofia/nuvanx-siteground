#!/usr/bin/env bash
set -euo pipefail

usage() {
  cat <<'EOF'
Usage: issue-45-purge.sh MIRROR_REPOSITORY APPROVED_MANIFEST BUNDLE_PATH EVIDENCE_DIRECTORY TOOLS_DIRECTORY

The manifest must be an approved exact path list produced by the inventory
script. The script rewrites locally and verifies the result. It does not push
unless ISSUE45_FORCE_PUSH=YES is set explicitly. A force push updates only
mutable branch and tag refs; GitHub-managed pull-request refs are never pushed.
EOF
}

if [[ $# -ne 5 ]]; then
  usage >&2
  exit 64
fi

repo_dir="$(cd "$1" && pwd)"
manifest="$(cd "$(dirname "$2")" && pwd)/$(basename "$2")"
bundle_path="$(cd "$(dirname "$3")" && pwd)/$(basename "$3")"
evidence_dir="$(mkdir -p "$4" && cd "$4" && pwd)"
tools_dir="$(cd "$5" && pwd)"
verify_script="$tools_dir/issue-45-verify.sh"

if [[ "$(git -C "$repo_dir" rev-parse --is-bare-repository)" != "true" ]]; then
  echo 'ERROR: history rewrite must run in an isolated mirror/bare clone.' >&2
  exit 65
fi
if [[ ! -s "$manifest" ]]; then
  echo "ERROR: approved manifest is missing or empty: $manifest" >&2
  exit 66
fi
if [[ ! -x "$verify_script" && ! -f "$verify_script" ]]; then
  echo "ERROR: verification script not found: $verify_script" >&2
  exit 66
fi
if ! command -v git-filter-repo >/dev/null 2>&1 && ! git filter-repo --version >/dev/null 2>&1; then
  echo 'ERROR: git-filter-repo is required.' >&2
  exit 69
fi
if ! command -v gitleaks >/dev/null 2>&1; then
  echo 'ERROR: Gitleaks is required before a rewrite can be approved.' >&2
  exit 69
fi

if grep -nE '^[[:space:]]*(#|$)|^(glob:|regex:)' "$manifest"; then
  echo 'ERROR: approved manifest must contain exact Git paths only, one per line.' >&2
  exit 65
fi

origin_url="$(git -C "$repo_dir" remote get-url origin)"
if [[ -z "$origin_url" ]]; then
  echo 'ERROR: origin remote is missing.' >&2
  exit 65
fi

cp "$manifest" "$evidence_dir/approved-purge-paths.txt"
sha256sum "$evidence_dir/approved-purge-paths.txt" > "$evidence_dir/approved-purge-paths.sha256"
git -C "$repo_dir" show-ref --head | sort > "$evidence_dir/refs-immediately-before-rewrite.txt"
git -C "$repo_dir" count-objects -vH > "$evidence_dir/repository-size-immediately-before.txt"

echo "Creating offline administrative bundle: $bundle_path"
git -C "$repo_dir" bundle create "$bundle_path" --all
sha256sum "$bundle_path" > "$bundle_path.sha256"

(
  cd "$repo_dir"
  git filter-repo \
    --sensitive-data-removal \
    --invert-paths \
    --paths-from-file "$manifest" \
    --force \
    2>&1 | tee "$evidence_dir/git-filter-repo-output.txt"
)

if ! git -C "$repo_dir" remote get-url origin >/dev/null 2>&1; then
  git -C "$repo_dir" remote add origin "$origin_url"
else
  git -C "$repo_dir" remote set-url origin "$origin_url"
fi

bash "$verify_script" \
  "$repo_dir" \
  "$manifest" \
  "$evidence_dir/pre-push-verification" \
  "$tools_dir"

changed_refs_file="$repo_dir/filter-repo/changed-refs"
if [[ -f "$changed_refs_file" ]]; then
  cp "$changed_refs_file" "$evidence_dir/changed-refs.txt"
  grep -c '^refs/pull/.*/head$' "$changed_refs_file" > "$evidence_dir/affected-pull-request-count.txt" || true
  grep '^refs/pull/.*/head$' "$changed_refs_file" > "$evidence_dir/affected-pull-request-refs.txt" || true
else
  printf '0\n' > "$evidence_dir/affected-pull-request-count.txt"
  : > "$evidence_dir/affected-pull-request-refs.txt"
fi

if [[ "${ISSUE45_FORCE_PUSH:-NO}" != "YES" ]]; then
  cat <<EOF
Local rewrite and pre-push verification passed.
No remote refs were changed.
Review:
  $evidence_dir/approved-purge-paths.txt
  $evidence_dir/git-filter-repo-output.txt
  $evidence_dir/pre-push-verification/verification-summary.txt
Then rerun with ISSUE45_FORCE_PUSH=YES to execute an atomic force update of
refs/heads/* and refs/tags/* only.
EOF
  exit 0
fi

cat <<'EOF'
WARNING: performing an irreversible atomic force update of mutable branches and tags.
GitHub-managed refs/pull/* are intentionally excluded and require GitHub Support cleanup.
Branch protections or rulesets may need temporary administrative suspension.
EOF

git -C "$repo_dir" push \
  --force \
  --prune \
  --atomic \
  origin \
  'refs/heads/*:refs/heads/*' \
  'refs/tags/*:refs/tags/*' \
  2>&1 | tee "$evidence_dir/force-mutable-refs-push.txt"

echo 'Mutable ref force update completed. Create a brand-new mirror clone and run issue-45-verify.sh again before closing the incident.'
