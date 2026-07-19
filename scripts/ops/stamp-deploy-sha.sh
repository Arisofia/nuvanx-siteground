#!/usr/bin/env bash
set -euo pipefail

THEME_DIR="${1:-wp-content/themes/nuvanx-medical}"
DEPLOY_SHA="${2:-$(git rev-parse HEAD)}"
MARKER="$THEME_DIR/.nvx-deploy-sha"

if [[ ! "$DEPLOY_SHA" =~ ^[0-9a-f]{40}$ ]]; then
  echo "ERROR: deploy SHA must be a full 40-character lowercase commit SHA: $DEPLOY_SHA" >&2
  exit 1
fi

if [[ ! -d "$THEME_DIR" ]]; then
  echo "ERROR: theme directory does not exist: $THEME_DIR" >&2
  exit 1
fi

printf '%s\n' "$DEPLOY_SHA" > "$MARKER"
[[ "$(cat "$MARKER")" == "$DEPLOY_SHA" ]]
echo "$DEPLOY_SHA"
