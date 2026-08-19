#!/usr/bin/env bash
set -Eeuo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
fail() { echo "RELEASE_REGRESSION_CONTRACT=FAIL reason=$1" >&2; exit 1; }
assertion_count=0
pass_assert() {
  assertion_count=$((assertion_count + 1))
  echo "RELEASE_REGRESSION_ASSERT=PASS name=$1"
}

BRIDAL="$ROOT/wp-content/themes/nuvanx-medical/inc/nvx-catalog-json.php"
IDENTITY_CONTRACT="$ROOT/scripts/production/test-deploy-identity-contract.mjs"
DEPLOY="$ROOT/tools/deploy/deploy-to-prod.sh"
WORKFLOW="$ROOT/.github/workflows/production.yml"
BOUNDARY="$ROOT/scripts/production/verify-production-boundary.mjs"
ENV_FLAGS="$ROOT/wp-content/themes/nuvanx-medical/inc/nvx-environment-flags.php"
DEPLOY_STAMP="$ROOT/wp-content/themes/nuvanx-medical/inc/nvx-deploy-stamp.php"
LCP_CSS_CONTRACT="$ROOT/scripts/lint/test-lcp-css-delivery.mjs"
SEO_OWNERSHIP_CONTRACT="$ROOT/scripts/lint/test-seo-catalog-ownership.php"
DOCUMENT_BUFFER_GOVERNANCE="$ROOT/wp-content/themes/nuvanx-medical/inc/nvx-document-buffer-governance.php"
GTM_CONTEXT="$ROOT/wp-content/themes/nuvanx-medical/inc/nvx-gtm-integration.php"
SEO_TOOLING_DIR="$ROOT/scripts/seo"
THEME_DIR="$ROOT/wp-content/themes/nuvanx-medical"

for required in "$BRIDAL" "$IDENTITY_CONTRACT" "$DEPLOY" "$WORKFLOW" "$BOUNDARY" "$ENV_FLAGS" "$DEPLOY_STAMP" "$LCP_CSS_CONTRACT" "$SEO_OWNERSHIP_CONTRACT" "$DOCUMENT_BUFFER_GOVERNANCE" "$GTM_CONTEXT" "$SEO_TOOLING_DIR/package-lock.json" "$THEME_DIR/composer.lock"; do
  [[ -s "$required" ]] || fail "missing_file:$required"
done

# Bridal retirement must remain an AND condition. This assertion is textual
# because the source depends on WordPress runtime state, but it tolerates
# formatting changes and produces an explicit diagnostic.
grep -Eq '\$is_seed[[:space:]]*=[[:space:]]*\$has_meta_key[[:space:]]*&&[[:space:]]*\$has_seed_marker' "$BRIDAL" \
  || fail 'bridal_seed_requires_meta_and_marker'
if grep -Eq '\$is_seed[[:space:]]*=[[:space:]]*\$has_meta_key[[:space:]]*\|\|[[:space:]]*\$has_seed_marker' "$BRIDAL"; then
  fail 'bridal_seed_or_logic_forbidden'
fi
pass_assert 'bridal-seed-and-contract'

node "$IDENTITY_CONTRACT" || fail 'deploy_identity_behavior'
pass_assert 'deploy-identity-behavior'

# Production deploys must refuse anonymous/manual identity before cutover.
grep -Eq 'DEPLOY_RUN_ID=.*GITHUB_RUN_ID' "$DEPLOY" || fail 'deploy_run_id_not_sourced_from_github'
grep -Eq 'DEPLOY_RUN_ID.*\^\[0-9\].*\$' "$DEPLOY" || fail 'deploy_run_id_numeric_guard_missing'
! grep -Eq 'DEPLOY_RUN_ID=.*manual' "$DEPLOY" || fail 'manual_deploy_identity_still_allowed'
pass_assert 'deploy-run-id-enforcement'

# Validate semantic workflow wiring without depending on step display names or
# environment assignment ordering.
grep -Eq 'EXPECTED_RUN_ID=.*GITHUB_RUN_ID' "$WORKFLOW" || fail 'release_expected_run_id_not_wired'
grep -Fq 'ORIGIN_SSH_ALIAS=nvx-prod-audit' "$WORKFLOW" || fail 'audit_origin_alias_not_wired'
grep -Fq 'ORIGIN_SSH_ALIAS=nvx-prod-hubspot' "$WORKFLOW" || fail 'hubspot_origin_alias_not_wired'
grep -Fq 'steps.production_identity.outcome' "$WORKFLOW" || fail 'identity_failure_not_compensated'
pass_assert 'workflow-identity-wiring'

# Boundary must use the shared semantic parser/validator and explicit run ID.
grep -Fq "from './deploy-identity-contract.mjs'" "$BOUNDARY" || fail 'boundary_shared_contract_missing'
grep -Fq "process.env.EXPECTED_RUN_ID || ''" "$BOUNDARY" || fail 'boundary_expected_run_id_not_explicit'
! grep -Fq 'process.env.EXPECTED_RUN_ID || process.env.GITHUB_RUN_ID' "$BOUNDARY" || fail 'boundary_current_audit_run_fallback_forbidden'
pass_assert 'boundary-identity-semantics'

# Shell-local variables inside the origin String.raw script must not use
# JavaScript template interpolation syntax. Dynamic values used in ERE matches
# must be escaped before interpolation so regex metacharacters stay literal.
! grep -Fq '${name}' "$BOUNDARY" || fail 'boundary_shell_name_js_interpolation_forbidden'
! grep -Fq '${expected}' "$BOUNDARY" || fail 'boundary_shell_expected_js_interpolation_forbidden'
grep -Fq 'escape_ere()' "$BOUNDARY" || fail 'boundary_ere_escape_helper_missing'
grep -Fq 'name_re="$(escape_ere "$name")"' "$BOUNDARY" || fail 'boundary_name_regex_escape_missing'
grep -Fq 'expected_re="$(escape_ere "$expected")"' "$BOUNDARY" || fail 'boundary_expected_regex_escape_missing'
grep -Fq '$name_re' "$BOUNDARY" || fail 'boundary_escaped_name_reference_missing'
grep -Fq '$expected_re' "$BOUNDARY" || fail 'boundary_escaped_expected_reference_missing'
pass_assert 'boundary-shell-local-interpolation'

# Public deploy identity has exactly one wp_head owner. Staging only writes the
# legacy `.nvx-deploy-sha` file, so the canonical stamp renderer must fall back
# to nvx_environment_deploy_sha() while the environment module remains resolver-only.
! grep -Fq "add_action( 'wp_head', 'nvx_environment_render_deploy_sha'" "$ENV_FLAGS" || fail 'legacy_deploy_sha_head_emitter_forbidden'
! grep -Fq 'function nvx_environment_render_deploy_sha' "$ENV_FLAGS" || fail 'legacy_deploy_sha_renderer_forbidden'
grep -Fq "function_exists( 'nvx_environment_deploy_sha' )" "$DEPLOY_STAMP" || fail 'deploy_stamp_environment_fallback_guard_missing'
grep -Fq "nvx_environment_deploy_sha()" "$DEPLOY_STAMP" || fail 'deploy_stamp_environment_fallback_missing'
grep -Fq "add_action( 'wp_head', 'nvx_render_deploy_stamp_meta', 1 );" "$DEPLOY_STAMP" || fail 'canonical_deploy_stamp_head_owner_missing'
pass_assert 'single-deploy-sha-head-owner'

# The theme must not own a full-document rewrite. nvx-integrations.php still
# contains the historical callback for compatibility while this hotfix is
# intentionally surgical; it must be retired immediately after integrations
# load and before template_redirect executes. Reflection narrows removal to the
# closure defined by nvx-integrations.php at priority 999999, leaving plugin
# buffers and all other callbacks intact.
grep -Fq "require_once __DIR__ . '/nvx-document-buffer-governance.php';" "$GTM_CONTEXT" || fail 'document_buffer_governance_not_loaded'
grep -Fq '\$hook->callbacks[999999]' "$DOCUMENT_BUFFER_GOVERNANCE" || fail 'document_buffer_priority_not_scoped'
grep -Fq "__DIR__ . '/nvx-integrations.php'" "$DOCUMENT_BUFFER_GOVERNANCE" || fail 'document_buffer_source_not_scoped'
grep -Fq 'new ReflectionFunction' "$DOCUMENT_BUFFER_GOVERNANCE" || fail 'document_buffer_callback_identity_not_verified'
grep -Fq "\$hook->remove_filter( 'template_redirect', \$callback, 999999 );" "$DOCUMENT_BUFFER_GOVERNANCE" || fail 'document_buffer_callback_not_retired'
pass_assert 'no-theme-full-document-rewrite'

# LCP delivery rules are part of the release contract, not an optional lint.
# The canonical test protects the inlined foundation, blocking structural CSS,
# non-blocking Google Fonts, and the narrow editorial-only defer boundary.
node "$LCP_CSS_CONTRACT" || fail 'lcp_css_delivery_contract'
pass_assert 'lcp-css-delivery'

# Keep routed SEO metadata complete and enforce one text-metadata owner.
php "$SEO_OWNERSHIP_CONTRACT" || fail 'seo_catalog_ownership_contract'
pass_assert 'seo-catalog-ownership'

# scripts/seo remains an independent support package. CI validates syntax only;
# no credentialed Google/GTM publisher or diagnostic is executed automatically.
while IFS= read -r -d '' seo_script; do
  node --check "$seo_script" >/dev/null || fail "seo_script_syntax:$seo_script"
done < <(find "$SEO_TOOLING_DIR" -maxdepth 1 -type f -name '*.js' -print0)
pass_assert 'seo-tooling-syntax'

# The canonical weekly schedule already executes this release contract. Audit
# the two lockfiles that actually carry dependencies without creating a third
# workflow or adding registry-sensitive audits to every pull request.
if [[ "${GITHUB_EVENT_NAME:-}" == 'schedule' ]]; then
  (
    cd "$SEO_TOOLING_DIR"
    npm audit --audit-level=high
  ) || fail 'weekly_seo_npm_audit'
  (
    cd "$THEME_DIR"
    composer audit --locked --format=summary
  ) || fail 'weekly_theme_composer_audit'
  pass_assert 'weekly-dependency-security-audit'
fi

echo "RELEASE_REGRESSION_CONTRACT=PASS assertions=$assertion_count"
