# GitHub Actions Runner Strategy

## Status: Self-Hosted Runner Not Applicable on Current SiteGround Environment

A self-hosted GitHub Actions runner was tested on the current SiteGround shared-hosting environment and did not complete configuration.

Observed failure:

```text
./config.sh: line 81: File size limit exceeded
```

The repository therefore uses GitHub-hosted runners (`ubuntu-latest`) and bounded SSH transport. Do not re-enable self-hosted workflow variants without a new infrastructure and security review.

## Current SSH Model

The canonical Staging and Production workflows use:

- strict host-key verification;
- `ConnectTimeout 15`;
- `ConnectionAttempts 1`;
- five externally controlled connection attempts;
- linear 15s, 30s, 45s, 60s backoff between failed probes;
- explicit transport-failure logging.

Run `31446989846` completed successfully and proved GitHub-hosted runner → SiteGround SSH connectivity plus the end-to-end Staging path. Its first SSH connection succeeded, so that run did **not** exercise recovery after a failed connection attempt.

## Production Promotion Gates

Exact SHA equality by itself is not sufficient for Production promotion.

Production first resolves the candidate from the live Staging `.nvx-deploy-sha` marker. It then requires a non-expired artifact named exactly:

```text
staging2-block-c-<candidate-sha>
```

The source Staging run is fetched from the GitHub Actions API and must satisfy all of these conditions:

- `status=completed`;
- `conclusion=success`;
- `head_branch=master`;
- workflow path is `.github/workflows/staging.yml`, optionally followed only by GitHub's `@<ref>` suffix;
- event is a supported canonical Staging event.

For a push-triggered Staging run, `run.head_sha` must equal the candidate SHA exactly.

For a supported manual `workflow_dispatch` that deploys an older master-contained SHA, GitHub records the dispatch ref tip as `run.head_sha`, not necessarily the selected `inputs.sha`. In that case:

- the live Staging marker and exact artifact name bind the selected candidate SHA;
- the dispatch head must remain contained in current `master`;
- the selected candidate SHA must be an ancestor of that dispatch head.

Failed Staging runs are never production-eligible, even if they uploaded diagnostic evidence through `if: always()`.

## Runner Registration Tokens

For reference, repository self-hosted runner registration tokens can be generated through the GitHub UI, CLI, or REST API. They are short-lived and should never be pasted into chat, issues, PR comments, or workflow logs.

Example CLI request:

```bash
gh api --method POST \
  repos/Arisofia/nuvanx-siteground/actions/runners/registration-token \
  --jq '.token'
```

## Canonical Architecture

- Exactly two workflows are supported: `.github/workflows/staging.yml` and `.github/workflows/production.yml`.
- Canonical jobs use GitHub-hosted runners.
- SSH uses strict host-key verification and bounded retries.
- Staging owns deploy, rollback, browser acceptance, and exact-SHA evidence.
- Production promotes only a candidate with valid successful Staging evidence.
