# Issues for Future PRs

Documented issues from bot analysis that should be addressed in separate PRs. Findings must be re-verified against current code before modification.

## Deployment Status

**Staging pipeline: VALIDATED** ✅
- Self-hosted runner references were removed; canonical workflows use GitHub-hosted runners.
- Run `31446989846` completed successfully for exact SHA `2757a2ee99e43cd142a574953a2c6dd24936af5f`.
- Block C result: 159/159 PASS, 0 FIX, 0 BLOCKED; valoración placement/interactivity PASS.
- The initial SSH connection in that run succeeded without a failed attempt, so the retry/backoff recovery path was not exercised by an actual timeout.
- Retry logic remains a resilience mechanism, not the demonstrated causal reason that this particular run succeeded.

**Production acceptance hardening: addressed in `fix/pipeline-hardening-20260811`**
- Production must accept only a completed, successful canonical Staging run.
- Artifact, run `head_sha`, live Staging marker and candidate SHA must identify the same commit.
- Failed Staging runs are not production-eligible even if they uploaded diagnostic artifacts.

## Critical Issues

### 1. CSS Deferral Without Fallback - High Priority
**File:** `wp-content/themes/nuvanx-medical/functions.php:164-178`
**Issue:** Stylesheets are enqueued with `media='print'` and switched to `all` through an `onload` handler, without a verified `<noscript>` fallback.
**Impact:** Structural styles can remain unavailable when JavaScript/onload behavior does not execute.
**Fix:** Add a valid non-JavaScript fallback or replace the deferral strategy after validating the affected handles.

### 2. Valoración Fallback Accessibility Defect - High Priority
**Files:**
- `wp-content/themes/nuvanx-medical/templates/page-landing-valoracion.php`
- `wp-content/themes/nuvanx-medical/inc/nvx-valoracion-managed-page.php`
**Issue:** The fallback template branch references `aria-labelledby="nvx-valoracion-hero-title"` without rendering that ID/H1. The canonical managed `/madrid/valoracion/` renderer does render its own H1 (`nvx-valoracion-h1`).
**Impact:** The latent fallback path has a broken accessible name relationship; this is not evidence that the current canonical live route lacks an H1.
**Fix:** Repair the fallback branch ID/heading relationship without duplicating the managed renderer H1.

## Medium Priority Issues

### 3. SEO Titles Conflict
**Files:**
- `wp-content/themes/nuvanx-medical/inc/nvx-contacto-valoracion-page.php:596-622`
- `wp-content/themes/nuvanx-medical/inc/nvx-valoracion-managed-page.php:141-148`
- `wp-content/themes/nuvanx-medical/inc/nvx-seo-metadata.php`
**Issue:** Multiple PHP filters and the JSON metadata catalog can supply titles/descriptions at different priorities.
**Impact:** Multiple sources of truth increase drift risk even where the highest-priority catalog currently wins.
**Fix:** Re-verify each route, then consolidate metadata ownership.

### 4. Blog Metadata Keys Incorrect
**File:** `wp-content/themes/nuvanx-medical/inc/data/seo-blog-post-metadata.json:29-44`
**Issue:** Reported keys may not match published WordPress `post_name` values.
**Impact:** Metadata entries can fail to apply to intended posts.
**Fix:** Compare every JSON key against current published slugs before renaming anything.

### 5. Structured Data Collision
**File:** `wp-content/themes/nuvanx-medical/inc/nvx-structured-data.php:1544-1555`
**Issue:** Reported custom `WebSite` node may use the same `@id` as the Yoast graph node.
**Impact:** Duplicate graph identities can cause custom properties to be deduplicated or ignored.
**Fix:** Verify current rendered JSON-LD, then mutate/extend the canonical node rather than emitting a conflicting duplicate.

### 6. Hardcoded Production URLs in Staging Scripts
**Files:**
- `scripts/staging2/verify-all-pages.mjs:3-12`
- `scripts/staging2/verify-critical-pages.mjs`
**Issue:** Reported staging tooling contains hardcoded `https://nuvanx.com` values.
**Impact:** Manual execution can target production unexpectedly.
**Fix:** Re-verify current code and parameterize base URL/expected host where still present.

### 7. Equipo Image Error Masking
**File:** `wp-content/themes/nuvanx-medical/inc/nvx-equipo-page.php`
**Issue:** `nvx_equipo_capture_media_if_empty()` injects `onerror="this.style.display='none'"` into captured images.
**Impact:** A failed asset request can be hidden visually without correcting the underlying asset URL or transport failure.
**Current evidence:** The latest Block C run passed `/equipo-medico/` in desktop, tablet and mobile, so the earlier network error is not currently reproduced. That does not prove the `onerror` handler repaired the root cause.
**Fix:** Identify and validate the real portrait source first; remove the masking handler once the source is proven reliable.

## Low Priority / Info Issues

### 8. Clinics Hub Navigation Change
**File:** `wp-content/themes/nuvanx-medical/inc/nvx-clinics-hub.php:1069-1071`
**Issue:** In-page navigation was reported as replaced by full-page links, leaving anchors unused.
**Impact:** Hub jump-navigation behavior may have changed.
**Fix:** Verify intended UX before restoring or removing anchors.

### 9. Template Include Safety
**File:** `wp-content/themes/nuvanx-medical/page-casos-de-pacientes.php`
**Issue:** Reported change from `require` to `require_once` for `page.php`.
**Impact:** Could suppress rendering if the template was already included in the same request.
**Fix:** Verify the current call path before changing include semantics.

### 10. SSH Alias Allowlist
**File:** `scripts/staging2/verify-staging-boundary.mjs`
**Issue:** Reported unused `nvx-staging-pr`/preview alias variants should be checked against the current canonical PR-preview workflow.
**Impact:** Stale aliases obscure the real execution contract.
**Fix:** Remove only aliases with no current producer/consumer.

### 11. SEO/Ads Workflows Orphaned
**Issue:** `seo-verification.yml` and `audit-google-ads-readonly.yml` were removed while related scripts may remain.
**Impact:** Scheduled/automatic coverage may have disappeared even though tooling still exists.
**Fix:** Map each remaining script to one of the two canonical workflows or remove genuinely orphaned code.

### 12. WP-Config Mutation Without Backup
**File:** `.github/workflows/staging.yml`
**Issue:** Staging normalization can mutate `wp-config.php` (`WP_DEBUG_DISPLAY`) without an explicit pre-mutation `wp-config.php` backup and PHP syntax validation.
**Impact:** A failed mutation could leave configuration damaged outside the theme/DB rollback snapshot.
**Fix:** Add a guarded backup and `php -l` validation around any configuration mutation.

### 13. Lighthouse Failure Handling
**File:** `.github/workflows/production.yml`
**Issue:** Failed/unusable Lighthouse reports are represented with zero metric values rather than making evidence validity explicit.
**Impact:** Zero values can be mistaken for measured performance data.
**Fix:** Validate report structure and distinguish `FAILED`/missing metrics from numeric measurements.

### 14. Release Candidate File Behavior
**File:** `release/production-candidate.txt`
**Issue:** The file triggers the release workflow but the deployment candidate is resolved from the live Staging marker; the file content itself is not currently the source of truth.
**Impact:** Editing the file to SHA X can be misleading if Staging currently exposes SHA Y.
**Fix:** Either assert file content equals the live accepted Staging SHA or remove the file as an authorization signal and use an explicit release mechanism.

## Recommended PR Order

1. **Pipeline hardening** — strict successful exact-SHA Production acceptance; bounded observable SSH retries.
2. **CSS deferral & fallback accessibility** — user-facing resilience/accessibility.
3. **SEO/structured data/blog metadata** — consolidate sources of truth after live verification.
4. **Script configuration and evidence validity** — staging URL parameterization, Lighthouse evidence, candidate authorization semantics.
5. **Documentation/minor cleanup** — navigation, include semantics, stale aliases/orphaned tooling after verification.
