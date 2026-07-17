# Actions budget hardening summary

The repository previously ran required gates both on every feature-branch push
and again for the corresponding pull request. This produced duplicate checks and
contributed to more than 2,500 recorded workflow runs.

The corrected policy validates pull requests targeting `master` and commits that
land on `master`, with cancellation of superseded in-progress runs. Temporary
probe and one-shot deployment workflows have been removed.
