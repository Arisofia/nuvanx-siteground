# Editorial Architecture Release Checklist

## Candidate

The candidate is the full 40-character HEAD of `feat/editorial-architecture-phase1-phase2-20260722`.

## Required before staging2 deployment

- Editorial Architecture Gate: PASS.
- Theme Hygiene Gate: PASS.
- Deploy Staging2 contract: PASS.
- Review threads: 0 unresolved.
- PR remains draft.

## Required staging2 execution

Run `Deploy Staging2 (manual)` with:

- ref: the feature branch;
- `git_sha`: exact branch HEAD;
- mode: `DEPLOY_AND_MIGRATE`;
- confirmation: `DEPLOY_STAGING2`.

The same run must confirm:

- SSH and preflight;
- database backup;
- immutable theme payload;
- publication of Phase 1 and Phase 2 pages;
- canonical menu assignment;
- governed redirects;
- independent smoke;
- rendered acceptance;
- internal-link audit;
- deployed marker equal to the requested SHA.

## Required visual QA

Run `Staging2 Visual QA` with the same ref and exact deployed SHA.

The artifact must contain:

- desktop and mobile captures for the 16 governed routes;
- desktop mega-menu open state;
- mobile drawer open state;
- mobile Protocolos Signature accordion open state;
- `report.json` with `ok: true` and no findings;
- no 403 response, no missing H1 and no horizontal overflow.

## Decision

No merge and no production promotion until both workflows pass on the same immutable SHA and the visual artifact has been reviewed.
