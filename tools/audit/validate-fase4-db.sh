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
REPORT="$OUTPUT_DIR/validate-fase4-${ENV_LABEL}.txt"

{
  echo "# Fase 4 DB validation — $ENV_LABEL"
  cd "$WP_ROOT"
  wp db query "SELECT ID, post_title FROM wp_posts WHERE post_status='publish'
    AND (post_content LIKE '%NVX\_%' OR post_content LIKE '%et_pb_%'
      OR post_content LIKE '%tmp-%' OR post_content LIKE '%brand-manual%'
      OR post_content LIKE '%zzzz%' OR post_content LIKE '%Thermage%'
      OR post_content LIKE '%nvx-phase3c%');"
} > "$REPORT"

echo "Report written to $REPORT"