# Audit tools (read-only)

All scripts accept `--wp-root` and/or `--output-dir` where applicable. Reports default to `./artifacts/audit-results/`.

**Do not run mutating scripts from this folder.** Deploy/migration helpers live under `tools/deploy/` and `tools/migrations/`.

## Scripts

| Script | Purpose |
|--------|---------|
| `audit-theme.sh` | Theme PHP/CSS marker audit |
| `comprobacion-http.sh` | HTTP 200 checks from `--urls-file` |
| `search-styles.sh` | Grep/find style and legacy markers |
| `validate-yoast-db.sh` | Yoast DB + optional public curl |
| `filesystem-inventory.sh` | Published pages + legacy DB queries |
| `validate-public-html.sh` | Homepage legacy/video marker counts |
| `check-staging.sh` | Staging env metadata |
| `audit-nuvanx-fix.sh` | Plugin/footer/form audit |
| `site-validation.sh` | Theme/plugin list + retired path check |

## Example

```bash
bash tools/audit/search-styles.sh \
  --wp-root /path/to/wordpress \
  --output-dir ./artifacts/audit-results

bash tools/audit/comprobacion-http.sh \
  --urls-file docs/audits/urls.example.txt \
  --base-url https://example.com \
  --output-dir ./artifacts/audit-results
```
