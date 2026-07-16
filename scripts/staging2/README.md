# Staging2 maintenance scripts

## Content and navigation cleanup

Run only after the workflow **Deploy theme to staging2 + flush cache** succeeds with ref:

```text
fix/staging2-geo-schema-20260716
```

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