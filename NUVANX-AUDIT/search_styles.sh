#!/bin/bash
cd /home/u54-jiiuzkghob55/www/nuvanx.com/public_html/wp-content

echo "=== SEARCH IN THEMES ==="
grep -rn "nvx-nosotros" themes/ 2>/dev/null || echo "None found in themes"

echo "=== SEARCH IN PLUGINS ==="
grep -rn "nvx-nosotros" plugins/ 2>/dev/null || echo "None found in plugins"

echo "=== SEARCH IN MU-PLUGINS ==="
grep -rn "nvx-nosotros" mu-plugins/ 2>/dev/null || echo "None found in mu-plugins"

echo "=== SEARCH IN DIVI THEME OPTIONS CUSTOM CSS ==="
# Divi theme options are stored in the database under option name 'et_divi'
# which is a serialized array containing 'custom_css' or similar keys.
wp option get et_divi --format=json 2>/dev/null | grep -o '"custom_css":"[^"]*"' | head -c 200 || echo "None or couldn't parse"

echo "=== GENERAL NVX STYLE SHEETS ==="
find . -name "*nvx*" -o -name "*custom*"
