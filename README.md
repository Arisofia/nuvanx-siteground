# NUVANX repository

Canonical SiteGround deployment source for the NUVANX WordPress site.

## Current structure

- [wp-content/themes/nuvanx-medical](wp-content/themes/nuvanx-medical): production theme (single source of truth).
- [wp-content/mu-plugins](wp-content/mu-plugins): site-specific must-use plugins.
- [tools/deploy](tools/deploy): deployment helpers; see [docs/operations/deployment.md](docs/operations/deployment.md).
- [docs](docs): operational documentation, navigation architecture and content strategy notes.
- [scripts](scripts): hygiene gates, staging2 verification and production-readiness commands.
- [.github/workflows](.github/workflows): Deploy Staging2, Theme Hygiene Gate and Navigation Architecture Gate.

## Repository access

Configure the canonical Git remote, GitHub CLI, and non-interactive authentication
with the GitHub access bootstrap. Credentials are
provided at runtime and are never committed to the repository.

## Safety note

The production baseline should be verified from the active WordPress installation and SiteGround environment before syncing.
Repository hygiene is validated by CI before promotion.
Only one theme is tracked: `nuvanx-medical`. No draft themes, audit archives, historical CSS snapshots, WordPress language packs (`wp-content/languages/`), or Divi/ET runtime cache (`wp-content/et-cache/`) are kept in this repository.
