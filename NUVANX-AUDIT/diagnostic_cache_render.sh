#!/bin/bash
cd /home/customer/www/nuvanx.com/public_html || exit 1
set -euo pipefail
set +H

printf '%s\n' '== FRONT PAGE CONFIG =='
wp option get show_on_front
wp option get page_on_front
wp post get "$(wp option get page_on_front)" --fields=ID,post_title,post_status,post_type,post_modified --format=table

printf '%s\n' '== POST_CONTENT HOME =='
wp post get "$(wp option get page_on_front)" --field=post_content | grep -Ei 'Thermage|thermage|nvx-home-video-feature|<video|nvx-thermage' || echo 'DB limpia'

printf '%s\n' '== THE_CONTENT FILTRADO =='
wp eval '
$id = (int) get_option("page_on_front");
$post = get_post($id);
echo apply_filters("the_content", $post->post_content);
' | grep -Ei 'Thermage|thermage|nvx-home-video-feature|<video|nvx-thermage' || echo 'the_content limpio'

printf '%s\n' '== HTML PUBLICO HOME =='
curl -sL -H "Cache-Control: no-cache" -H "Pragma: no-cache" "https://nuvanx.com/?nocache=$(date +%s)" | grep -Ei 'Thermage|thermage|nvx-home-video-feature|<video|nvx-thermage' || echo 'HTML público limpio'

printf '%s\n' '== BUSCAR TEXTO LEGACY EXACTO =='
grep -RInEi 'Vanguardia y alta precisión|Thermage FLX en Madrid|Thermage FLX y CO2|Más allá de la estética|LaseMaR1500' \
  wp-content/themes/nuvanx-medical \
  wp-content/mu-plugins \
  wp-content/plugins \
  --exclude-dir='_archive*' \
  --exclude-dir='_disabled*' \
  --exclude-dir='backups*' \
  2>/dev/null || echo 'No encontrado en archivos activos'

printf '%s\n' '== TEMPLATE HOME =='
wp eval '
$id = (int) get_option("page_on_front");
echo "front_id=$id\n";
echo "template=" . get_page_template_slug($id) . "\n";
echo "theme=" . get_stylesheet() . "\n";
'

printf '%s\n' '== ARCHIVOS TEMPLATE DISPONIBLES =='
ls -la wp-content/themes/nuvanx-medical | grep -Ei 'front-page|home|page|index|template'

printf '%s\n' '== PURGA TOTAL SG =='
rm -f wp-content/uploads/siteground-optimizer-assets/siteground-optimizer-combined-css-*.css || true
rm -f wp-content/uploads/siteground-optimizer-assets/siteground-optimizer-combined-js-*.js || true

wp cache flush
wp sg purge
wp sg optimize combine-css disable || true
wp sg purge
wp sg optimize combine-css enable || true
wp sg purge

printf '%s\n' '== HEADERS HOME =='
curl -I -H "Cache-Control: no-cache" "https://nuvanx.com/?nocache=$(date +%s)"
