#!/bin/bash
validate_env() {
  local dir=$1
  local env=$2
  echo "=== VALIDATE $env ==="
  cd $dir
  wp db query "SELECT ID, post_title FROM wp_posts WHERE post_status='publish' AND (post_content LIKE '%NVX_SCHEMA%' OR post_content LIKE '%NVX_PHASE%' OR post_content LIKE '%NVX_HOME_V2%' OR post_content LIKE '%NVX_THERMAGE_INTERNAL_LINK%' OR post_content LIKE '%et_pb_%' OR post_content LIKE '%tmp-%' OR post_content LIKE '%brand-manual%' OR post_content LIKE '%preg_replace%' OR post_content LIKE '%zzzz%');"
}
validate_env '/home/u54-jiiuzkghob55/www/nuvanx.com/public_html' 'PROD'
validate_env '/home/u54-jiiuzkghob55/www/staging2.nuvanx.com/public_html' 'STAGING'
