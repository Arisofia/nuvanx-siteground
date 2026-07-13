#!/usr/bin/env bash
set -Eeuo pipefail

BASE_URL=""
OUTPUT_DIR="./artifacts/audit-results"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --base-url) BASE_URL="$2"; shift 2 ;;
    --output-dir) OUTPUT_DIR="$2"; shift 2 ;;
    *) echo "Unknown arg: $1" >&2; exit 2 ;;
  esac
done

[[ -n "$BASE_URL" ]] || { echo "Requires --base-url" >&2; exit 2; }

mkdir -p "$OUTPUT_DIR"
REPORT="$OUTPUT_DIR/validate-public-html.txt"
URL="${BASE_URL%/}/?nocache=$(date +%s)"

{
  echo "# Public HTML validation"
  echo "# base_url=$BASE_URL"
  echo "legacy_marker_lines=$(curl -sL -H 'Cache-Control: no-cache' "$URL" | grep -Eci 'Thermage|nvx-phase3c|et_pb_|zzzz' || true)"
  echo "video_marker_lines=$(curl -sL -H 'Cache-Control: no-cache' "$URL" | grep -Eci 'nvx-home-video-feature|<video|nvx-home-hero-video' || true)"
} > "$REPORT"

echo "Report written to $REPORT"