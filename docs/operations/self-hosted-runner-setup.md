# Self-Hosted GitHub Actions Runner Setup for SiteGround

## Problem
SiteGround intermittently blocks GitHub Actions runner IPs, causing SSH connection timeouts. This is an architectural issue that cannot be solved with timeout parameters.

## Solution
Deploy a self-hosted GitHub Actions runner directly on the SiteGround server. This eliminates the need for external SSH connections since the runner executes within the server environment.

## Setup Instructions

### Step 1: Obtain Runner Token
1. Go to GitHub → Settings → Actions → Runners
2. Click "New self-hosted runner"
3. Select "Linux" architecture
4. Copy the token (save it for step 2)

### Step 2: Install Runner on SiteGround
SSH into your SiteGround server and run:

```bash
# Create directory for runner
mkdir -p ~/actions-runner && cd ~/actions-runner

# Download runner
curl -o actions-runner-linux-x64-2.323.0.tar.gz -L \
  https://github.com/actions/runner/releases/download/v2.323.0/actions-runner-linux-x64-2.323.0.tar.gz

# Extract runner
tar xzf ./actions-runner-linux-x64-2.323.0.tar.gz

# Configure runner
./config.sh --url https://github.com/Arisofia/nuvanx-siteground \
            --token YOUR_RUNNER_TOKEN \
            --name siteground-production \
            --labels siteground,production \
            --unattended

# Start runner as background process
./run.sh &
```

### Step 3: Make Runner Persistent
Create a systemd service to keep the runner running:

```bash
# Create systemd service file
sudo tee /etc/systemd/system/actions-runner.service > /dev/null <<EOF
[Unit]
Description=GitHub Actions Runner
After=network.target

[Service]
Type=simple
User=YOUR_SITEGROUND_USER
WorkingDirectory=/home/YOUR_SITEGROUND_USER/actions-runner
ExecStart=/home/YOUR_SITEGROUND_USER/actions-runner/run.sh
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

# Enable and start service
sudo systemctl daemon-reload
sudo systemctl enable actions-runner
sudo systemctl start actions-runner
```

### Step 4: Update Workflow Files
The repository now includes self-hosted runner workflow files:
- `.github/workflows/staging-selfhosted.yml` - Staging workflow for self-hosted runner
- `.github/workflows/production-selfhosted.yml` - Production workflow for self-hosted runner

Key changes from SSH-based workflows:
- `runs-on: siteground` instead of `runs-on: ubuntu-latest`
- No SSH configuration steps
- Direct command execution instead of `ssh host "command"`
- Eliminated all SSH retry logic (no longer needed)

### Step 5: Switch to Self-Hosted Workflows
1. Rename current workflows:
   ```bash
   mv .github/workflows/staging.yml .github/workflows/staging-ssh.yml
   mv .github/workflows/production.yml .github/workflows/production-ssh.yml
   ```

2. Activate self-hosted workflows:
   ```bash
   mv .github/workflows/staging-selfhosted.yml .github/workflows/staging.yml
   mv .github/workflows/production-selfhosted.yml .github/workflows/production.yml
   ```

3. Commit and push changes

## Benefits
- **No SSH connectivity issues** - Runner is local to the server
- **Faster deployments** - No SSH connection overhead
- **More reliable** - Eliminates external network dependencies
- **Cost-effective** - Uses existing server resources

## Maintenance
- Monitor runner status in GitHub Actions settings
- Update runner binary periodically (check for new releases)
- Check runner logs if deployments fail
- Ensure systemd service stays running

## Rollback Plan
If issues arise with self-hosted runner:
1. Stop the runner: `sudo systemctl stop actions-runner`
2. Revert to SSH-based workflows
3. Use SSH workflows as fallback
