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

# SiteGround Speed Optimizer's documented purge command clears Dynamic,
# File-based and Object caches. Any failure must abort the deployment.
wp sg purge
echo "sg_purge=ok"

# Legacy forms are intentionally forbidden as executable commands:
# wp sg purge dynamic
# wp sg purge memcached

# Physical HTML / asset caches left behind when SG Dynamic Cache orphans files.
# Missing cache paths are non-fatal; deletion errors remain visible through the final smoke checks.
rm -rf wp-content/uploads/siteground-optimizer-assets/siteground-optimizer-combined-* 2>/dev/null || true
rm -rf wp-content/cache/sgo-cache       2>/dev/null || true
rm -rf wp-content/cache/supercache      2>/dev/null || true
rm -rf wp-content/cache/sg-cachepress   2>/dev/null || true
rm -rf wp-content/cache/*               2>/dev/null || true

# Combined/minified asset leftovers that can keep pre-deploy CSS/JS alive.
find wp-content/uploads/siteground-optimizer-assets -mindepth 1 -maxdepth 1 \
  \( -name 'siteground-optimizer-combined-*' -o -name '*.css' -o -name '*.js' \) \
  -delete 2>/dev/null || true

# OpCache reset — non-fatal because PHP CLI may not have OpCache loaded.
wp eval 'if (function_exists("opcache_reset")) { opcache_reset(); echo "opcache=ok\n"; }' \
  && true || echo "WARN: opcache_reset skipped or errored" >&2

echo "$LABEL"
