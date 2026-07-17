# Actions budget verification checklist

After runner allocation is restored:

1. Open one pull request targeting `master`.
2. Confirm each required gate runs once for the pull request.
3. Push a second commit to that branch.
4. Confirm the older in-progress run is cancelled.
5. Merge the pull request.
6. Confirm each required gate runs once for the resulting `master` commit.
7. Confirm no standalone feature-branch `push` runs are created.

Close issue #60 only after jobs show actual checkout and test steps rather than
`steps: null`.
