# GitHub CLI access bootstrap

Use the repository bootstrap command to configure the canonical remote, install
GitHub CLI when necessary, authenticate without storing a token in the repository,
and verify access to the repository:

```bash
export GH_TOKEN='<fine-grained token>'
./scripts/ops/bootstrap-github-access.sh
```

The token must grant access to `Arisofia/nuvanx-siteground`. To dispatch and inspect
GitHub Actions, grant read/write access to Actions and read access to repository
contents. Prefer a short-lived, fine-grained token and remove it from the shell when
the operation is complete:

```bash
unset GH_TOKEN GITHUB_TOKEN
```

The command fails closed when `origin` points elsewhere, authentication is missing,
or the authenticated account cannot read the canonical repository. Override the
expected URL only for an intentional fork:

```bash
GITHUB_REMOTE_URL=https://github.com/OWNER/nuvanx-siteground.git \
  ./scripts/ops/bootstrap-github-access.sh
```

Network policy is external to the repository. If a managed proxy rejects Ubuntu or
GitHub endpoints, allow HTTPS access to `archive.ubuntu.com`,
`security.ubuntu.com`, `github.com`, and `api.github.com`, then rerun the command.
The bootstrap does not disable TLS verification or bypass the proxy.

Run the deterministic contract test with:

```bash
./scripts/ops/test-bootstrap-github-access.sh
```
