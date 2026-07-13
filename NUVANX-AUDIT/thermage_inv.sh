#!/bin/bash
run_inventory() {
  local dir=$1
  local env=$2
  echo "=== THERMAGE INVENTORY $env ==="
  cd $dir
  wp db query "SELECT ID, post_title, post_status, post_type, post_name FROM wp_posts WHERE post_content LIKE '%Thermage%' OR post_title LIKE '%Thermage%' OR post_name LIKE '%thermage%';"
  
  echo "Menus:"
  wp menu list
  echo "Primary Menu items:"
  wp menu item list "Primary Menu" || wp menu item list "primary" || wp menu item list "Principal" || echo "No primary menu found"

  echo "Links:"
  wp db query "SELECT ID, post_title FROM wp_posts WHERE post_content LIKE '%thermage-flx-radiofrecuencia-monopolar-madrid%';"
}
run_inventory '/home/u54-jiiuzkghob55/www/nuvanx.com/public_html' 'PROD'
run_inventory '/home/u54-jiiuzkghob55/www/staging2.nuvanx.com/public_html' 'STAGING'
