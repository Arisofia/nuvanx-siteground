# Deployment helpers

Mutating scripts require `--confirm` or `NUVANX_CONFIRM=yes`.

| Script | Purpose |
|--------|---------|
| `deploy-to-staging2.sh` | Guard Staging2 identity, PHP lint, backup, rsync theme, stamp SHA and purge caches |
| `deploy-required-mu-plugins.sh` | Validate WordPress root identity and retire absorbed MU plugins |
| `deploy-to-prod.sh` | Guarded production promotion tooling for an explicitly authorized exact SHA |
| `flush-prod-cache.sh` | Flush WordPress and SiteGround optimizer caches for the production root |

## Workflow ownership

The shell scripts in this directory are implementation helpers. Release orchestration is owned by the reusable GitHub workflows:

- `.github/workflows/deploy-staging2.yml`
- `.github/workflows/staging2-acceptance.yml`
- `.github/workflows/deploy.yml`

They are `workflow_call` workflows. A normal push to `master` does not deploy Staging2 or production. An authorized release path must explicitly call them with the exact 40-character candidate SHA. Production mutation in the permanent production workflow remains disabled until explicit authorization.

See [`docs/operations/deployment.md`](../../docs/operations/deployment.md) for the canonical release model.

## Migrations are separate from deploys

One-time or bounded data migrations do not belong in this directory. Retained migration tooling lives under [`tools/migrations/`](../migrations/) and must not be executed as part of routine deployment.

The currently retained CMS cleanup migration is documented in [`tools/migrations/README.md`](../migrations/README.md). It remains only because active theme compatibility guards explicitly depend on evidence that the migration has completed.

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
