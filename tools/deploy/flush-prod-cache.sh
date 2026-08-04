#!/usr/bin/env bash
# MUTATING: flushes WordPress cache on production. Requires --confirm or NUVANX_CONFIRM=yes.
set -Eeuo pipefail

WP_ROOT=""
CONFIRM=0

usage() {
  cat >&2 <<'EOF'
Usage:
  flush-prod-cache.sh \
    --wp-root /home/customer/www/nuvanx.com/public_html \
    --confirm

Flushes WordPress cache and SiteGround optimizer caches on the production root.
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --wp-root) WP_ROOT="$2"; shift 2 ;;
    --confirm) CONFIRM=1; shift ;;
    *)
      usage
      echo "Unknown arg: $1" >&2
      exit 2
      ;;
  esac
done

[[ -n "$WP_ROOT" ]] || { usage; echo "Requires --wp-root" >&2; exit 2; }
[[ "$CONFIRM" -eq 1 || "${NUVANX_CONFIRM:-}" == "yes" ]] || {
  echo "Requires --confirm or NUVANX_CONFIRM=yes" >&2
  exit 2
}

if [[ ! -d "$WP_ROOT" ]]; then
    echo "ERROR: WP_ROOT directory not found: $WP_ROOT" >&2
    exit 1
fi

cd "$WP_ROOT"

siteurl="$(wp option get siteurl)"
theme="$(wp theme list --status=active --field=name)"
echo "prod siteurl=$siteurl theme=$theme"

if [[ "$siteurl" != 'https://nuvanx.com' ]]; then
  echo "ERROR: refusing to flush cache on non-production siteurl=$siteurl" >&2
  exit 1
fi

echo "== Flush WordPress and SiteGround caches =="
wp cache flush || true
wp sg purge || true
rm -rf wp-content/uploads/siteground-optimizer-assets/siteground-optimizer-combined-* 2>/dev/null || true
rm -rf wp-content/cache/sgo-cache/* 2>/dev/null || true
rm -rf wp-content/cache/* 2>/dev/null || true
wp eval 'if (function_exists("opcache_reset")) { opcache_reset(); echo "opcache=ok\n"; }' || true

echo "Cache flushed for $WP_ROOT (siteurl=$siteurl)"