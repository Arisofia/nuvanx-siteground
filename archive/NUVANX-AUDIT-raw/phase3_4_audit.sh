#!/bin/bash
cd /home/customer/www/staging2.nuvanx.com/public_html || exit 1
set -euo pipefail
set +H

echo "== THEME ACTIVO =="
wp option get stylesheet
wp theme list

echo "== FRONT CONFIG =="
wp option get show_on_front
wp option get page_on_front

echo "== MU-PLUGINS =="
find wp-content/mu-plugins -maxdepth 2 -type f -o -type d | sort

echo "== THEME FILES SUSPICIOUS =="
find wp-content/themes/nuvanx-medical -maxdepth 4 \( -iname '*old*' -o -iname '*legacy*' -o -iname '*patch*' -o -iname '*fix*' -o -iname '*clean*' -o -iname '*staging*' -o -iname '*.bak' -o -iname '*.disabled' -o -iname 'zzzz*' \) -print || true

echo "== ACTIVE CODE RESIDUES =="
grep -RInEi 'Thermage|thermage|1594|nvx-thermage|nvx-phase3c|et_pb_|brand-manual|zzzz|legacy|old|patch|hotfix|tmp-|preg_replace.*important|remove_filter.*wpautop|ob_start' \
  wp-content/themes/nuvanx-medical \
  wp-content/mu-plugins \
  --exclude-dir='backups*' \
  --exclude-dir='_archive*' \
  --exclude-dir='_disabled*' \
  2>/dev/null || echo 'OK'

echo "== IMPORTANT =="
grep -RIn '\!important' wp-content/themes/nuvanx-medical wp-content/mu-plugins 2>/dev/null | grep -v 'screen-reader-text' || echo 'OK'

echo "== CSS TOKENS / HEX / CH =="
grep -RInE '#[0-9a-fA-F]{3,6}|!important|max-width:[[:space:]]*(14|20|30|34|40|52|58|68|72)ch|width:[[:space:]]*[0-9.]+ch|nvx-thermage|nvx-phase3c|et_pb_|#b8956b' \
  wp-content/themes/nuvanx-medical/assets/css \
  2>/dev/null || echo 'OK'

echo "== WP POSTS LEGACY =="
wp db query "
SELECT ID, post_title, post_status, post_type, post_name
FROM wp_posts
WHERE post_content LIKE '%Thermage%'
   OR post_title LIKE '%Thermage%'
   OR post_name LIKE '%thermage%'
   OR post_excerpt LIKE '%Thermage%'
   OR post_content LIKE '%et_pb_%'
   OR post_content LIKE '%nvx-phase3c%'
   OR post_content LIKE '%nvx-thermage%'
   OR post_content LIKE '%brand-manual%'
   OR post_content LIKE '%zzzz%'
   OR post_content LIKE '%tmp-%';
" || true

echo "== POSTMETA LEGACY =="
wp db query "
SELECT post_id, meta_key
FROM wp_postmeta
WHERE meta_value LIKE '%Thermage%'
   OR meta_value LIKE '%thermage%'
   OR meta_value LIKE '%1594%'
   OR meta_value LIKE '%et_pb_%'
   OR meta_value LIKE '%nvx-phase3c%'
   OR meta_value LIKE '%nvx-thermage%'
   OR meta_value LIKE '%brand-manual%'
   OR meta_value LIKE '%zzzz%'
   OR meta_value LIKE '%tmp-%';
" || true

echo "== YOAST META THERMAGE =="
wp db query "
SELECT post_id, meta_key, meta_value
FROM wp_postmeta
WHERE meta_key LIKE '%yoast%'
  AND (meta_value LIKE '%Thermage%' OR meta_value LIKE '%thermage%' OR meta_value LIKE '%1594%');
" || true

echo "== YOAST INDEXABLE THERMAGE =="
wp db query "
SELECT id, object_id, object_type, object_sub_type, permalink, title, description, breadcrumb_title
FROM wp_yoast_indexable
WHERE title LIKE '%Thermage%'
   OR description LIKE '%Thermage%'
   OR breadcrumb_title LIKE '%Thermage%'
   OR permalink LIKE '%thermage%'
   OR title LIKE '%1594%'
   OR description LIKE '%1594%';
" || true
