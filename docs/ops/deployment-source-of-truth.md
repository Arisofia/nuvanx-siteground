# NUVANX deployment source of truth

## Canonical repository state

- Repository: `Arisofia/nuvanx-siteground`
- Canonical branch: `master`
- Canonical WordPress theme: `wp-content/themes/nuvanx-medical`
- Deployment identity: full 40-character Git commit SHA
- Review environment: `https://staging2.nuvanx.com`
- Production environment: `https://nuvanx.com`

Branch names, tags and the literal value `master` are mutable references. They may select a checkout, but they are never accepted as proof of what is deployed.

## Runtime identity contract

Each deployment workflow resolves the checked-out commit with:

```bash
git rev-parse HEAD
```

The workflow writes that exact value to:

```text
wp-content/themes/nuvanx-medical/.nvx-deploy-sha
```

The theme exposes it in public HTML:

```html
<meta name="nvx-deploy-sha" content="<40-character-sha>" />
```

The value is non-secret. Its only purpose is immutable deployment traceability.

## Staging2 contract

1. Checkout the requested ref.
2. Resolve the immutable SHA.
3. Stamp `.nvx-deploy-sha` before `rsync`.
4. Verify the marker on the remote filesystem.
5. Purge caches.
6. Verify the rendered marker on all P0 routes.
7. Run the structural smoke suite.

The verified SHA emitted by the staging2 workflow is the only valid production candidate.

## Production promotion contract

Production accepts only a full 40-character SHA. Before any backup or write:

1. The workflow checks out that exact SHA.
2. It verifies staging2 currently renders the same SHA and passes the P0 contract.
3. It stamps the same marker into the production checkout.
4. It backs up the current production theme and MU plugins.
5. It promotes the checkout.
6. It verifies the remote marker.
7. It purges caches.
8. It verifies production renders the same SHA.

A branch name, tag, shortened SHA or a SHA not currently validated on staging2 is rejected.

## Required environment inventory

The deployment record for each release must include:

- exact Git SHA;
- workflow run URL;
- active theme slug;
- WordPress `siteurl` and `home` validation;
- PHP and WordPress versions;
- required MU plugins;
- backup location and checksums;
- P0 verification result;
- smoke-test result;
- rollback target.

Secrets and secret values must never be written to this document, workflow summaries or public HTML.
