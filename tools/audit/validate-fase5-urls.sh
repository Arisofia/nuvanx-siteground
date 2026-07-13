#!/usr/bin/env bash
set -Eeuo pipefail

BASE_URL=""
URLS_FILE=""
OUTPUT_DIR="./artifacts/audit-results"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --base-url) BASE_URL="$2"; shift 2 ;;
    --urls-file) URLS_FILE="$2"; shift 2 ;;
    --output-dir) OUTPUT_DIR="$2"; shift 2 ;;
    *) echo "Unknown arg: $1" >&2; exit 2 ;;
  esac
done

mkdir -p "$OUTPUT_DIR"
REPORT="$OUTPUT_DIR/validate-fase5-urls.txt"

{
  echo "# Fase 5 public URL validation"
  if [[ -n "$URLS_FILE" && -f "$URLS_FILE" ]]; then
    while IFS= read -r path || [[ -n "$path" ]]; do
      [[ -n "$path" ]] || continue
      if [[ "$path" =~ ^https?:// ]]; then url="$path"; else url="${BASE_URL%/}/${path#/}"; fi
      echo "== $url =="
      curl -sL "${url}?nocache=$(date +%s)" | grep -Ei "Thermage|NVX_|et_pb_|tmp-|brand-manual|zzzz|nvx-phase3c" || echo "CLEAN"
    done < "$URLS_FILE"
  elif [[ -n "$BASE_URL" ]]; then
    for path in / /medicina-estetica-laser/ /madrid/valoracion/ /equipo-medico/ /contacto/; do
      url="${BASE_URL%/}$path"
      echo "== $url =="
      curl -sL "${url}?nocache=$(date +%s)" | grep -Ei "Thermage|NVX_|et_pb_|zzzz" || echo "CLEAN"
    done
  else
    echo "Requires --base-url or --urls-file" >&2
    exit 2
  fi
} > "$REPORT"

echo "Report written to $REPORT"