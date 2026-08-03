#!/usr/bin/env bash
# PHP Audit Script for nuvanx-medical theme
# This script installs dependencies, runs PHPCS and PHPStan, and generates baselines

set -Eeuo pipefail

THEME_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$THEME_DIR"

echo "🔍 PHP Audit Script for nuvanx-medical theme"
echo "Working directory: $THEME_DIR"
echo ""

# Install dependencies if needed
if [ ! -d "vendor" ]; then
    echo "📦 Installing Composer dependencies..."
    composer install --no-interaction
    echo ""
fi

# Run PHPCS
echo "🔍 Running PHP_CodeSniffer..."
echo "==================================="
PHPCS_EXIT=0
./vendor/bin/phpcs --standard=phpcs.xml.dist --report=summary --report-file=phpcs-report.txt || PHPCS_EXIT=$?
echo ""
if [ "$PHPCS_EXIT" -eq 0 ]; then
    echo "✅ PHPCS: No errors found"
else
    echo "⚠️  PHPCS: Found violations (exit code: $PHPCS_EXIT)"
fi
echo ""

# Run PHPStan
echo "🔍 Running PHPStan..."
echo "==================================="
PHPSTAN_EXIT=0
./vendor/bin/phpstan analyse --configuration=phpstan.neon --error-format=table --no-progress --memory-limit=512M || PHPSTAN_EXIT=$?

echo ""
if [ "$PHPSTAN_EXIT" -eq 0 ]; then
    echo "✅ PHPStan: No errors found"
else
    echo "⚠️  PHPStan: Found errors (exit code: $PHPSTAN_EXIT)"
fi
echo ""

# Generate summary report
echo "📊 Audit Summary"
echo "==================================="
echo "PHPCS exit code: $PHPCS_EXIT"
echo "PHPStan exit code: $PHPSTAN_EXIT"
echo ""

# Exit with error if either tool failed
if [ "$PHPCS_EXIT" -ne 0 ] || [ "$PHPSTAN_EXIT" -ne 0 ]; then
    echo "❌ Audit failed. Please review the output above."
    exit 1
else
    echo "✅ Audit completed successfully."
    exit 0
fi