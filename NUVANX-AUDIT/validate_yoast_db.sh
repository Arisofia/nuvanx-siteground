#!/bin/bash
cd /home/customer/www/nuvanx.com/public_html || exit 1
set -euo pipefail
set +H

echo "== POSTMETA =="
wp db query "
SELECT post_id, meta_key, meta_value
FROM wp_postmeta
WHERE meta_key LIKE '%yoast%'
  AND (meta_value LIKE '%Thermage%' OR meta_value LIKE '%thermage%');
"

echo "== INDEXABLE =="
wp db query "
SELECT id, object_id, object_type, object_sub_type, permalink, title, description
FROM wp_yoast_indexable
WHERE title LIKE '%Thermage%'
   OR description LIKE '%Thermage%'
   OR breadcrumb_title LIKE '%Thermage%'
   OR permalink LIKE '%thermage%';
"

echo "== CURL LASER =="
curl -sL -H "Cache-Control: no-cache" -H "Pragma: no-cache" "https://nuvanx.com/medicina-estetica-laser/?nocache=$(date +%s)" \
| grep -Ei 'Thermage|thermage|nvx-thermage|1594' || echo 'MEDICINA LASER LIMPIA'

echo "== CURL VALORACION =="
curl -sL -H "Cache-Control: no-cache" -H "Pragma: no-cache" "https://nuvanx.com/madrid/valoracion/?nocache=$(date +%s)" \
| grep -Ei 'Thermage|thermage|nvx-thermage|1594' || echo 'VALORACION LIMPIA'
