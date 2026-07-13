# Historical findings — NUVANX audit bundle

Source: `archive/NUVANX-AUDIT-raw/` (30 files, preserved as-is)

## Why this bundle exists

The raw archive preserves scripts and PHP snapshots from a SiteGround production/staging cleanup (legacy CSS, Thermage references, HubSpot forms, cache diagnostics). It documents **historical work**; canonical tooling lives under `tools/audit/`, `tools/deploy/`, and `tools/migrations/`.

## Key findings

### Legacy markers

Scripts targeted: `Thermage`, `nvx-phase3c`, `et_pb_`, `brand-manual`, `zzzz`, post ID `1594`, runtime `!important` patches.

### Environment coupling (resolved in canonical tools)

Historical scripts hardcoded SiteGround paths. Sanitized tools use `--wp-root`, `--base-url`, `--urls-file`, and `--output-dir`.

### Mutating scripts (remain in archive)

`run_final_validation.sh`, `user_root_cleanup.sh`, `limpieza_fisica.sh`, `fix_contacto_css*.sh`, `disable_p1b_flush.sh`, `diagnostic_cache_render.sh` — kept in `archive/NUVANX-AUDIT-raw/` only.

### Integrated canonical tooling

| Archive original | Canonical path |
|-----------------|----------------|
| `comprobacion_http.sh` | `tools/audit/comprobacion-http.sh` |
| `search_styles.sh` | `tools/audit/search-styles.sh` |
| `validate_yoast_db.sh` | `tools/audit/validate-yoast-db.sh` |
| `thermage_inv.sh` | `tools/audit/thermage-inventory.sh` |
| `phase3_4_audit.sh` | `tools/audit/phase3-4-audit.sh` |
| `inventory.sh` | `tools/audit/filesystem-inventory.sh` |
| `val_fase4.sh` | `tools/audit/validate-fase4-db.sh` |
| `val_fase5.sh` | `tools/audit/validate-fase5-urls.sh` |
| `validate_fase6.sh` | `tools/audit/validate-fase6-db.sh` |
| `validate_public.sh` | `tools/audit/validate-public-html.sh` |
| `check_staging.sh` | `tools/audit/check-staging.sh` |
| `auditoria_nuvanx_fix.sh` | `tools/audit/audit-nuvanx-fix.sh` |
| `validacion.sh` | `tools/audit/site-validation.sh` |
| `deploy_to_prod.sh` | `tools/deploy/deploy-to-prod.sh` |
| `flush_prod.sh` | `tools/deploy/flush-prod-cache.sh` |
| `backup_fase3.sh` | `tools/migrations/backup-post-content.sh` |
| `staging_reset_p1.sh` | `tools/migrations/staging-reset-p1.sh` |
| `clean_residual_staging.sh` | `tools/migrations/clean-residual-staging.sh` |

### PHP snapshots (archive only)

`nuvanx-fix.php`, `nuvanx-redirects.php`, `nuvanx-contacto-hubspot-form.php`, `nuvanx-valoracion-native-hubspot-form.php`

## Validation rules

- Avoid `!important` in source CSS except minimal exceptions
- Remove legacy markers from active code paths
- Avoid runtime DOM rewriting
- Keep source and minified CSS synchronized