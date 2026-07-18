# Deployment operations

Deployment and migration scripts require explicit confirmation.

## Staging2 (automatic)

GitHub Actions workflow **Deploy theme to staging2 + flush cache** deploys
`wp-content/themes/nuvanx-medical` (+ form MU plugins) to
`/home/customer/www/staging2.nuvanx.com/public_html` on push to `master`
(theme / mu-plugins / staging scripts paths) or via `workflow_dispatch`.

## Deploy (production)

Production is **manual only**. Prefer the Actions workflow:

1. Validate staging2: smoke green + visual QA.
2. GitHub → Actions → **Deploy theme to production + flush cache**
3. Inputs:
   - `ref`: `master` (or a known-good SHA)
   - `confirm`: exactly `PROMOTE_PRODUCTION`
4. Wait for `post-verify` (structural markers + full smoke on `https://nuvanx.com`).

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