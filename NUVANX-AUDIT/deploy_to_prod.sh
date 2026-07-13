#!/bin/bash
set -euo pipefail

PROD_DIR="/home/customer/www/nuvanx.com/public_html"
STAGING_DIR="/home/customer/www/staging2.nuvanx.com/public_html"
DATE="$(date +%Y%m%d-%H%M%S)"

echo "== FASE 9: DEPLOY CONTROLADO A PRODUCCIÓN =="

echo "1. Backup Producción..."
cd "$PROD_DIR"
BACKUP_DIR="wp-content/backups-nuvanx/pre-final-sync-$DATE"
mkdir -p "$BACKUP_DIR"
wp db export "$BACKUP_DIR/db.sql" --quiet
tar -czf "$BACKUP_DIR/theme.tgz" wp-content/themes/nuvanx-medical
tar -czf "$BACKUP_DIR/mu-plugins.tgz" wp-content/mu-plugins

echo "2. Desactivar combine-css/combine-js..."
wp sg optimize combine-css disable || true
wp sg optimize combine-js disable || true
rm -f wp-content/uploads/siteground-optimizer-assets/siteground-optimizer-combined-css-*.css || true
rm -f wp-content/uploads/siteground-optimizer-assets/siteground-optimizer-combined-js-*.js || true

echo "3. Sincronizar archivos limpios desde staging..."
rsync -av --delete \
  --exclude="backups-nuvanx" \
  --exclude="quarantine" \
  --exclude="_archive*" \
  --exclude="_disabled*" \
  --exclude="*.bak*" \
  --exclude="*.disabled" \
  "$STAGING_DIR/wp-content/themes/nuvanx-medical/" \
  "$PROD_DIR/wp-content/themes/nuvanx-medical/"

rsync -av --delete \
  --exclude="backups-nuvanx" \
  --exclude="quarantine" \
  --exclude="_archive*" \
  --exclude="_disabled*" \
  --exclude="*.bak*" \
  --exclude="*.disabled" \
  "$STAGING_DIR/wp-content/mu-plugins/" \
  "$PROD_DIR/wp-content/mu-plugins/"

echo "4. Purgar caché en Producción..."
wp cache flush
wp sg purge
rm -rf wp-content/cache/sgo-cache/* || true

echo "== DONE =="
