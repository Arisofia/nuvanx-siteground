#!/usr/bin/env bash
# MUTATING: flushes WordPress + SiteGround caches. Requires --confirm.
set -Eeuo pipefail

WP_ROOT=""
CONFIRM=0
NVX_TOOLS_DEPLOY_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

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
bash "$NVX_TOOLS_DEPLOY_DIR/nvx-purge-wp-caches.sh" --wp-root "$WP_ROOT" --label "Cache flushed for $WP_ROOT"
