#!/usr/bin/env bash
set -euo pipefail

MODE="${1:-audit}"
CONFIRMATION="${2:-}"
ROOT="${STAGING2_ROOT:-/home/customer/www/staging2.nuvanx.com/public_html}"
SCRIPT_SOURCE="${CLEANUP_SCRIPT_SOURCE:-$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/cleanup-content-navigation.php}"
STAGING_URL="https://staging2.nuvanx.com"

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

SITEURL="$(wp option get siteurl)"
HOMEURL="$(wp option get home)"
THEME="$(wp theme list --status=active --field=name)"
FRONT_PAGE="$(wp option get page_on_front)"

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

wp eval-file "$SCRIPT_SOURCE" --apply
wp cache flush || true
wp sg purge || true

HEALTH_URL="${STAGING_URL}/?content_cleanup=$(date +%s)"
HTTP_CODE="$(curl -sS -o /dev/null -w '%{http_code}' -H 'Cache-Control: no-cache' "$HEALTH_URL")"
[[ "$HTTP_CODE" == "200" ]] || {
  echo "ERROR: staging health check failed for $HEALTH_URL (HTTP $HTTP_CODE, expected 200)" >&2
  exit 1
}

# Match post-apply checklist markers (PHP also emits APPLIED after DB writes).
echo "STAGING2_CONTENT_CLEANUP_APPLIED"
echo "STAGING2_CONTENT_CLEANUP_COMPLETE"
echo "staging_http_status=$HTTP_CODE"
echo "production_modified=false"
