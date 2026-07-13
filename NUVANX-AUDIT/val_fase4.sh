#!/bin/bash
run_validation() {
  local dir=$1
  local env=$2
  echo "=== VALIDATION $env ==="
  cd $dir
  wp db query "SELECT ID, post_title FROM wp_posts WHERE post_status='publish' AND (post_content LIKE '%NVX\_%' OR post_content LIKE '%et_pb_%' OR post_content LIKE '%tmp-%' OR post_content LIKE '%brand-manual%' OR post_content LIKE '%preg_replace%' OR post_content LIKE '%zzzz%' OR post_content LIKE '%Thermage%' OR post_content LIKE '%nvx-phase3c%');"
}
run_validation '/home/u54-jiiuzkghob55/www/nuvanx.com/public_html' 'PROD'
run_validation '/home/u54-jiiuzkghob55/www/staging2.nuvanx.com/public_html' 'STAGING'
