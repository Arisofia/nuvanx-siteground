#!/usr/bin/env bash
set -Eeuo pipefail

URLS_FILE=""
OUTPUT_DIR="./artifacts/audit-results"
CACHE_BUST_PARAM="nocache"
BASE_URL=""
FAIL_ON_NON_200=1

usage() {
  cat <<'EOF' >&2
Usage: comprobacion-http.sh --urls-file PATH [options]

Options:
  --urls-file PATH       Required. One URL per line.
  --output-dir PATH      Report directory (default: ./artifacts/audit-results)
  --base-url URL         Prefix for relative paths in urls file
  --cache-bust-param KEY Query param for cache bust (default: nocache; empty to disable)
  --allow-non-200        Do not exit non-zero when any URL is not HTTP 200
EOF
  exit 2
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --urls-file) URLS_FILE="$2"; shift 2 ;;
    --output-dir) OUTPUT_DIR="$2"; shift 2 ;;
    --base-url) BASE_URL="$2"; shift 2 ;;
    --cache-bust-param) CACHE_BUST_PARAM="$2"; shift 2 ;;
    --allow-non-200) FAIL_ON_NON_200=0; shift ;;
    -h|--help) usage ;;
    *) echo "Unknown arg: $1" >&2; usage ;;
  esac
done

[[ -n "$URLS_FILE" && -f "$URLS_FILE" ]] || { echo "Missing --urls-file" >&2; exit 2; }

mkdir -p "$OUTPUT_DIR"
REPORT="$OUTPUT_DIR/http-status-report.txt"
ERRORS=0
CHECKED=0

resolve_url() {
  local raw="$1"
  if [[ "$raw" =~ ^https?:// ]]; then
    printf '%s' "$raw"
  elif [[ -n "$BASE_URL" ]]; then
    printf '%s/%s' "${BASE_URL%/}" "${raw#/}"
  else
    echo "Relative URL without --base-url: $raw" >&2
    return 1
  fi
}

append_cache_bust() {
  local url="$1"
  [[ -n "$CACHE_BUST_PARAM" ]] || { printf '%s' "$url"; return; }
  if [[ "$url" == *\?* ]]; then
    printf '%s&%s=%s' "$url" "$CACHE_BUST_PARAM" "$(date +%s)"
  else
    printf '%s?%s=%s' "$url" "$CACHE_BUST_PARAM" "$(date +%s)"
  fi
}

{
  echo "# HTTP status audit"
  echo "# generated=$(date -u +%Y-%m-%dT%H:%M:%SZ)"
  echo "# urls_file=$URLS_FILE"
  echo
  while IFS= read -r line || [[ -n "$line" ]]; do
    line="${line%%#*}"
    line="$(echo "$line" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"
    [[ -n "$line" ]] || continue
    url="$(resolve_url "$line")" || continue
    final_url="$(append_cache_bust "$url")"
    status="$(curl -Ls -o /dev/null -w '%{http_code}' "$final_url" || echo "000")"
    printf '%s\t%s\n' "$status" "$url"
    CHECKED=$((CHECKED + 1))
    [[ "$status" == "200" ]] || ERRORS=$((ERRORS + 1))
  done < "$URLS_FILE"
  echo
  echo "checked=$CHECKED errors=$ERRORS"
} | tee "$REPORT"

echo "Report written to $REPORT"
[[ "$FAIL_ON_NON_200" -eq 1 && "$ERRORS" -gt 0 ]] && exit 1