# NUVANX repository

Canonical source for the NUVANX WordPress site on SiteGround.

## Structure

| Path | Role |
|------|------|
| `wp-content/themes/nuvanx-medical/` | Canonical production theme |
| `wp-content/mu-plugins/` | Required NUVANX must-use plugins |
| `tools/deploy/` | Host-level deploy and cache scripts |
| `scripts/theme-hygiene/` | CI contract tests for theme and docs |
| `scripts/staging2/` | Staging2 rendered acceptance and visual helpers |
| `.github/workflows/` | Permanent CI and deploy workflows |
| `docs/` | Architecture and operations documentation |

Only one theme is tracked: `nuvanx-medical`.

## Workflows

| Workflow | Purpose |
|----------|---------|
| **Theme Hygiene Gate** | Lint, PHP/JS contracts, theme hygiene before merge |
| **Deploy Staging2 (manual)** | Immutable SHA deploy to `staging2.nuvanx.com` + rendered acceptance |
| **Staging2 Rendered Acceptance** | Manual revalidation of a deployed SHA |
| **SonarQube Cloud CI** | JS coverage and optional Sonar scan |

A push to `master` does not deploy. Staging2 requires an explicit workflow run with a full 40-character SHA and confirmation `DEPLOY_STAGING2`.

## Documentation

- [Architecture](docs/architecture.md)
- [Deployment operations](docs/operations/deployment.md)
- [Document governance](docs/operations/global-document-governance.md)
- [Governance checklist](docs/operations/global-document-governance-checklist.md)
- [Deploy helpers](tools/deploy/README.md)

## Safety

Confirm the active WordPress installation and SiteGround environment before any host-level promotion. Production promotion is manual and host-authorized only after Staging2 rendered acceptance passes for the exact SHA.
