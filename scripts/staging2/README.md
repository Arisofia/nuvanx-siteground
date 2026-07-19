# Staging2 maintenance scripts

## Deployment identity after a manual upload

When the theme is copied by SSH instead of the deployment workflow, stamp the
immutable source commit before purging caches. Run this from the WordPress
document root and pass the full SHA resolved in the source checkout:

```bash
bash scripts/staging2/stamp-deploy-sha.sh <40-character-git-sha>
wp sg purge
```

The public HTML must then expose the same value in
`meta[name="nvx-deploy-sha"]`. Do not use a branch name or a shortened SHA as
deployment identity.

## Content and navigation cleanup

Run only after a full theme deployment to staging2 has completed, its
`nvx-deploy-sha` marker matches the intended source SHA, and caches have been
purged.

From the staging2 WordPress root:

```bash
wp eval-file scripts/staging2/cleanup-content-navigation.php
```

Review the JSON dry-run output. Then apply deterministic corrections:

```bash
wp eval-file scripts/staging2/cleanup-content-navigation.php --apply
```

The script refuses to run unless both `siteurl` and `home` equal `https://staging2.nuvanx.com` and the active theme is `nuvanx-medical`.

Backups are written outside the public web root under the SSH user's home directory. No backup or database export is stored in Git.

`SEMANTIC_REVIEW_REQUIRED` means that virtual-consultation language or Endolift/radiofrequency conflation remains and must be reviewed manually. The script does not invent medical or operational copy.
