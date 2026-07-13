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
REPORT="$OUTPUT_DIR/filesystem-inventory-${ENV_LABEL}.txt"

queries=(
  "SELECT ID, post_title FROM wp_posts WHERE post_status='publish' AND post_content LIKE '%et_pb_%';"
  "SELECT ID, post_title FROM wp_posts WHERE post_status='publish' AND post_content LIKE '%tmp-%';"
  "SELECT ID, post_title FROM wp_posts WHERE post_status='publish' AND post_content LIKE '%brand-manual%';"
  "SELECT ID, post_title FROM wp_posts WHERE post_status='publish' AND post_content LIKE '%zzzz%';"
  "SELECT ID, post_title FROM wp_posts WHERE post_status='publish' AND post_content LIKE '%NVX_PHASE%';"
)

{
  echo "# Filesystem inventory — $ENV_LABEL"
  cd "$WP_ROOT"
  echo "== PAGES =="
  wp post list --post_type=page --post_status=publish --fields=ID,post_title,post_name --format=table
  for q in "${queries[@]}"; do
    echo "== QUERY: $q =="
    wp db query "$q"
  done
  echo "== POSTMETA =="
  wp db query "SELECT post_id, meta_key FROM wp_postmeta
    WHERE meta_value LIKE '%et_pb_%' OR meta_value LIKE '%NVX_PHASE%' OR meta_value LIKE '%brand-manual%';"
} > "$REPORT"

echo "Report written to $REPORT"