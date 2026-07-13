#!/bin/bash
run_inventory() {
  local dir=$1
  local env=$2
  echo "=== $env ==="
  cd $dir
  echo "Pages:"
  wp post list --post_type=page --post_status=publish --fields=ID,post_title,post_name --format=table
  
  local queries=(
    "SELECT ID, post_title FROM wp_posts WHERE post_status='publish' AND post_content LIKE '%et_pb_%';"
    "SELECT ID, post_title FROM wp_posts WHERE post_status='publish' AND post_content LIKE '%tmp-%';"
    "SELECT ID, post_title FROM wp_posts WHERE post_status='publish' AND post_content LIKE '%brand-manual%';"
    "SELECT ID, post_title FROM wp_posts WHERE post_status='publish' AND post_content LIKE '%preg_replace%';"
    "SELECT ID, post_title FROM wp_posts WHERE post_status='publish' AND post_content LIKE '%zzzz%';"
    "SELECT ID, post_title FROM wp_posts WHERE post_status='publish' AND post_content LIKE '%NVX_SCHEMA%';"
    "SELECT ID, post_title FROM wp_posts WHERE post_status='publish' AND post_content LIKE '%NVX_THERMAGE_INTERNAL_LINK%';"
    "SELECT ID, post_title FROM wp_posts WHERE post_status='publish' AND post_content LIKE '%NVX_HOME_V2%';"
    "SELECT ID, post_title FROM wp_posts WHERE post_status='publish' AND post_content LIKE '%NVX_PHASE%';"
    "SELECT ID, post_title FROM wp_posts WHERE post_status='publish' AND post_content LIKE '%jsonld-schema%';"
    "SELECT ID, post_title FROM wp_posts WHERE post_status='publish' AND post_content LIKE '%seo-geo-fixes%';"
    "SELECT ID, post_title FROM wp_posts WHERE post_status='publish' AND post_content LIKE '%breadcrumb-schema%';"
  )
  for q in "${queries[@]}"; do
    echo "Query: $q"
    wp db query "$q"
  done
  
  echo "Postmeta:"
  wp db query "SELECT post_id, meta_key FROM wp_postmeta WHERE meta_value LIKE '%et_pb_%' OR meta_value LIKE '%tmp-%' OR meta_value LIKE '%NVX_SCHEMA%' OR meta_value LIKE '%NVX_PHASE%' OR meta_value LIKE '%brand-manual%';"
}

run_inventory '/home/u54-jiiuzkghob55/www/nuvanx.com/public_html' 'PROD'
run_inventory '/home/u54-jiiuzkghob55/www/staging2.nuvanx.com/public_html' 'STAGING'
