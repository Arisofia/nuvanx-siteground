# Deployment operations

Deployment and migration scripts require explicit confirmation.

## Staging2 (manual, protected GitHub workflow)

A push to `master` **does not deploy** to Staging2. Deployment is available only
through the GitHub Actions workflow **Deploy Staging2 (manual)**
(`.github/workflows/deploy-staging2.yml`) using `workflow_dispatch` from the
`master` branch.

The workflow deploys only:

```text
wp-content/themes/nuvanx-medical/
```

It does not deploy to production, does not copy the database, does not replace
the complete WordPress tree and cannot run from a normal push or pull request.
Pull requests execute only the non-mutating workflow contract test.

### Required GitHub environment and secrets

Create or retain a protected GitHub Environment named `staging2`. Require manual
approval there when repository governance supports it. Store these values as
environment secrets, never in repository files:

- `STAGING2_SSH_HOST`: SiteGround SSH hostname.
- `STAGING2_SSH_PORT`: SiteGround SSH port; normally `18765`.
- `STAGING2_SSH_USER`: SiteGround SSH username.
- `STAGING2_SSH_PRIVATE_KEY`: private key authorized by SiteGround.
- `STAGING2_SSH_KNOWN_HOSTS`: pinned `known_hosts` entry for the exact host and
  port. Generate and verify it from a trusted operator workstation; the workflow
  intentionally refuses runtime `ssh-keyscan` trust.

The private key must not require an interactive passphrase because GitHub Actions
uses SSH `BatchMode`. Restrict the corresponding server-side key to the staging
account whenever SiteGround permits it.

### Run a deployment

1. Merge and validate the intended change in `master`.
2. Copy the complete 40-character commit SHA.
3. Open **Actions → Deploy Staging2 (manual) → Run workflow**.
4. Select branch `master`.
5. Enter the immutable SHA in `git_sha`.
6. Select `DEPLOY_STAGING2` in the confirmation field.
7. Approve the protected `staging2` environment when prompted.

The workflow rejects any SHA that is not contained in `origin/master`.

### Remote safety sequence

The workflow uploads the selected theme snapshot to an isolated directory under:

```text
/home/customer/www/staging2.nuvanx.com/public_html/wp-content/.nuvanx-deployments/
```

It then runs `tools/deploy/deploy-to-staging2.sh`, which:

1. Confirms the exact Staging2 filesystem root, `siteurl`, `home` and active theme.
2. Lints all PHP files in the uploaded theme.
3. Creates a timestamped theme backup under
   `wp-content/backups-nuvanx/pre-staging2-*`.
4. Synchronizes the theme using `rsync --delete`.
5. Writes the selected SHA to `.nvx-deploy-sha`.
6. Purges WordPress, SiteGround and opcode caches.
7. Automatically restores the backup if a post-mutation command fails.
8. Runs `scripts/staging2/smoke-verify-staging2.sh` from the GitHub runner.

A successful run emits `DEPLOY_STAGING2_OK` and records the SHA in the Actions
summary. The temporary upload is always removed at the end of the workflow, regardless of success or failure.

### Host-authorized fallback

When GitHub Actions or SSH secrets are unavailable, an authorized SiteGround
operator may still upload the intended theme manually to:

```text
/home/customer/www/staging2.nuvanx.com/public_html/wp-content/themes/nuvanx-medical/
```

After the upload, run the SHA stamp, cache purge and smoke verification described
by the repository scripts. Do not infer a deployment merely from a merge to
GitHub.

## Deploy (production)

Production is **manual only**. The Staging2 workflow does not contain the
production root and cannot promote code to `nuvanx.com`. After Staging2 validation,
an authorized operator may promote a known-good SHA using the host-level procedure:

1. Validate Staging2: smoke green + visual QA.
2. Run the guarded promotion command below from the host with the confirmed
   staging and production roots.
3. Run `post-promote-verify` against `https://nuvanx.com`.

The production script creates a theme and selected MU-plugin backup under
`wp-content/backups-nuvanx/pre-sync-TIMESTAMP/` before synchronization.

### SSH / on-server alternative

When already on the SiteGround host with both trees and `wp-cli`:

```bash
export WP_PROD=/home/customer/www/nuvanx.com/public_html
export WP_STG2=/home/customer/www/staging2.nuvanx.com/public_html

NUVANX_CONFIRM=yes bash tools/deploy/deploy-to-prod.sh \
  --prod-root "$WP_PROD" \
  --staging-root "$WP_STG2" \
  --confirm
```

Notes:

- Rsyncs **theme** with `--delete`.
- Copies only the NUVANX form MU plugins; it does not wipe other MU plugins.
- Disables SiteGround CSS minify/combine and deletes stale `nvx-*.min.css`.
- Guards: production `siteurl`/`home` must be `https://nuvanx.com`; staging must
  be Staging2.

### Post-promote verification (read-only)

```bash
BASE_URL=https://nuvanx.com bash scripts/ops/post-promote-verify.sh
BASE_URL=https://nuvanx.com bash scripts/staging2/smoke-verify-staging2.sh
```

Expect `POST_PROMOTE_VERIFY_OK` and `SMOKE_VERIFY_OK`.

## Cache flush

```bash
NUVANX_CONFIRM=yes bash tools/deploy/flush-prod-cache.sh \
  --wp-root /home/customer/www/nuvanx.com/public_html \
  --confirm
```

## Migrations

See [tools/migrations/README.md](../../tools/migrations/README.md). Never run
migrations in CI.

## Pre-deploy audit

Run read-only checks from [tools/audit/README.md](../../tools/audit/README.md)
against Staging2 first.
