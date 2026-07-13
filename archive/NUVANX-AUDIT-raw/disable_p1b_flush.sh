#!/bin/bash
cd /home/u54-jiiuzkghob55/www/staging2.nuvanx.com/public_html || exit 1

echo "=== Confirmar entorno ==="
wp option get siteurl
wp theme list --status=active

echo "=== Buscar y desactivar MU-plugin priority1b ==="
find wp-content/mu-plugins -maxdepth 1 -name "*priority1b*" -name "*.php" | while read f; do
  echo "Desactivando: $f"
  mv "$f" "${f}.disabled-phase9c"
done

echo "=== Purgar cache ==="
wp cache flush

echo "=== DONE ==="
