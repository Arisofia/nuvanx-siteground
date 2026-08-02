# Deployment helpers

Mutating scripts require `--confirm` or `NUVANX_CONFIRM=yes`.

| Script | Purpose |
|--------|---------|
| `deploy-to-staging2.sh` | Guard Staging2 identity, lint, backup, rsync theme, stamp SHA, purge caches |
| `deploy-required-mu-plugins.sh` | Deploy required NUVANX MU plugins (staging2 or production) |
| `deploy-to-prod.sh` | Promote theme from staging disk to production; copy form MU plugins only |
| `flush-prod-cache.sh` | Flush WordPress object cache for a given root |
| `nvx-cms-content-cleanup.php` | Dry-run / apply residual CMS block + claim rewrites in `post_content` |

## CMS content cleanup (host)

From the WordPress root on staging2 (preferred) or production:

```bash
# Offline rule engine check (no DB):
php tools/deploy/nvx-cms-content-cleanup.php --self-test

# Dry-run against all pages/posts/revisions:
wp eval-file tools/deploy/nvx-cms-content-cleanup.php

# Apply after reviewing hit_totals:
NVX_CMS_CLEANUP_APPLY=1 wp eval-file tools/deploy/nvx-cms-content-cleanup.php
```

Only remove matching theme strippers after dry-run reports `dirty=0` (or after a successful APPLY that leaves zero residual hits).

Staging2 deploy is driven by GitHub Actions (`.github/workflows/deploy-staging2.yml`). Production promote remains host-level only. See [docs/operations/deployment.md](../../docs/operations/deployment.md).

## Staging2 (via GitHub)

Use **Deploy Staging2 (manual)** with a full SHA and confirmation `DEPLOY_STAGING2`.

## Production (on SiteGround host)

```bash
export WP_PROD=/home/customer/www/nuvanx.com/public_html
export WP_STG2=/home/customer/www/staging2.nuvanx.com/public_html

NUVANX_CONFIRM=yes bash tools/deploy/deploy-to-prod.sh \
  --prod-root "$WP_PROD" \
  --staging-root "$WP_STG2" \
  --confirm
```

```bash
NUVANX_CONFIRM=yes bash tools/deploy/flush-prod-cache.sh \
  --wp-root /home/customer/www/nuvanx.com/public_html \
  --confirm
```
