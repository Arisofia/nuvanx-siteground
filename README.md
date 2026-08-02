# NUVANX repository

Canonical source for the NUVANX WordPress site on SiteGround.

## Structure

| Path | Role |
|------|------|
| `wp-content/themes/nuvanx-medical/` | Canonical production theme |
| `wp-content/mu-plugins/` | Required NUVANX must-use plugins |
| `tools/deploy/` | Host-level deploy and cache scripts |
| `scripts/staging2/` | Staging2 rendered acceptance and visual helpers |
| `.github/workflows/` | Permanent CI and deploy workflows |
| `docs/` | Architecture and operations documentation |

Only one theme is tracked: `nuvanx-medical`.

## Prerequisites & Development

- **PHP**: 8.1+ (production target running on SiteGround).
- **Node.js**: v22+ (required for running `scripts/staging2/verify-rendered-document.mjs` and visual QA scripts).
- **WP-CLI & rsync**: required on SiteGround target host for automated deployment execution.

### Local Audit & Validation Commands

```bash
# Lint theme PHP files
find wp-content/themes/nuvanx-medical/ -name "*.php" -exec php -l {} \;

# Validate staging2 script syntax
node --check scripts/staging2/verify-rendered-document.mjs
bash -n tools/deploy/deploy-to-staging2.sh

# Test CMS cleanup in dry-run mode offline
php tools/deploy/nvx-cms-content-cleanup.php --self-test
```

## Branching & Release Policy

- `master`: Main source of truth for production code. Pushing to `master` does **NOT** trigger automatic deployments; it only enables the optional SonarQube Cloud CI workflow when configured.
- Feature/Audit branches: created off `master` (e.g., `review/final-audit`) and merged via Pull Request.

## Workflows & Secrets

| Workflow | Purpose |
|----------|---------|
| **Deploy Staging2 (manual)** | Immutable SHA deploy to `staging2.nuvanx.com` + rendered acceptance |
| **Staging2 Rendered Acceptance** | Manual revalidation of a deployed SHA |
| **SonarQube Cloud CI** | Optional Sonar scan when token and flag are enabled |

### GitHub Actions Secrets Configured

- `STAGING2_SSH_HOST`
- `STAGING2_SSH_PORT`
- `STAGING2_SSH_USER`
- `STAGING2_SSH_PRIVATE_KEY`
- `STAGING2_SSH_KNOWN_HOSTS`

## Deployment Checklist

1. Merge PR into `master` and obtain the full 40-character Git commit SHA for the deployment target (`git_sha`).
2. Navigate to GitHub Actions -> **Deploy Staging2 (manual)**.
3. Select `master` branch, paste the 40-character `git_sha`, set confirmation to `DEPLOY_STAGING2`, and launch.
4. For the **Staging2 Rendered Acceptance** workflow, use the full `expected_sha` from the deployment target and verify the rendered acceptance result on `staging2.nuvanx.com`.
5. Verify the deployment by checking the HTTP status and Content-Type for `/robots.txt` and `/sitemap.xml` (including the redirect behavior from `/sitemap.xml` to `/sitemap_index.xml`).
6. Perform manual QA on `staging2.nuvanx.com`.

## Public Integrations Note

HubSpot Portal ID (`147416356`) and Form ID (`5042522a-0bc5-4381-ac3e-5aee8649b69c`) are public-facing frontend markers embedded in the theme markup by design. They are intentionally preserved in the repository and will continue to be managed in the theme layer unless a separate security decision changes that policy.

## SEO / Runtime Notes

- `/robots.txt` is expected to respond with HTTP 200 and `Content-Type: text/plain` on the runtime site; this should be verified during staging2 acceptance.
- `/sitemap.xml` is expected to redirect to `/sitemap_index.xml`; Yoast SEO generates the XML sitemap index at runtime.
- Runtime validation should include HTTP status and Content-Type checks for these endpoints, and the XML sitemap index should be verified on the deployed staging2 environment.
- The site is currently scoped to Spanish content and does not maintain a multi-language hreflang matrix in this repository.

## Documentation

- [Architecture](docs/architecture.md)
- [Deployment operations](docs/operations/deployment.md)
- [Document governance](docs/operations/global-document-governance.md)
- [Governance checklist](docs/operations/global-document-governance-checklist.md)
- [Deploy helpers](tools/deploy/README.md)

## Safety

Confirm the active WordPress installation and SiteGround environment before any host-level promotion. Production promotion is manual and host-authorized only after Staging2 rendered acceptance passes for the exact SHA.
