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

# Run CSS/PHP linting
node scripts/lint/no-hardcoded-colors.mjs
node scripts/lint/no-hardcoded-fontsize.mjs
node scripts/lint/no-inline-layout-styles.mjs
```

## Branching & Release Policy

- `master`: Main source of truth for production code. A push to `master` triggers the Staging2 deployment workflow; it does **not** promote code to production.
- Pull requests: the Staging2 workflow validates the deployment contract, while the actual deployment job runs only for `master`.
- Production promotion remains manual and host-authorized only after Staging2 rendered acceptance passes for the exact SHA.

## Workflows & Secrets

| Workflow | Purpose |
|----------|---------|
| **Code Quality (Lint)** | PHP syntax, PHPCS, PHPStan, and custom CSS/PHP linting |
| **Deploy Staging2** | Validates deployment contracts on pull requests and deploys an immutable SHA to `staging2.nuvanx.com` on `master` |
| **Deploy** | Production deployment with E2E Playwright testing and atomic SiteGround sync |

### GitHub Actions Secrets Configured

- `STAGING2_SSH_HOST`
- `STAGING2_SSH_PORT`
- `STAGING2_SSH_USER`
- `STAGING2_SSH_PRIVATE_KEY`
- `STAGING2_SSH_KNOWN_HOSTS`

## Deployment Checklist

1. Merge the validated change into `master` and record the resulting full 40-character Git commit SHA.
2. Allow **Deploy Staging2** to validate the deployment contract and deploy that immutable SHA to `staging2.nuvanx.com`.
3. Confirm that **Staging2 Rendered Acceptance** completes successfully for the exact deployed SHA.
4. Verify the deployment by checking the HTTP status and Content-Type for `/robots.txt` and `/sitemap.xml` (including the redirect behavior from `/sitemap.xml` to `/sitemap_index.xml`).
5. Perform manual QA on `staging2.nuvanx.com`.
6. Only after Staging2 acceptance and manual QA pass, consider the separate manual production promotion process.

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
- [Design Guide](DESIGN_GUIDE.md) - Sistema de diseño unificado y principios de consistencia visual

## Safety

Confirm the active WordPress installation and SiteGround environment before any host-level promotion. Production promotion is manual and host-authorized only after Staging2 rendered acceptance passes for the exact SHA.
