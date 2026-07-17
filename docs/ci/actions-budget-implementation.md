# Actions deduplication implementation

Implemented after the repository accumulated more than 2,500 workflow runs.

- Required gates validate pull requests targeting `master`.
- Required gates validate commits that land on `master`.
- Feature-branch pushes no longer duplicate pull-request validation.
- Superseded runs on the same pull request or ref are cancelled.
- The temporary hosted-runner probe was removed.
- The one-shot eight-point staging deployment workflow was removed.

The private-repository hosted-runner quota remains an account-level concern; this
change prevents the repository configuration from consuming duplicate runs once
runner allocation is restored.
