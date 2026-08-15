#!/bin/bash
#
# Inject Deploy Stamp into WordPress Environment
#
# Creates an immutable deploy stamp with DEPLOY_SHA, DEPLOY_RUN_ID, DEPLOY_TIMESTAMP, RELEASE_ID
# Injects into WordPress environment and creates deploy-stamp.json file
#
# Usage: ./inject-deploy-stamp.sh <deploy_sha> <deploy_run_id> <release_id> <wordpress_path>
#

set -euo pipefail

DEPLOY_SHA="${1:-}"
DEPLOY_RUN_ID="${2:-}"
RELEASE_ID="${3:-}"
WORDPRESS_PATH="${4:-/home/customer/www/nuvanx-siteground/public_html}"

# Validate inputs
if [ -z "$DEPLOY_SHA" ]; then
  echo "ERROR: DEPLOY_SHA is required"
  exit 1
fi

if [ -z "$DEPLOY_RUN_ID" ]; then
  echo "ERROR: DEPLOY_RUN_ID is required"
  exit 1
fi

if [ -z "$RELEASE_ID" ]; then
  echo "ERROR: RELEASE_ID is required"
  exit 1
fi

# Validate SHA format (40 hex characters)
if ! [[ "$DEPLOY_SHA" =~ ^[0-9a-f]{40}$ ]]; then
  echo "ERROR: DEPLOY_SHA must be a 40-character hex string"
  exit 1
fi

# Generate deploy timestamp
DEPLOY_TIMESTAMP=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

# Create deploy stamp JSON
DEPLOY_STAMP_JSON=$(cat <<EOF
{
  "DEPLOY_SHA": "$DEPLOY_SHA",
  "DEPLOY_RUN_ID": "$DEPLOY_RUN_ID",
  "DEPLOY_TIMESTAMP": "$DEPLOY_TIMESTAMP",
  "RELEASE_ID": "$RELEASE_ID"
}
EOF
)

# Create deploy stamp file in theme data directory
DEPLOY_STAMP_FILE="$WORDPRESS_PATH/wp-content/themes/nuvanx-medical/inc/data/deploy-stamp.json"

echo "$DEPLOY_STAMP_JSON" > "$DEPLOY_STAMP_FILE"

echo "Deploy stamp created: $DEPLOY_STAMP_FILE"
echo "DEPLOY_SHA: $DEPLOY_SHA"
echo "DEPLOY_RUN_ID: $DEPLOY_RUN_ID"
echo "DEPLOY_TIMESTAMP: $DEPLOY_TIMESTAMP"
echo "RELEASE_ID: $RELEASE_ID"

# Verify the file was created
if [ ! -f "$DEPLOY_STAMP_FILE" ]; then
  echo "ERROR: Failed to create deploy stamp file"
  exit 1
fi

echo "Deploy stamp injection completed successfully"