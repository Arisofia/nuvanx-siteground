# Issues for Future PRs

Documented issues from bot analysis that should be addressed in separate PRs.

## Operational Context

**SiteGround connectivity is mitigated via bounded retry.**

- GitHub-hosted runner → SiteGround SSH has shown intermittent failures but is demonstrably functional.
- Self-hosted runner is not viable in the tested SiteGround environment due to the observed `File size limit exceeded` failure and shared-hosting constraints.
- Current architecture keeps GitHub-hosted runners, strict host-key verification, and bounded external retries.

## Medium Priority Issues

### 1. SEO Titles Conflict

**Files:**

- `wp-content/themes/nuvanx-medical/inc/nvx-contacto-valoracion-page.php`
- `wp-content/themes/nuvanx-medical/inc/nvx-seo-metadata.php`

**Issue:** `nvx-contacto-valoracion-page.php` registers multiple hardcoded `wpseo_title` and `wpseo_metadesc` filters for contacto/valoración alongside the centralized SEO metadata owner. The contacto title is also defined twice at different priorities in the same module.
**Impact:** Multiple sources of truth create maintenance drift and make the resulting title depend on filter priority.
**Fix:** Reconcile metadata ownership route by route before removing filters.

## Recommended PR Order

1. **PR 1 - SEO metadata ownership** (reconcile hardcoded filters with the centralized metadata owner).
