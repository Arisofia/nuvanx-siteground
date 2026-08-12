#!/usr/bin/env bash
# MUTATING: deploy an already-accepted nuvanx-medical candidate to production.
# Requires --confirm or NUVANX_CONFIRM=yes.
#
# Safety model:
# - exact production identity and exact 40-char candidate SHA
# - source is staged and validated away from the live theme
# - mandatory SQL + theme backup before mutation
# - no production MU-plugin mutation; legacy ownership must already be clean
# - directory cutover avoids rsyncing partial files into the live theme
# - exact .nvx-deploy-sha marker is part of the staged release
# - any post-cutover failure restores the previous live theme automatically
# - SiteGround dynamic-cache purge restores the original Speed Optimizer state
set -Eeuo pipefail

PROD_ROOT=""
SOURCE_THEME=""
SHA=""
CONFIRM=0

usage() {
  cat >&2 <<'EOF'
Usage:
  deploy-to-prod.sh \
    --prod-root /home/customer/www/nuvanx.com/public_html \
    --source-theme /absolute/path/to/accepted/theme \
    --sha <full-lowercase-40-char-commit-sha> \
    --confirm
EOF
}

require_confirm() {
  [[ "$CONFIRM" -eq 1 || "${NUVANX_CONFIRM:-}" == "yes" ]] || {
    echo "Refusing to run without --confirm or NUVANX_CONFIRM=yes" >&2
    exit 1
  }
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --prod-root) PROD_ROOT="$2"; shift 2 ;;
    --source-theme) SOURCE_THEME="$2"; shift 2 ;;
    --sha) SHA="$2"; shift 2 ;;
    --confirm) CONFIRM=1; shift ;;
    *) usage; echo "Unknown arg: $1" >&2; exit 2 ;;
  esac
done

[[ -n "$PROD_ROOT" && -n "$SOURCE_THEME" && -n "$SHA" ]] || { usage; exit 2; }
[[ "$SHA" =~ ^[0-9a-f]{40}$ ]] || { echo "ERROR: SHA must be a full lowercase 40-character commit SHA" >&2; exit 2; }
[[ "$PROD_ROOT" == '/home/customer/www/nuvanx.com/public_html' ]] || {
  echo "ERROR: refusing unexpected production root: $PROD_ROOT" >&2
  exit 1
}

command -v wp >/dev/null 2>&1 || { echo "wp-cli required" >&2; exit 2; }
command -v rsync >/dev/null 2>&1 || { echo "rsync required" >&2; exit 2; }
command -v php >/dev/null 2>&1 || { echo "php required" >&2; exit 2; }
command -v tar >/dev/null 2>&1 || { echo "tar required" >&2; exit 2; }
require_confirm

THEMES_ROOT="$PROD_ROOT/wp-content/themes"
LIVE_THEME="$THEMES_ROOT/nuvanx-medical"
[[ -d "$LIVE_THEME" ]] || { echo "ERROR: production theme missing at $LIVE_THEME" >&2; exit 1; }
[[ -d "$SOURCE_THEME" ]] || { echo "ERROR: source theme missing at $SOURCE_THEME" >&2; exit 1; }

live_real="$(cd "$LIVE_THEME" && pwd -P)"
source_real="$(cd "$SOURCE_THEME" && pwd -P)"
[[ "$live_real" != "$source_real" ]] || { echo "ERROR: source theme is the live production theme" >&2; exit 1; }

RUN_TOKEN="${NVX_RUN_TOKEN:-$(date +%Y%m%d-%H%M%S)-$$}"
[[ "$RUN_TOKEN" =~ ^[A-Za-z0-9._-]+$ ]] || { echo "ERROR: invalid NVX_RUN_TOKEN" >&2; exit 2; }
RELEASE_ROOT="$THEMES_ROOT/.nvx-prod-release-${SHA}-${RUN_TOKEN}"
STAGED_THEME="$RELEASE_ROOT/nuvanx-medical"
PREVIOUS_THEME="$THEMES_ROOT/.nvx-prod-previous-${RUN_TOKEN}"
FAILED_THEME="$THEMES_ROOT/.nvx-prod-failed-${RUN_TOKEN}"
BACKUP_DIR="$PROD_ROOT/wp-content/backups-nuvanx/pre-prod-${RUN_TOKEN}-${SHA:0:12}"
SWAPPED=0
ROLLBACK_IN_PROGRESS=0

cleanup_uncommitted_release() {
  if [[ "$SWAPPED" -eq 0 ]]; then
    rm -rf "$RELEASE_ROOT" 2>/dev/null || true
  fi
}
trap cleanup_uncommitted_release EXIT

# SiteGround exposes `wp sg purge` only while Speed Optimizer is active on some
# installations. Temporarily activate it only when needed, purge, and always
# restore the original plugin state before returning.
purge_siteground_dynamic_cache() {
  local plugin='sg-cachepress'
  local activated_temporarily=0
  local purge_rc=0
  local restore_rc=0

  cd "$PROD_ROOT"

  if wp help sg >/dev/null 2>&1; then
    wp sg purge
    echo 'SITEGROUND_DYNAMIC_PURGE=PASS mode=existing-command'
    return 0
  fi

  if ! wp plugin is-installed "$plugin" >/dev/null 2>&1; then
    echo 'SITEGROUND_DYNAMIC_PURGE=SKIPPED reason=sg-command-and-plugin-unavailable'
    return 0
  fi

  if ! wp plugin is-active "$plugin" >/dev/null 2>&1; then
    wp plugin activate "$plugin" --quiet
    activated_temporarily=1
  fi

  if wp help sg >/dev/null 2>&1; then
    wp sg purge || purge_rc=$?
  else
    echo "ERROR: SiteGround command unavailable after transient Speed Optimizer activation" >&2
    purge_rc=1
  fi

  if [[ "$activated_temporarily" -eq 1 ]]; then
    wp plugin deactivate "$plugin" --quiet || restore_rc=$?
    if wp plugin is-active "$plugin" >/dev/null 2>&1; then
      echo "ERROR: Speed Optimizer remained active after transient cache purge" >&2
      restore_rc=1
    fi
  fi

  if [[ "$restore_rc" -ne 0 ]]; then
    return "$restore_rc"
  fi
  if [[ "$purge_rc" -ne 0 ]]; then
    return "$purge_rc"
  fi

  if [[ "$activated_temporarily" -eq 1 ]]; then
    echo 'SITEGROUND_DYNAMIC_PURGE=PASS mode=transient-plugin-activation restored=inactive'
  else
    echo 'SITEGROUND_DYNAMIC_PURGE=PASS mode=plugin-already-active'
  fi
}

echo "== Guard production identity =="
(
  cd "$PROD_ROOT"
  db="$(wp config get DB_NAME)"
  siteurl="$(wp option get siteurl)"
  home="$(wp option get home)"
  blog_public="$(wp option get blog_public)"
  theme="$(wp theme list --status=active --field=name)"
  echo "prod db=$db siteurl=$siteurl home=$home blog_public=$blog_public theme=$theme"
  [[ "$db" == 'db0ecrycwv2tgb' ]] || { echo "ERROR: unexpected production DB=$db" >&2; exit 1; }
  [[ "$siteurl" == 'https://nuvanx.com' ]] || { echo "ERROR: unexpected prod siteurl=$siteurl" >&2; exit 1; }
  [[ "$home" == 'https://nuvanx.com' ]] || { echo "ERROR: unexpected prod home=$home" >&2; exit 1; }
  [[ "$blog_public" == '1' ]] || { echo "ERROR: production blog_public=$blog_public" >&2; exit 1; }
  [[ "$theme" == 'nuvanx-medical' ]] || { echo "ERROR: active theme is $theme" >&2; exit 1; }
)

for legacy_mu in \
  nuvanx-valoracion-native-hubspot-form.php \
  nuvanx-contacto-hubspot-form.php \
  nvx-disable-public-facebook-pixel.php \
  nuvanx-google-attribution.php
do
  [[ ! -e "$PROD_ROOT/wp-content/mu-plugins/$legacy_mu" ]] || {
    echo "ERROR: legacy production MU plugin still present: $legacy_mu" >&2
    exit 1
  }
done
[[ ! -d "$PROD_ROOT/wp-content/mu-plugins/nuvanx-google-attribution" ]] || {
  echo "ERROR: legacy production attribution MU package still present" >&2
  exit 1
}

echo "== Stage accepted theme away from live production =="
[[ ! -e "$RELEASE_ROOT" ]]
[[ ! -e "$PREVIOUS_THEME" ]]
[[ ! -e "$FAILED_THEME" ]]
mkdir -p "$STAGED_THEME"
rsync -a --delete \
  --exclude='.git' --exclude='php_errorlog' --exclude='*.log' \
  --exclude='backups-nuvanx' --exclude='quarantine' \
  --exclude='_archive*' --exclude='_disabled*' --exclude='*.bak*' \
  "$SOURCE_THEME/" "$STAGED_THEME/"
printf '%s\n' "$SHA" > "$STAGED_THEME/.nvx-deploy-sha"
[[ "$(tr -d '\r\n' < "$STAGED_THEME/.nvx-deploy-sha")" == "$SHA" ]]

for required in \
  assets/css/nvx-fonts.css \
  assets/css/nvx-tokens.css \
  assets/css/nvx-base.css \
  assets/css/nvx-site-layout.css \
  assets/css/nvx-components.css \
  assets/css/nvx-patterns-editorial.css \
  assets/css/nvx-header.css \
  assets/css/nvx-footer.css \
  assets/css/nvx-posts.css \
  inc/nvx-blog-system.php \
  functions.php
do
  [[ -f "$STAGED_THEME/$required" ]] || { echo "ERROR: staged release missing $required" >&2; exit 1; }
done
grep -Fq 'nvx-patterns-editorial.css' "$STAGED_THEME/functions.php"
find "$STAGED_THEME" -path '*/vendor' -prune -o -name '*.php' -type f -print0 | xargs -0 -n1 php -l >/dev/null
find "$STAGED_THEME/assets/css" -maxdepth 1 -type f -name 'nvx-*.min.css' -delete 2>/dev/null || true

echo "== Mandatory pre-deploy rollback snapshot =="
umask 077
mkdir -p "$BACKUP_DIR"
(
  cd "$PROD_ROOT"
  wp db export "$BACKUP_DIR/db.sql" --quiet
)
tar -czf "$BACKUP_DIR/theme.tgz" -C "$PROD_ROOT" wp-content/themes/nuvanx-medical
if [[ -d "$PROD_ROOT/wp-content/mu-plugins" ]]; then
  tar -czf "$BACKUP_DIR/mu-plugins.tgz" -C "$PROD_ROOT" wp-content/mu-plugins
fi
[[ -s "$BACKUP_DIR/db.sql" ]]
[[ -s "$BACKUP_DIR/theme.tgz" ]]
if [[ -f "$LIVE_THEME/.nvx-deploy-sha" ]]; then
  tr -d '\r\n' < "$LIVE_THEME/.nvx-deploy-sha" > "$BACKUP_DIR/previous-sha.txt"
else
  : > "$BACKUP_DIR/previous-sha.txt"
fi

echo "ROLLBACK_SNAPSHOT=PASS path=$BACKUP_DIR"

rollback_after_swap() {
  local rc="${1:-$?}"
  trap - ERR INT TERM HUP
  set +e
  if [[ "$ROLLBACK_IN_PROGRESS" -eq 1 ]]; then
    return
  fi
  ROLLBACK_IN_PROGRESS=1
  if [[ "$SWAPPED" -eq 1 ]]; then
    echo "ROLLBACK_TRIGGERED rc=$rc previous=$PREVIOUS_THEME" >&2
    rm -rf "$FAILED_THEME"
    if [[ -d "$LIVE_THEME" ]]; then
      mv "$LIVE_THEME" "$FAILED_THEME"
    fi
    if [[ -d "$PREVIOUS_THEME" ]]; then
      mv "$PREVIOUS_THEME" "$LIVE_THEME"
    fi
    (
      cd "$PROD_ROOT"
      wp cache flush || true
      purge_siteground_dynamic_cache || true
      wp eval 'if (function_exists("opcache_reset")) { opcache_reset(); }' || true
    )
    restored="$(cat "$BACKUP_DIR/previous-sha.txt" 2>/dev/null || true)"
    if [[ -n "$restored" && -f "$LIVE_THEME/.nvx-deploy-sha" ]]; then
      actual="$(tr -d '\r\n' < "$LIVE_THEME/.nvx-deploy-sha")"
      [[ "$actual" == "$restored" ]] || echo "WARN: rollback marker $actual != expected $restored" >&2
    fi
    echo "ROLLBACK_PRODUCTION=PASS" >&2
  fi
  exit "$rc"
}
trap rollback_after_swap ERR
trap 'rollback_after_swap 130' INT
trap 'rollback_after_swap 143' TERM
trap 'rollback_after_swap 129' HUP

echo "== Directory cutover =="
mv "$LIVE_THEME" "$PREVIOUS_THEME"
mv "$STAGED_THEME" "$LIVE_THEME"
SWAPPED=1

echo "== Verify exact production release on disk =="
(
  trap - ERR INT TERM HUP
  cd "$PROD_ROOT"
  [[ "$(tr -d '\r\n' < wp-content/themes/nuvanx-medical/.nvx-deploy-sha)" == "$SHA" ]]
  [[ "$(wp config get DB_NAME)" == 'db0ecrycwv2tgb' ]]
  [[ "$(wp option get home)" == 'https://nuvanx.com' ]]
  [[ "$(wp option get siteurl)" == 'https://nuvanx.com' ]]
  [[ "$(wp option get blog_public)" == '1' ]]
  [[ "$(wp theme list --status=active --field=name)" == 'nuvanx-medical' ]]
purge_rc=0
(
  trap - ERR INT TERM HUP
  cd "$PROD_ROOT"
  inner_rc=0
  wp cache flush || inner_rc=$?
  purge_siteground_dynamic_cache || inner_rc=$?
  rm -rf wp-content/uploads/siteground-optimizer-assets/siteground-optimizer-combined-* 2>/dev/null || true
  rm -rf wp-content/cache/sgo-cache/* wp-content/cache/* 2>/dev/null || true
  wp eval 'if (function_exists("opcache_reset")) { opcache_reset(); echo "opcache=ok\n"; }' || true
  exit "$inner_rc"
) || purge_rc=$?
[[ "$purge_rc" -eq 0 ]] || echo "WARN: production cache purge reported a non-fatal error rc=$purge_rc" >&2

[[ "$(tr -d '\r\n' < "$LIVE_THEME/.nvx-deploy-sha")" == "$SHA" ]]

trap - ERR INT TERM HUP
rm -rf "$PREVIOUS_THEME" "$RELEASE_ROOT"
SWAPPED=0
trap - EXIT

echo "DEPLOY_PRODUCTION_OK sha=$SHA backup=$BACKUP_DIR"
