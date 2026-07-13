#!/usr/bin/env bash
set -Eeuo pipefail

TAG="${1:-prod-2026-07-13}"
TARGET_DIR="wp-content/themes/nuvanx-medical"

echo "Rollback no ejecutado automáticamente; revisar tag $TAG y restaurar manualmente."
echo "Destino esperado: $TARGET_DIR"
