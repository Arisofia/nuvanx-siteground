#!/bin/bash
run_backup() {
  local dir=$1
  local env=$2
  echo "=== BACKUP $env ==="
  cd $dir
  mkdir -p wp-content/backups-nuvanx/cleanup-embedded-legacy-20260708
  wp post get 9 --field=post_content > wp-content/backups-nuvanx/cleanup-embedded-legacy-20260708/prod-home-post9-pre-clean.txt || true
  wp post get 1415 --field=post_content > wp-content/backups-nuvanx/cleanup-embedded-legacy-20260708/prod-1415-pre-clean.txt || true
  wp post get 1543 --field=post_content > wp-content/backups-nuvanx/cleanup-embedded-legacy-20260708/prod-1543-pre-clean.txt || true
  wp post get 1537 --field=post_content > wp-content/backups-nuvanx/cleanup-embedded-legacy-20260708/prod-1537-pre-clean.txt || true
  wp post get 1399 --field=post_content > wp-content/backups-nuvanx/cleanup-embedded-legacy-20260708/prod-1399-pre-clean.txt || true
  wp post get 1241 --field=post_content > wp-content/backups-nuvanx/cleanup-embedded-legacy-20260708/prod-1241-pre-clean.txt || true
}

run_backup '/home/u54-jiiuzkghob55/www/nuvanx.com/public_html' 'PROD'
run_backup '/home/u54-jiiuzkghob55/www/staging2.nuvanx.com/public_html' 'STAGING'
