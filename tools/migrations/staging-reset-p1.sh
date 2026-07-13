#!/usr/bin/env bash
# MUTATING: staging quarantine reset. Requires --confirm. Not for CI.
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
command -v wp >/dev/null 2>&1 || { echo "wp-cli required" >&2; exit 2; }

DATE="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="$WP_ROOT/wp-content/backups-nuvanx/reset-p1-$DATE"
mkdir -p "$BACKUP_DIR/quarantine"
cd "$WP_ROOT"

wp db export "$BACKUP_DIR/db.sql"
tar -czf "$BACKUP_DIR/theme.tgz" wp-content/themes/nuvanx-medical
tar -czf "$BACKUP_DIR/mu-plugins.tgz" wp-content/mu-plugins

wp sg optimize combine-css disable || true
wp sg optimize combine-js disable || true
rm -f wp-content/uploads/siteground-optimizer-assets/siteground-optimizer-combined-* 2>/dev/null || true
rm -rf wp-content/cache/sgo-cache/* 2>/dev/null || true
wp cache flush
wp sg purge

find wp-content/themes/nuvanx-medical wp-content/mu-plugins -type d -name '_archive*' -exec mv {} "$BACKUP_DIR/quarantine/" \; 2>/dev/null || true
find wp-content/themes/nuvanx-medical wp-content/mu-plugins -type f \( -name '*.bak' -o -name '*zzzz*' -o -name '*legacy*' \) -exec mv {} "$BACKUP_DIR/quarantine/" \; 2>/dev/null || true

echo "Staging reset complete. Backup: $BACKUP_DIR"