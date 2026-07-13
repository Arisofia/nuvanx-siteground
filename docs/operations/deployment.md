# Deployment operations

Deployment and migration scripts require explicit confirmation.

## Deploy (production)

```bash
NUVANX_CONFIRM=yes bash tools/deploy/deploy-to-prod.sh \
  --prod-root /path/to/prod/public_html \
  --staging-root /path/to/staging/public_html \
  --confirm
```

Creates backup under `wp-content/backups-nuvanx/pre-sync-TIMESTAMP/` before rsync.

## Cache flush

```bash
NUVANX_CONFIRM=yes bash tools/deploy/flush-prod-cache.sh \
  --wp-root /path/to/prod/public_html \
  --confirm
```

## Migrations

See [tools/migrations/README.md](../../tools/migrations/README.md). Never run in CI.

## Pre-deploy audit

Run read-only checks from [tools/audit/README.md](../../tools/audit/README.md) against staging first.