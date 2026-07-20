#!/usr/bin/env bash
# MUTATING: deploy a checked-out NUVANX theme snapshot to staging2 only.
# Intended for the protected manual GitHub Actions workflow or an authorized
# SiteGround operator. Never accepts a production root.
set -Eeuo pipefail

EXPECTED_ROOT='/home/customer/www/staging2.nuvanx.com/public_html'
EXPECTED_URL='https://staging2.nuvanx.com'
THEME_REL='wp-content/themes/nuvanx-medical'
WP_ROOT=''
SOURCE_THEME=''
DEPLOY_SHA=''
CONFIRM=0
BACKUP_DIR=''
MUTATION_STARTED=0

usage() {
  cat >&2 <<'EOF'
Usage:
  deploy-to-staging2.sh \
    --wp-root /home/customer/www/staging2.nuvanx.com/public_html \
    --source-theme /home/customer/www/staging2.nuvanx.com/public_html/wp-content/.nuvanx-deployments/<release>/theme \
    --sha <40-character-git-sha> \
    --confirm
EOF
}

fail() {
  echo "ERROR: $*" >&2
  exit 1
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --wp-root) WP_ROOT="${2:-}"; shift 2 ;;
    --source-theme) SOURCE_THEME="${2:-}"; shift 2 ;;
    --sha) DEPLOY_SHA="${2:-}"; shift 2 ;;
    --confirm) CONFIRM=1; shift ;;
    *) usage; fail "unknown argument: $1" ;;
  esac
done

[[ "$CONFIRM" -eq 1 || "${NUVANX_CONFIRM:-}" == 'yes' ]] || fail 'explicit confirmation is required'
[[ "$WP_ROOT" == "$EXPECTED_ROOT" ]] || fail "refusing unexpected WordPress root: $WP_ROOT"
[[ "$DEPLOY_SHA" =~ ^[0-9a-f]{40}$ ]] || fail 'SHA must contain 40 lowercase hexadecimal characters'
[[ -n "$SOURCE_THEME" ]] || fail 'source theme path is required'
[[ "$SOURCE_THEME" == "$WP_ROOT"/wp-content/.nuvanx-deployments/*/theme ]] || fail 'source theme must be inside the staging2 deployment area'

for command_name in wp rsync tar php find; do
  command -v "$command_name" >/dev/null 2>&1 || fail "required command is unavailable: $command_name"
done

[[ -d "$WP_ROOT" ]] || fail "WordPress root does not exist: $WP_ROOT"
[[ -f "$WP_ROOT/wp-config.php" ]] || fail 'wp-config.php not found in staging2 root'
[[ -d "$SOURCE_THEME" ]] || fail "source theme does not exist: $SOURCE_THEME"
[[ -f "$SOURCE_THEME/style.css" ]] || fail 'source theme is missing style.css'
[[ -f "$SOURCE_THEME/functions.php" ]] || fail 'source theme is missing functions.php'
[[ -f "$SOURCE_THEME/assets/css/nvx-tokens.css" ]] || fail 'source theme is missing nvx-tokens.css'
[[ -f "$SOURCE_THEME/assets/css/nvx-patterns-editorial.css" ]] || fail 'source theme is missing nvx-patterns-editorial.css'
[[ -f "$SOURCE_THEME/inc/nvx-blog-system.php" ]] || fail 'source theme is missing nvx-blog-system.php'

LIVE_THEME="$WP_ROOT/$THEME_REL"
[[ -d "$LIVE_THEME" ]] || fail "live staging2 theme does not exist: $LIVE_THEME"

rollback() {
  local exit_code=$?
  trap - ERR
  if [[ "$MUTATION_STARTED" -eq 1 && -n "$BACKUP_DIR" && -f "$BACKUP_DIR/theme.tgz" ]]; then
    echo 'ROLLBACK: restoring the pre-deploy staging2 theme' >&2
    rm -rf "$LIVE_THEME"
    tar -xzf "$BACKUP_DIR/theme.tgz" -C "$WP_ROOT"
    (
      cd "$WP_ROOT"
      wp cache flush || true
      wp sg purge || true
    )
    echo "ROLLBACK_COMPLETE backup=$BACKUP_DIR" >&2
  fi
  exit "$exit_code"
}
trap rollback ERR

echo '== Guard staging2 identity =='
(
  cd "$WP_ROOT"
  siteurl="$(wp option get siteurl)"
  home="$(wp option get home)"
  theme="$(wp theme list --status=active --field=name)"
  echo "siteurl=$siteurl home=$home active_theme=$theme"
  [[ "$siteurl" == "$EXPECTED_URL" ]] || fail "unexpected siteurl: $siteurl"
  [[ "$home" == "$EXPECTED_URL" ]] || fail "unexpected home URL: $home"
  [[ "$theme" == 'nuvanx-medical' ]] || fail "unexpected active theme: $theme"
)

echo '== Validate source PHP =='
PHP_LINT_LOG="$(mktemp)"
if ! find "$SOURCE_THEME" -type f -name '*.php' -print0 | xargs -0 -n1 php -l >"$PHP_LINT_LOG" 2>&1; then
  cat "$PHP_LINT_LOG" >&2
  rm -f "$PHP_LINT_LOG"
  fail 'source theme PHP lint failed'
fi
rm -f "$PHP_LINT_LOG"

DATE="$(date +%Y%m%d-%H%M%S)"
SHORT_SHA="${DEPLOY_SHA:0:12}"
BACKUP_DIR="$WP_ROOT/wp-content/backups-nuvanx/pre-staging2-${DATE}-${SHORT_SHA}"

echo "== Backup staging2 theme to $BACKUP_DIR =="
mkdir -p "$BACKUP_DIR"
tar -czf "$BACKUP_DIR/theme.tgz" -C "$WP_ROOT" "$THEME_REL"
printf '%s\n' "$DEPLOY_SHA" > "$BACKUP_DIR/intended-sha.txt"

MUTATION_STARTED=1

echo '== Disable SiteGround asset transformations =='
(
  cd "$WP_ROOT"
  wp sg optimize css disable || true
  wp sg optimize combine-css disable || true
  wp sg optimize combine-js disable || true
)

echo '== Synchronize theme to staging2 =='
rsync -a --delete \
  --exclude='.git' \
  --exclude='php_errorlog' \
  --exclude='*.log' \
  --exclude='backups-nuvanx' \
  --exclude='quarantine' \
  --exclude='_archive*' \
  --exclude='_disabled*' \
  --exclude='*.bak*' \
  "$SOURCE_THEME/" \
  "$LIVE_THEME/"

find "$LIVE_THEME/assets/css" -maxdepth 1 -type f -name 'nvx-*.min.css' -delete 2>/dev/null || true
printf '%s\n' "$DEPLOY_SHA" > "$LIVE_THEME/.nvx-deploy-sha"

echo '== Verify deployed files and marker =='
for required_file in \
  style.css \
  functions.php \
  assets/css/nvx-fonts.css \
  assets/css/nvx-tokens.css \
  assets/css/nvx-base.css \
  assets/css/nvx-site-layout.css \
  assets/css/nvx-components.css \
  assets/css/nvx-patterns-editorial.css \
  assets/css/nvx-header.css \
  assets/css/nvx-footer.css \
  inc/nvx-blog-system.php
do
  [[ -f "$LIVE_THEME/$required_file" ]] || fail "deployed theme is missing $required_file"
done
[[ "$(tr -d '\r\n' < "$LIVE_THEME/.nvx-deploy-sha")" == "$DEPLOY_SHA" ]] || fail 'deployed SHA marker does not match'
grep -Fq 'nvx-patterns-editorial.css' "$LIVE_THEME/functions.php" || fail 'functions.php does not enqueue the canonical editorial stylesheet'

echo '== Purge staging2 caches =='
(
  cd "$WP_ROOT"
  wp cache flush || true
  wp sg purge || true
  rm -rf wp-content/uploads/siteground-optimizer-assets/siteground-optimizer-combined-* 2>/dev/null || true
  rm -rf wp-content/cache/sgo-cache/* 2>/dev/null || true
  rm -rf wp-content/cache/* 2>/dev/null || true
  wp eval 'if (function_exists("opcache_reset")) { opcache_reset(); echo "opcache=ok\n"; }' || true
)

trap - ERR
MUTATION_STARTED=0

echo "DEPLOY_STAGING2_OK sha=$DEPLOY_SHA backup=$BACKUP_DIR"
