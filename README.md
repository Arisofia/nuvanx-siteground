# NUVANX SiteGround

Canonical source repository for the NUVANX WordPress theme and the operational tooling used to validate and deploy it to SiteGround.

## Source of truth

- Canonical branch: `master`
- Theme: `wp-content/themes/nuvanx-medical/`
- Required MU plugin source: `wp-content/mu-plugins/`
- Staging2: `https://staging2.nuvanx.com`
- Production: `https://nuvanx.com`
- Deployment identity: exact 40-character Git SHA stored in `.nvx-deploy-sha`

Git history is the archive for retired audits, diagnostics and incident-era implementation details. They are intentionally not kept in the active tree.

## Permanent GitHub Actions

| Workflow | Purpose |
|---|---|
| `audit-production-performance.yml` | Manual Lighthouse performance audit against production. |
| `ci-quality.yml` | Manual strict PHP syntax, custom CSS/PHP lints, PHPCS and PHPStan gate. |
| `deploy-staging2.yml` | Reusable exact-SHA Staging2 deployment with isolation, rollback and Block A checks. |
| `deploy.yml` | Reusable production Block B gate and atomic deployment, executed only with explicit authorization. |
| `h1-hubspot-e2e.yml` | Manual real-submit production QA for the HubSpot valuation funnel. |
| `indexnow-submit.yml` | Post-release IndexNow submission for production URLs. |
| `production-release.yml` | Release-control caller on `release/production`; resolves the immutable candidate manifest and authorizes `deploy.yml`. |
| `production-seo-geo-audit.yml` | Post-release production SEO/GEO origin audit. |
| `staging2-acceptance.yml` | Reusable Block C acceptance over the live published-page inventory at three viewports plus valoración placement. |
| `staging2-pr-preview.yml` | Labeled same-repository PR preview with guarded Staging2 deployment, acceptance and rollback. |
| `staging2-sync.yml` | Automatically syncs relevant `master` changes to Staging2 and runs Block C. |
| `workflow-hygiene.yml` | Repository hygiene plus Gitleaks full-history security scanning. |

Relevant pushes to `master` **do automatically deploy Staging2** through `staging2-sync.yml` and then run canonical acceptance. They never deploy production. Production promotion is controlled separately by `release/production` and the immutable `release/production-candidate.txt` manifest.

## Canonical validation

The canonical browser acceptance entrypoint is:

- `scripts/staging2/block-c-entrypoint.mjs`
- `scripts/staging2/valoracion-placement.mjs`
- `scripts/staging2/verify-staging-boundary.mjs`
- `scripts/validate-page-templates.mjs`

Block C keeps a minimum canonical baseline of 52 published pages, but the runtime inventory is dynamic. Every published WordPress page returned by the trusted inventory is validated at desktop, tablet and mobile; additional published pages therefore increase the total test count automatically. The internal `block-c-52x3.mjs` filename is retained for compatibility and does not define the runtime page count.

Install browser dependencies only in the scoped package:

```bash
cd scripts/staging2
npm ci --ignore-scripts
npx playwright install chromium
```

Then run the canonical entrypoint from the repository root with `EXPECTED_SHA` set to the deployed SHA.

## Operational tooling

- `tools/deploy/deploy-to-staging2.sh`
- `tools/deploy/deploy-to-prod.sh`
- `tools/deploy/deploy-required-mu-plugins.sh`
- `tools/deploy/flush-prod-cache.sh`
- `tools/wp-cli/`

Mutating scripts require their explicit confirmation guard. Production deployment is never inferred from `master` or a staging deployment.

## Dependency policy

- Node browser dependencies are scoped to `scripts/staging2/`; there is no root Node package.
- PHP development dependencies are declared by `wp-content/themes/nuvanx-medical/composer.json` and restored with Composer in CI.
- `vendor/`, `composer.phar`, local editor settings, generated audits and QA artifacts are not source code and must not be committed.

## Documentation

- Architecture: `docs/architecture.md`
- Deployment/runbook: `docs/operations/deployment.md`
- Global document governance: `docs/global-document-governance.md`
- Security policy: `SECURITY.md`
- Historical security evidence: `docs/security/`
