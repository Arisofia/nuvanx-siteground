#!/usr/bin/env bash
set -Eeuo pipefail

WP_ROOT=""
OUTPUT_DIR="./artifacts/audit-results"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --wp-root) WP_ROOT="$2"; shift 2 ;;
    --output-dir) OUTPUT_DIR="$2"; shift 2 ;;
    *) echo "Unknown arg: $1" >&2; exit 2 ;;
  esac
done

[[ -n "$WP_ROOT" && -d "$WP_ROOT" ]] || { echo "Requires --wp-root" >&2; exit 2; }
command -v wp >/dev/null 2>&1 || { echo "wp-cli required" >&2; exit 2; }

mkdir -p "$OUTPUT_DIR"
REPORT="$OUTPUT_DIR/check-staging.txt"

{
  echo "# Staging environment check"
  cd "$WP_ROOT"
  echo "pwd=$(pwd)"
  echo "wp_version=$(wp core version)"
  echo "siteurl=$(wp option get siteurl)"
  echo "home=$(wp option get home)"
  echo "blog_public=$(wp option get blog_public)"
  echo "db_name=$(wp config get DB_NAME)"
  echo "table_prefix=$(wp config get table_prefix)"
} > "$REPORT"

echo "Report written to $REPORT"