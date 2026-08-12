# Deployment helpers

Mutating scripts require `--confirm` or `NUVANX_CONFIRM=yes`.

| Script | Purpose |
|--------|---------|
| `deploy-to-staging2.sh` | Guard Staging2 identity, PHP lint, backup, rsync theme, stamp SHA and purge caches |
| `deploy-required-mu-plugins.sh` | Validate WordPress root identity and retire absorbed MU plugins |
| `deploy-to-prod.sh` | Guarded production promotion tooling for an explicitly authorized exact SHA |
| `flush-prod-cache.sh` | Flush WordPress and SiteGround optimizer caches for the production root |

## Workflow ownership

The shell scripts in this directory are implementation helpers. Release orchestration is owned by the canonical GitHub workflows:

- `.github/workflows/staging.yml` - Complete Staging2 lifecycle
- `.github/workflows/production.yml` - Production promotion with SEO/GEO audits

A relevant push to `master` can automatically deploy **Staging2 only** through `staging.yml`. Production deployment requires explicit authorization via the `release/production` branch and changes to `release/production-candidate.txt`.

See [`docs/operations/deployment.md`](../../docs/operations/deployment.md) for the canonical release model.

## Migrations are separate from deploys

One-time or bounded data migrations do not belong in this directory. Retained migration tooling lives under [`tools/migrations/`](../migrations/).

The currently retained CMS cleanup migration is documented in [`tools/migrations/README.md`](../migrations/README.md). It remains only because active theme compatibility guards explicitly depend on evidence that the migration has completed.

The shared content-hygiene migration and the divergence audit are the sole exception: `deploy-to-prod.sh` runs `tools/migrations/content-hygiene-shared.php` and `tools/migrations/audit-content-divergence.php` inside the atomic post-cutover window. If either the `MIGRATION_OK` or the `AUDIT_CLEAN` status is missing, the deploy rolls back both the previous theme and the database snapshot together. No other migration may be executed as part of routine deployment.

To prevent editorial content changes (e.g. H1 text) from triggering full rollbacks, the audit runs twice:
- Pre-cutover: checks only non-migratable issues (legal page H1, missing pages) and fails fast on those
- Post-migration: requires full AUDIT_CLEAN including string/regex hygiene rules after migration fixes them

**Important changes:**
- The workflow step "Run shared content migration" has been removed. The migration now executes only once, inside deploy-to-prod.sh's atomic post-cutover window.
- BACKUP_DIR has been moved outside the document root to `$PROD_PARENT/.nvx-backups/` to prevent HTTP exposure of the database dump.

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
