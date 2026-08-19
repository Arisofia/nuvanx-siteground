# Issues for Future PRs

Documented issues from bot analysis that should be addressed in separate PRs.

## Operational Context

**SiteGround connectivity is mitigated via bounded retry.**

- GitHub-hosted runner → SiteGround SSH has shown intermittent failures but is demonstrably functional.
- Self-hosted runner is not viable in the tested SiteGround environment due to the observed `File size limit exceeded` failure and shared-hosting constraints.
- Current architecture keeps GitHub-hosted runners, strict host-key verification, and bounded external retries.

## Low Priority Issues

### 1. SEO Metadata Architecture Cleanup

**Status:** LOW PRIORITY - System is functional, architectural cleanup needed

**Files:**

- `wp-content/themes/nuvanx-medical/inc/nvx-contacto-valoracion-page.php` (priority 21 filters)
- `wp-content/themes/nuvanx-medical/inc/nvx-seo-metadata.php` (priority 100 filters)

**Current State:**
- `nvx-seo-metadata.php` implements centralized catalog-based metadata with priority 100
- `nvx-contacto-valoracion-page.php` has legacy hardcoded filters for valoración/contacto with priority 21
- The centralized system correctly overrides legacy filters due to higher priority
- Both systems are currently functional, but architectural duplication exists

**Impact:** Minimal functional impact, but creates maintenance complexity with dual metadata systems

**Recommended Action:** Phase out legacy hardcoded filters in `nvx-contacto-valoracion-page.php` after ensuring all valoración/contacto metadata is properly catalogued in the centralized system

**Note:** This is a technical debt cleanup item, not a blocking issue for current operations
