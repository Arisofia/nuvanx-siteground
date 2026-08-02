#!/usr/bin/env bash
# MUTATING: NUVANX no longer ships required form/attribution MU plugins.
# Form mount, contact SEO safeguards, Meta Pixel front-end disable, and Google
# attribution markers live in the active theme (nuvanx-medical).
#
# This script remains as a no-op guard so older workflows do not fail: it only
# verifies the WordPress root identity and removes retired MU plugin basenames
# if they are still present on disk.
set -Eeuo pipefail

ENVIRONMENT=''
WP_ROOT=''
SOURCE_MU=''
CONFIRM=0

RETIRED_MU_PLUGINS=(
  'nuvanx-valoracion-native-hubspot-form.php'
  'nuvanx-contacto-hubspot-form.php'
  'nvx-disable-public-facebook-pixel.php'
  'nuvanx-google-attribution.php'
)

usage() {
  cat >&2 <<'EOF'
Usage:
  deploy-required-mu-plugins.sh \
    --environment staging2|production \
    --wp-root /absolute/wordpress/root \
    --source-mu /absolute/source/mu-plugins \
    --confirm

Note: source-mu is accepted for workflow compatibility but is no longer copied.
Theme owns form/SEO/pixel/attribution behavior.
EOF
}

fail() {
  echo "ERROR: $*" >&2
  exit 1
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

(
  cd "$WP_ROOT"
  [[ "$(wp option get siteurl)" == "$EXPECTED_URL" ]] || fail 'unexpected siteurl'
  [[ "$(wp option get home)" == "$EXPECTED_URL" ]] || fail 'unexpected home URL'
  [[ "$(wp theme list --status=active --field=name)" == 'nuvanx-medical' ]] || fail 'unexpected active theme'
)

echo "== Retire absorbed MU plugins on $ENVIRONMENT =="
for plugin in "${RETIRED_MU_PLUGINS[@]}"; do
  target="$WP_ROOT/wp-content/mu-plugins/$plugin"
  if [[ -f "$target" ]]; then
    rm -f "$target"
    echo "removed $plugin"
  else
    echo "absent $plugin"
  fi
done
rm -rf "$WP_ROOT/wp-content/mu-plugins/nuvanx-google-attribution"

(
  cd "$WP_ROOT"
  wp cache flush || true
)

echo "MU_RETIRE_OK environment=$ENVIRONMENT (theme owns former MU responsibilities)"
