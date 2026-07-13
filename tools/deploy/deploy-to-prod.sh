#!/usr/bin/env bash
# MUTATING: staging -> prod rsync. Requires --confirm. Not for CI.
set -Eeuo pipefail

PROD_ROOT=""
STAGING_ROOT=""
CONFIRM=0

require_confirm() {
  [[ "$CONFIRM" -eq 1 || "${NUVANX_CONFIRM:-}" == "yes" ]] || {
    echo "Refusing to run without --confirm or NUVANX_CONFIRM=yes" >&2
    exit 1
  }
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --prod-root) PROD_ROOT="$2"; shift 2 ;;
    --staging-root) STAGING_ROOT="$2"; shift 2 ;;
    --confirm) CONFIRM=1; shift ;;
    *) echo "Unknown arg: $1" >&2; exit 2 ;;
  esac
done

[[ -n "$PROD_ROOT" && -n "$STAGING_ROOT" ]] || {
  echo "Usage: $0 --prod-root PATH --staging-root PATH --confirm" >&2
  exit 2
}
command -v wp >/dev/null 2>&1 || { echo "wp-cli required" >&2; exit 2; }
require_confirm

DATE="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="$PROD_ROOT/wp-content/backups-nuvanx/pre-sync-$DATE"

echo "== Backup production =="
mkdir -p "$BACKUP_DIR"
(cd "$PROD_ROOT" && wp db export "$BACKUP_DIR/db.sql" --quiet)
tar -czf "$BACKUP_DIR/theme.tgz" -C "$PROD_ROOT" wp-content/themes/nuvanx-medical
tar -czf "$BACKUP_DIR/mu-plugins.tgz" -C "$PROD_ROOT" wp-content/mu-plugins

echo "== Disable SG combine =="
(cd "$PROD_ROOT" && wp sg optimize combine-css disable || true)
(cd "$PROD_ROOT" && wp sg optimize combine-js disable || true)

echo "== Rsync theme + mu-plugins =="
rsync -av --delete \
  --exclude='backups-nuvanx' --exclude='quarantine' \
  --exclude='_archive*' --exclude='_disabled*' --exclude='*.bak*' \
  "$STAGING_ROOT/wp-content/themes/nuvanx-medical/" \
  "$PROD_ROOT/wp-content/themes/nuvanx-medical/"

rsync -av --delete \
  --exclude='backups-nuvanx' --exclude='quarantine' \
  --exclude='_archive*' --exclude='_disabled*' --exclude='*.bak*' \
  "$STAGING_ROOT/wp-content/mu-plugins/" \
  "$PROD_ROOT/wp-content/mu-plugins/"

echo "== Purge prod cache =="
(cd "$PROD_ROOT" && wp cache flush && wp sg purge)
rm -rf "$PROD_ROOT/wp-content/cache/sgo-cache/"* 2>/dev/null || true

echo "== DONE backup=$BACKUP_DIR =="