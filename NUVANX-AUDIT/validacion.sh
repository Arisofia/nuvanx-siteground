#!/bin/bash
cd /home/customer/www/nuvanx.com/public_html || exit 1

echo "================ TEMAS ================"

wp theme list \
  --fields=name,status,version \
  --format=table

echo
echo "================ PLUGINS ================"

wp plugin list \
  --fields=name,status,version \
  --format=table

echo
echo "================ DIRECTORIOS RETIRADOS ================"

for path in \
  "wp-content/themes/nuvanx-editorial-medical-v2" \
  "wp-content/plugins/plugin" \
  "wp-content/backups-nuvanx"
do
  if [ -e "$path" ]; then
    echo "ERROR: todavía existe $path"
  else
    echo "OK: retirado $path"
  fi
done

echo
echo "================ BACKUP SQL PÚBLICO ================"

curl -LsS \
  -o /dev/null \
  -w "HTTP %{http_code} · %{content_type}\n" \
  "https://nuvanx.com/wp-content/backups-nuvanx/20260711-203530/database.sql"

echo
echo "================ MARCADORES DEL TEMA ANTIGUO ================"

curl -Ls \
"https://nuvanx.com/?physical_cleanup=$(date +%s)" |
grep -iE \
'data-nvxe-header|nvxe-topbar|nvxe-main|nvxe-footer|nvxe-journal' \
|| true
