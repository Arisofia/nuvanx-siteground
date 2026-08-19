#!/usr/bin/env bash
# MUTATING: deploy a checked-out nuvanx-medical theme snapshot to staging2 only.
# Intended for the protected manual GitHub Actions workflow or an authorized
# SiteGround operator. Never accepts a production root.
set -Eeuo pipefail

EXPECTED_ROOT='/home/customer/www/staging2.nuvanx.com/public_html'
EXPECTED_URL='https://staging2.nuvanx.com'
PROD_ROOT='/home/customer/www/nuvanx.com/public_html'
PROD_URL='https://nuvanx.com'
THEME_REL='wp-content/themes/nuvanx-medical'
WP_ROOT=''
SOURCE_THEME=''
DEPLOY_SHA=''
CONFIRM=0
BACKUP_DIR=''
MUTATION_STARTED=0
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

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

provision_staging_hubspot_runtime_credential() {
  local secret_file
  local prod_hash
  local staging_hash

  secret_file="$(mktemp)" || fail 'mktemp failed: unable to create temporary file for credential provisioning'
  chmod 600 "$secret_file"

  if ! (
    cd "$PROD_ROOT"
    wp eval 'if (!defined("NVX_HUBSPOT_ACCESS_TOKEN") || !is_string(NVX_HUBSPOT_ACCESS_TOKEN) || strlen(NVX_HUBSPOT_ACCESS_TOKEN) < 20) { exit(3); } echo NVX_HUBSPOT_ACCESS_TOKEN;' > "$secret_file"
  ); then
    rm -f "$secret_file"
    fail 'production HubSpot runtime credential is unavailable; Staging2 QA cannot be provisioned'
  fi
  [[ -s "$secret_file" ]] || { rm -f "$secret_file"; fail 'production HubSpot runtime credential resolved empty'; }

  # Use extracted PHP script for credential provisioning
  local php_script="$SCRIPT_DIR/provision-hubspot-credential.php"
  [[ -f "$php_script" ]] || { rm -f "$secret_file"; fail 'HubSpot credential provisioning script not found'; }

  if ! PROD_SECRET_FILE="$secret_file" STAGING_CONFIG="$WP_ROOT/wp-config.php" php "$php_script"; then
    local php_exit_code=$?
    rm -f "$secret_file"
    case "$php_exit_code" in
      2) fail 'Staging HubSpot credential provisioning prerequisites failed (secret too short or config missing)' ;;
      3) fail 'Staging wp-config insertion anchor not found (tried: existing define, stop editing comment, wp-settings.php require)' ;;
      4) fail 'Staging wp-config credential update failed' ;;
      5) fail 'Staging wp-config atomic credential update failed' ;;
      *) fail "Staging HubSpot credential provisioning failed with exit code $php_exit_code" ;;
    esac
  fi

  php -l "$WP_ROOT/wp-config.php" >/dev/null || { rm -f "$secret_file"; fail 'Staging2 wp-config failed syntax validation after credential provisioning'; }
  prod_hash="$(sha256sum "$secret_file" | awk '{print $1}')"
  staging_hash="$(cd "$WP_ROOT" && wp eval 'if (!defined("NVX_HUBSPOT_ACCESS_TOKEN") || !is_string(NVX_HUBSPOT_ACCESS_TOKEN)) { exit(3); } echo hash("sha256", NVX_HUBSPOT_ACCESS_TOKEN);')"
  staging_status=$?
  rm -f "$secret_file"

  if [[ "$staging_status" -eq 3 || -z "$staging_hash" ]]; then
    fail 'Staging2 HubSpot runtime credential check failed: NVX_HUBSPOT_ACCESS_TOKEN missing or invalid in Staging2 wp-config'
  elif [[ "$staging_status" -ne 0 ]]; then
    fail 'Staging2 HubSpot runtime credential check failed: wp eval error'
  fi

  [[ -n "$prod_hash" && "$staging_hash" == "$prod_hash" ]] || fail 'Staging2 HubSpot runtime credential parity check failed'
  echo 'STAGING_HUBSPOT_CREDENTIAL=PASS source=production-readonly value_exposed=0'
}

purge_siteground_cache_if_available() {
  local plugin_slug='sg-cachepress'
  local was_active=0
  local purge_ok=0

  if wp plugin is-active "$plugin_slug" >/dev/null 2>&1; then
    was_active=1
    wp sg purge
    echo 'siteground_wp_cli_purge=PASS mode=already-active'
    return 0
  fi

  if ! wp plugin is-installed "$plugin_slug" >/dev/null 2>&1; then
    echo 'ERROR: SiteGround Speed Optimizer is not installed; cannot purge Dynamic Cache reproducibly.' >&2
    return 1
  fi

  # Loading the inactive plugin with --require exposes the command but does not
  # invalidate the host Dynamic Cache. SiteGround only performs the real purge
  # while Speed Optimizer is active. Activate it only for the purge, then return
  # staging to its original inactive state.
  wp plugin activate "$plugin_slug" --quiet
  if ! wp plugin is-active "$plugin_slug" >/dev/null 2>&1; then
    echo 'ERROR: Speed Optimizer did not become active for the transient purge.' >&2
    return 1
  fi

  if wp sg purge; then
    purge_ok=1
  fi

  wp plugin deactivate "$plugin_slug" --quiet || {
    echo 'ERROR: failed to restore Speed Optimizer to its original inactive state.' >&2
    return 1
  }

  if wp plugin is-active "$plugin_slug" >/dev/null 2>&1; then
    echo 'ERROR: Speed Optimizer remained active after transient purge.' >&2
    return 1
  fi

  [[ "$purge_ok" -eq 1 ]] || {
    echo 'ERROR: SiteGround Dynamic Cache purge command failed.' >&2
    return 1
  }

  [[ "$was_active" -eq 0 ]] || return 0
  echo 'siteground_wp_cli_purge=PASS mode=transient-activation-restored-inactive'
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
[[ "$WP_ROOT" != "$PROD_ROOT" ]] || fail 'staging root must never equal production root'
[[ "$DEPLOY_SHA" =~ ^[0-9a-f]{40}$ ]] || fail 'SHA must contain 40 lowercase hexadecimal characters'
[[ -n "$SOURCE_THEME" ]] || fail 'source theme path is required'
[[ "$SOURCE_THEME" == "$WP_ROOT"/wp-content/.nuvanx-deployments/*/theme ]] || fail 'source theme must be inside the staging2 deployment area'

for command_name in wp rsync tar php find mktemp sha256sum awk; do
  command -v "$command_name" >/dev/null 2>&1 || fail "required command is unavailable: $command_name"
done

[[ -d "$WP_ROOT" ]] || fail "WordPress root does not exist: $WP_ROOT"
[[ -f "$WP_ROOT/wp-config.php" ]] || fail 'wp-config.php not found in staging2 root'
[[ -d "$SOURCE_THEME" ]] || fail "source theme does not exist: $SOURCE_THEME"
[[ -f "$SCRIPT_DIR/tools/migrations/ensure-governed-blog-parity.php" ]] || fail 'governed blog parity migration is missing from immutable release tooling'

SOURCE_REQUIRED_FILES=(
  style.css
  functions.php
  header.php
  assets/css/nvx-fonts.css
  assets/css/nvx-tokens.css
  assets/css/nvx-base.css
  assets/css/nvx-site-layout.css
  assets/css/nvx-components.css
  assets/css/nvx-patterns-editorial.css
  assets/css/nvx-header.css
  assets/css/nvx-footer.css
  assets/css/nvx-accessibility-governance.css
  assets/js/nvx-runtime-governance.js
  assets/css/nvx-soluciones-medicas.css
  template-parts/content/nvx-soluciones-medicas.php
  templates/page-soluciones-medicas.php
  inc/nvx-blog-system.php
  inc/nvx-document-governance.php
  inc/nvx-page-hygiene.php
  inc/nvx-solutions-page.php
  inc/nvx-clinics-hub.php
  inc/nvx-valoracion-modal.php
)

for required_file in "${SOURCE_REQUIRED_FILES[@]}"; do
  [[ -f "$SOURCE_THEME/$required_file" ]] || fail "source theme is missing $required_file"
done

LIVE_THEME="$WP_ROOT/$THEME_REL"
[[ -d "$LIVE_THEME" ]] || fail "live staging2 theme does not exist: $LIVE_THEME"

rollback() {
  local exit_code=$?
  trap - ERR
  if [[ "$MUTATION_STARTED" -eq 1 && -n "$BACKUP_DIR" && -f "$BACKUP_DIR/theme.tgz" ]]; then
    echo 'SAFETY_RESTORE: restoring the pre-deploy staging2 theme after rejected deployment' >&2
    rm -rf "$LIVE_THEME"
    tar -xzf "$BACKUP_DIR/theme.tgz" -C "$WP_ROOT"
    (
      cd "$WP_ROOT"
      wp cache flush || true
      purge_siteground_cache_if_available || true
    )
    echo "SAFETY_RESTORE_COMPLETE backup=$BACKUP_DIR" >&2
  fi
  exit "$exit_code"
}
trap rollback ERR

echo '== Guard staging2 identity =='
(
  cd "$WP_ROOT"
  siteurl="$(wp option get siteurl)"
  home="$(wp option get home)"
  blog_public="$(wp option get blog_public)"
  theme="$(wp theme list --status=active --field=name)"
  nvx_env="$(wp eval 'echo defined("NVX_ENV") ? NVX_ENV : "";')"
  wp_environment="$(wp eval 'echo function_exists("wp_get_environment_type") ? wp_get_environment_type() : "";')"

  echo "siteurl=$siteurl home=$home active_theme=$theme blog_public=$blog_public nvx_env=$nvx_env wp_environment=$wp_environment"

  [[ "$siteurl" == "$EXPECTED_URL" ]] || fail "unexpected siteurl: $siteurl"
  [[ "$home" == "$EXPECTED_URL" ]] || fail "unexpected home URL: $home"
  [[ "$theme" == 'nuvanx-medical' ]] || fail "unexpected active theme: $theme"
  [[ "$blog_public" == '0' ]] || fail "staging2 must have blog_public=0; got: $blog_public"
  [[ "$nvx_env" == 'staging' ]] || fail "staging2 must define NVX_ENV=staging; got: ${nvx_env:-undefined}"
  [[ "$wp_environment" == 'staging' ]] || fail "staging2 must report WP environment type staging; got: ${wp_environment:-undefined}"
)

echo '== Guard production read-only source identity =='
(
  cd "$PROD_ROOT"
  [[ "$(wp config get DB_NAME)" == 'db0ecrycwv2tgb' ]] || fail 'unexpected production DB identity while sourcing governed post'
  [[ "$(wp option get home)" == "$PROD_URL" ]] || fail 'unexpected production home while sourcing governed post'
  [[ "$(wp option get siteurl)" == "$PROD_URL" ]] || fail 'unexpected production siteurl while sourcing governed post'
  [[ "$(wp option get blog_public)" == '1' ]] || fail 'production source must remain public'
  [[ "$(wp theme list --status=active --field=name)" == 'nuvanx-medical' ]] || fail 'unexpected production active theme'
)

echo '== Provision Staging2 HubSpot runtime credential from production read-only source =='
provision_staging_hubspot_runtime_credential

echo '== Validate source PHP =='
PHP_LINT_LOG="$(mktemp)"
if ! find "$SOURCE_THEME" -type f -name '*.php' -print0 | xargs -0 -n1 php -l >"$PHP_LINT_LOG" 2>&1; then
  cat "$PHP_LINT_LOG" >&2
  rm -f "$PHP_LINT_LOG"
  fail 'source theme PHP lint failed'
fi
php -l "$SCRIPT_DIR/tools/migrations/ensure-governed-blog-parity.php" >/dev/null
rm -f "$PHP_LINT_LOG"

DATE="$(date +%Y%m%d-%H%M%S)"
SHORT_SHA="${DEPLOY_SHA:0:12}"
BACKUP_DIR="$WP_ROOT/wp-content/backups-nuvanx/pre-staging2-${DATE}-${SHORT_SHA}"

echo "== Backup staging2 theme to $BACKUP_DIR =="
mkdir -p "$BACKUP_DIR"
tar -czf "$BACKUP_DIR/theme.tgz" -C "$WP_ROOT" "$THEME_REL"
printf '%s\n' "$DEPLOY_SHA" > "$BACKUP_DIR/intended-sha.txt"

MUTATION_STARTED=1

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

find "$LIVE_THEME/assets/css" -maxdepth 1 -type f -name 'nvx-*.min.css' -delete
printf '%s\n' "$DEPLOY_SHA" > "$LIVE_THEME/.nvx-deploy-sha"

echo '== Verify deployed files and marker =='
for required_file in "${SOURCE_REQUIRED_FILES[@]}"; do
  [[ -f "$LIVE_THEME/$required_file" ]] || fail "deployed theme is missing $required_file"
done
[[ "$(tr -d '\r\n' < "$LIVE_THEME/.nvx-deploy-sha")" == "$DEPLOY_SHA" ]] || fail 'deployed SHA marker does not match'
grep -Fq 'nvx-patterns-editorial.css' "$LIVE_THEME/functions.php" || fail 'functions.php does not enqueue the canonical editorial stylesheet'
grep -Fq 'nvx-document-governance.php' "$LIVE_THEME/functions.php" || fail 'functions.php does not load document governance'
grep -Fq 'nvx_document_governance_print_head_contract' "$LIVE_THEME/inc/nvx-document-governance.php" || fail 'document governance missing head contract emitter'
grep -Fq 'window.nvxValoracionModal' "$LIVE_THEME/inc/nvx-valoracion-modal.php" || fail 'valoracion modal boot config is missing'

echo '== Synchronize governed matrix post identity from production read-only source =='
PROD_POST_JSON="$(mktemp)"
trap 'rm -f "$PROD_POST_JSON"' RETURN
(
  cd "$PROD_ROOT"
  wp post get 3334 --format=json > "$PROD_POST_JSON"
)
[[ -s "$PROD_POST_JSON" ]] || fail 'production governed post export is empty'
(
  cd "$WP_ROOT"
  PRODUCTION_POST_JSON_FILE="$PROD_POST_JSON" wp eval-file "$SCRIPT_DIR/tools/migrations/ensure-governed-blog-parity.php" --allow-root
)
rm -f "$PROD_POST_JSON"
trap - RETURN

echo '== Purge staging2 caches =='
(
  cd "$WP_ROOT"
  wp cache flush
  purge_siteground_cache_if_available
  rm -rf wp-content/uploads/siteground-optimizer-assets/siteground-optimizer-combined-*
  rm -rf wp-content/cache/sgo-cache/*
  rm -rf wp-content/cache/*
  wp eval 'if (function_exists("opcache_reset")) { opcache_reset(); echo "opcache=ok\n"; }'
)

trap - ERR
MUTATION_STARTED=0

echo "DEPLOY_STAGING2_OK sha=$DEPLOY_SHA backup=$BACKUP_DIR"
