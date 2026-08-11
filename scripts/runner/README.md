# Self-Hosted GitHub Actions Runner - NOT APPLICABLE

## Status: Self-Hosted Runner Not Viable on SiteGround

**SiteGround shared hosting has file size limits that prevent GitHub Actions self-hosted runner configuration.**

Attempted installation resulted in:
```
./config.sh: line 81: File size limit exceeded
```

## Current Solution: SSH Retry Loops

The SSH timeout issue has been resolved using retry loops with backoff in the workflows:
- `production.yml` - SSH retry loops with 15s×attempt backoff for all 3 SSH connection points
- `staging.yml` - Existing SSH retry pattern (already had working retry logic)

This is the working solution without requiring self-hosted runner infrastructure.

## Token Generation Info (For Reference)

GitHub Actions runner registration tokens can be obtained via:
- GitHub Web UI: Settings → Actions → Runners → New self-hosted runner
- GitHub CLI: `gh api --method POST repos/{owner}/{repo}/actions/runners/registration-token --jq '.token'`
- REST API: `POST /repos/{owner}/{repo}/actions/runners/registration-token`

Tokens expire after 1 hour.

## Why Self-Hosted Runner Was Considered

GitHub Actions external runners are intermittently blocked by SiteGround's IP throttling, causing SSH connection timeouts. Self-hosted runner would eliminate external SSH connections by running directly on SiteGround.

## Why Self-Hosted Runner Is Not Viable Here

1. **SiteGround file size limits** prevent runner configuration
2. **Shared hosting restrictions** on background processes
3. **No systemd available** (requires cron-based workarounds)
4. **Security concerns** with sensitive jobs running on production server

## Alternative Approaches (Not Currently Implemented)

If SiteGround environment limitations are resolved in the future:
1. Dedicated server/VPS hosting (no file size limits)
2. Cloud runner with VPN tunnel to SiteGround
3. Alternative deployment method (SFTP/Webhook instead of SSH)

## Current Architecture Remains Valid

- Workflows use `ubuntu-latest` GitHub Actions runners
- SSH retry loops handle intermittent SiteGround IP blocking
- Exact-SHA promotion model preserved
- Security boundaries maintained