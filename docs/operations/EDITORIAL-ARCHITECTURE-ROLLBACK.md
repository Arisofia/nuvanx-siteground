# Editorial Architecture Staging Rollback

The guarded staging deployment already creates a database backup and a theme backup before mutation.

Additional rollback points:

- The previous WordPress menu remains present but unassigned.
- The migration is idempotent and can reconstruct `NUVANX Principal`.
- Retired pages use WordPress Trash rather than direct status mutation.
- Eye Frame remains a draft or absent page.
- A deployment is accepted only when the served marker equals the immutable requested SHA.

Production rollback is outside this release because production is not modified.
