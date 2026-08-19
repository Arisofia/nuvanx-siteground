# Issues for Future PRs

Documented issues from bot analysis that should be addressed in separate PRs.

## Operational Context

**SiteGround connectivity is mitigated via bounded retry.**

- GitHub-hosted runner → SiteGround SSH has shown intermittent failures but is demonstrably functional.
- Self-hosted runner is not viable in the tested SiteGround environment due to the observed `File size limit exceeded` failure and shared-hosting constraints.
- Current architecture keeps GitHub-hosted runners, strict host-key verification, and bounded external retries.

## Resolved quality debt

### SEO metadata architecture cleanup

**Status:** RESOLVED IN QUALITY CONTRACT

Canonical text metadata is owned by `wp-content/themes/nuvanx-medical/inc/nvx-seo-metadata.php` and the versioned `seo-metadata.json` catalog. `/contacto/` now has a complete catalog record, while the page-local Valoración/Contacto title/description and social text registrations are retired after module registration. Contact-specific social image and schema ownership remains separate by design.

`scripts/lint/test-seo-catalog-ownership.php` blocks future canonical routes whose `seo_id` has no complete metadata record and verifies the known legacy registrations remain retired.
