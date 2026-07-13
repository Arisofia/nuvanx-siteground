#!/usr/bin/env bash
set -Eeuo pipefail

WP_ROOT=""
BASE_URL=""
OUTPUT_DIR="./artifacts/audit-results"
CHECK_PATHS="wp-content/themes/nuvanx-editorial-medical-v2 wp-content/plugins/plugin wp-content/backups-nuvanx"

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
BASE_URL="${BASE_URL:-$(cd "$WP_ROOT" && wp option get home 2>/dev/null || true)}"

mkdir -p "$OUTPUT_DIR"
REPORT="$OUTPUT_DIR/site-validation.txt"

{
  echo "# Site validation"
  cd "$WP_ROOT"
  echo "== THEMES =="
  wp theme list --fields=name,status,version --format=table
  echo "== PLUGINS =="
  wp plugin list --fields=name,status,version --format=table
  echo "== RETIRED PATHS =="
  for path in $CHECK_PATHS; do
    [[ -e "$path" ]] && echo "ERROR: still exists $path" || echo "OK: removed $path"
  done
  if [[ -n "$BASE_URL" ]]; then
    echo "== LEGACY THEME MARKERS (public HTML) =="
    curl -sL "${BASE_URL%/}/?nocache=$(date +%s)" \
      | grep -iE 'data-nvxe-header|nvxe-topbar|nvxe-main|nvxe-footer' || echo "CLEAN"
  fi
} > "$REPORT"

echo "Report written to $REPORT"