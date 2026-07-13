# NUVANX repository

This workspace is being reorganized into a canonical SiteGround deployment source.

## Current structure
- [wp-content/themes/nuvanx-medical](wp-content/themes/nuvanx-medical): production theme directory currently present in the workspace.
- [wp-content/mu-plugins](wp-content/mu-plugins): site-specific mu-plugins.
- [tools/audit](tools/audit): read-only audit utilities (14 scripts, parameterized).
- [tools/deploy](tools/deploy): deployment helpers that must be reviewed before use.
- [tools/migrations](tools/migrations): migration helpers and temporary scaffolding.
- [docs](docs): operational and audit documentation.
- [archive/NUVANX-AUDIT-raw](archive/NUVANX-AUDIT-raw): historical raw audit bundle preserved as-is.

## Safety note
The historical audit bundle has been archived rather than treated as the canonical source of truth.
The production baseline should be verified from the active WordPress installation and SiteGround environment before syncing.
