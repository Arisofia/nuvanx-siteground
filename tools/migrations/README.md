# Retained migrations

This directory contains bounded migration tooling. Most scripts are **not** part of routine deployment; the explicit exception is `content-hygiene-shared.php`, which is release-owned and runs inside the protected Staging2/Production deployment transaction together with `audit-content-divergence.php`.

A one-off migration remains here only while active code or data compatibility explicitly depends on proof that the migration has completed. After the migration has been executed with backup, dry-run evidence reports a clean state, and the associated compatibility guards are removed, the migration script should be deleted in the same reviewed cleanup cycle.

## Shared release migration contract

`content-hygiene-shared.php` and `audit-content-divergence.php` form one release contract:

- the audit may report string/regex hygiene or retired-page state as migratable before cutover;
- the shared migration applies those bounded changes;
- the post-migration audit must report `Status: AUDIT_CLEAN`;
- production executes the migration inside the rollback-protected release window after a mandatory SQL/theme snapshot;
- the production database snapshot lives outside `public_html`.

### Retired strategy/prototype pages

The following internal prototype names must never remain as public WordPress content records:

- `/liposculpt-air/` → record status `trash`; HTTP 301 target `/remodelacion-corporal-laser-madrid/`.
- `/v-lift-awake/` → record status `trash`; HTTP 301 target `/papada-definicion-mandibular-madrid/`.

The shared migration uses `wp_trash_post()` and refuses the retirement mutation when `EMPTY_TRASH_DAYS < 1`, preventing accidental permanent deletion. Object-linked and direct custom-link navigation items to these retired routes are removed. The divergence audit treats any non-trash record for either slug as `AUDIT_PENDING_MIGRABLE`, and the post-migration audit must verify no non-trash record remains.

This means these two records must not appear in the published WordPress page inventory, page sitemap or canonical Block C page matrix. Their old URLs remain useful only as permanent redirects for legacy links.

## Audit script exit code semantics

`audit-content-divergence.php` has the following exit code contract:

- **Exit 0, Status: AUDIT_CLEAN** - No issues found.
- **Exit 0, Status: AUDIT_PENDING_MIGRABLE** - Only bounded migratable issues are pending: string/regex hygiene and/or retired prototype page state.
- **Exit 1, Status: AUDIT_FAIL** - Non-migratable issues found, such as H1 problems, missing legal pages or audit database/query errors.

**Important:** Exit 0 does not always mean "content is clean". Callers must grep for the status string to determine actual audit state. This is intentional to allow migratable issues to pass pre-cutover checks while still being fixed by the shared migration.

## `nvx-cms-content-cleanup.php`

Removes residual legacy CMS blocks/claims from WordPress `post_content`. The active theme still contains narrow `TODO(legacy-guard)` compatibility paths conditioned on this migration, so the script is intentionally retained.

Required retirement sequence:

1. `php tools/migrations/nvx-cms-content-cleanup.php --self-test`
2. read-only Staging2 scan with `wp eval "require 'tools/migrations/nvx-cms-content-cleanup.php';"`
3. backed-up/authorized Staging2 apply if dirty, followed by `dirty=0`
4. backed-up/authorized production apply if dirty, followed by `dirty=0`
5. normal acceptance remains green
6. remove matching compatibility guards and this migration script in a separate reviewed change

Never promote a release solely because a migration process exited with code 0; the canonical Staging acceptance and exact-SHA evidence remain mandatory.
