# Deployment helpers

Mutating scripts require `--confirm` or `NUVANX_CONFIRM=yes`.

| Script | Purpose |
|--------|---------|
| `deploy-to-staging2.sh` | Guard Staging2 identity, PHP lint, backup, rsync theme, stamp SHA and purge caches |
| `deploy-required-mu-plugins.sh` | Validate WordPress root identity and retire absorbed MU plugins |
| `deploy-to-prod.sh` | Guarded production promotion tooling for an explicitly authorized exact SHA |
| `flush-prod-cache.sh` | Flush WordPress and SiteGround optimizer caches for the production root |
| `nvx-cms-content-cleanup.php` | Retained CMS migration: detect/apply residual legacy block and claim rewrites in `post_content` |

## Workflow ownership

The shell scripts are implementation helpers. Release orchestration is owned by the reusable GitHub workflows:

- `.github/workflows/deploy-staging2.yml`
- `.github/workflows/staging2-acceptance.yml`
- `.github/workflows/deploy.yml`

They are `workflow_call` workflows. A normal push to `master` does not deploy Staging2 or production. An authorized release path must explicitly call them with the exact 40-character candidate SHA. Production mutation in the permanent production workflow remains disabled until explicit authorization.

See [`docs/operations/deployment.md`](../../docs/operations/deployment.md) for the canonical release model.

## Retained CMS migration — do not treat as routine deploy tooling

`nvx-cms-content-cleanup.php` is intentionally retained because the active theme still contains narrow `TODO(legacy-guard)` compatibility code whose removal is conditioned on this migration having been completed. Do not delete the migration script or those guards merely because current rendered pages pass acceptance.

Retirement requires explicit evidence of all of the following:

1. rule-engine self-test passes;
2. Staging2 dry-run is reviewed;
3. migration is applied to Staging2 when needed and a subsequent dry-run reports `dirty=0`;
4. production execution is explicitly authorized, backed up and applied when needed;
5. production follow-up dry-run reports `dirty=0` and the normal production acceptance remains green;
6. only then remove the corresponding `TODO(legacy-guard)` compatibility code and this migration script in a separate reviewed change.

Commands, run from the WordPress root with this repository/tooling available:

```bash
# Offline rule-engine check; no database mutation.
php tools/deploy/nvx-cms-content-cleanup.php --self-test

# Read-only CMS scan.
wp eval-file tools/deploy/nvx-cms-content-cleanup.php

# Mutating migration; only after review/backup/authorization.
wp eval-file tools/deploy/nvx-cms-content-cleanup.php --confirm
```

## Host-level emergency production operation

Direct host operation is an emergency/explicit-authorization path, not the default release trigger:

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
