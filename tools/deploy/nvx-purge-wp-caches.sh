#!/usr/bin/env bash
# Shared WordPress + SiteGround Dynamic Cache purge for staging2 and production.
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

# WordPress object cache.
# Fails loudly if WP-CLI is not available or the command errors — no || true.
wp cache flush

# SiteGround Optimizer: purge variants. sg sub-commands may not exist on every
# SG plan; we allow missing sub-commands (exit 1 from unknown command) but NOT
# permission errors or server-side failures.
wp sg purge         && echo "sg_purge=ok"         || echo "WARN: wp sg purge returned non-zero (sub-command may not be available)" >&2
wp sg purge dynamic && echo "sg_purge_dynamic=ok" || echo "WARN: wp sg purge dynamic returned non-zero" >&2
wp sg purge memcached && echo "sg_purge_memcached=ok" || echo "WARN: wp sg purge memcached returned non-zero" >&2

# Physical HTML / asset caches left behind when SG Dynamic Cache orphans files.
# rm -rf is kept non-fatal (paths may not exist) but errors go to stderr visibly.
rm -rf wp-content/uploads/siteground-optimizer-assets/siteground-optimizer-combined-* 2>/dev/null || true
rm -rf wp-content/cache/sgo-cache       2>/dev/null || true
rm -rf wp-content/cache/supercache      2>/dev/null || true
rm -rf wp-content/cache/sg-cachepress   2>/dev/null || true
rm -rf wp-content/cache/*               2>/dev/null || true

# Combined/minified asset leftovers that can keep pre-deploy CSS/JS alive.
find wp-content/uploads/siteground-optimizer-assets -mindepth 1 -maxdepth 1 \
  \( -name 'siteground-optimizer-combined-*' -o -name '*.css' -o -name '*.js' \) \
  -delete 2>/dev/null || true

# OpCache reset — non-fatal (PHP CLI may not have OpCache loaded).
wp eval 'if (function_exists("opcache_reset")) { opcache_reset(); echo "opcache=ok\n"; }' \
  && true || echo "WARN: opcache_reset skipped or errored" >&2

echo "$LABEL"
