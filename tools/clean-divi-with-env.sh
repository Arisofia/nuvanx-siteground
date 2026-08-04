#!/usr/bin/env bash
set -e

echo "🧹 Script de limpieza de Divi - Usando variables de entorno"
echo "=========================================================="
echo ""

# Leer variables de entorno
SSH_HOST="${SSH_HOST:-}"
SSH_PORT="${SSH_PORT:-18765}"
SSH_USER="${SSH_USER:-}"
SSH_KEY="${SSH_KEY:-}"
WP_PATH="${WP_PATH:-}"

# Validar que las variables estén configuradas
if [[ -z "$SSH_HOST" || -z "$SSH_USER" || -z "$SSH_KEY" || -z "$WP_PATH" ]]; then
    echo "❌ Faltan variables de entorno requeridas:"
    echo "   SSH_HOST=$SSH_HOST"
    echo "   SSH_USER=$SSH_USER"
    echo "   SSH_KEY=${SSH_KEY:+(configurada)}"
    echo "   WP_PATH=$WP_PATH"
    echo ""
    echo "📝 Ejecuta así:"
    echo "   export SSH_HOST='siteground300.siteground.eu'"
    echo "   export SSH_USER='tu_usuario'"
    echo "   export SSH_KEY='/ruta/a/tu/clave.pem'"
    echo "   export WP_PATH='/home/customer/www/nuvanx.com/public_html'"
    echo "   ./tools/clean-divi-with-env.sh"
    exit 1
fi

echo "📍 Servidor: $SSH_HOST:$SSH_PORT"
echo "👤 Usuario: $SSH_USER"
echo "📂 Ruta WordPress: $WP_PATH"
echo ""
echo "⚡ Ejecutando en modo automático..."

echo ""
echo "📦 Paso 1: Backup de base de datos..."
ssh -i "$SSH_KEY" -p $SSH_PORT -o StrictHostKeyChecking=no $SSH_USER@$SSH_HOST "cd $WP_PATH && wp db export backup-pre-divi-clean-\$(date +%Y%m%d).sql"

echo ""
echo "⚡ Procediendo con limpieza real..."

echo ""
echo "📤 Paso 3: Desplegar archivos nuevos (inc/, src/, tests/)..."
rsync -avz -e "ssh -i $SSH_KEY -p $SSH_PORT -o StrictHostKeyChecking=no" \
  --exclude='.git*' \
  --exclude='node_modules*' \
  --exclude='*.log' \
  inc/ src/ tests/ \
  $SSH_USER@$SSH_HOST:$WP_PATH/wp-content/themes/nuvanx-medical/

echo ""
echo "🧹 Paso 4: Ejecutar limpieza real..."
ssh -i "$SSH_KEY" -p $SSH_PORT -o StrictHostKeyChecking=no $SSH_USER@$SSH_HOST "cd $WP_PATH && POST_TYPES='page,post' BATCH_SIZE=25 wp eval-file wp-content/themes/nuvanx-medical/inc/cli-clean-divi.php"

echo ""
echo "🗑️  Paso 4: Purgar caché..."
ssh -i "$SSH_KEY" -p $SSH_PORT -o StrictHostKeyChecking=no $SSH_USER@$SSH_HOST "cd $WP_PATH && wp siteground cache purge --type=all"

echo ""
echo "✅ Limpieza completada exitosamente"
echo "📝 Backup guardado en: backup-pre-divi-clean-$(date +%Y%m%d).sql"
