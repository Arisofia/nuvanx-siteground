# Ticket 43 final QA hardening

This branch corrects the remaining architectural gaps after PR #5:

- semantic intro paragraph classes instead of positional selectors;
- strict rejection of `nth-child` and `nth-of-type` in migrated assets;
- explicit `base_sha` and `target_sha` scope validation;
- non-empty candidate scope evidence;
- deterministic source/minified CSS equivalence;
- delivery manifest traceability for both SHAs.

No production deployment, cache purge, visible copy change, route change or hardcoded `front-page.php` is included.
