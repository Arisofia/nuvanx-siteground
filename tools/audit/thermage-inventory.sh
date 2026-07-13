#!/usr/bin/env bash
set -Eeuo pipefail

WP_ROOT=""
ENV_LABEL="ENV"
OUTPUT_DIR="./artifacts/audit-results"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --wp-root) WP_ROOT="$2"; shift 2 ;;
    --env-label) ENV_LABEL="$2"; shift 2 ;;
    --output-dir) OUTPUT_DIR="$2"; shift 2 ;;
    *) echo "Unknown arg: $1" >&2; exit 2 ;;
  esac
done

[[ -n "$WP_ROOT" && -d "$WP_ROOT" ]] || { echo "Requires --wp-root" >&2; exit 2; }
command -v wp >/dev/null 2>&1 || { echo "wp-cli required" >&2; exit 2; }

mkdir -p "$OUTPUT_DIR"
REPORT="$OUTPUT_DIR/thermage-inventory-${ENV_LABEL}.txt"

{
  echo "# Thermage inventory — $ENV_LABEL"
  echo "# wp_root=$WP_ROOT"
  cd "$WP_ROOT"
  echo "== POSTS =="
  wp db query "SELECT ID, post_title, post_status, post_type, post_name FROM wp_posts
    WHERE post_content LIKE '%Thermage%' OR post_title LIKE '%Thermage%' OR post_name LIKE '%thermage%';"
  echo "== MENUS =="
  wp menu list 2>/dev/null || true
  echo "== THERMAGE LINKS =="
  wp db query "SELECT ID, post_title FROM wp_posts
    WHERE post_content LIKE '%thermage-flx-radiofrecuencia-monopolar-madrid%';"
} > "$REPORT"

echo "Report written to $REPORT"