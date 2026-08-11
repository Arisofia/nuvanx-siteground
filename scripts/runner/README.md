# Self-Hosted GitHub Actions Runner - NOT APPLICABLE

## Status: Self-Hosted Runner Not Viable on SiteGround

A self-hosted runner was evaluated and then reverted. The attempted SiteGround setup failed during runner configuration with:

```text
./config.sh: line 81: File size limit exceeded
```

The canonical workflows therefore remain on GitHub-hosted `ubuntu-latest` runners.

## Current SSH Strategy

The workflows use bounded SSH connection attempts with outer retry/backoff logic. Production uses fail-fast SSH settings (`ConnectTimeout 15`, `ConnectionAttempts 1`) before retrying externally.

Staging run `31446989846` completed successfully end-to-end and validated GitHub-hosted runner → SiteGround SSH connectivity, deployment, Block C and acceptance evidence. In that run the initial SSH connection succeeded in roughly 2.6 seconds, so the retry/backoff recovery path was **not** exercised by an actual failed connection.

Therefore the verified claim is:

- GitHub-hosted runner → SiteGround SSH connectivity works.
- The complete Staging pipeline succeeded on run `31446989846`.
- Retry/backoff logic is present as resilience for intermittent transport failures.
- That specific run does not prove that a retry recovered a timeout, because no initial timeout occurred.

## Token Generation Info (For Reference)

GitHub Actions runner registration tokens can be obtained via:

- GitHub Web UI: Settings → Actions → Runners → New self-hosted runner
- GitHub CLI: `gh api --method POST repos/{owner}/{repo}/actions/runners/registration-token --jq '.token'`
- REST API: `POST /repos/{owner}/{repo}/actions/runners/registration-token`

Tokens expire after 1 hour.

## Why Self-Hosted Runner Was Considered

Intermittent SSH connectivity from GitHub-hosted runners to SiteGround had been observed. A SiteGround-local runner would have removed the external SSH hop, but it also would have materially changed the security boundary by executing workflow code directly on the hosting account.

## Why Self-Hosted Runner Is Not Used Here

1. The attempted SiteGround runner configuration hit the hosting file-size limit shown above.
2. Shared-hosting restrictions make persistent runner supervision unsuitable.
3. No systemd-based service model is available in this hosting context.
4. Running deployment workflows directly on the production hosting account would increase blast radius and requires a separate security design.

## Current Architecture

- Canonical workflows use GitHub-hosted runners.
- SSH uses strict host-key verification and bounded retry logic.
- Exact-SHA Staging acceptance and Production promotion remain the deployment model.
- Self-hosted runner files and workflow references have been removed.
