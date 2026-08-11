# SEO / Google Ads support tooling

This directory contains **manual diagnostic/support scripts**, not additional GitHub Actions workflows. The repository intentionally keeps only the canonical `Staging` and `Production` workflows.

## Ownership

- `google-ads-list-campaigns.js` — read-only Google Ads credential/API diagnostic. It may be called manually with credentials supplied through environment variables or a private JSON file. Its CI-facing output is deliberately bounded and does not print credential fingerprints, free-form Google Ads messages, request metadata or account resource names.
- `classify-google-credential.js` — local shape/presence diagnostic for a Google Ads JSON credential bundle. It reports only presence classes/counts; it must not print secret values.
- `get-refresh-token.js` — **private local interactive helper only**. It refuses CI/non-TTY execution, requires OAuth client credentials from the local environment and prints the newly issued refresh token only to that private terminal so it can be transferred immediately to the secret manager.
- `index-pages.js` — sitemap/index URL support utility. Network target/host constraints are owned by the caller; production verification remains owned by the canonical Production workflow and its origin-side audit scripts.

## Security rules

1. Never commit `google-ads.json`, OAuth tokens, developer tokens or API keys.
2. Never paste refresh tokens or client secrets into GitHub issues, PRs, Actions logs or chat transcripts.
3. Do not add a temporary workflow to run these helpers. If a diagnostic becomes release-critical, integrate a read-only, secret-safe check into one of the two canonical workflows instead.
4. Generated dependency metadata (`package-lock.json`) must be updated with npm rather than hand-edited when dependency specs change.
