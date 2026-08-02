#!/usr/bin/env bash
# MUTATING: guarded clean-up of retired MU plugins.
# NUVANX no longer ships required form/attribution MU plugins; all form mounts,
# contact SEO safeguards, Meta Pixel front-end disable, and Google attribution
# markers live in the active theme (nuvanx-medical).
#
# This script is a compatibility guard for existing workflows:
# - verifies WordPress root identity, URL and active theme
# - removes retired MU plugin basenames if they are still present on disk
# - never copies new MU plugins or changes ownership of responsibilities
set -Eeuo pipefail

ENVIRONMENT=''
WP_ROOT=''
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
    --confirm

Note: This script only validates the WordPress root and removes retired MU plugin
files when present. The active theme (nuvanx-medical) owns form, SEO, pixel and
attribution behavior.
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
    --source-mu) shift 2 ;; # legacy flag accepted but ignored
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
