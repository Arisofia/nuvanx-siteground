# Deployment operations

Mutating deploy scripts require `--confirm` or `NUVANX_CONFIRM=yes`.

## Identity

- Canonical branch: `master`
- Deployment identity: full lowercase 40-character Git SHA
- Staging2: `https://staging2.nuvanx.com`
- Production: `https://nuvanx.com`

Branch names and tags may select a checkout. They are never proof of what is live. The live marker is:

```text
wp-content/themes/nuvanx-medical/.nvx-deploy-sha
```

exposed as:

```html
<meta name="nvx-deploy-sha" content="<40-character-sha>" />
```

## Staging2 (manual GitHub workflow)

Push to `master` does **not** deploy. Use **Actions → Deploy Staging2 (manual)** (`.github/workflows/deploy-staging2.yml`) with `workflow_dispatch` from `master`.

### Scope

```text
wp-content/themes/nuvanx-medical/
wp-content/mu-plugins/   (required NUVANX MU plugins only)
```

The workflow does not deploy production, does not copy the database, and does not replace the full WordPress tree. Pull requests only run the non-mutating contract job.

### Environment secrets (`staging2`)

| Secret | Purpose |
|--------|---------|
| `STAGING2_SSH_HOST` | SiteGround SSH hostname |
| `STAGING2_SSH_PORT` | SSH port (normally `18765`) |
| `STAGING2_SSH_USER` | SSH username |
| `STAGING2_SSH_PRIVATE_KEY` | BatchMode private key (no passphrase) |
| `STAGING2_SSH_KNOWN_HOSTS` | Pinned host key; no runtime `ssh-keyscan` |

### Run a deployment

1. Merge the change into `master`.
2. Copy the full 40-character SHA (no trailing spaces).
3. Run **Deploy Staging2 (manual)** on `master`.
4. Set `git_sha` to that SHA.
5. Set confirmation to `DEPLOY_STAGING2`.
6. Approve the protected `staging2` environment when prompted.

The workflow refuses any SHA not contained in `origin/master`.

### Remote sequence

1. Upload an isolated release under  
   `/home/customer/www/staging2.nuvanx.com/public_html/wp-content/.nuvanx-deployments/<sha>-<run_id>/`
2. Run `tools/deploy/deploy-to-staging2.sh` (root/URL/theme guards, PHP lint, backup, rsync, SHA stamp, cache purge).
3. Run `tools/deploy/deploy-required-mu-plugins.sh` for Staging2.
4. Verify the remote `.nvx-deploy-sha` marker.
5. Run `scripts/staging2/verify-rendered-document.mjs` against `https://staging2.nuvanx.com` with `EXPECTED_SHA` equal to the deployed SHA.
6. Remove the temporary remote release directory.

Success criteria: remote marker match + full rendered acceptance green.

## Production (host-level only)

Production is **manual**. The Staging2 workflow never writes to `nuvanx.com`.

After Staging2 is green for a SHA, on the SiteGround host with both trees and `wp-cli`:

```bash
export WP_PROD=/home/customer/www/nuvanx.com/public_html
export WP_STG2=/home/customer/www/staging2.nuvanx.com/public_html

NUVANX_CONFIRM=yes bash tools/deploy/deploy-to-prod.sh \
  --prod-root "$WP_PROD" \
  --staging-root "$WP_STG2" \
  --confirm
```

That script:

- rsyncs the theme with `--delete`
- copies only the required NUVANX form MU plugins
- disables SiteGround CSS minify/combine and removes stale `nvx-*.min.css`
- requires production `siteurl`/`home` = `https://nuvanx.com` and staging = Staging2

### Production cache flush

```bash
NUVANX_CONFIRM=yes bash tools/deploy/flush-prod-cache.sh \
  --wp-root /home/customer/www/nuvanx.com/public_html \
  --confirm
```

### Staging2 verification (required before promote)

```bash
BASE_URL=https://staging2.nuvanx.com EXPECTED_SHA=<40-char-sha> \
  node scripts/staging2/verify-rendered-document.mjs
```

After production promote, confirm the production theme marker and critical routes manually; the Staging2 acceptance script enforces Staging2 noindex policy and is not a production smoke suite.

## Release record

Each release should record: Git SHA, workflow run URL, active theme, `siteurl`/`home`, PHP/WordPress versions, MU plugins, backup path, rendered-acceptance result, rollback target. Never write secret values into docs or HTML.
