#!/usr/bin/env bash
set -Eeuo pipefail

TARGET_DIR="${1:-wp-content/themes/nuvanx-medical-wpvibe-draft}"
mkdir -p "$TARGET_DIR"
cp -R wp-content/themes/nuvanx-medical/. "$TARGET_DIR"

echo "Tema copiado a $TARGET_DIR"
