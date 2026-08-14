# Deployment operations

Mutating deploy scripts require `--confirm` or `NUVANX_CONFIRM=yes`.

## Identity and invariants

- Canonical source branch: `master`
- Deployment identity: full lowercase 40-character Git SHA
- Staging2: `https://staging2.nuvanx.com`
- Production: `https://nuvanx.com`
- Live theme marker: `wp-content/themes/nuvanx-medical/.nvx-deploy-sha`
- Persistent GitHub Actions workflows: exactly **two**
- Cross-environment mutation lock: `nuvanx-environment-mutation`

Branch names, tags and release-control files express intent. They are not proof of what is live. The environment marker plus successful validation evidence are authoritative.

## Canonical workflow model

Only these workflows are persistent:

1. `.github/workflows/staging.yml`
2. `.github/workflows/production.yml`

The same two workflow blobs are kept on `master` and `release/production`. Repository hygiene inside `staging.yml` rejects any future third workflow.

### Staging

`staging.yml` owns the complete Staging2 lifecycle in one workflow:

- static repository, PHP, JavaScript and design-system gates;
- exact-SHA Staging2 deployment from `master`;
- strict environment-isolation checks;
- rollback snapshot of theme, required MU plugins and Staging2 database;
- required MU-plugin and content-hygiene deployment;
- cache purge and exact `.nvx-deploy-sha` verification;
- public Staging2 boundary validation;
- WordPress template validation;
- canonical Block C browser acceptance and valoración-placement validation;
- read-only proof that production remained unchanged;
- automatic full Staging2 rollback after a failed mutation;
- same-repository, label-gated PR preview using trusted `master` tooling.

A relevant push to `master` can mutate **Staging2 only**. It never authorizes production.

The production-eligible Staging2 evidence artifact is:

```text
staging2-block-c-<sha>
```

The runtime acceptance inventory is dynamic and validates every trusted published WordPress page at the configured viewports. Canonical manifest membership is enforced before the browser matrix runs.

### Production

`production.yml` owns the complete production lifecycle in one workflow:

- read-only Staging2 and production identity gate;
- resolution of the SHA **actually deployed on Staging2** from `.nvx-deploy-sha`;
- verification that the live Staging2 SHA is contained in `origin/master`;
- verification of a successful, non-expired exact-SHA `staging2-block-c-<sha>` artifact from `master`;
- exact candidate materialization from Git history;
- strict production SSH/preflight checks;
- guarded atomic production cutover through `tools/deploy/deploy-to-prod.sh`;
- exact public and on-disk production SHA verification;
- SEO/GEO, document-title and IndexNow post-release validation;
- optional Lighthouse matrix and optional live HubSpot E2E for explicit manual runs.

Staging and production use the same `nuvanx-environment-mutation` concurrency group. A Staging2 mutation therefore cannot advance the live staging payload while Production is resolving, validating and promoting it.

## Production authorization

Production can be started manually from the `Production` workflow. The `release/production` branch also remains a release-control path, but its push trigger is scoped to:

```text
release/production-candidate.txt
```

That file is an **authorization signal only**. Its stored SHA is not used as the production payload source. On every release, `production.yml` resolves the current live Staging2 deploy marker and requires exact successful acceptance evidence for that live SHA before any production mutation.

This removes the stale-manifest race that can occur when Staging2 advances after a release candidate file was written.

## Staging2 secrets

Required:

- `STAGING2_SSH_HOST`
- `STAGING2_SSH_PORT`
- `STAGING2_SSH_USER`
- `STAGING2_SSH_PRIVATE_KEY`
- `STAGING2_SSH_KNOWN_HOSTS`

## Production secrets

Required for production mutation and production-origin audits:

- `PROD_SSH_HOST`
- `PROD_SSH_PORT`
- `PROD_SSH_USER`
- `PROD_SSH_PRIVATE_KEY`
- `PROD_SSH_KNOWN_HOSTS`

## Local Staging2 acceptance

Install the scoped dependencies, then execute the resilient entrypoint from the repository root:

```bash
cd scripts/staging2
npm ci --ignore-scripts
npx playwright install chromium
cd ../..
EXPECTED_SHA=<40-char-sha> BASE_URL=https://staging2.nuvanx.com node scripts/staging2/block-c-entrypoint.mjs
EXPECTED_SHA=<40-char-sha> BASE_URL=https://staging2.nuvanx.com node scripts/staging2/valoracion-placement.mjs
```

The Staging acceptance runners adhere to the following exit-code contracts:

### Valoración placement runner (`valoracion-placement.mjs` / `valoracion-placement-resilient.mjs`)
- `0`: Validation passed (`VALORACION_PLACEMENT=PASS` / `VALORACION_INTERACTIVITY=PASS`).
- `1`: Real assertion failure (`VALORACION_PLACEMENT=FAIL_REAL`).
- `75` (`EX_TEMPFAIL`): Transient challenge exhaustion (`VALORACION_PLACEMENT=TRANSIENT_ONLY`), triggering automatic Staging2 rollback and writing diagnostics to GitHub Step Summary.

### Block C matrix runner (`block-c-entrypoint.mjs` / `block-c-matrix.mjs`)
- `0`: Validation passed (`BLOCK_C_RESILIENT=PASS`). All published routes and viewports validated with complete browser visual geometry. Eligible for Production acceptance.
- `1`: Real assertion failure (`BLOCK_C_RESILIENT=FAIL_REAL`) or malformed results. Rollback remains armed to revert Staging2.
- `75` (`EX_TEMPFAIL`): Transient challenge exhaustion (`BLOCK_C_RESILIENT=FAIL_TRANSIENT_EXHAUSTED`). Rollback is disarmed (`STAGING_MUTATION_ARMED=0`) because origin SHA and HTTP 200 were verified, but the run remains ineligible for Production acceptance due to incomplete visual validation.

## Repository hygiene

Repository hygiene is part of `staging.yml`; it is no longer a separate workflow. It rejects transient one-shot workflows, workflow self-mutation, tracked generated/local debris, empty/editor-residue files and any `.github/workflows` state other than `production.yml` plus `staging.yml`. Gitleaks runs on the applicable trusted Staging workflow paths.

Operational evidence belongs in GitHub Actions artifacts or Git history, not as permanent root-level audit dumps.

## Release record

For every production release retain:

- exact live-and-accepted Staging2 SHA;
- exact Block C run/artifact;
- environment identity evidence;
- rollback/backup evidence;
- production public-boundary evidence;
- post-release audit evidence.

Never store secret values in repository documents or HTML.
