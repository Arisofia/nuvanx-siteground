# Deployment helpers

**Mutating scripts — manual use only. Not for CI.**

All scripts require `--confirm` or `NUVANX_CONFIRM=yes`.

| Script | Purpose |
|--------|---------|
| `deploy-to-prod.sh` | Backup prod, rsync theme+mu-plugins from staging, purge cache |
| `flush-prod-cache.sh` | Flush WordPress object cache |

```bash
NUVANX_CONFIRM=yes bash tools/deploy/flush-prod-cache.sh --wp-root /path/to/wordpress --confirm
```