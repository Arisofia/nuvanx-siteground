# GitHub Actions budget policy

Required validation workflows run in two situations only:

1. pull requests targeting `master`;
2. commits that land on `master`.

Feature-branch `push` events must not duplicate the pull-request validation run.
Each required gate uses a concurrency group and cancels an older in-progress run
when a newer commit supersedes it on the same pull request or ref.

One-shot diagnostic and deployment workflows must be removed after use. Staging2
deployment remains restricted to relevant paths on `master` or explicit manual
dispatch.

This policy reduces private-repository runner usage without weakening the merge
or post-merge validation gates.
