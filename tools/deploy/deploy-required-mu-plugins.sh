#!/usr/bin/env bash
# MUTATING: deploy only the two canonical NUVANX form MU plugins.
# Supports staging2 and production with strict root/URL guards, backups and
# automatic rollback. Never deletes unrelated MU plugins.
set -Eeuo pipefail

ENVIRONMENT=''
WP_ROOT=''
SOURCE_MU=''
CONFIRM=0
BACKUP_DIR=''
MUTATION_STARTED=0

REQUIRED_MU_PLUGINS=(
  'nuvanx-valoracion-native-hubspot-form.php'
  'nuvanx-contacto-hubspot-form.php'
)

usage() {
  cat >&2 <<'EOF'
Usage:
  deploy-required-mu-plugins.sh \
    --environment staging2|production \
    --wp-root /absolute/wordpress/root \
    --source-mu /absolute/source/mu-plugins \
    --confirm
EOF
}

fail() {
  echo "ERROR: $*" >&2
  return 1
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --environment) ENVIRONMENT="${2:-}"; shift 2 ;;
    --wp-root) WP_ROOT="${2:-}"; shift 2 ;;
    --source-mu) SOURCE_MU="${2:-}"; shift 2 ;;
    --confirm) CONFIRM=1; shift ;;
    *) usage; fail "unknown argument: $1" ;;
  esac
done

[[ "$CONFIRM" -eq 1 || "${NUVANX_CONFIRM:-}" == 'yes' ]] || fail 'explicit confirmation is required'
[[ -n "$SOURCE_MU" ]] || fail 'source MU plugin path is required'

case "$ENVIRONMENT" in
  staging2)
    EXPECTED_ROOT='/home/customer/www/staging2.nuvanx.com/public_html'
    EXPECTED_URL='https://staging2.nuvanx.com'
    ;;
  production)
    EXPECTED_ROOT='/home/customer/www/nuvanx.com/public_html'
    EXPECTED_URL='https://nuvanx.com'
    ;;
  *) fail 'environment must be staging2 or production' ;;
esac

[[ "$WP_ROOT" == "$EXPECTED_ROOT" ]] || fail "refusing unexpected WordPress root: $WP_ROOT"
[[ -d "$WP_ROOT" && -f "$WP_ROOT/wp-config.php" ]] || fail 'invalid WordPress root'
[[ -d "$SOURCE_MU" ]] || fail "source MU plugin directory does not exist: $SOURCE_MU"

for command_name in wp php rsync cmp cp; do
  command -v "$command_name" >/dev/null 2>&1 || fail "required command is unavailable: $command_name"
done

for plugin in "${REQUIRED_MU_PLUGINS[@]}"; do
  [[ -f "$SOURCE_MU/$plugin" ]] || fail "source MU plugin is missing: $plugin"
  php -l "$SOURCE_MU/$plugin" >/dev/null || fail "source MU plugin PHP lint failed: $plugin"
done

(
  cd "$WP_ROOT"
  [[ "$(wp option get siteurl)" == "$EXPECTED_URL" ]] || fail 'unexpected siteurl'
  [[ "$(wp option get home)" == "$EXPECTED_URL" ]] || fail 'unexpected home URL'
  [[ "$(wp theme list --status=active --field=name)" == 'nuvanx-medical' ]] || fail 'unexpected active theme'
)

DATE="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="$WP_ROOT/wp-content/backups-nuvanx/pre-mu-${ENVIRONMENT}-${DATE}"
TARGET_MU="$WP_ROOT/wp-content/mu-plugins"
mkdir -p "$BACKUP_DIR/existing" "$TARGET_MU"

for plugin in "${REQUIRED_MU_PLUGINS[@]}"; do
  if [[ -f "$TARGET_MU/$plugin" ]]; then
    cp -p "$TARGET_MU/$plugin" "$BACKUP_DIR/existing/$plugin"
  else
    : > "$BACKUP_DIR/$plugin.was-missing"
  fi
done

rollback() {
  local exit_code=$?
  trap - ERR
  if [[ "$MUTATION_STARTED" -eq 1 ]]; then
    echo "ROLLBACK: restoring MU plugins from $BACKUP_DIR" >&2
    for plugin in "${REQUIRED_MU_PLUGINS[@]}"; do
      if [[ -f "$BACKUP_DIR/existing/$plugin" ]]; then
        cp -p "$BACKUP_DIR/existing/$plugin" "$TARGET_MU/$plugin"
      elif [[ -f "$BACKUP_DIR/$plugin.was-missing" ]]; then
        rm -f "$TARGET_MU/$plugin"
      fi
    done
    (
      cd "$WP_ROOT"
      wp cache flush || true
      wp sg purge || true
    )
    echo "ROLLBACK_MU_COMPLETE backup=$BACKUP_DIR" >&2
  fi
  exit "$exit_code"
}
trap rollback ERR

MUTATION_STARTED=1
for plugin in "${REQUIRED_MU_PLUGINS[@]}"; do
  rsync -a "$SOURCE_MU/$plugin" "$TARGET_MU/$plugin"
  php -l "$TARGET_MU/$plugin" >/dev/null
  cmp -s "$SOURCE_MU/$plugin" "$TARGET_MU/$plugin" || fail "deployed MU plugin differs from source: $plugin"
done

(
  cd "$WP_ROOT"
  wp cache flush || true
  wp sg purge || true
  wp eval 'if (function_exists("opcache_reset")) { opcache_reset(); echo "opcache=ok\n"; }' || true
)

trap - ERR
MUTATION_STARTED=0

echo "DEPLOY_MU_PLUGINS_OK environment=$ENVIRONMENT backup=$BACKUP_DIR"
