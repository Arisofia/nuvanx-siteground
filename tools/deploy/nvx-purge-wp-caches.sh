#!/usr/bin/env bash
# Shared WordPress + SiteGround cache purge for staging2 and production.
# Usage: bash tools/deploy/nvx-purge-wp-caches.sh --wp-root /path/to/wordpress [--label cache_purge=ok]
set -Eeuo pipefail

WP_ROOT=""
LABEL='cache_purge=ok'

while [[ $# -gt 0 ]]; do
  case "$1" in
    --wp-root) WP_ROOT="${2:-}"; shift 2 ;;
    --label) LABEL="${2:-}"; shift 2 ;;
    *) echo "ERROR: unknown argument: $1" >&2; exit 2 ;;
  esac
done

[[ -n "$WP_ROOT" ]] || { echo "ERROR: --wp-root is required" >&2; exit 2; }
[[ -d "$WP_ROOT" ]] || { echo "ERROR: WordPress root does not exist: $WP_ROOT" >&2; exit 2; }
[[ -f "$WP_ROOT/wp-config.php" ]] || { echo "ERROR: wp-config.php not found in $WP_ROOT" >&2; exit 2; }

cd "$WP_ROOT"

# Mandatory WordPress and SiteGround cache layers. Any WP-CLI, permission,
# or server-side failure aborts the deployment under strict shell mode.
wp cache flush
echo "wp_cache_flush=ok"

wp sg purge
echo "sg_purge=ok"

# Delete known disk-cache trees only when present. Missing paths are normal;
# filesystem permission or deletion failures are blocking.
shopt -s nullglob dotglob
cache_targets=(
  wp-content/uploads/siteground-optimizer-assets/siteground-optimizer-combined-*
  wp-content/cache/sgo-cache
  wp-content/cache/supercache
  wp-content/cache/sg-cachepress
  wp-content/cache/*
)
if (( ${#cache_targets[@]} > 0 )); then
  rm -rf -- "${cache_targets[@]}"
fi
shopt -u nullglob dotglob
echo "disk_cache_cleanup=ok"

# Remove combined/minified assets when the optimizer directory exists.
optimizer_assets='wp-content/uploads/siteground-optimizer-assets'
if [[ -d "$optimizer_assets" ]]; then
  find "$optimizer_assets" -mindepth 1 -maxdepth 1 \
    \( -name 'siteground-optimizer-combined-*' -o -name '*.css' -o -name '*.js' \) \
    -delete
fi
echo "optimizer_assets_cleanup=ok"

# OpCache is optional. When available, a false reset result is a blocking
# failure rather than a successful marker.
wp eval 'if (!function_exists("opcache_reset")) { echo "opcache=unavailable\n"; } elseif (!opcache_reset()) { fwrite(STDERR, "opcache=failed\n"); exit(1); } else { echo "opcache=ok\n"; }'

echo "$LABEL"
