#!/usr/bin/env bash
set -Eeuo pipefail

WP_ROOT=""
OUTPUT_DIR="./artifacts/audit-results"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --wp-root) WP_ROOT="$2"; shift 2 ;;
    --output-dir) OUTPUT_DIR="$2"; shift 2 ;;
    *) echo "Unknown arg: $1"; exit 2 ;;
  esac
done

if [[ -z "$WP_ROOT" ]]; then
  echo "Usage: $0 --wp-root /path/to/wordpress --output-dir ./artifacts/audit-results" >&2
  exit 2
fi

mkdir -p "$OUTPUT_DIR"

REPORT="$OUTPUT_DIR/theme-audit.txt"
{
  echo "WP_ROOT=$WP_ROOT"
  echo "== PHP files =="
  find "$WP_ROOT/wp-content/themes/nuvanx-medical" -type f -name '*.php' | wc -l
  echo "== CSS important markers =="
  grep -RIn '!important' "$WP_ROOT/wp-content/themes/nuvanx-medical/assets/css" 2>/dev/null || true
} > "$REPORT"

echo "Audit written to $REPORT"
