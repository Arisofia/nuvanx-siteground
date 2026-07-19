#!/usr/bin/env bash
set -Eeuo pipefail

# Stamp the immutable source SHA after a manual theme upload. Run from the
# WordPress document root, after the complete theme has been copied.
SHA="${1:-}"
THEME_REL="${NVX_THEME_REL:-wp-content/themes/nuvanx-medical}"

if [[ ! "$SHA" =~ ^[0-9a-f]{40}$ ]]; then
  echo "ERROR: pass the full 40-character lowercase Git SHA." >&2
  exit 1
fi

if [[ "$THEME_REL" != "wp-content/themes/nuvanx-medical" ]]; then
  echo "ERROR: refusing an unexpected theme path: $THEME_REL" >&2
  exit 1
fi

if [[ ! -d "$THEME_REL" ]]; then
  echo "ERROR: theme directory not found: $THEME_REL" >&2
  exit 1
fi

printf '%s\n' "$SHA" > "$THEME_REL/.nvx-deploy-sha"
echo "DEPLOY_MARKER_STAMPED=$SHA"
