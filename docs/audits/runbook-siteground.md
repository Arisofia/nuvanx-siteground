# SiteGround audit runbook

Execute from SSH on staging first, then production read-only audits.

## Prerequisites

- `wp-cli` in PATH
- `bash`, `curl`, `grep`, `find`
- WordPress root path (e.g. `/home/customer/www/nuvanx.com/public_html`)

## Staging smoke (read-only)

```bash
export WP_STAGING=/home/customer/www/staging2.nuvanx.com/public_html
export OUT=./artifacts/audit-results
mkdir -p "$OUT"

bash tools/audit/check-staging.sh --wp-root "$WP_STAGING" --output-dir "$OUT"
bash tools/audit/phase3-4-audit.sh --wp-root "$WP_STAGING" --output-dir "$OUT"
bash tools/audit/search-styles.sh --wp-root "$WP_STAGING" --output-dir "$OUT"
bash tools/audit/validate-public-html.sh \
  --base-url https://staging2.nuvanx.com --output-dir "$OUT"
```

## Production read-only

```bash
export WP_PROD=/home/customer/www/nuvanx.com/public_html
export OUT=./artifacts/audit-results

bash tools/audit/site-validation.sh --wp-root "$WP_PROD" --output-dir "$OUT"
bash tools/audit/validate-yoast-db.sh --wp-root "$WP_PROD" --output-dir "$OUT"
bash tools/audit/thermage-inventory.sh --wp-root "$WP_PROD" --env-label PROD --output-dir "$OUT"
bash tools/audit/comprobacion-http.sh \
  --urls-file docs/audits/urls.example.txt \
  --base-url https://nuvanx.com \
  --output-dir "$OUT"
```

## Mutating operations (manual only)

Require `NUVANX_CONFIRM=yes` and `--confirm`. See [deployment.md](../operations/deployment.md).

## Reports

All read-only scripts write to `--output-dir` (default `./artifacts/audit-results/`). This path is gitignored.