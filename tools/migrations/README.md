# Migrations

One-time or gated migration helpers. **Require `--confirm` or `NUVANX_CONFIRM=yes`.**

| Script | Purpose |
|--------|---------|
| `backup-post-content.sh` | Export post content to `wp-content/backups-nuvanx/` |
| `staging-reset-p1.sh` | Staging backup, quarantine legacy files, purge cache |
| `clean-residual-staging.sh` | sed patches on hubspot/meta mu-plugins (creates `.pre-clean.bak`) |

Rollback: restore from backup dirs or `.pre-clean.bak` files created by each script.