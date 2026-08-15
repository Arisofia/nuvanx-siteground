#!/usr/bin/env bash
set -Eeuo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
HELPER="$ROOT/scripts/production/configure-siteground-ssh.sh"
TMP_ROOT="$(mktemp -d)"
trap 'rm -rf "$TMP_ROOT"' EXIT
mkdir -p "$TMP_ROOT/bin"

cat > "$TMP_ROOT/bin/ssh" <<'MOCK'
#!/usr/bin/env bash
set -euo pipefail
calls_file="${MOCK_SSH_CALLS:?}"
config="${HOME:?}/.ssh/config"
host="$(sed -nE 's/^[[:space:]]*HostName[[:space:]]+(.+)$/\1/p' "$config" | head -n1)"
printf '%s\n' "$host" >> "$calls_file"
case "${MOCK_SSH_MODE:?}:$host" in
  primary-pass:primary.example) exit 0 ;;
  fallback-pass:primary.example)
    echo 'ssh: connect to host primary.example port 18765: Connection timed out' >&2
    exit 255
    ;;
  fallback-pass:fallback.example) exit 0 ;;
  primary-auth:primary.example)
    echo 'user@primary.example: Permission denied (publickey).' >&2
    exit 255
    ;;
  primary-hostkey:primary.example)
    echo 'Host key verification failed.' >&2
    exit 255
    ;;
  fallback-auth:primary.example)
    echo 'ssh: connect to host primary.example port 18765: Connection timed out' >&2
    exit 255
    ;;
  fallback-auth:fallback.example)
    echo 'fallback@fallback.example: Permission denied (publickey).' >&2
    exit 255
    ;;
  *)
    echo "unexpected mock invocation mode=${MOCK_SSH_MODE:-unset} host=$host" >&2
    exit 99
    ;;
esac
MOCK
chmod +x "$TMP_ROOT/bin/ssh"

run_case() {
  local name="$1" mode="$2" expected_rc="$3" expected_transport="$4" expected_calls="$5"
  local case_root="$TMP_ROOT/$name"
  mkdir -p "$case_root/home" "$case_root/runtime"
  local env_file="$case_root/github-env"
  local calls_file="$case_root/calls"
  : > "$env_file"
  : > "$calls_file"

  set +e
  PATH="$TMP_ROOT/bin:$PATH" \
  HOME="$case_root/home" \
  RUNNER_TEMP="$case_root/runtime" \
  GITHUB_ENV="$env_file" \
  MOCK_SSH_CALLS="$calls_file" \
  MOCK_SSH_MODE="$mode" \
  NVX_SSH_ALIAS='nvx-prod' \
  NVX_SSH_KEY_BASENAME='prod_key' \
  PRIMARY_HOST='primary.example' \
  PRIMARY_PORT='18765' \
  PRIMARY_USER='primary-user' \
  PRIMARY_KEY='PRIMARY-KEY' \
  PRIMARY_KNOWN_HOSTS='primary.example ssh-ed25519 AAAAPRIMARY' \
  FALLBACK_HOST='fallback.example' \
  FALLBACK_PORT='18765' \
  FALLBACK_USER='fallback-user' \
  FALLBACK_KEY='FALLBACK-KEY' \
  FALLBACK_KNOWN_HOSTS='fallback.example ssh-ed25519 AAAAFALLBACK' \
  SSH_RETRY_ATTEMPTS='1' \
  SSH_RETRY_BASE_DELAY_SECONDS='1' \
  bash "$HELPER" >"$case_root/stdout" 2>"$case_root/stderr"
  local rc=$?
  set -e

  [[ "$rc" -eq "$expected_rc" ]] || {
    echo "SSH_HELPER_TEST=FAIL case=$name expected_rc=$expected_rc actual_rc=$rc" >&2
    cat "$case_root/stdout" "$case_root/stderr" >&2 || true
    exit 1
  }

  local calls
  calls="$(wc -l < "$calls_file" | tr -d ' ')"
  [[ "$calls" -eq "$expected_calls" ]] || {
    echo "SSH_HELPER_TEST=FAIL case=$name expected_calls=$expected_calls actual_calls=$calls" >&2
    cat "$calls_file" >&2 || true
    exit 1
  }

  if [[ -n "$expected_transport" ]]; then
    grep -Fxq "PRODUCTION_SSH_TRANSPORT=$expected_transport" "$env_file" || {
      echo "SSH_HELPER_TEST=FAIL case=$name missing_transport=$expected_transport" >&2
      cat "$env_file" >&2 || true
      exit 1
    }
  else
    ! grep -Fq 'PRODUCTION_SSH_TRANSPORT=' "$env_file" || {
      echo "SSH_HELPER_TEST=FAIL case=$name unexpected_transport" >&2
      cat "$env_file" >&2 || true
      exit 1
    }
  fi

  echo "SSH_HELPER_TEST=PASS case=$name rc=$rc calls=$calls transport=${expected_transport:-none}"
}

run_case primary-pass primary-pass 0 primary 1
run_case transport-fallback fallback-pass 0 staging-endpoint-fallback 2
run_case auth-fail-closed primary-auth 1 '' 1
run_case hostkey-fail-closed primary-hostkey 1 '' 1
run_case fallback-auth-fail-closed fallback-auth 255 '' 2

echo 'SITEGROUND_SSH_HELPER_CONTRACT=PASS cases=5'
