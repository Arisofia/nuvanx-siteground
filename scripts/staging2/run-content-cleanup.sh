#!/usr/bin/env bash
set -euo pipefail

MODE="${1:-audit}"
CONFIRMATION="${2:-}"
ROOT="${STAGING2_ROOT:-/home/customer/www/staging2.nuvanx.com/public_html}"
SCRIPT_SOURCE="${CLEANUP_SCRIPT_SOURCE:-$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/cleanup-content-navigation.php}"
STAGING_URL="https://staging2.nuvanx.com"
WP_SKIP=(--skip-plugins --skip-themes)

if [[ "$MODE" != "audit" && "$MODE" != "apply" ]]; then
  echo "ERROR: mode must be audit or apply" >&2
  exit 1
fi

if [[ "$MODE" == "apply" && "$CONFIRMATION" != "CONFIRM-STAGING2" ]]; then
  echo "ERROR: apply requires exact confirmation token CONFIRM-STAGING2" >&2
  exit 1
fi

[[ -d "$ROOT" ]] || { echo "ERROR: staging root not found: $ROOT" >&2; exit 1; }
[[ -s "$SCRIPT_SOURCE" ]] || { echo "ERROR: cleanup script not found: $SCRIPT_SOURCE" >&2; exit 1; }

cd "$ROOT"

# Precondition reads do not need plugins/themes loaded.
SITEURL="$(wp option get siteurl "${WP_SKIP[@]}")"
HOMEURL="$(wp option get home "${WP_SKIP[@]}")"
THEME="$(wp theme list --status=active --field=name)"
FRONT_PAGE="$(wp option get page_on_front "${WP_SKIP[@]}")"

[[ "$SITEURL" == "$STAGING_URL" ]] || {
  echo "ERROR: unexpected SITEURL: $SITEURL (expected $STAGING_URL)" >&2
  exit 1
}
[[ "$HOMEURL" == "$STAGING_URL" ]] || {
  echo "ERROR: unexpected HOMEURL: $HOMEURL (expected $STAGING_URL)" >&2
  exit 1
}
[[ "$THEME" == "nuvanx-medical" ]] || {
  echo "ERROR: unexpected active theme: $THEME (expected nuvanx-medical)" >&2
  exit 1
}
[[ "$FRONT_PAGE" == "9" ]] || {
  echo "ERROR: unexpected FRONT_PAGE: $FRONT_PAGE (expected 9)" >&2
  exit 1
}

php -l "$SCRIPT_SOURCE"

if [[ "$MODE" == "audit" ]]; then
  wp eval-file "$SCRIPT_SOURCE"
  echo "STAGING2_CONTENT_AUDIT_COMPLETE"
  exit 0
fi

NVX_CONTENT_CLEANUP_APPLY=1 wp eval-file "$SCRIPT_SOURCE"
wp cache flush || true
wp sg purge || true

echo "Running post-apply verification audit..."
wp eval-file "$SCRIPT_SOURCE"

HEALTH_URL="${STAGING_URL}/?content_cleanup=$(date +%s)"
# Follow redirects so a final 2xx success is accepted; capture last status.
HTTP_CODE="$(curl -sS -L -o /dev/null -w '%{http_code}' -H 'Cache-Control: no-cache' "$HEALTH_URL")"
case "$HTTP_CODE" in
  2??) ;;
  *)
    echo "ERROR: staging health check failed for $HEALTH_URL (HTTP $HTTP_CODE, expected 2xx)" >&2
    exit 1
    ;;
esac

# Match post-apply checklist markers (PHP also emits APPLIED after DB writes).
echo "STAGING2_CONTENT_CLEANUP_APPLIED"
echo "STAGING2_CONTENT_CLEANUP_COMPLETE"
echo "staging_http_status=$HTTP_CODE"
echo "production_modified=false"
