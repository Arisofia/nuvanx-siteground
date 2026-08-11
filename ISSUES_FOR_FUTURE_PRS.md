# Issues for Future PRs

Documented issues from bot analysis that should be addressed in separate PRs.

## Deployment Status

**SSH Timeout Issue: RESOLVED** ✅
- SSH retry loops with backoff implemented in production.yml
- SiteGround IP blocking persists for GitHub Actions external runners
- Self-hosted runner not viable due to SiteGround file size limits
- Current solution: SSH retry loops resolve timeout issue without architectural changes

## Critical Issues

### 1. CSS Deferral Without Fallback - High Priority
**File:** `wp-content/themes/nuvanx-medical/functions.php:164-178`
**Issue:** Stylesheets enqueued with `media='print'` and only made visible via JavaScript onerror handler
**Impact:** Site renders unstyled for visitors without JavaScript
**Fix:** Add `<noscript><link rel="stylesheet" ...></noscript>` fallback or change deferral strategy

### 2. Accessibility: Missing H1 in Valoración Landing - High Priority  
**File:** `wp-content/themes/nuvanx-medical/templates/page-landing-valoracion.php:45-47`
**Issue:** H1 deleted but `aria-labelledby="nvx-valoracion-hero-title"` still references it
**Impact:** Screen-reader users get broken label, page has no top-level heading
**Fix:** Restore H1 with correct id or change section to use aria-label

## Medium Priority Issues

### 3. SEO Titles Conflict
**Files:** 
- `wp-content/themes/nuvanx-medical/inc/nvx-contacto-valoracion-page.php:596-622`
- `wp-content/themes/nuvanx-medical/inc/nvx-valoracion-managed-page.php:141-148`
**Issue:** PHP filters hardcode titles conflicting with routes.json + seo-metadata.json model
**Impact:** Two sources of truth, whichever runs last wins
**Fix:** Add "contacto" entry to seo-metadata.json, remove PHP filters or prioritize correctly

### 4. Blog Metadata Keys Incorrect
**File:** `wp-content/themes/nuvanx-medical/inc/data/seo-blog-post-metadata.json:29-44`
**Issue:** Keys don't match published WordPress post_name values
**Impact:** Titles/descriptions never applied to actual pages
**Fix:** Rename keys to match live slugs (laser-co2-vs-radiofrecuencia-cuando-elegir, etc.)

### 5. Structured Data Collision
**File:** `wp-content/themes/nuvanx-medical/inc/nvx-structured-data.php:1544-1555`
**Issue:** WebSite node uses same @id as Yoast, gets deduplicated away
**Impact:** Custom name/description never reach output
**Fix:** Mutate existing Yoast node instead of creating duplicate

### 6. Hardcoded Production URLs in Staging Scripts
**Files:** 
- `scripts/staging2/verify-all-pages.mjs:3-12`
- `scripts/staging2/verify-critical-pages.mjs`
**Issue:** Hardcode https://nuvanx.com while living in scripts/staging2/
**Impact:** Scripts not parameterized, will hit production if run manually
**Fix:** Parameterize base URL or wire into workflow

## Low Priority / Info Issues

### 7. Clinics Hub Navigation Change
**File:** `wp-content/themes/nuvanx-medical/inc/nvx-clinics-hub.php:1069-1071`
**Issue:** In-page nav replaced by full page links, anchors orphaned
**Impact:** Hub's own jump navigation gone, external deep links still work
**Fix:** Document intended behavior or restore in-page navigation

### 8. Template Include Safety
**File:** `wp-content/themes/nuvanx-medical/page-casos-de-pacientes.php`
**Issue:** Changed require to require_once for page.php
**Impact:** Could silently render nothing if page.php included earlier
**Fix:** Use plain require or get_template_part for safety

### 9. SSH Alias Allowlist
**File:** `scripts/staging2/verify-staging-boundary.mjs`
**Issue:** Added nvx-staging-pr with no producing workflow
**Impact:** Harmless but obscures canonical alias
**Fix:** Remove unused aliases from allowlist

### 10. SEO/Ads Workflows Orphaned
**Issue:** seo-verification.yml and audit-google-ads-readonly.yml deleted but scripts remain
**Impact:** Search Console coverage and daily SEO run gone
**Fix:** Remove orphaned scripts or restore coverage in new workflows

### 11. WP-Config Mutation Without Backup
**File:** `.github/workflows/staging.yml:244-247`
**Issue:** Mutates wp-config.php without backup or syntax check
**Impact:** Corrupted wp-config.php not recoverable by workflow
**Fix:** Add backup and php -l check before mutation

### 12. Lighthouse Failure Handling
**File:** `.github/workflows/production.yml:446-459`
**Issue:** Records zeros instead of failing on unusable reports
**Impact:** Evidence can contain fabricated zeros
**Fix:** Add validation that JSON contains performance.score

### 13. Release Candidate File Behavior
**File:** `release/production-candidate.txt`
**Issue:** File triggers workflow but content never read
**Impact:** Editing file to SHA X deploys whatever is on Staging2
**Fix:** Assert file content equals resolved Staging2 marker or remove file

## Recommended PR Order

1. **PR 1 - CSS Deferral & Accessibility** (High priority, user experience impact)
2. **PR 2 - SEO/Structured Data** (Medium priority, SEO impact)  
3. **PR 3 - Script Configuration** (Medium priority, tooling safety)
4. **PR 4 - Documentation & Minor Fixes** (Low priority, cleanup)
