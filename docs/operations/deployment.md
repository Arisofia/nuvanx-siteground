# Deployment operations

Deployment and migration scripts require explicit confirmation.

## Staging2 (manual, host-authorized)

Automated GitHub Actions deployment workflows were intentionally removed from
this repository. A push to `master` therefore **does not deploy** to staging2,
and a new workflow must not be recreated implicitly.

An operator with approved SiteGround / WP-CLI access must deploy the intended
Git SHA to `/home/customer/www/staging2.nuvanx.com/public_html`, stamp it with
`scripts/staging2/stamp-deploy-sha.sh`, purge the cache, and then run the
staging smoke verification. This keeps GitHub publication separate from the
host-level change and makes the deployed revision auditable.

## Deploy (production)

Production is **manual only**. GitHub Actions deployment workflows are not
available in this repository. After staging2 validation, an authorized operator
may promote a known-good SHA using the host-level procedure:

1. Validate staging2: smoke green + visual QA.
2. Run the guarded promotion command below from the host with the confirmed
   staging and production roots.
3. Run `post-promote-verify` against `https://nuvanx.com`.

Creates a theme (+ mu-plugins) tarball under
`wp-content/backups-nuvanx/pre-sync-TIMESTAMP/` on the production host before rsync.

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
- Copies only the NUVANX form MU plugins (does **not** wipe other mu-plugins).
- Disables SiteGround CSS minify/combine and deletes stale `nvx-*.min.css`.
- Guards: prod `siteurl`/`home` must be `https://nuvanx.com`; staging must be staging2.

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

See [tools/migrations/README.md](../../tools/migrations/README.md). Never run in CI.

## Pre-deploy audit

Run read-only checks from [tools/audit/README.md](../../tools/audit/README.md) against staging first.
