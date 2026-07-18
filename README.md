# NUVANX repository

Canonical SiteGround deployment source for the NUVANX WordPress site.

## Current structure

- [wp-content/themes/nuvanx-medical](wp-content/themes/nuvanx-medical): production theme (single source of truth).
- [wp-content/mu-plugins](wp-content/mu-plugins): site-specific must-use plugins.
- [tools/audit](tools/audit): read-only audit helpers.
- [tools/deploy](tools/deploy): deployment helpers; see [docs/operations/deployment.md](docs/operations/deployment.md).
- [tools/migrations](tools/migrations): migration utilities.
- [docs](docs): operational documentation and design system notes.

## Security incidents

Dated incident reports live under [docs/security/](docs/security/). Name new reports `INCIDENT-YYYY-MM-DD.md`.

Current:

- [docs/security/INCIDENT-2026-07-15.md](docs/security/INCIDENT-2026-07-15.md) — repository secret-exposure incident; containment and remediation.

## Safety note

The production baseline should be verified from the active WordPress installation and SiteGround environment before syncing.
Repository hygiene is validated by CI before promotion.
Only one theme is tracked: `nuvanx-medical`. No draft themes, audit archives, or historical CSS snapshots are kept in this repository.
