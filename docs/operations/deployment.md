# Deployment operations

Mutating deploy scripts require `--confirm` or `NUVANX_CONFIRM=yes`.

## Identity and invariants

- Canonical branch: `master`
- Deployment identity: full lowercase 40-character Git SHA
- Staging2: `https://staging2.nuvanx.com`
- Production: `https://nuvanx.com`
- Live theme marker: `wp-content/themes/nuvanx-medical/.nvx-deploy-sha`

Branch names and tags select source or release-control intent. They are not proof of what is live; the environment marker and validation evidence are authoritative.

## Persistent workflow model

The active deployment path is:

1. `.github/workflows/staging2-sync.yml` — on relevant pushes to `master`, normalizes Staging2, calls the exact-SHA deploy and then canonical acceptance.
2. `.github/workflows/deploy-staging2.yml` — reusable exact-SHA Staging2 deployment with rollback snapshot, isolation checks and Block A boundary verification.
3. `.github/workflows/staging2-acceptance.yml` — reusable exact-SHA Block C acceptance over the trusted live published-page inventory.
4. `.github/workflows/production-release.yml` — release-control entrypoint triggered only by pushes to `release/production`; reads `release/production-candidate.txt`.
5. `.github/workflows/deploy.yml` — reusable Block B production gate and atomic deploy; the mutation job runs only when a trusted caller supplies `authorize_production: true`.
6. `.github/workflows/production-seo-geo-audit.yml` and `.github/workflows/indexnow-submit.yml` — post-release production validation/discovery workflows.

A relevant push to `master` can mutate **Staging2 only**. It never authorizes production. Production promotion requires the separate `release/production` control path and an exact accepted candidate SHA.

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
scripts/staging2/block-c-entrypoint.mjs
scripts/staging2/valoracion-placement.mjs
```

The canonical baseline requires at least 52 published pages. The runtime inventory is dynamic: every trusted published WordPress page is tested at three viewports, so additional pages are included automatically. The legacy internal filename `block-c-52x3.mjs` remains an implementation detail and is not a fixed-size acceptance contract.

The exact-SHA evidence artifact is named:

```text
staging2-block-c-<sha>
```

For local/manual execution, install dependencies in `scripts/staging2/`, then execute the resilient entrypoint from the repository root so retries and artifact paths remain canonical:

```bash
cd scripts/staging2
npm ci --ignore-scripts
npx playwright install chromium
cd ../..
EXPECTED_SHA=<40-char-sha> BASE_URL=https://staging2.nuvanx.com node scripts/staging2/block-c-entrypoint.mjs
EXPECTED_SHA=<40-char-sha> BASE_URL=https://staging2.nuvanx.com node scripts/staging2/valoracion-placement.mjs
```

## Production readiness and deployment

`.github/workflows/deploy.yml` verifies:

- candidate is an exact full SHA contained in `master`;
- a successful, non-expired Block C artifact exists for that exact SHA;
- Staging2 theme payload is byte-equivalent to the accepted candidate theme tree;
- production identity and hardened deploy tooling pass read-only guards.

Its production mutation job is conditional on `authorize_production`. The trusted `.github/workflows/production-release.yml` caller supplies `authorize_production: true` only after resolving the exact SHA from the `release/production` candidate manifest. A change to `master` alone cannot trigger production mutation.

The authorized production job checks out hardened tooling from `master`, checks out the exact candidate payload separately, performs strict SSH/preflight checks, uploads the accepted theme, executes the guarded directory cutover, verifies the public production boundary and confirms the exact SHA on disk.

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

Operational evidence belongs in GitHub Actions artifacts or Git history, not as permanent root-level audit dumps. `vendor/`, `composer.phar`, `.vscode/`, generated audit output, one-shot workflows and self-mutating workflows are prohibited by `.github/workflows/workflow-hygiene.yml`. The same workflow also runs Gitleaks against full Git history on `master` and manual security validation.

## Release record

For every release retain the exact SHA, acceptance run/artifact, environment identity, backup/rollback target and production validation evidence. Never store secret values in repository documents or HTML.
