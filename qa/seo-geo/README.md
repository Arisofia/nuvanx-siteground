# Rendered SEO/GEO audit

This directory is the output target for the rendered-site audit executed by
`.github/workflows/seo-geo-gate.yml`.

The audit checks production and staging HTML without storing complete page
content. Generated evidence contains only metadata, schema type names, H1 text,
HTTP status and normalized findings.

## Environment policies

### Production

Blocking findings:

- a page is `noindex`;
- a canonical is missing or points outside `nuvanx.com`;
- the document head references `staging2.nuvanx.com`;
- a required URL cannot be fetched or returns an error status.

### Staging

Blocking findings:

- a page does not declare `noindex` through the robots meta tag or
  `X-Robots-Tag`;
- a required URL cannot be fetched or returns an error status.

Editorial and semantic deficiencies are initially recorded as warnings. They
can be promoted to blocking checks after the baseline is corrected.

## Local execution

```bash
node scripts/seo/test-audit-rendered-pages.mjs
node scripts/seo/audit-rendered-pages.mjs
```

Optional variables:

```bash
NVX_PRODUCTION_URL=https://nuvanx.com \
NVX_STAGING_URL=https://staging2.nuvanx.com \
NVX_STAGING_BASIC_AUTH='user:password' \
NVX_AUDIT_ENVIRONMENTS=production,staging \
NVX_SEO_ENFORCE=critical \
node scripts/seo/audit-rendered-pages.mjs
```

`NVX_STAGING_BASIC_AUTH` is consumed only as an HTTP Authorization header. Its
value is never written to the reports or logs.

## Generated files

- `rendered-audit.json`: machine-readable evidence.
- `rendered-audit.md`: compact reviewer summary.

Generated reports are CI artifacts and should not be committed after local
runs.
