#!/usr/bin/env bash
# Design Consistency Audit Script
# Validates design consistency across critical pages

set -Eeuo pipefail

THEME_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$THEME_DIR"

echo "🎨 Design Consistency Audit for nuvanx-medical theme"
echo "Theme directory: $THEME_DIR"
echo ""

# Create screenshots directory
mkdir -p wp-content/themes/nuvanx-medical/tests/screenshots

echo "📸 Running Visual Regression Tests..."
echo "=========================================="
cd wp-content/themes/nuvanx-medical
npx playwright test tests/visual-regression.spec.ts --headed || true

echo ""
echo "🎯 Running Design Consistency Tests..."
echo "=========================================="
npx playwright test tests/design-consistency.spec.ts || true

echo ""
echo "📊 Audit Summary"
echo "=========================================="
echo "Screenshots saved to: tests/screenshots/"
echo "Visual regression tests completed"
echo "Design consistency tests completed"
echo ""
echo "📋 Next Steps:"
echo "1. Review screenshots in tests/screenshots/"
echo "2. Compare header styles across pages"
echo "3. Check spacing and margin consistency"
echo "4. Verify typography and color usage"
echo "5. Document inconsistencies in tests/design-audit-report.md"
echo ""
echo "✅ Design audit completed. Manual review required for visual inconsistencies."