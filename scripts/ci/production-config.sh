#!/usr/bin/env bash
# Production configuration for one-shot operational runs
# This file centralizes production paths and expected states for operational workflows

# Production paths
PROD_ROOT="/home/customer/www/nuvanx.com/public_html"
PROD_PARENT="/home/customer/www/nuvanx.com"

# Expected master SHA for this operational run
EXPECTED_MASTER_SHA="cef5dfb0fa23db5d5b3c37bd5d6dfcc3dc1910c5"

# Operational branch name (parameterized for reuse)
OPERATIONAL_BRANCH="${OPERATIONAL_BRANCH:-ops/retire-meta-mu-owner-20260821}"

# MU-plugin to retire
MU_PLUGIN_NAME="nuvanx-meta-dedupe-event-id.php"
MU_PLUGIN_PATH="${PROD_ROOT}/wp-content/mu-plugins/${MU_PLUGIN_NAME}"

# SSH alias for production
PROD_SSH_ALIAS="${PROD_SSH_ALIAS:-nvx-prod}"

echo "PRODUCTION_CONFIG_LOADED=1"
