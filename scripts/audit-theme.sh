#!/usr/bin/env bash
set -Eeuo pipefail

THEME="wp-content/themes/nuvanx-medical"

printf 'Tema canónico: %s\n' "$THEME"
if [ -d "$THEME" ]; then
  find "$THEME" -maxdepth 2 -type f | sort | sed 's#^#- #' 
fi
