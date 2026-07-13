#!/bin/bash
cd /home/customer/www/nuvanx.com/public_html || exit 1

set -eu

STAMP="$(date +%Y%m%d-%H%M%S)"

OLD_THEME="nuvanx-editorial-medical-v2"
WRITER_PLUGIN="plugin"

ARCHIVE="$HOME/nuvanx-legacy-archive/$STAMP"
PRIVATE_BACKUPS="$HOME/nuvanx-private-backups/$STAMP"

mkdir -p \
  "$ARCHIVE/themes" \
  "$ARCHIVE/plugins" \
  "$PRIVATE_BACKUPS"

echo "================ PREFLIGHT ================"

ACTIVE_THEME="$(wp option get stylesheet)"
PARENT_THEME="$(wp option get template)"

echo "Tema activo: $ACTIVE_THEME"
echo "Template: $PARENT_THEME"

if [ "$ACTIVE_THEME" != "nuvanx-medical" ]; then
  echo "ERROR: el tema activo no es nuvanx-medical."
  exit 1
fi

if [ "$PARENT_THEME" != "nuvanx-medical" ]; then
  echo "ERROR: nuvanx-medical depende de otro tema."
  exit 1
fi

if ! wp theme list \
  --status=inactive \
  --field=name |
  grep -qx "$OLD_THEME"
then
  echo "ERROR: $OLD_THEME no figura como tema inactivo."
  exit 1
fi

if ! wp plugin list \
  --status=inactive \
  --field=name |
  grep -qx "$WRITER_PLUGIN"
then
  echo "ERROR: el plugin $WRITER_PLUGIN no figura como inactivo."
  exit 1
fi

echo
echo "Comprobando referencias al directorio de backups..."

BACKUP_REFS="$(
  grep -RniF \
    --exclude-dir="backups-nuvanx" \
    --exclude-dir="cache" \
    --exclude-dir="uploads" \
    "backups-nuvanx" \
    wp-config.php \
    wp-content \
    2>/dev/null || true
)"

if [ -n "$BACKUP_REFS" ]; then
  echo "ERROR: existen referencias externas a backups-nuvanx:"
  echo "$BACKUP_REFS"
  exit 1
fi

echo "Preflight correcto."

echo
echo "================ ARCHIVANDO TEMA ================"

if [ -d "wp-content/themes/$OLD_THEME" ]; then
  mv \
    "wp-content/themes/$OLD_THEME" \
    "$ARCHIVE/themes/"

  echo "Archivado: $OLD_THEME"
fi

echo
echo "================ ARCHIVANDO TEMPLATE WRITER ================"

if [ -d "wp-content/plugins/$WRITER_PLUGIN" ]; then
  mv \
    "wp-content/plugins/$WRITER_PLUGIN" \
    "$ARCHIVE/plugins/"

  echo "Archivado: wp-content/plugins/$WRITER_PLUGIN"
fi

echo
echo "================ MOVIENDO BACKUPS PRIVADOS ================"

if [ -d "wp-content/backups-nuvanx" ]; then
  mv \
    "wp-content/backups-nuvanx" \
    "$PRIVATE_BACKUPS/backups-nuvanx"

  echo "Backups movidos fuera de public_html."
fi

wp cache flush
wp sg purge 2>/dev/null || true

echo
echo "Archivo legacy:"
echo "$ARCHIVE"

echo
echo "Backup privado:"
echo "$PRIVATE_BACKUPS"
