# Deployment operations

Mutating deploy scripts require `--confirm` or `NUVANX_CONFIRM=yes`.

## Identity and invariants

- Canonical branch: `master`
- Deployment identity: full lowercase 40-character Git SHA
- Staging2: `https://staging2.nuvanx.com`
- Production: `https://nuvanx.com`
- Live theme marker: `wp-content/themes/nuvanx-medical/.nvx-deploy-sha`

Branch names and tags select source. They are not proof of what is live.

## Persistent workflow model

The repository keeps reusable deployment gates rather than push-triggered one-shot callers:

1. `.github/workflows/deploy-staging2.yml` — `workflow_call`, exact-SHA Staging2 deployment with rollback snapshot and isolation checks.
2. `.github/workflows/staging2-acceptance.yml` — `workflow_call`, exact-SHA Block C acceptance.
3. `.github/workflows/deploy.yml` — `workflow_call`, production readiness gate. Its production mutation job remains disabled with `if: false` until explicit authorization.

A normal push to `master` does **not** deploy either environment. An authorized release orchestration must explicitly call these reusable workflows and pass the exact candidate SHA.

## Staging2 deployment

The reusable Staging2 workflow validates that the requested SHA is contained in `origin/master`, snapshots the current staging state, uploads an isolated release, runs the guarded deployment scripts, applies content hygiene, purges caches and verifies the deployed marker and environment identity.

Scope:

```text
wp-content/themes/nuvanx-medical/
wp-content/mu-plugins/   # required NUVANX MU plugins only
```

It does not copy the production database or replace the entire WordPress tree.

### Required Staging2 secrets

- `STAGING2_SSH_HOST`
- `STAGING2_SSH_PORT`
- `STAGING2_SSH_USER`
- `STAGING2_SSH_PRIVATE_KEY`
- `STAGING2_SSH_KNOWN_HOSTS`

## Canonical Staging2 acceptance

The permanent acceptance workflow validates the origin boundary from SiteGround, classifies external SiteGround AntiBot/network failures separately, validates templates, installs the scoped Playwright dependencies, and executes:

```text
scripts/staging2/block-c-52x3.mjs
scripts/staging2/valoracion-placement.mjs
```

The exact-SHA evidence artifact is named:

```text
staging2-block-c-<sha>
```

For local/manual execution, install dependencies in `scripts/staging2/`, then execute the scripts from the repository root so their artifact paths remain canonical:

```bash
cd scripts/staging2
npm ci --ignore-scripts
npx playwright install chromium
cd ../..
EXPECTED_SHA=<40-char-sha> BASE_URL=https://staging2.nuvanx.com node scripts/staging2/block-c-52x3.mjs
EXPECTED_SHA=<40-char-sha> BASE_URL=https://staging2.nuvanx.com node scripts/staging2/valoracion-placement.mjs
```

## Production readiness and deployment

`.github/workflows/deploy.yml` verifies:

- candidate is an exact full SHA contained in `master`;
- a successful, non-expired Block C artifact exists for that exact SHA;
- Staging2 disk marker equals the candidate;
- production identity and hardened deploy tooling pass read-only guards.

The production mutation job is intentionally disabled in the permanent workflow. Promotion requires an explicit authorization path; it must never occur merely because `master` changed.

Host-level emergency deployment, when explicitly authorized:

```bash
export WP_PROD=/home/customer/www/nuvanx.com/public_html
export WP_STG2=/home/customer/www/staging2.nuvanx.com/public_html

NUVANX_CONFIRM=yes bash tools/deploy/deploy-to-prod.sh \
  --prod-root "$WP_PROD" \
  --staging-root "$WP_STG2" \
  --confirm
```

Production cache flush:

```bash
NUVANX_CONFIRM=yes bash tools/deploy/flush-prod-cache.sh \
  --wp-root /home/customer/www/nuvanx.com/public_html \
  --confirm
```

## Repository hygiene

Operational evidence belongs in GitHub Actions artifacts or Git history, not as permanent root-level audit dumps. `vendor/`, `composer.phar`, `.vscode/`, `audit/`, one-shot workflows and self-mutating workflows are prohibited by `.github/workflows/workflow-hygiene.yml`.

## Release record

For every release retain the exact SHA, acceptance run/artifact, environment identity, backup/rollback target and production validation evidence. Never store secret values in repository documents or HTML.
