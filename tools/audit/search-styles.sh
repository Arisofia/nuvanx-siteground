#!/usr/bin/env bash
set -Eeuo pipefail

WP_ROOT=""
OUTPUT_DIR="./artifacts/audit-results"
SEARCH_TERM="nvx-nosotros"
INCLUDE_DIVI_OPTION=1

usage() {
  cat <<'EOF' >&2
Usage: search-styles.sh --wp-root PATH [options]
EOF
  exit 2
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --wp-root) WP_ROOT="$2"; shift 2 ;;
    --output-dir) OUTPUT_DIR="$2"; shift 2 ;;
    --search-term) SEARCH_TERM="$2"; shift 2 ;;
    --skip-divi-option) INCLUDE_DIVI_OPTION=0; shift ;;
    -h|--help) usage ;;
    *) echo "Unknown arg: $1" >&2; usage ;;
  esac
done

[[ -n "$WP_ROOT" && -d "$WP_ROOT/wp-content" ]] || { echo "Invalid --wp-root" >&2; exit 2; }
command -v wp >/dev/null 2>&1 || INCLUDE_DIVI_OPTION=0

mkdir -p "$OUTPUT_DIR"
REPORT="$OUTPUT_DIR/style-search-report.txt"
WP_CONTENT="$WP_ROOT/wp-content"

{
  echo "# Style search audit"
  echo "# wp_root=$WP_ROOT search_term=$SEARCH_TERM"
  echo "=== THEMES ==="
  grep -RIn "$SEARCH_TERM" "$WP_CONTENT/themes" 2>/dev/null || echo "None"
  echo "=== PLUGINS ==="
  grep -RIn "$SEARCH_TERM" "$WP_CONTENT/plugins" 2>/dev/null || echo "None"
  echo "=== MU-PLUGINS ==="
  grep -RIn "$SEARCH_TERM" "$WP_CONTENT/mu-plugins" 2>/dev/null || echo "None"
  if [[ "$INCLUDE_DIVI_OPTION" -eq 1 ]]; then
    echo "=== DIVI CUSTOM CSS ==="
    (cd "$WP_ROOT" && wp option get et_divi --format=json 2>/dev/null | grep -o '"custom_css":"[^"]*"' | head -c 500) || echo "None"
  fi
  echo "=== NVX CSS FILES ==="
  find "$WP_CONTENT" \( -iname '*nvx*' -o -iname '*custom*' \) -type f 2>/dev/null | grep -E '\.(css|scss|less)$' || true
  echo "=== LEGACY MARKERS ==="
  grep -RInE 'Thermage|thermage|nvx-phase3c|zzzz|brand-manual' \
    "$WP_CONTENT/themes/nuvanx-medical" "$WP_CONTENT/mu-plugins" 2>/dev/null || echo "None"
} > "$REPORT"

echo "Report written to $REPORT"