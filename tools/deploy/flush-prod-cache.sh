#!/usr/bin/env bash
# MUTATING: flushes WordPress cache. Requires --confirm.
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
  echo "Requires --confirm or NUVANX_CONFIRM=yes" >&2; exit 2
}

cd "$WP_ROOT"
wp option get siteurl
wp theme list --status=active
wp cache flush || true
wp sg purge || true
rm -rf wp-content/uploads/siteground-optimizer-assets/siteground-optimizer-combined-* 2>/dev/null || true
rm -rf wp-content/cache/sgo-cache/* 2>/dev/null || true
rm -rf wp-content/cache/* 2>/dev/null || true
wp eval 'if (function_exists("opcache_reset")) { opcache_reset(); echo "opcache=ok\n"; }' || true
echo "Cache flushed for $WP_ROOT"