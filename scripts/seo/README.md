# SEO / Google Ads support tooling

This directory contains **manual diagnostic/support scripts**, not additional GitHub Actions workflows. The repository intentionally keeps only the canonical `Staging` and `Production` workflows.

## Package and CI boundary

`scripts/seo` remains an independent Node package. It intentionally is **not merged into the repository-root package** because its Google Ads, Search Console, OAuth and GTM dependencies are operational tooling rather than website runtime dependencies.

The CI contract is deliberately lighter than the theme/release contract:

- every pull request/push that reaches the canonical static gate syntax-checks the top-level `scripts/seo/*.js` files with `node --check`;
- no credentialed Google/GTM diagnostic or publisher is executed automatically;
- the existing weekly `Staging` schedule runs `npm audit --audit-level=high` against this package lock;
- changes to dependencies must update `scripts/seo/package-lock.json` with npm;
- a third SEO-specific workflow is forbidden while repository hygiene requires exactly the canonical `Staging` and `Production` workflows.

This keeps Google SDK dependency churn out of the root package and gives the support tooling its own auditable lockfile without duplicating CI infrastructure.

## Ownership

- `google-ads-list-campaigns.js` — read-only Google Ads credential/API diagnostic. It may be called manually with credentials supplied through environment variables or a private JSON file. Its CI-facing output is deliberately bounded and does not print credential fingerprints, free-form Google Ads messages, request metadata or account resource names.
- `ads-full-analysis.js` — read-only Google Ads performance and structure diagnostic. Queries 30-day bounded performance across campaigns, asset groups, demographics, and geo locations, keeping error output redacted and bounded.
- `classify-google-credential.js` — local shape/presence diagnostic for a Google Ads JSON credential bundle. It reports only presence classes/counts; it must not print secret values.
- `get-refresh-token.js` — **private local interactive helper only**. It refuses CI/non-TTY execution, requires OAuth client credentials from the local environment and prints the newly issued refresh token only to that private terminal so it can be transferred immediately to the secret manager.
- `auth-gtm.js` — **private local interactive helper only**. It refuses CI/non-TTY execution, requests the GTM edit/version/publish scopes, and writes exactly one `GTM_REFRESH_TOKEN` assignment into the repository-root `.env.local` with permissions `0600`.
- `setup-gtm-conversion-trigger.js` — **private local manual publisher only**. It refuses CI/non-TTY execution and requires `GTM_CONFIRM_PUBLISH=yes`. All GTM account/container and Google Ads conversion identifiers must be supplied explicitly through environment variables; the script has no production target defaults. It uses only an isolated workspace, synchronizes a reused workspace before mutation, never falls back to `Default Workspace`, verifies existing canonical entities without replacing them, and publishes only entity IDs created by the current invocation.
- `gsc-client.js` — shared Google Search Console API helper. Computes dynamic 30-day and 7-day windows accounting for GSC's 3-day data latency.
- `gsc-full-analysis.js` — read-only Google Search Console diagnostic covering device breakdown, query performance, and URL-level clicks/CTR across the dynamic 30-day window.
- `search-console-analytics.js` — summary Search Console audit script reporting top queries and landing pages across the dynamic 30-day window.
- `pagespeed-cwv-analysis.js` — Core Web Vitals and PageSpeed Insights runner using native Node HTTPS, auditing mobile/desktop performance metrics across key URLs.
- `index-pages.js` — sitemap/index URL support utility. Network target/host constraints are owned by the caller; production verification remains owned by the canonical Production workflow and its origin-side audit scripts.

## GTM publisher required environment

Before running `setup-gtm-conversion-trigger.js`, configure these values explicitly in the private local environment or `.env.local`:

- `GTM_REFRESH_TOKEN`
- `GTM_CLIENT_ID` and `GTM_CLIENT_SECRET` (or the corresponding `GOOGLE_ADS_*` OAuth client pair)
- `GTM_ACCOUNT_ID`
- `GTM_CONTAINER_ID`
- `GOOGLE_ADS_CONVERSION_ID` in `AW-<digits>` format
- `GOOGLE_ADS_CONVERSION_LABEL`

The publisher must be invoked deliberately from a private local TTY:

```bash
source .env.local
GTM_CONFIRM_PUBLISH=yes node scripts/seo/setup-gtm-conversion-trigger.js
```

Do not disable legacy WordPress tracking snippets merely because this helper exits successfully. First verify the resulting live GTM container/version and the expected conversion event end-to-end.

## Security rules

1. Never commit `google-ads.json`, OAuth tokens, developer tokens or API keys.
2. Never paste refresh tokens or client secrets into GitHub issues, PRs, Actions logs or chat transcripts.
3. Do not add a temporary workflow to run these helpers. If a diagnostic becomes release-critical, integrate a read-only, secret-safe check into one of the two canonical workflows instead.
4. Generated dependency metadata (`package-lock.json`) must be updated with npm rather than hand-edited when dependency specs change.
