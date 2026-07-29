# Preproduction audit — 2026-07-29

Operational branch used to deploy the exact current master tree to staging2 and run repository hygiene, rendered acceptance, visual QA, and the complete public-route audit before production.

The temporary nonce in `style.css` will be reverted before the final exact-tree validation. This branch must not be merged.
