# Staging2 database cleanup execution checklist

## Preconditions

- [ ] Workflow **Deploy theme to staging2 + flush cache** completed with ref `fix/staging2-geo-schema-20260716`.
- [ ] Workflow output contains `DEPLOY_FLUSH_OK`.
- [ ] `siteurl` and `home` are exactly `https://staging2.nuvanx.com`.
- [ ] Active theme is `nuvanx-medical`.
- [ ] Static front page ID is `9`.
- [ ] Production has not been modified.

## Audit

From a checkout containing the reviewed scripts:

```bash
bash scripts/staging2/run-content-cleanup.sh audit
```

Capture the complete output. Review every proposed record change and every semantic finding.

## Apply deterministic corrections

Only after approving the audit output:

```bash
bash scripts/staging2/run-content-cleanup.sh apply CONFIRM-STAGING2
```

Expected markers:

```text
STAGING2_CONTENT_CLEANUP_APPLIED
STAGING2_CONTENT_CLEANUP_COMPLETE
staging_http_status=200
production_modified=false
```

## Mandatory manual review

- [ ] Remove or correct unapproved virtual/online-consultation promises.
- [ ] Correct any content that identifies Endolift as monopolar radiofrequency.
- [ ] Confirm the Goya excerpt punctuation.
- [ ] Confirm the menu label is `Clínica Goya · Barrio Salamanca`.
- [ ] Review Doctoralia separately; repository scripts do not modify it.

## Post-apply validation

- [ ] Re-run audit and confirm zero deterministic changes remain.
- [ ] No incomplete `Solicitar.` CTA.
- [ ] No empty lists.
- [ ] No unintended inline styles in inherited content.
- [ ] No absolute production links inside staging content.
- [ ] `características individuales` is spelled correctly.
- [ ] Forms remain visible and submit correctly.
- [ ] Menu desktop and mobile are correct.
- [ ] Required routes return HTTP 200.
- [ ] No horizontal overflow.
- [ ] Production remains unchanged.

## Release status

Completion of this checklist authorizes staging2 visual and functional QA only. It does not authorize a production deployment.