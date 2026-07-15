# Ticket 43 — Final hardening contract

This document records the blocking controls for the editorial-home staging deployment.

## Corrected controls

- The manual workflow requires both `target_sha` and `base_sha`.
- `base_sha` must be an ancestor of `target_sha`, and both values must differ.
- Candidate scope is validated with `git diff "$BASE" "$TARGET"`; an empty diff is rejected.
- Home composition uses semantic classes (`nvx-home-editorial__lead` and `nvx-home-editorial__body`) instead of positional paragraph selectors.
- The architecture gate rejects `nth-child`, `nth-of-type`, non-canonical fonts, `transition: all`, `!important`, legacy markers and versioned runtime classes.
- The six deployed CSS assets are rebuilt during QA and compared byte-for-byte with the committed minified files.
- Staging deployment requires Basic Auth, a complete backup, SHA-256 verification, authenticated HTTP validation, Playwright screenshots, strict home validation and a 27-case route regression matrix.
- Rollback restores page 9 and the complete CSS set without cache purge.
- Production remains read-only until the staging artifacts receive visual approval.

## Required workflow evidence

The successful workflow run must publish:

- changed-file and diff-stat scope reports;
- copy-lock output;
- source and transfer SHA-256 manifests;
- three viewport screenshots, hero/intro crops and full-page screenshots;
- home validation JSON;
- 27-case route-regression JSON;
- delivery manifest containing `base_sha`, `target_sha`, workflow run ID and `production_deployed: false`.

No production deployment, cache purge, visible copy change, route change or hardcoded `front-page.php` is authorized by this hardening commit.
