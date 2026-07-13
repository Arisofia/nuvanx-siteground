#!/bin/bash
cd /home/customer/www/staging2.nuvanx.com/public_html || exit 1
set -euo pipefail
set +H

DATE="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="wp-content/backups-nuvanx/source-of-truth-reset-$DATE"
mkdir -p "$BACKUP_DIR"

echo "== FASE 1: BACKUP STAGING =="
wp db export "$BACKUP_DIR/db.sql"
tar -czf "$BACKUP_DIR/theme.tgz" wp-content/themes/nuvanx-medical
tar -czf "$BACKUP_DIR/mu-plugins.tgz" wp-content/mu-plugins

echo "== FASE 2: APAGAR COMBINACIÓN SITEGROUND EN STAGING =="
wp sg optimize combine-css disable || true
wp sg optimize combine-js disable || true
rm -f wp-content/uploads/siteground-optimizer-assets/siteground-optimizer-combined-css-*.css || true
rm -f wp-content/uploads/siteground-optimizer-assets/siteground-optimizer-combined-js-*.js || true
rm -rf wp-content/cache/sgo-cache/* || true
wp cache flush
wp sg purge

echo "== FASE 3: LIMPIAR RUTAS ACTIVAS =="
mkdir -p "$BACKUP_DIR/quarantine"
find wp-content/themes/nuvanx-medical wp-content/mu-plugins -type d -name "_archive*" -exec mv {} "$BACKUP_DIR/quarantine/" \; || true
find wp-content/themes/nuvanx-medical wp-content/mu-plugins -type d -name "_disabled*" -exec mv {} "$BACKUP_DIR/quarantine/" \; || true

find wp-content/themes/nuvanx-medical wp-content/mu-plugins -type f \( -name "*zzzz*" -o -name "*.bak" -o -name "*.disabled" -o -name "*legacy*" -o -name "*old*" -o -name "*patch*" -o -name "*hotfix*" -o -name "style-clean.css" -o -name "style-staging.css" -o -name "nvx-home-center-fix.css" \) -exec mv {} "$BACKUP_DIR/quarantine/" \; || true

echo "== FASE 4: AUDITORÍA DURA DE STAGING =="
printf '%s\n' '== ACTIVE CODE RESIDUES =='
grep -RInEi 'Thermage|thermage|1594|nvx-thermage|nvx-phase3c|et_pb_|brand-manual|zzzz|legacy|old|patch|hotfix|tmp-|preg_replace.*important|remove_filter.*wpautop|ob_start' \
wp-content/themes/nuvanx-medical \
wp-content/mu-plugins \
--exclude-dir='backups*' \
--exclude-dir='_archive*' \
--exclude-dir='_disabled*' \
2>/dev/null || echo 'OK'

printf '%s\n' '== IMPORTANT =='
grep -RIn '!important' wp-content/themes/nuvanx-medical wp-content/mu-plugins 2>/dev/null | grep -v 'screen-reader-text' || echo 'OK'

printf '%s\n' '== JSONLD THERMAGE =='
grep -RInEi 'Thermage|thermage|1594' wp-content/mu-plugins/nuvanx-jsonld-data.json || echo 'OK'
