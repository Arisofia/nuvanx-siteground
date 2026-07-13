#!/usr/bin/env bash
# MUTATING: sed patches on mu-plugins. Requires --confirm. Historical repair — use with caution.
set -Eeuo pipefail

WP_ROOT=""
CONFIRM=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --wp-root) WP_ROOT="$2"; shift 2 ;;
    --confirm) CONFIRM=1; shift ;;
    *) echo "Unknown arg: $1" >&2; exit 2 ;;
  esac
done

[[ -n "$WP_ROOT" ]] || { echo "Requires --wp-root" >&2; exit 2; }
[[ "$CONFIRM" -eq 1 || "${NUVANX_CONFIRM:-}" == "yes" ]] || {
  echo "Requires --confirm" >&2; exit 2
}

cd "$WP_ROOT"
QUAR="$WP_ROOT/wp-content/backups-nuvanx/quarantine"
mkdir -p "$QUAR"

find wp-content/mu-plugins -type f -name '*.bak-*' -exec mv {} "$QUAR/" \; 2>/dev/null || true

FILE_HS="wp-content/mu-plugins/nuvanx-hubspot-form-standardizer.php"
if [[ -f "$FILE_HS" ]]; then
  cp "$FILE_HS" "${FILE_HS}.pre-clean.bak"
  sed -i 's/1594 =>.*//g' "$FILE_HS"
  sed -i '/Thermage/d' "$FILE_HS"
  sed -i '/thermage/d' "$FILE_HS"
fi

FILE_META="wp-content/mu-plugins/nuvanx-meta-dedupe-event-id.php"
if [[ -f "$FILE_META" ]]; then
  cp "$FILE_META" "${FILE_META}.pre-clean.bak"
  sed -i 's/patch/modify/g' "$FILE_META"
  sed -i 's/Patch/Modify/g' "$FILE_META"
fi

echo "Residual staging cleanup done. Rollback from .pre-clean.bak files if needed."