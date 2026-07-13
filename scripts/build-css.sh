#!/usr/bin/env bash
set -Eeuo pipefail

CSS_DIR="wp-content/themes/nuvanx-medical/assets/css"

mkdir -p "$CSS_DIR"
for source in "$CSS_DIR"/*.css; do
    [[ "$source" == *.min.css ]] && continue
    [[ -f "$source" ]] || continue

    target="${source%.css}.min.css"
    npx --yes lightningcss \
        --minify \
        --targets '>= 0.25%' \
        --output-file "$target" \
        "$source"
done
