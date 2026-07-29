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

# WordPress object cache. Any failure aborts the deployment.
wp cache flush
echo 'wp_cache_flush=ok'

# SiteGround Speed Optimizer's documented command clears its assets, file cache
# and Dynamic Cache. Any execution failure aborts the deployment.
wp sg purge
echo 'sg_purge=ok'

# URL-scoped variants are intentionally not executed without their required URL:
# wp sg purge dynamic
# wp sg purge memcached

# Delete known disk-cache trees only when present. Missing paths are normal;
# filesystem permission or deletion failures remain blocking under strict mode.
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

# Remove combined/minified asset leftovers when the optimizer directory exists.
optimizer_assets='wp-content/uploads/siteground-optimizer-assets'
if [[ -d "$optimizer_assets" ]]; then
  find "$optimizer_assets" -mindepth 1 -maxdepth 1 \
    \( -name 'siteground-optimizer-combined-*' -o -name '*.css' -o -name '*.js' \) \
    -delete
fi

# OpCache is optional. PHP CLI cannot reset the web cache when opcache.enable_cli
# is disabled, so that state is reported as unavailable. When CLI OpCache is
# active, a false reset result is a real purge failure.
wp eval 'if (!function_exists("opcache_reset") || !filter_var(ini_get("opcache.enable_cli"), FILTER_VALIDATE_BOOLEAN)) { echo "opcache=unavailable\n"; } elseif (!opcache_reset()) { fwrite(STDERR, "opcache=failed\n"); exit(1); } else { echo "opcache=ok\n"; }'

echo "$LABEL"
