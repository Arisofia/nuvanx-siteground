#!/usr/bin/env bash
# SSH error classification helper
# Returns classification status and transport_retry flag
# Usage: ssh-error-classify.sh <error_log_file> <ssh_exit_code>
# Outputs: status=<classification> transport_retry=<true|false>

set -euo pipefail

SSH_ERR_FILE="${1:?}"
SSH_RC="${2:-0}"
[[ -f "$SSH_ERR_FILE" ]] || { echo "status=ssh_other transport_retry=false"; exit 0; }

# If SSH exit code is not 255, it's a remote environment error
if [[ "$SSH_RC" -ne 255 ]]; then
  echo "status=remote_env transport_retry=false"
  exit 0
fi

# SSH error classification patterns
TRANSPORT_PATTERNS=(
  'Connection timed out'
  'Operation timed out'
  'Connection refused'
  'Connection reset by peer'
  'No route to host'
  'Network is unreachable'
  'Could not resolve hostname'
  'Temporary failure in name resolution'
  'Name or service not known'
  'kex_exchange_identification:.*Connection closed'
  'kex_exchange_identification:.*Connection reset'
  'Connection closed by .* port'
)

AUTH_PATTERNS=(
  'Permission denied'
  'Authentication failed'
  'Too many authentication failures'
  'No more authentication methods to try'
)

HOSTKEY_PATTERNS=(
  'Host key verification failed'
  'REMOTE HOST IDENTIFICATION HAS CHANGED'
  'No .* host key is known'
)

status='ssh_other'
transport_retry='false'

# Check transport errors (retryable)
for pattern in "${TRANSPORT_PATTERNS[@]}"; do
  if grep -Eqi "$pattern" "$SSH_ERR_FILE"; then
    status='transport'
    transport_retry='true'
    break
  fi
done

# Check auth errors (non-retryable)
if [[ "$status" == 'ssh_other' ]]; then
  for pattern in "${AUTH_PATTERNS[@]}"; do
    if grep -Eqi "$pattern" "$SSH_ERR_FILE"; then
      status='auth'
      break
    fi
  done
fi

# Check hostkey errors (non-retryable)
if [[ "$status" == 'ssh_other' ]]; then
  for pattern in "${HOSTKEY_PATTERNS[@]}"; do
    if grep -Eqi "$pattern" "$SSH_ERR_FILE"; then
      status='hostkey'
      break
    fi
  done
fi

echo "status=$status transport_retry=$transport_retry"
