#!/usr/bin/env bash
set -Eeuo pipefail

WP_ROOT=""
BASE_URL=""
OUTPUT_DIR="./artifacts/audit-results"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --wp-root) WP_ROOT="$2"; shift 2 ;;
    --base-url) BASE_URL="$2"; shift 2 ;;
    --output-dir) OUTPUT_DIR="$2"; shift 2 ;;
    *) echo "Unknown arg: $1" >&2; exit 2 ;;
  esac
done

[[ -n "$WP_ROOT" && -d "$WP_ROOT" ]] || { echo "Requires --wp-root" >&2; exit 2; }
command -v wp >/dev/null 2>&1 || { echo "wp-cli required" >&2; exit 2; }

mkdir -p "$OUTPUT_DIR"
REPORT="$OUTPUT_DIR/validate-yoast-db.txt"
BASE_URL="${BASE_URL:-$(cd "$WP_ROOT" && wp option get home 2>/dev/null || true)}"

{
  echo "# Yoast DB audit"
  echo "# wp_root=$WP_ROOT base_url=$BASE_URL"
  echo "== POSTMETA =="
  cd "$WP_ROOT" && wp db query "
SELECT post_id, meta_key, LEFT(meta_value,120) AS meta_value
FROM wp_postmeta
WHERE meta_key LIKE '%yoast%'
  AND (meta_value LIKE '%Thermage%' OR meta_value LIKE '%thermage%');"
  echo "== INDEXABLE =="
  wp db query "
SELECT id, object_id, object_type, permalink, title
FROM wp_yoast_indexable
WHERE title LIKE '%Thermage%' OR description LIKE '%Thermage%'
   OR permalink LIKE '%thermage%';"
  if [[ -n "$BASE_URL" ]]; then
    for path in medicina-estetica-laser madrid/valoracion; do
      url="${BASE_URL%/}/$path"
      echo "== CURL $url =="
      curl -sL -H "Cache-Control: no-cache" "${url}?nocache=$(date +%s)" \
        | grep -Ei 'Thermage|thermage|nvx-thermage|1594' || echo "CLEAN"
    done
  fi
} > "$REPORT"

echo "Report written to $REPORT"