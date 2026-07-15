# NUVANX repository

Canonical SiteGround deployment source for the NUVANX WordPress site.

## Current structure

- [wp-content/themes/nuvanx-medical](wp-content/themes/nuvanx-medical): production theme tracked in this repository.
- [wp-content/themes/nuvanx-medical-wpvibe-draft](wp-content/themes/nuvanx-medical-wpvibe-draft): tracked draft theme; **not** active in production.
- [wp-content/mu-plugins](wp-content/mu-plugins): site-specific must-use plugins.
- [tools/audit](tools/audit): 14 read-only audit scripts.
- [tools/deploy](tools/deploy): deployment helpers subject to review before use.
- [tools/migrations](tools/migrations): migration utilities.
- [docs](docs): operational documentation.
- [archive/NUVANX-AUDIT-raw](archive/NUVANX-AUDIT-raw): historical raw audit bundle preserved as-is.

## Security incident (2026-07-15)

See [docs/security/INCIDENT-2026-07-15.md](docs/security/INCIDENT-2026-07-15.md) for the repository secret-exposure incident, containment status, and open production/history remediation actions.

## Safety note

The historical audit bundle has been archived rather than treated as the canonical source of truth.
The production baseline should be verified from the active WordPress installation and SiteGround environment before syncing.
Repository hygiene is validated by CI before promotion.
