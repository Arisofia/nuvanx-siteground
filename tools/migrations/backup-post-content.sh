#!/usr/bin/env bash
# MUTATING: writes post content backups under wp-content/backups-nuvanx/
set -Eeuo pipefail

WP_ROOT=""
BACKUP_LABEL=""
POST_IDS=""
CONFIRM=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --wp-root) WP_ROOT="$2"; shift 2 ;;
    --backup-label) BACKUP_LABEL="$2"; shift 2 ;;
    --post-ids) POST_IDS="$2"; shift 2 ;;
    --confirm) CONFIRM=1; shift ;;
    *) echo "Unknown arg: $1" >&2; exit 2 ;;
  esac
done

[[ -n "$WP_ROOT" && -n "$BACKUP_LABEL" && -n "$POST_IDS" ]] || {
  echo "Usage: $0 --wp-root PATH --backup-label NAME --post-ids '9 1415' --confirm" >&2
  exit 2
}
[[ "$CONFIRM" -eq 1 || "${NUVANX_CONFIRM:-}" == "yes" ]] || {
  echo "Requires --confirm" >&2; exit 2
}

command -v wp >/dev/null 2>&1 || { echo "wp-cli required" >&2; exit 2; }
DIR="$WP_ROOT/wp-content/backups-nuvanx/$BACKUP_LABEL"
mkdir -p "$DIR"
cd "$WP_ROOT"

for id in $POST_IDS; do
  wp post get "$id" --field=post_content > "$DIR/post-${id}-pre.txt" || true
  echo "Backed up post $id -> $DIR/post-${id}-pre.txt"
done