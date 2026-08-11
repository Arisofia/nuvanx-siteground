# Self-Hosted GitHub Actions Runner on SiteGround — Archived

## Status

This setup is **not supported by the current NUVANX deployment architecture**.

A self-hosted runner was tested on the current SiteGround shared-hosting environment and failed during configuration with:

```text
./config.sh: line 81: File size limit exceeded
```

The previous instructions in this document referenced workflow files that no longer exist and would violate the repository invariant that exactly two canonical workflows are present.

## Current Supported Architecture

Use only:

- `.github/workflows/staging.yml`
- `.github/workflows/production.yml`

Both use GitHub-hosted runners and strict SSH transport with bounded retries.

Do **not** create or activate `staging-selfhosted.yml`, `production-selfhosted.yml`, `staging-ssh.yml`, or `production-ssh.yml` variants. Adding extra workflow files will fail the repository/workflow invariant enforced by CI and Production.

## Historical Context

The self-hosted approach was considered to remove dependence on GitHub-hosted runner → SiteGround SSH connectivity. It was not adopted because the tested hosting environment could not configure the runner and because running deployment jobs directly on the WordPress host would expand the security and operational blast radius.

## Re-evaluation Criteria

Revisit a self-hosted runner only if all of the following change and are explicitly re-audited:

- hosting environment supports the runner binary and persistent execution;
- process supervision is supported;
- least-privilege separation between Staging and Production is available;
- repository workflow invariants and concurrency controls are redesigned deliberately;
- security review approves executing CI jobs on the hosting origin.

Until then, this file is historical documentation only and contains no activation procedure.
