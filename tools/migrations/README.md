# Retained migrations

This directory contains bounded migration tooling that is **not** part of routine deployment.

A migration remains here only while active code or data compatibility explicitly depends on proof that the migration has completed. After the migration has been executed with backup, dry-run evidence reports a clean state, and the associated compatibility guards are removed, the migration script should be deleted in the same reviewed cleanup cycle.

## `nvx-cms-content-cleanup.php`

Removes residual legacy CMS blocks/claims from WordPress `post_content`. The active theme still contains narrow `TODO(legacy-guard)` compatibility paths conditioned on this migration, so the script is intentionally retained.

Required retirement sequence:

1. `php tools/migrations/nvx-cms-content-cleanup.php --self-test`
2. read-only Staging2 scan with `wp eval-file`
3. backed-up/authorized Staging2 apply if dirty, followed by `dirty=0`
4. backed-up/authorized production apply if dirty, followed by `dirty=0`
5. normal acceptance remains green
6. remove matching compatibility guards and this migration script in a separate reviewed change

Never treat files in this directory as automatic deployment steps.
