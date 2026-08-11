# Self-Hosted GitHub Actions Runner Setup Guide

## Overview
This guide documents how to register a self-hosted GitHub Actions runner on SiteGround to resolve SSH connection timeout issues. The runner executes directly on the SiteGround server, eliminating external SSH connections that are intermittently blocked by SiteGround's IP throttling.

## Important: Token Generation

The runner registration token can be obtained via:
- GitHub Web UI (Settings → Actions → Runners → New self-hosted runner)
- GitHub CLI API: `gh api --method POST repos/{owner}/{repo}/actions/runners/registration-token --jq '.token'`
- GitHub REST API: `POST /repos/{owner}/{repo}/actions/runners/registration-token`

The token expires after 1 hour. The API method is preferred for automation as it doesn't require browser interaction.

## Setup Steps

### Step 1: Generate Token in GitHub Web UI
1. Go to GitHub → Arisofia/nuvanx-siteground → Settings → Actions → Runners
2. Click "New self-hosted runner"
3. Select:
   - Operating system: Linux
   - Architecture: x64
4. GitHub will display configuration commands including:
   ```
   ./config.sh --url https://github.com/Arisofia/nuvanx-siteground --token APTR...
   ```
5. **Copy the token** (the `APTR...` part) - this is the only time you'll see it

### Step 2: Install Runner on SiteGround
Connect to SiteGround via SSH from your local terminal:
```bash
ssh nuvanx-prod   # or nuvanx-staging depending on where you want the runner
```

Once connected:
```bash
# Create runner directory (outside webroot)
mkdir -p ~/github-runner
cd ~/github-runner

# Download runner
curl -o runner.tar.gz -L https://github.com/actions/runner/releases/download/v2.323.0/actions-runner-linux-x64-2.323.0.tar.gz
tar xzf runner.tar.gz && rm runner.tar.gz

# Configure runner (paste the token you copied from GitHub Web UI)
./config.sh \
  --url https://github.com/Arisofia/nuvanx-siteground \
  --token PASTE_YOUR_APTR_TOKEN_HERE \
  --name siteground-nvx \
  --labels siteground,nuvanx \
  --unattended
```

### Step 3: Setup Keep-Alive (SiteGround has no systemd)
```bash
# Create keep-alive script
cat > ~/keep-runner.sh << 'EOF'
#!/bin/bash
RUNNER_DIR="$HOME/github-runner"
PID_FILE="$RUNNER_DIR/.runner.pid"

# If runner is already running, exit
if [[ -f "$PID_FILE" ]]; then
  pid=$(cat "$PID_FILE")
  kill -0 "$pid" 2>/dev/null && exit 0
fi

# Start runner in background
cd "$RUNNER_DIR"
nohup ./run.sh >> "$RUNNER_DIR/runner.log" 2>&1 &
echo $! > "$PID_FILE"
EOF

chmod +x ~/keep-runner.sh

# Add to crontab (check every 5 minutes)
(crontab -l 2>/dev/null; echo "*/5 * * * * $HOME/keep-runner.sh") | crontab -

# Start runner immediately
~/keep-runner.sh
sleep 3 && tail -5 ~/github-runner/runner.log
```

### Step 4: Verify Registration
1. Go back to GitHub → Settings → Actions → Runners
2. You should see `siteground-nvx` in green (online)
3. This confirms the runner is properly registered and running

## Workflow Configuration
The workflows are already configured to use the self-hosted runner:
- `staging.yml` → `deploy_staging` job uses `[self-hosted, siteground]`
- `production.yml` → `release` and `post_audits` jobs use `[self-hosted, siteground]`

Jobs that don't need SSH remain on `ubuntu-latest`:
- Quality checks (linting, schema validation)
- Performance tests (Lighthouse HTTP)
- HubSpot E2E (API calls)

## Troubleshooting
- **Runner not showing in GitHub**: Check runner logs at `~/github-runner/runner.log`
- **Runner keeps stopping**: Verify cron is active with `crontab -l`
- **Connection issues**: This approach eliminates SSH, so network issues should be resolved
- **Need to re-register**: Delete old runner from GitHub Web UI and repeat setup with new token

## Future Reference
- Store this guide for future runner setups
- Token generation always requires GitHub Web UI access
- SiteGround shared hosting lacks systemd, hence the cron-based keep-alive
- Runner should be outside webroot to avoid web access