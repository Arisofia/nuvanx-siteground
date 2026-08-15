#!/usr/bin/env bash
set -Eeuo pipefail

: "${NVX_SSH_ALIAS:?Missing NVX_SSH_ALIAS}"
: "${NVX_SSH_KEY_BASENAME:?Missing NVX_SSH_KEY_BASENAME}"
: "${PRIMARY_HOST:?Missing primary SSH host}"
: "${PRIMARY_USER:?Missing primary SSH user}"
: "${PRIMARY_KEY:?Missing primary SSH private key}"
: "${PRIMARY_KNOWN_HOSTS:?Missing primary SSH known_hosts}"
: "${FALLBACK_HOST:?Missing fallback SSH host}"
: "${FALLBACK_USER:?Missing fallback SSH user}"
: "${FALLBACK_KEY:?Missing fallback SSH private key}"
: "${FALLBACK_KNOWN_HOSTS:?Missing fallback SSH known_hosts}"

SSH_RETRY_ATTEMPTS="${SSH_RETRY_ATTEMPTS:-5}"
SSH_RETRY_BASE_DELAY_SECONDS="${SSH_RETRY_BASE_DELAY_SECONDS:-15}"
PRIMARY_PORT="${PRIMARY_PORT:-18765}"
FALLBACK_PORT="${FALLBACK_PORT:-18765}"

[[ "$NVX_SSH_ALIAS" =~ ^[A-Za-z0-9._-]+$ ]]
[[ "$NVX_SSH_KEY_BASENAME" =~ ^[A-Za-z0-9._-]+$ ]]
[[ "$PRIMARY_PORT" =~ ^[0-9]{1,5}$ ]]
[[ "$FALLBACK_PORT" =~ ^[0-9]{1,5}$ ]]
[[ "$SSH_RETRY_ATTEMPTS" =~ ^[1-9][0-9]*$ ]]
[[ "$SSH_RETRY_BASE_DELAY_SECONDS" =~ ^[1-9][0-9]*$ ]]

mkdir -p "$HOME/.ssh"
chmod 700 "$HOME/.ssh"
key_path="$HOME/.ssh/$NVX_SSH_KEY_BASENAME"
known_hosts_path="$HOME/.ssh/known_hosts"
config_path="$HOME/.ssh/config"

classify_ssh_error() {
  local file="$1"
  if grep -Eqi 'Connection timed out|Operation timed out' "$file"; then echo TCP_TIMEOUT
  elif grep -qi 'Connection refused' "$file"; then echo TCP_REFUSED
  elif grep -Eqi 'Connection reset by peer|kex_exchange_identification:.*(Connection closed|Connection reset)|Connection closed by .* port' "$file"; then echo CONNECTION_RESET
  elif grep -Eqi 'No route to host|Network is unreachable' "$file"; then echo NO_ROUTE
  elif grep -Eqi 'Could not resolve hostname|Temporary failure in name resolution|Name or service not known' "$file"; then echo DNS_FAILURE
  elif grep -Eqi 'Host key verification failed|REMOTE HOST IDENTIFICATION HAS CHANGED|No .* host key is known' "$file"; then echo HOST_KEY_FAILURE
  elif grep -Eqi 'Permission denied|Authentication failed|Too many authentication failures|No more authentication methods to try|no mutual signature' "$file"; then echo AUTH_FAILURE
  else echo SSH_HANDSHAKE_OR_UNKNOWN
  fi
}

is_transport_reason() {
  case "$1" in
    TCP_TIMEOUT|TCP_REFUSED|CONNECTION_RESET|NO_ROUTE|DNS_FAILURE) return 0 ;;
    *) return 1 ;;
  esac
}

write_identity() {
  local mode="$1" host="$2" port="$3" user="$4" key="$5" known_hosts="$6"
  printf '%s\n' "$key" > "$key_path"
  chmod 600 "$key_path"
  printf '%s\n' "$known_hosts" > "$known_hosts_path"
  chmod 600 "$known_hosts_path"
  test -s "$known_hosts_path" || { echo "SITEGROUND_SSH=FAIL mode=$mode reason=EMPTY_KNOWN_HOSTS" >&2; return 1; }
  cat > "$config_path" <<EOF
Host $NVX_SSH_ALIAS
  HostName $host
  User $user
  Port $port
  IdentityFile $key_path
  IdentitiesOnly yes
  BatchMode yes
  StrictHostKeyChecking yes
  UserKnownHostsFile $known_hosts_path
  ConnectTimeout 15
  ConnectionAttempts 1
  ServerAliveInterval 15
  ServerAliveCountMax 2
EOF
  chmod 600 "$config_path"
}

probe_identity() {
  local mode="$1"
  local retry_delay="$SSH_RETRY_BASE_DELAY_SECONDS"
  local last_reason='UNKNOWN'
  for attempt in $(seq 1 "$SSH_RETRY_ATTEMPTS"); do
    local err_file="${RUNNER_TEMP:-/tmp}/nvx-${NVX_SSH_ALIAS}-${mode}-attempt-${attempt}.err"
    : > "$err_file"
    if ssh -n "$NVX_SSH_ALIAS" true 2>"$err_file"; then
      echo "SITEGROUND_SSH_ATTEMPT=PASS mode=$mode attempt=$attempt"
      SITEGROUND_LAST_REASON='PASS'
      return 0
    fi
    last_reason="$(classify_ssh_error "$err_file")"
    SITEGROUND_LAST_REASON="$last_reason"
    echo "SITEGROUND_SSH_ATTEMPT=FAIL mode=$mode attempt=$attempt reason=$last_reason" >&2
    tail -n 2 "$err_file" >&2 || true

    if ! is_transport_reason "$last_reason"; then
      echo "SITEGROUND_SSH=FAIL mode=$mode reason=$last_reason retry_or_fallback=forbidden" >&2
      return 2
    fi
    if [[ "$attempt" -ne "$SSH_RETRY_ATTEMPTS" ]]; then
      sleep "$retry_delay"
      retry_delay=$((retry_delay + SSH_RETRY_BASE_DELAY_SECONDS))
    fi
  done
  echo "SITEGROUND_SSH=TRANSPORT_EXHAUSTED mode=$mode reason=$last_reason attempts=$SSH_RETRY_ATTEMPTS" >&2
  return 75
}

write_identity primary "$PRIMARY_HOST" "$PRIMARY_PORT" "$PRIMARY_USER" "$PRIMARY_KEY" "$PRIMARY_KNOWN_HOSTS"
set +e
probe_identity primary
primary_rc=$?
set -e
primary_reason="${SITEGROUND_LAST_REASON:-unknown}"
if [[ "$primary_rc" -eq 0 ]]; then
  [[ -z "${GITHUB_ENV:-}" ]] || echo 'PRODUCTION_SSH_TRANSPORT=primary' >> "$GITHUB_ENV"
  echo 'SITEGROUND_SSH=PASS mode=primary'
  exit 0
fi
if [[ "$primary_rc" -ne 75 ]]; then
  exit 1
fi

# Production SSH endpoint is required - no fallback to staging credentials.
# A transport-only failure (75) means the production endpoint is unreachable,
# which is a hard deployment failure regardless of staging availability.
echo "SITEGROUND_SSH=FAIL mode=primary_unreachable reason=$primary_reason" >&2
exit 255
