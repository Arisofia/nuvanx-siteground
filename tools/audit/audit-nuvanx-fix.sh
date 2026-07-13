#!/usr/bin/env bash
# READ-ONLY audit: plugin/footer/form/redirect checks. Writes HTML snapshots to --output-dir only.
set -Eeuo pipefail

WP_ROOT=""
BASE_URL=""
OUTPUT_DIR="./artifacts/audit-results"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --wp-root) WP_ROOT="$2"; shift 2 ;;
    --base-url) BASE_URL="$2"; shift 2 ;;
    --output-dir) OUTPUT_DIR="$2"; shift 2 ;;
    *) echo "Unknown arg: $1" >&2; exit 2 ;;
  esac
done

[[ -n "$WP_ROOT" && -d "$WP_ROOT/wp-content" ]] || { echo "Requires --wp-root" >&2; exit 2; }
command -v wp >/dev/null 2>&1 || { echo "wp-cli required" >&2; exit 2; }

BASE_URL="${BASE_URL:-$(cd "$WP_ROOT" && wp option get home 2>/dev/null || true)}"
STAMP="$(date +%Y%m%d-%H%M%S)"
mkdir -p "$OUTPUT_DIR/html"
REPORT="$OUTPUT_DIR/nuvanx-fix-audit-${STAMP}.txt"
THEME="$WP_ROOT/wp-content/themes/nuvanx-medical"

{
  echo "== PLUGIN STATUS =="
  cd "$WP_ROOT"
  wp plugin status nuvanx-fix 2>&1 || true
  echo "== FOOTER CANONICAL =="
  grep -nE 'nvx-footer__legal-nav|politica-de-privacidad|aviso-legal' "$THEME/footer.php" 2>/dev/null || true
  echo "== REDIRECTS IN CODE =="
  grep -RniE 'politica-de-privacidad|wp_safe_redirect|template_redirect' \
    "$THEME" "$WP_ROOT/wp-content/mu-plugins" 2>/dev/null || true
  if [[ -n "$BASE_URL" ]]; then
    echo "== PUBLIC REDIRECT CHAIN =="
    curl -sSIL --max-redirs 5 "${BASE_URL%/}/politica-de-privacidad/?audit=$STAMP" \
      | grep -iE '^HTTP/|^location:' || true
    echo "== FOOTER HTML =="
    curl -sL "${BASE_URL%/}/?footer_audit=$STAMP" > "$OUTPUT_DIR/html/home.html"
    grep -o 'nvx-footer__legal-nav' "$OUTPUT_DIR/html/home.html" | wc -l
    for pair in "contacto|/contacto/" "valoracion|/madrid/valoracion/"; do
      name="${pair%%|*}"; path="${pair#*|}"
      curl -sL "${BASE_URL%/}${path}?form_audit=$STAMP" > "$OUTPUT_DIR/html/${name}.html"
      echo "forms in $name: $(grep -ci '<form' "$OUTPUT_DIR/html/${name}.html" || true)"
    done
  fi
  echo "== HUBSPOT MU-PLUGINS =="
  for f in nuvanx-contacto-hubspot-form.php nuvanx-redirects.php; do
    echo "--- $f ---"
    grep -nE 'privacy|form|redirect|hubspot' "$WP_ROOT/wp-content/mu-plugins/$f" 2>/dev/null | head -40 || echo "missing"
  done
} | tee "$REPORT"

echo "Report written to $REPORT"