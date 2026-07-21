#!/usr/bin/env bash
set -euo pipefail
WP_ROOT='/home/customer/www/staging2.nuvanx.com/public_html'
cd "$WP_ROOT"

echo "=== .htaccess COMPLETO ==="
cat .htaccess

echo ""
echo "=== Tiene bloque WordPress? ==="
grep -c "BEGIN WordPress" .htaccess || echo "0 — FALTA EL BLOQUE"
