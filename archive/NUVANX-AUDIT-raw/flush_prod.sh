#!/bin/bash
cd /home/u54-jiiuzkghob55/www/nuvanx.com/public_html || exit 1
wp option get siteurl
wp theme list --status=active
wp cache flush
echo "=== PROD CACHE FLUSHED ==="
