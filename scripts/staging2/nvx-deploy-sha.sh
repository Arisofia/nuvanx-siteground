# Shared deploy-SHA acceptance helpers for bash smoke (source this file).
# Keep message text aligned with scripts/staging2/nvx-deploy-sha.mjs.
# shellcheck shell=bash

NVX_DEPLOY_SHA_SH_LOADED=1

# extract_deploy_sha_from_html_file <html_file>
extract_deploy_sha_from_html_file() {
  local body_file="$1"
  # Prefer name-then-content, then content-then-name (same as nvx-deploy-sha.mjs).
  local sha
  sha="$(grep -Eio '<meta[^>]+name=["'\'']nvx-deploy-sha["'\''][^>]+content=["'\''][a-f0-9]{40}["'\'']' "$body_file" 2>/dev/null | head -n 1 | grep -Eio '[a-f0-9]{40}' | head -n 1 || true)"
  if [[ -z "$sha" ]]; then
    sha="$(grep -Eio '<meta[^>]+content=["'\''][a-f0-9]{40}["'\''][^>]+name=["'\'']nvx-deploy-sha["'\'']' "$body_file" 2>/dev/null | head -n 1 | grep -Eio '[a-f0-9]{40}' | head -n 1 || true)"
  fi
  printf '%s' "$sha"
  return 0
}

# assert_html_deploy_sha <page_path> <html_file> [expected_sha]
# Prints ERROR line and returns 1 on failure. expected_sha may be empty (marker presence only).
assert_html_deploy_sha() {
  local page_path="$1"
  local body_file="$2"
  local expected_sha="${3:-}"
  local deployed_sha

  if ! grep -Eiq '<meta[^>]+name=["'\'']nvx-deploy-sha["'\'']' "$body_file"; then
    echo "ERROR: ${page_path} is missing meta nvx-deploy-sha (stale full-page cache or theme head not executing)" >&2
    return 1
  fi

  deployed_sha="$(extract_deploy_sha_from_html_file "$body_file")"
  if [[ -z "$deployed_sha" ]]; then
    echo "ERROR: ${page_path} is missing meta nvx-deploy-sha (stale full-page cache or theme head not executing)" >&2
    return 1
  fi

  if [[ -n "$expected_sha" && "$deployed_sha" != "$expected_sha" ]]; then
    echo "ERROR: ${page_path} served SHA ${deployed_sha} instead of ${expected_sha}" >&2
    return 1
  fi
  return 0
}
