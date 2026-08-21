#!/bin/bash
#
# Pre-Merge/Promotion Protection Gate
#
# Required minimum checks before merging to master or promoting to production.
# Without all greens → no merge/promotion.
#
# Usage: ./scripts/ci/pre-merge-protection.sh [environment]
# environment: "merge" (to master) or "promotion" (to production)
#

set -Eeuo pipefail

ENVIRONMENT="${1:-merge}"
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

echo "=== PRE-MERGE/PROMOTION PROTECTION ==="
echo "Environment: ${ENVIRONMENT}"
echo "Project root: ${PROJECT_ROOT}"
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Track overall status
OVERALL_STATUS=0
CHECKS_PASSED=0
CHECKS_FAILED=0

# Function to run a check
run_check() {
  local check_name="$1"
  local check_command="$2"
  
  echo "Running: ${check_name}..."
  
  if eval "${check_command}" > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} ${check_name}: PASS"
    ((CHECKS_PASSED++))
    return 0
  else
    echo -e "${RED}✗${NC} ${check_name}: FAIL"
    ((CHECKS_FAILED++))
    OVERALL_STATUS=1
    return 1
  fi
}

# === 1. Syntax Checks ===
echo "=== SYNTAX CHECKS ==="
run_check "PHP Syntax" "php -l wp-content/themes/nuvanx-medical/functions.php"
run_check "PHP Syntax (all files)" "find wp-content/themes/nuvanx-medical -name '*.php' -exec php -l {} +"

# === 2. PHPCS ===
echo ""
echo "=== PHPCS ==="
if [[ -f "phpcs.xml" ]] || [[ -f "phpcs.xml.dist" ]]; then
  run_check "PHPCS Code Style" "phpcs wp-content/themes/nuvanx-medical"
else
  echo -e "${YELLOW}⊘${NC} PHPCS: SKIP (no phpcs.xml found)"
fi

# === 3. PHPStan ===
echo ""
echo "=== PHPSTAN ==="
if [[ -f "phpstan.neon" ]] || [[ -f "phpstan.neon.dist" ]]; then
  run_check "PHPStan Static Analysis" "phpstan analyse wp-content/themes/nuvanx-medical"
else
  echo -e "${YELLOW}⊘${NC} PHPStan: SKIP (no phpstan.neon found)"
fi

# === 4. Security/Secrets ===
echo ""
echo "=== SECURITY/SECRETS ==="
run_check "No secrets in code" "! git grep -i 'password\|api_key\|secret' -- '*.php' '*.js' '*.mjs' ':!node_modules/' ':!vendor/'"
run_check "No hardcoded credentials" "! git grep -E '(AKIA|sk-|token)' -- '*.php' '*.js' '*.mjs' ':!node_modules/' ':!vendor/'"

# === 5. Theme Hygiene ===
echo ""
echo "=== THEME HYGIENE ==="
if [[ -f "tools/migrations/diagnose-jsonld-storage.php" ]]; then
  run_check "Theme Hygiene" "wp eval 'require \"${PROJECT_ROOT}/tools/migrations/diagnose-jsonld-storage.php\";' --allow-root"
else
  echo -e "${YELLOW}⊘${NC} Theme Hygiene: SKIP (diagnose script not found)"
fi

# === 6. Data/Catalog Validation ===
echo ""
echo "=== DATA/CATALOG VALIDATION ==="
run_check "Catalog JSON validation" "cat wp-content/themes/nuvanx-medical/inc/data/treatments-catalog.json | jq empty"
run_check "SEO Metadata validation" "cat wp-content/themes/nuvanx-medical/inc/data/seo-metadata.json | jq empty"

# === 7. Publication Topology ===
echo ""
echo "=== PUBLICATION TOPOLOGY ==="
if [[ -f "tools/migrations/generate-publication-manifest.php" ]]; then
  run_check "Publication Manifest" "wp eval 'require \"${PROJECT_ROOT}/tools/migrations/generate-publication-manifest.php\";' --allow-root"
else
  echo -e "${YELLOW}⊘${NC} Publication Manifest: SKIP (generation script not found)"
fi

# === 8. SEO/Schema ===
echo ""
echo "=== SEO/SCHEMA ==="
run_check "Schema validation" "cat wp-content/themes/nuvanx-medical/inc/data/treatment-hub-schema.json | jq empty"
run_check "Aesthetic treatment schema" "cat wp-content/themes/nuvanx-medical/inc/data/aesthetic-treatment-pages.json | jq empty"

# === 9. Block C ===
echo ""
echo "=== BLOCK C ==="
if [[ -f "scripts/staging2/block-c-origin-browser-fallback.mjs" ]]; then
  run_check "Block C contract" "node scripts/staging2/block-c-origin-browser-fallback.mjs"
else
  echo -e "${YELLOW}⊘${NC} Block C: SKIP (contract not found)"
fi

# === 10. HubSpot ===
echo ""
echo "=== HUBSPOT ==="
if [[ -f "scripts/staging2/test-functional-consent-host-contract.mjs" ]]; then
  run_check "Functional consent host marker" "node scripts/staging2/test-functional-consent-host-contract.mjs"
fi
if [[ -f "scripts/staging2/test-hubspot-specific-gate.mjs" ]]; then
  # Only run if agent-browser is available
  if command -v agent-browser &> /dev/null; then
    run_check "HubSpot specific gate" "node scripts/staging2/test-hubspot-specific-gate.mjs"
  else
    echo -e "${YELLOW}⊘${NC} HubSpot: SKIP (agent-browser not installed)"
  fi
else
  echo -e "${YELLOW}⊘${NC} HubSpot: SKIP (gate not found)"
fi

# === 11. Visual States ===
echo ""
echo "=== VISUAL STATES ==="
if [[ -f "scripts/staging2/visual-qa-by-state.mjs" ]]; then
  if command -v agent-browser &> /dev/null; then
    run_check "Visual QA by state" "node scripts/staging2/visual-qa-by-state.mjs"
  else
    echo -e "${YELLOW}⊘${NC} Visual QA: SKIP (agent-browser not installed)"
  fi
else
  echo -e "${YELLOW}⊘${NC} Visual QA: SKIP (script not found)"
fi

# === 12. Production Parity (only for promotion) ===
echo ""
echo "=== PRODUCTION PARITY ==="
if [[ "${ENVIRONMENT}" = "promotion" ]]; then
  if [[ -f "scripts/production/verify-production-identity.mjs" ]]; then
    run_check "Production identity verification" "node scripts/production/verify-production-identity.mjs"
  else
    echo -e "${YELLOW}⊘${NC} Production Parity: SKIP (verification script not found)"
  fi
else
  echo -e "${YELLOW}⊘${NC} Production Parity: SKIP (not promotion environment)"
fi

# === Summary ===
echo ""
echo "=== SUMMARY ==="
echo "Checks passed: ${CHECKS_PASSED}"
echo "Checks failed: ${CHECKS_FAILED}"
echo ""

if [[ ${OVERALL_STATUS} -eq 0 ]]; then
  echo -e "${GREEN}✓${NC} ALL CHECKS PASSED"
  echo "Pre-merge/promotion protection: PASSED"
  exit 0
else
  echo -e "${RED}✗${NC} SOME CHECKS FAILED"
  echo "Pre-merge/promotion protection: FAILED"
  echo "Without all greens → no merge/promotion"
  exit 1
fi