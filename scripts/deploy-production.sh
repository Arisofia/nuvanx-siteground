#!/usr/bin/env bash
set -Eeuo pipefail

TARGET_DIR="${1:-wp-content/themes/nuvanx-medical}"
mkdir -p "$TARGET_DIR"
cp -R wp-content/themes/nuvanx-medical/. "$TARGET_DIR"

echo "Tema listo para despliegue en $TARGET_DIR"
