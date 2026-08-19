# NUVANX Staging2 acceptance

This directory contains the canonical browser acceptance harness used before any production promotion.

## Required release order

1. A candidate SHA contained in `master` is deployed to Staging2.
2. The Staging workflow must complete successfully for that exact SHA.
3. `.nvx-deploy-sha` on Staging2 must equal the candidate SHA.
4. The immutable `staging2-block-c-<SHA>` acceptance artifact must exist and come from a completed successful canonical Staging run.
5. Only after those conditions are true may `production.yml` be dispatched with that accepted candidate SHA.
6. Production verifies the immutable acceptance manifest, acquires the FIFO mutation lease, promotes that exact accepted SHA, and verifies the live disk marker and public boundary.

## P0-A attribution acceptance

A Staging2 attribution run is not approved until one new QA submission is proven end to end. The same new UUID must be present in HubSpot and `public.web_lead_captures`, with `is_test_lead = true`, `reconciliation_status = 'qa_suppressed'`, and `applied_lead_id IS NULL`. The acceptance transition is therefore `web_lead_captures: 0 -> 1 QA`; static gates, a successful deploy, or a historical QA contact are not substitutes for this evidence.

The QA UUID must produce no operational `public.leads` row, HubSpot Deal, Google Data Manager/offline-conversion side effect, or production Meta event.

Do not update the production candidate before Staging acceptance is complete. This ordering prevents a production run from racing an in-progress Staging deployment.
