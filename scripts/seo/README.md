# SEO / Google Ads support tooling

This directory contains **manual diagnostic/support scripts**, not additional GitHub Actions workflows. The repository intentionally keeps only the canonical `Staging` and `Production` workflows.

## Ownership

- `google-ads-list-campaigns.js` — read-only Google Ads credential/API diagnostic. It may be called manually with credentials supplied through environment variables or a private JSON file. Its CI-facing output is deliberately bounded and does not print credential fingerprints, free-form Google Ads messages, request metadata or account resource names.
- `ads-full-analysis.js` — read-only Google Ads performance and structure diagnostic. Queries 30-day bounded performance across campaigns, asset groups, demographics, and geo locations, keeping error output redacted and bounded.
- `classify-google-credential.js` — local shape/presence diagnostic for a Google Ads JSON credential bundle. It reports only presence classes/counts; it must not print secret values.
- `get-refresh-token.js` — **private local interactive helper only**. It refuses CI/non-TTY execution, requires OAuth client credentials from the local environment and prints the newly issued refresh token only to that private terminal so it can be transferred immediately to the secret manager.
- `auth-gtm.js` — **private local interactive helper only**. It refuses CI/non-TTY execution, requests GTM container edit scopes, and writes/updates `GTM_REFRESH_TOKEN` strictly into the local `.env.local` file.
- `setup-gtm-conversion-trigger.js` — idempotent Google Tag Manager configuration tool. Creates or updates the Google Ads Conversion Tracking tag (`awct`) and matching custom event triggers in the container.
- `gsc-client.js` — shared Google Search Console API helper. Computes dynamic 30-day and 7-day windows accounting for GSC's 3-day data latency.
- `gsc-full-analysis.js` — read-only Google Search Console diagnostic covering device breakdown, query performance, and URL-level clicks/CTR across the dynamic 30-day window.
- `search-console-analytics.js` — summary Search Console audit script comparing top queries and pages between the current and previous period.
- `pagespeed-cwv-analysis.js` — Core Web Vitals and PageSpeed Insights runner using native Node HTTPS, auditing mobile/desktop performance metrics across key URLs.
- `index-pages.js` — sitemap/index URL support utility. Network target/host constraints are owned by the caller; production verification remains owned by the canonical Production workflow and its origin-side audit scripts.

## Security rules

1. Never commit `google-ads.json`, OAuth tokens, developer tokens or API keys.
2. Never paste refresh tokens or client secrets into GitHub issues, PRs, Actions logs or chat transcripts.
3. Do not add a temporary workflow to run these helpers. If a diagnostic becomes release-critical, integrate a read-only, secret-safe check into one of the two canonical workflows instead.
4. Generated dependency metadata (`package-lock.json`) must be updated with npm rather than hand-edited when dependency specs change.
