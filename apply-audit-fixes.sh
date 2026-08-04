#!/usr/bin/env bash
set -e

echo "🚀 Aplicando cambios de la auditoría en nuvanx-siteground..."

# 1. Crear directorios requeridos
mkdir -p inc tests .github/workflows dist/css src/css

echo "✅ Directorios creados correctamente."
echo "📝 Los archivos ya han sido creados individualmente."
echo ""
echo "🎉 Proceso completado. Pasos siguientes:"
echo ""
echo "1. Instalar dependencias:"
echo "   npm install"
echo ""
echo "2. Compilar CSS:"
echo "   npm run build"
echo ""
echo "3. Verificar limpieza de Divi (dry-run):"
echo "   wp eval-file inc/cli-clean-divi.php --dry-run"
echo ""
echo "4. Ejecutar pruebas E2E:"
echo "   BASE_URL=https://staging.nuvanx.com npx playwright test"
echo ""
echo "5. Commit y push:"
echo "   git add ."
echo "   git commit -m 'refactor: apply production gate audit fixes, telemetry & layout shell'"
echo "   git push origin main"
