# Issues for Future PRs

Documented issues from bot analysis that should be addressed in separate PRs.

## Deployment Status

**SSH Connectivity: VERIFIED** ✅

- SSH retry loops with backoff implemented in production.yml (3 SSH connection points)
- SSH retry loops with backoff implemented in staging.yml (matching production pattern)
- Successfully tested: Staging workflow completed in 7m48s with SSH connections
- Run ID 31446989846: SSH connection succeeded on first attempt (retry path not triggered)
- Configuration: ConnectTimeout 15, ConnectionAttempts 1 with 5 external retries with 15s×attempt backoff
- Note: Retry path was not exercised in test run because initial SSH connection succeeded

**Staging Acceptance: CLEAN VERIFICATION** ✅

- Run ID 31446989846: 159/159 PASS, 0 FIX, 0 BLOCKED
- Block C acceptance artifact: `staging2-block-c-2757a2ee99e43cd142a574953a2c6dd24936af5f`
- Production acceptance requires an exact candidate-named artifact that exists and is not expired.
- The source Staging run must have `status=completed`, `conclusion=success`, `head_branch=master`, and canonical workflow path `.github/workflows/staging.yml` (optionally followed by GitHub's `@<ref>` suffix).
- The live Staging `.nvx-deploy-sha`, artifact candidate SHA, and Production candidate SHA must match exactly.
- For push-triggered Staging evidence, `run.head_sha` must also equal the candidate SHA.
- For a supported manual `workflow_dispatch` of an older master-contained SHA, GitHub records the dispatch ref tip as `run.head_sha`; in that case the candidate must be contained in that dispatch head and both commits must remain in the accepted master lineage.
- Failed Staging runs are never production-eligible, even if they uploaded diagnostic artifacts.

**SiteGround IP Blocking: MITIGATED VIA RETRY PATTERN** ⚠️

- SiteGround connectivity from GitHub-hosted runners has shown intermittent SSH failures.
- Self-hosted runner is not viable in the tested SiteGround environment due to the observed `File size limit exceeded` failure and shared-hosting constraints.
- Current solution: bounded SSH retries with backoff mitigate intermittent transport failures while preserving GitHub-hosted runners.

## Critical Issues

### 1. CSS Deferral Without Fallback - High Priority

**File:** `wp-content/themes/nuvanx-medical/functions.php:164-178`
**Issue:** Stylesheets enqueued with `media='print'` and only made visible via JavaScript onload handling.
**Impact:** Site can render without those styles if JavaScript/onload does not execute.
**Fix:** Add a non-JavaScript fallback or change the deferral strategy.

### 2. Accessibility: Valoración Fallback ARIA/H1 - High Priority

**File:** `wp-content/themes/nuvanx-medical/templates/page-landing-valoracion.php`
**Issue:** The fallback template branch references `aria-labelledby="nvx-valoracion-hero-title"` without rendering that ID. The canonical managed renderer does render its H1, so this is a latent fallback accessibility defect rather than a confirmed missing-H1 defect on the canonical live route.
**Impact:** If the fallback branch is rendered, assistive technology receives a broken label reference.
**Fix:** Restore a matching heading/id in the fallback branch or use an appropriate `aria-label`.

## Medium Priority Issues

### 3. SEO Titles Conflict

**Files:**

- `wp-content/themes/nuvanx-medical/inc/nvx-contacto-valoracion-page.php:596-622`
- `wp-content/themes/nuvanx-medical/inc/nvx-valoracion-managed-page.php:141-148`

**Issue:** PHP filters hardcode titles alongside the routes/SEO metadata catalog model.
**Impact:** Multiple sources of truth create maintenance drift even where filter priority currently determines the winner.
**Fix:** Reconcile metadata ownership route by route before removing filters.

### 4. Blog Metadata Keys Incorrect

**File:** `wp-content/themes/nuvanx-medical/inc/data/seo-blog-post-metadata.json:29-44`
**Issue:** Keys may not match published WordPress `post_name` values.
**Impact:** Metadata can fail to apply to the intended posts.
**Fix:** Verify each key against live/current WordPress slugs before renaming.

### 5. Structured Data Collision

**File:** `wp-content/themes/nuvanx-medical/inc/nvx-structured-data.php:1544-1555`
**Issue:** Custom WebSite node may collide with Yoast's WebSite `@id`.
**Impact:** Custom values can be deduplicated or overridden.
**Fix:** Verify emitted graph and mutate the existing canonical node where appropriate.

### 6. Hardcoded Production URLs in Staging Scripts

**Files:**

- `scripts/staging2/verify-all-pages.mjs:3-12`
- `scripts/staging2/verify-critical-pages.mjs`

**Issue:** Scripts under `scripts/staging2/` contain production URLs.
**Impact:** Manual execution can target production unexpectedly.
**Fix:** Parameterize base URL and enforce the intended host at invocation.

## Low Priority / Info Issues

### 7. Clinics Hub Navigation Change

**File:** `wp-content/themes/nuvanx-medical/inc/nvx-clinics-hub.php:1069-1071`
**Issue:** In-page navigation was replaced by full-page links while anchors remain.
**Impact:** The hub's jump-navigation behavior changed.
**Fix:** Verify intended UX and either document or restore in-page navigation.

### 8. Template Include Safety

**File:** `wp-content/themes/nuvanx-medical/page-casos-de-pacientes.php`
**Issue:** `require_once` behavior should be verified against the page rendering path.
**Impact:** A prior include could suppress expected rendering.
**Fix:** Confirm current call graph before changing to `require` or `get_template_part`.

### 9. SSH Alias Allowlist

**File:** `scripts/staging2/verify-staging-boundary.mjs`
**Issue:** Verify whether all allowed SSH aliases are still produced by canonical workflows.
**Impact:** Stale aliases obscure the supported deployment paths.
**Fix:** Remove only aliases proven unused by current workflows.

### 10. SEO/Ads Workflows Orphaned

**Issue:** Legacy SEO/Ads workflows were removed while some supporting scripts remain.
**Impact:** Scheduled coverage may have been reduced while unused tooling remains.
**Fix:** Reconcile remaining scripts with the two-workflow architecture before deleting or restoring anything.

### 11. WP-Config Mutation Without Backup

**File:** `.github/workflows/staging.yml`
**Issue:** Runtime normalization can mutate `wp-config.php`; backup/syntax validation should be verified against the rollback snapshot sequence.
**Impact:** A malformed config mutation could complicate recovery.
**Fix:** Ensure backup precedes mutation or add a dedicated guarded config backup plus PHP validation.

### 12. Release Candidate File Behavior

**File:** `release/production-candidate.txt`
**Issue:** The file is an authorization/trigger signal while Production resolves the deploy candidate from the locked live Staging marker.
**Impact:** Operators may incorrectly assume editing the file alone selects the deployed SHA.
**Fix:** Decide whether to assert file content equals the resolved Staging marker or explicitly retain/document trigger-only semantics.

## Resolved in Current Pipeline Hardening

### Lighthouse Failure Handling - Resolved

**File:** `.github/workflows/production.yml`

Failed Lighthouse executions and corrupt/non-numeric reports are no longer represented as synthetic zero metrics. The matrix records per-page/mode failures as explicit failure evidence, continues collecting the remaining combinations, uploads the complete/partial evidence set, and fails once after the matrix if any combination is invalid.

Remaining performance work is separate: URL parameterization/coverage changes should be handled independently from evidence-validity semantics.

## Recommended PR Order

1. **PR 1 - CSS Deferral & Accessibility** (High priority, user experience impact)
2. **PR 2 - SEO/Structured Data** (Medium priority, SEO impact)
3. **PR 3 - Script Configuration** (Medium priority, tooling safety)
4. **PR 4 - Documentation & Minor Fixes** (Low priority, cleanup)
