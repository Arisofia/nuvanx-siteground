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

[[ -n "$WP_ROOT" && -d "$WP_ROOT/wp-content" ]] || { echo "Requires --wp-root" >&2; exit 2; }
command -v wp >/dev/null 2>&1 || { echo "wp-cli required" >&2; exit 2; }

mkdir -p "$OUTPUT_DIR"
REPORT="$OUTPUT_DIR/phase3-4-audit.txt"
THEME="$WP_ROOT/wp-content/themes/nuvanx-medical"
MU="$WP_ROOT/wp-content/mu-plugins"

{
  echo "# Phase 3-4 audit"
  cd "$WP_ROOT"
  echo "== ACTIVE THEME =="
  wp option get stylesheet
  wp theme list
  echo "== FRONT CONFIG =="
  wp option get show_on_front
  wp option get page_on_front
  echo "== MU-PLUGINS =="
  find "$MU" -maxdepth 2 \( -type f -o -type d \) | sort
  echo "== SUSPICIOUS THEME FILES =="
  find "$THEME" -maxdepth 4 \( -iname '*old*' -o -iname '*legacy*' -o -iname '*patch*' -o -iname '*.bak' -o -iname 'zzzz*' \) -print 2>/dev/null || true
  echo "== ACTIVE CODE RESIDUES =="
  grep -RInEi 'Thermage|thermage|1594|nvx-phase3c|et_pb_|brand-manual|zzzz' "$THEME" "$MU" \
    --exclude-dir='backups*' --exclude-dir='_archive*' 2>/dev/null || echo "OK"
  echo "== IMPORTANT =="
  grep -RIn '!important' "$THEME" "$MU" 2>/dev/null | grep -v screen-reader-text || echo "OK"
  echo "== WP POSTS LEGACY =="
  wp db query "SELECT ID, post_title, post_status, post_type FROM wp_posts
    WHERE post_content LIKE '%Thermage%' OR post_content LIKE '%et_pb_%'
       OR post_content LIKE '%nvx-phase3c%' OR post_content LIKE '%zzzz%';" || true
  echo "== YOAST INDEXABLE =="
  wp db query "SELECT id, object_id, permalink, title FROM wp_yoast_indexable
    WHERE title LIKE '%Thermage%' OR permalink LIKE '%thermage%';" || true
} > "$REPORT"

echo "Report written to $REPORT"