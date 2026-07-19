# Deployment helpers

**Mutating scripts — require `--confirm` or `NUVANX_CONFIRM=yes`.**

| Script | Purpose |
|--------|---------|
| `deploy-to-prod.sh` | Guard siteurl, backup prod, rsync theme from staging, copy form MU plugins only, strip `nvx-*.min.css`, purge cache |
| `flush-prod-cache.sh` | Flush WordPress object cache |

Deployment workflows are intentionally absent from GitHub. Use this guarded
host-level production path only after staging2 has been validated (see
[docs/operations/deployment.md](../../docs/operations/deployment.md)).

```bash
export WP_PROD=/home/customer/www/nuvanx.com/public_html
export WP_STG2=/home/customer/www/staging2.nuvanx.com/public_html

NUVANX_CONFIRM=yes bash tools/deploy/deploy-to-prod.sh \
  --prod-root "$WP_PROD" \
  --staging-root "$WP_STG2" \
  --confirm

BASE_URL=https://nuvanx.com bash scripts/ops/post-promote-verify.sh
```

```bash
NUVANX_CONFIRM=yes bash tools/deploy/flush-prod-cache.sh \
  --wp-root /home/customer/www/nuvanx.com/public_html \
  --confirm
```
