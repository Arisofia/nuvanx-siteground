# Deployment operations

Deployment and migration scripts require explicit confirmation.

## Staging2 (manual, protected GitHub workflow)

A push or pull request **does not deploy** to Staging2. Deployment is available
only through the GitHub Actions workflow **Deploy Staging2 (manual)**
(`.github/workflows/deploy-staging2.yml`) using `workflow_dispatch` and the
protected `staging2` environment.

The operator selects the exact repository ref to validate. The supplied
40-character `git_sha` must equal the HEAD of that selected ref. This permits
pre-merge validation of a pull-request branch without allowing arbitrary commits
or an unreviewed SHA to be deployed from another ref.

The workflow mutates only Staging2. It:

- synchronizes `wp-content/themes/nuvanx-medical/`;
- executes the scoped production-readiness WP-CLI migration;
- does not replace the complete WordPress tree;
- does not deploy or promote to production;
- cannot run from a normal push or pull-request event.

Pull requests execute only the non-mutating workflow contract job.

### Required GitHub environment and secrets

Create or retain a protected GitHub Environment named `staging2`. Require manual
approval there when repository governance supports it. Store these values as
environment secrets, never in repository files:

- `STAGING2_SSH_HOST`: SiteGround SSH hostname.
- `STAGING2_SSH_PORT`: SiteGround SSH port; normally `18765`.
- `STAGING2_SSH_USER`: SiteGround SSH username.
- `STAGING2_SSH_PRIVATE_KEY`: private key authorized by SiteGround.
- `STAGING2_SSH_KNOWN_HOSTS`: pinned `known_hosts` entry for the exact host and
  port. Generate and verify it from a trusted operator workstation; the workflow
  intentionally refuses runtime `ssh-keyscan` trust.

The private key must not require an interactive passphrase because GitHub Actions
uses SSH `BatchMode`. Restrict the corresponding server-side key to the staging
account whenever SiteGround permits it.

### Run a pre-merge deployment

1. Confirm the PR head has passed its required non-mutating checks.
2. Open **Actions → Deploy Staging2 (manual) → Run workflow**.
3. Select the PR branch to validate.
4. Copy the branch HEAD as a complete 40-character SHA.
5. Enter that exact SHA in `git_sha`.
6. Select `DEPLOY_STAGING2` in the confirmation field.
7. Approve the protected `staging2` environment when prompted.

The workflow rejects a SHA that differs from the selected workflow ref HEAD.
After any new commit is pushed, the deployment must be repeated with the new
head SHA.

### Remote safety sequence

The workflow uploads the selected immutable payload to an isolated directory:

```text
/home/customer/www/staging2.nuvanx.com/public_html/wp-content/.nuvanx-deployments/
```

It then runs `tools/deploy/deploy-to-staging2.sh`, which:

1. Confirms the exact Staging2 filesystem root, `siteurl`, `home` and active theme.
2. Validates PHP and shell syntax for the theme, migration and smoke script.
3. Creates a timestamped theme archive and database export outside `public_html`:

   ```text
   /home/customer/backups-nuvanx/staging2/pre-staging2-*
   ```

4. Applies restrictive filesystem permissions to the backup directory and files.
5. Disables SiteGround asset transformations that could hide the deployed state.
6. Synchronizes the theme using `rsync --delete`.
7. Writes the selected SHA to `.nvx-deploy-sha` and verifies required modules.
8. Purges WordPress, SiteGround and opcode caches.
9. Runs the production-readiness pre-audit with `--allow-pending`.
10. Applies the migration with the explicit `retire-prototypes` token.
11. Runs the blocking post-migration audit.
12. Executes rendered page and redirect smoke tests.
13. Automatically restores both the theme archive and database export if any
    post-mutation command fails.

A successful run emits `DEPLOY_STAGING2_OK` and `SMOKE_VERIFY_OK`, verifies the
deployed SHA marker and records the result in the Actions summary. The temporary
payload is always removed.

### Production-readiness migration contract

The protected Staging2 workflow executes:

```bash
wp --require=<release>/nvx-production-readiness-command.php \
  nvx production-readiness audit --allow-pending

wp --require=<release>/nvx-production-readiness-command.php \
  nvx production-readiness apply --confirm=retire-prototypes

wp --require=<release>/nvx-production-readiness-command.php \
  nvx production-readiness audit
```

The migration is scoped to approved strategy and Signature pages plus the
retirement of the explicitly governed prototype/Post-Maternity routes. It uses an
atomic WordPress option lock and is idempotent.

### Required visual QA after automated PASS

The workflow result is necessary but not sufficient. Validate at minimum on desktop
and mobile:

- `/tratamientos/`
- `/protocolos-signature/`
- `/remodelacion-corporal-laser-madrid/`
- `/liposculpt-air/` → `/remodelacion-corporal-laser-madrid/`
- `/v-lift-awake/` → `/papada-definicion-mandibular-madrid/`
- Post-Maternity → `/protocolos-signature/`

Confirm layout, navigation, one H1, CTA, canonical, robots and the absence of
provisional or retired terminology.

### Host-authorized fallback

When GitHub Actions or SSH secrets are unavailable, an authorized SiteGround
operator may upload the exact release payload manually and invoke
`tools/deploy/deploy-to-staging2.sh` with all required arguments. The same theme
and database backup, migration and smoke sequence is mandatory. Do not infer a
deployment merely from a GitHub commit or merge.

## Deploy (production)

Production is **manual only**. The Staging2 workflow does not contain the
production root and cannot promote code to `nuvanx.com`. After Staging2 validation,
an authorized operator may promote a known-good SHA using the host-level procedure:

1. Validate Staging2: migration audit green, smoke green and visual QA.
2. Merge the exact validated PR state.
3. Confirm the production candidate corresponds to the validated code.
4. Run the guarded promotion command from the host with the confirmed staging
   and production roots.
5. Run post-promotion verification against `https://nuvanx.com`.

The production script creates a theme and selected MU-plugin backup under
`wp-content/backups-nuvanx/pre-sync-TIMESTAMP/` before synchronization.

### SSH / on-server alternative

When already on the SiteGround host with both trees and `wp-cli`:

```bash
export WP_PROD=/home/customer/www/nuvanx.com/public_html
export WP_STG2=/home/customer/www/staging2.nuvanx.com/public_html

NUVANX_CONFIRM=yes bash tools/deploy/deploy-to-prod.sh \
  --prod-root "$WP_PROD" \
  --staging-root "$WP_STG2" \
  --confirm
```

Notes:

- Rsyncs **theme** with `--delete`.
- Copies only the NUVANX form MU plugins; it does not wipe other MU plugins.
- Disables SiteGround CSS minify/combine and deletes stale `nvx-*.min.css`.
- Guards: production `siteurl`/`home` must be `https://nuvanx.com`; staging must
  be Staging2.

### Post-promote verification (read-only)

```bash
BASE_URL=https://nuvanx.com bash scripts/staging2/smoke-verify-staging2.sh
```

Expect `SMOKE_VERIFY_OK`.

## Cache flush

```bash
NUVANX_CONFIRM=yes bash tools/deploy/flush-prod-cache.sh \
  --wp-root /home/customer/www/nuvanx.com/public_html \
  --confirm
```

## Migrations

General migrations remain host-authorized and must never run automatically on
push or pull request. A protected, manually approved `workflow_dispatch` may run
a migration only when the exact migration, backup, rollback and verification
contract is versioned in the repository, as implemented for production readiness.

See [tools/migrations/README.md](../../tools/migrations/README.md) for other
migration procedures.

## Pre-deploy audit

Run read-only checks from [tools/audit/README.md](../../tools/audit/README.md)
against Staging2 first.
