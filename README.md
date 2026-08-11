# NUVANX SiteGround

Canonical source repository for the NUVANX WordPress theme and the operational tooling used to validate and deploy it to SiteGround.

## Source of truth

- Canonical source branch: `master`
- Theme: `wp-content/themes/nuvanx-medical/`
- Required MU plugin source: `wp-content/mu-plugins/`
- Staging2: `https://staging2.nuvanx.com`
- Production: `https://nuvanx.com`
- Deployment identity: exact 40-character Git SHA stored in `.nvx-deploy-sha`

Git history is the archive for retired audits, diagnostics and incident-era implementation details. They are intentionally not kept in the active tree.

## Permanent GitHub Actions

Exactly two workflows are persistent:

| Workflow | Purpose |
|---|---|
| `staging.yml` | Repository/static quality, exact-SHA Staging2 deployment, full rollback snapshot, environment isolation, public/template validation, canonical Block C acceptance, valoración placement and guarded same-repo PR preview. |
| `production.yml` | Resolve the exact live-and-accepted Staging2 SHA, enforce production readiness, perform guarded atomic production deployment, verify the exact public/on-disk SHA, run SEO/GEO + IndexNow, with optional Lighthouse and live HubSpot E2E. |

Both environment-mutating paths share the `nuvanx-environment-mutation` concurrency group, so Staging2 cannot advance while Production is resolving and promoting its accepted payload.

Relevant pushes to `master` can automatically deploy **Staging2 only** through `staging.yml`. They never deploy production.

Production can be launched manually. The `release/production` branch remains an explicit release-control path, but only changes to `release/production-candidate.txt` trigger `production.yml`. That file is an authorization signal, not the payload source: Production resolves the SHA actually deployed on Staging2 and requires successful exact-SHA acceptance evidence before mutation.

The repository hygiene gate inside `staging.yml` rejects any future `.github/workflows` state other than `production.yml` plus `staging.yml`.

## Canonical validation

The canonical browser acceptance entrypoint is:

- `scripts/staging2/block-c-entrypoint.mjs`
- `scripts/staging2/valoracion-placement.mjs`
- `scripts/staging2/verify-staging-boundary.mjs`
- `scripts/validate-page-templates.mjs`

Block C requires every path in the canonical published-page manifest to be present in the trusted WordPress inventory. Every published WordPress page returned by that inventory is validated at desktop, tablet and mobile; additional published pages therefore increase the total test count automatically.

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

Mutating scripts require their explicit confirmation guard. Production deployment is never inferred from a `master` push or from an unvalidated staging state.

## Dependency policy

- Node browser dependencies are scoped to `scripts/staging2/`; there is no root Node package.
- PHP development dependencies are declared by `wp-content/themes/nuvanx-medical/composer.json` and restored with Composer in CI.
- `vendor/`, `composer.phar`, local editor settings, generated audits and QA artifacts are not source code and must not be committed.

## Documentation

- Architecture: `docs/architecture.md`
- Deployment/runbook: `docs/operations/deployment.md`
- Global document governance: `docs/operations/global-document-governance.md`
- Security policy: `SECURITY.md`
