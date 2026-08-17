#!/usr/bin/env bash
# Read-only parity audit for the NUVANX theme across repository, staging2 and production.
# Requires local git checkout plus SSH aliases with read access to SiteGround.
set -Eeuo pipefail

STAGING_ROOT="${STAGING_ROOT:-/home/customer/www/staging2.nuvanx.com/public_html}"
PROD_ROOT="${PROD_ROOT:-/home/customer/www/nuvanx.com/public_html}"
THEME_REL="${THEME_REL:-wp-content/themes/nuvanx-medical}"
STAGING_SSH_ALIAS="${STAGING_SSH_ALIAS:-nvx-staging2}"
PROD_SSH_ALIAS="${PROD_SSH_ALIAS:-nvx-prod}"
REPO_ROOT="${REPO_ROOT:-$(git rev-parse --show-toplevel)}"
LOCAL_DIR="${LOCAL_DIR:-$REPO_ROOT/$THEME_REL}"

[[ -d "$LOCAL_DIR" ]] || { echo "Local theme not found: $LOCAL_DIR" >&2; exit 1; }
for command_name in git find sort xargs sha256sum ssh diff awk comm mktemp; do
  command -v "$command_name" >/dev/null 2>&1 || { echo "Missing command: $command_name" >&2; exit 1; }
done

WORK_DIR="$(mktemp -d)"
trap 'rm -rf "$WORK_DIR"' EXIT
LOCAL_MANIFEST="$WORK_DIR/local-theme-sha256.txt"
STAGING_MANIFEST="$WORK_DIR/staging-theme-sha256.txt"
PROD_MANIFEST="$WORK_DIR/prod-theme-sha256.txt"

# Single source of truth for files that are deployment/runtime state rather than immutable theme source.
FIND_FILTER=(
  ! -name '.nvx-deploy-sha'
  ! -name '.nvx-deploy-stamp.json'
  ! -name 'php_errorlog'
  ! -name '*.log'
  ! -name '*.bak*'
  ! -path './backups-nuvanx/*'
  ! -path './quarantine/*'
  ! -path './_archive*'
  ! -path './_disabled*'
)

make_local_manifest() {
  (
    cd "$LOCAL_DIR"
    find . -type f "${FIND_FILTER[@]}" -print0 \
      | sort -z \
      | xargs -0 -r sha256sum
  ) > "$LOCAL_MANIFEST"
}

make_remote_manifest() {
  local alias="$1"
  local root="$2"
  local output="$3"
  local remote_theme="$root/$THEME_REL"

  {
    printf 'set -Eeuo pipefail\n'
    printf 'cd %q\n' "$remote_theme"
    printf 'find . -type f'
    printf ' %q' "${FIND_FILTER[@]}"
    printf ' -print0 | sort -z | xargs -0 -r sha256sum\n'
  } | ssh "$alias" bash -se > "$output"
}

paths_from_manifest() {
  local manifest="$1"
  awk '{ $1=""; sub(/^  /, ""); print }' "$manifest" | sort
}

make_local_manifest
make_remote_manifest "$STAGING_SSH_ALIAS" "$STAGING_ROOT" "$STAGING_MANIFEST"
make_remote_manifest "$PROD_SSH_ALIAS" "$PROD_ROOT" "$PROD_MANIFEST"

printf 'LOCAL_GIT_SHA=%s\n' "$(git -C "$REPO_ROOT" rev-parse HEAD)"
printf 'LOCAL_FILES=%s\n' "$(wc -l < "$LOCAL_MANIFEST" | tr -d ' ')"
printf 'STAGING_FILES=%s\n' "$(wc -l < "$STAGING_MANIFEST" | tr -d ' ')"
printf 'PROD_FILES=%s\n' "$(wc -l < "$PROD_MANIFEST" | tr -d ' ')"

echo '== Files only in local vs production =='
comm -23 <(paths_from_manifest "$LOCAL_MANIFEST") <(paths_from_manifest "$PROD_MANIFEST") || true

echo '== Files only in production vs local =='
comm -13 <(paths_from_manifest "$LOCAL_MANIFEST") <(paths_from_manifest "$PROD_MANIFEST") || true

echo '== Exact local ↔ production checksum diff =='
if diff -u "$LOCAL_MANIFEST" "$PROD_MANIFEST"; then
  echo 'LOCAL_PROD_THEME_PARITY=PASS'
else
  echo 'LOCAL_PROD_THEME_PARITY=FAIL'
fi

echo '== Exact staging2 ↔ production checksum diff =='
if diff -u "$STAGING_MANIFEST" "$PROD_MANIFEST"; then
  echo 'STAGING_PROD_THEME_PARITY=PASS'
else
  echo 'STAGING_PROD_THEME_PARITY=FAIL'
fi

echo '== Deploy identity markers =='
printf 'LOCAL_GIT_SHA='; git -C "$REPO_ROOT" rev-parse HEAD
printf 'STAGING_DEPLOY_SHA='; ssh "$STAGING_SSH_ALIAS" "tr -d '\\r\\n' < '$STAGING_ROOT/$THEME_REL/.nvx-deploy-sha' 2>/dev/null || true"; echo
printf 'PROD_DEPLOY_SHA='; ssh "$PROD_SSH_ALIAS" "tr -d '\\r\\n' < '$PROD_ROOT/$THEME_REL/.nvx-deploy-sha' 2>/dev/null || true"; echo
