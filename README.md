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
| `ci-quality.yml` | Manual PHP syntax, custom lint, secret-history, PHPCS and PHPStan checks |
| `security-gate.yml` | Manual Gitleaks security gate |
| `deploy-staging2.yml` | Reusable exact-SHA Staging2 deployment (`workflow_call`) |
| `staging2-acceptance.yml` | Reusable canonical Block C acceptance (`workflow_call`) |
| `deploy.yml` | Reusable production readiness gate; production mutation remains explicitly disabled until authorized |
| `workflow-hygiene.yml` | Repository hygiene and anti-self-mutation gate on PRs and `master` |

A push to `master` does **not** automatically deploy Staging2 or production. Release orchestration must explicitly call the reusable deployment workflows with an exact accepted SHA.

## Canonical validation

Staging browser acceptance is owned by:

- `scripts/staging2/block-c-52x3.mjs`
- `scripts/staging2/valoracion-placement.mjs`
- `scripts/staging2/verify-staging-boundary.mjs`
- `scripts/validate-page-templates.mjs`

Install browser dependencies only in the scoped package:

```bash
cd scripts/staging2
npm ci --ignore-scripts
npx playwright install chromium
```

Then run the canonical harness from the repository root with `EXPECTED_SHA` set to the deployed SHA.

## Operational tooling

- `tools/deploy/deploy-to-staging2.sh`
- `tools/deploy/deploy-to-prod.sh`
- `tools/deploy/deploy-required-mu-plugins.sh`
- `tools/deploy/flush-prod-cache.sh`
- `tools/wp-cli/`

Mutating scripts require their explicit confirmation guard. Production deployment is never inferred from branch name alone.

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
