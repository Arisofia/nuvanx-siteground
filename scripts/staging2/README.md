# NUVANX Staging2 acceptance

This directory contains the canonical browser acceptance harness used before any production promotion.

## Required release order

1. A candidate SHA contained in `master` is deployed to Staging2.
2. The Staging workflow must complete successfully for that exact SHA.
3. `.nvx-deploy-sha` on Staging2 must equal the candidate SHA.
4. The immutable `staging2-block-c-<SHA>` acceptance artifact must exist and come from a completed successful canonical Staging run.
5. Only after those conditions are true may `production.yml` be dispatched with that accepted candidate SHA.
6. Production verifies the immutable acceptance manifest, acquires the FIFO mutation lease, promotes that exact accepted SHA, and verifies the live disk marker and public boundary.

## Trigger ownership

A repository mutation performed from GitHub Actions with the repository `GITHUB_TOKEN` does not recursively trigger the `push` workflow. After an automation-authored master mutation, Staging acceptance therefore requires an explicit `workflow_dispatch` or a GitHub-native/user-authored master event that creates a canonical Staging run. Never infer acceptance from `master` advancing alone.

## P0-A attribution acceptance

"P0-A attribution" refers to our highest priority user conversion path. Acceptance here ensures we never break core lead routing and guarantees that QA traffic does not pollute production analytics or sales queues.

A Staging2 attribution run is not approved until one new QA submission is proven end-to-end. The acceptance transition is therefore `web_lead_captures: 0 -> 1 QA`; static gates, a successful deploy, or a historical QA contact are not substitutes for this evidence.

The same new UUID must be present across downstream systems with these exact values:
* **HubSpot (CRM):** A contact matching the new UUID must exist.
* **Database (`public.web_lead_captures`):** 
  * `is_test_lead = true`
  * `reconciliation_status = 'qa_suppressed'`
  * `applied_lead_id IS NULL`

To prevent QA data from skewing sales operations or paid media optimization, the QA UUID must produce **no side effects** in the following operational systems:
* **`public.leads`:** No operational row created.
* **HubSpot:** No Deal created.
* **Google Data Manager (Offline Conversions):** No conversion recorded (monitored via GDM logs/dashboards).
* **Meta Events:** No production event dispatched (monitored via Meta Events Manager).

Do not update the production candidate before Staging acceptance is complete. This ordering prevents a production run from racing an in-progress Staging deployment.
