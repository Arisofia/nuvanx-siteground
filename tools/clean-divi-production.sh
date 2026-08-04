#!/usr/bin/env bash
set -e

echo "🧹 Script de limpieza de Divi en Producción"
echo "=========================================="
echo ""

# Configuración - MODIFICA ESTOS VALORES
SSH_HOST="siteground300.siteground.eu"
SSH_PORT="18765"
SSH_USER="customer_u12345"  # Cambia esto por tu usuario real
WP_PATH="/home/customer/www/nuvanx.com/public_html"

echo "📍 Servidor: $SSH_HOST:$SSH_PORT"
echo "👤 Usuario: $SSH_USER"
echo "📂 Ruta WordPress: $WP_PATH"
echo ""
read -p "¿Continuar? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Cancelado por el usuario"
    exit 1
fi

echo ""
echo "📦 Paso 1: Backup de base de datos..."
ssh -p $SSH_PORT $SSH_USER@$SSH_HOST "cd $WP_PATH && wp db export backup-pre-divi-clean-\$(date +%Y%m%d).sql"

echo ""
echo "🔍 Paso 2: Ejecutar limpieza en modo dry-run..."
ssh -p $SSH_PORT $SSH_USER@$SSH_HOST "cd $WP_PATH && wp eval-file wp-content/themes/nuvanx/inc/cli-clean-divi.php --dry-run"

echo ""
read -p "¿Proceder con limpieza real? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Limpieza cancelada"
    exit 1
fi

echo ""
echo "🧹 Paso 3: Ejecutar limpieza real..."
ssh -p $SSH_PORT $SSH_USER@$SSH_HOST "cd $WP_PATH && wp eval-file wp-content/themes/nuvanx/inc/cli-clean-divi.php --post-type=page,post --batch-size=25"

echo ""
echo "🗑️  Paso 4: Purgar caché..."
ssh -p $SSH_PORT $SSH_USER@$SSH_HOST "cd $WP_PATH && wp siteground cache purge --type=all"

echo ""
echo "✅ Limpieza completada exitosamente"
echo "📝 Backup guardado en: backup-pre-divi-clean-$(date +%Y%m%d).sql"
