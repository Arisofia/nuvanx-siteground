#!/usr/bin/env bash

set -euo pipefail

readonly DEFAULT_REMOTE_URL="https://github.com/Arisofia/nuvanx-siteground.git"
REMOTE_URL="${GITHUB_REMOTE_URL:-$DEFAULT_REMOTE_URL}"
GH_BIN="${GH_BIN:-gh}"
GIT_BIN="${GIT_BIN:-git}"

log() {
  printf '[github-access] %s\n' "$*"
}

fail() {
  printf '[github-access] ERROR: %s\n' "$*" >&2
  exit 1
}

install_gh() {
  command -v apt-get >/dev/null 2>&1 || fail \
    "gh is missing and apt-get is unavailable; install GitHub CLI from https://cli.github.com/"

  local -a privilege=()
  if (( EUID != 0 )); then
    command -v sudo >/dev/null 2>&1 || fail "root or sudo is required to install GitHub CLI"
    privilege=(sudo)
  fi

  log "Installing GitHub CLI with apt-get"
  "${privilege[@]}" apt-get update
  DEBIAN_FRONTEND=noninteractive "${privilege[@]}" apt-get install -y gh
}

ensure_cli() {
  if ! command -v "$GH_BIN" >/dev/null 2>&1; then
    [[ "$GH_BIN" == "gh" ]] || fail "configured GH_BIN is not executable: $GH_BIN"
    install_gh
  fi
  "$GH_BIN" --version | head -n 1
}

ensure_remote() {
  "$GIT_BIN" rev-parse --is-inside-work-tree >/dev/null 2>&1 || fail "run this command inside a Git repository"

  if "$GIT_BIN" remote get-url origin >/dev/null 2>&1; then
    local current_url
    current_url="$("$GIT_BIN" remote get-url origin)"
    [[ "$current_url" == "$REMOTE_URL" ]] || fail \
      "origin is $current_url; expected $REMOTE_URL (set GITHUB_REMOTE_URL to override)"
  else
    "$GIT_BIN" remote add origin "$REMOTE_URL"
    log "Added origin: $REMOTE_URL"
  fi
}

authenticate() {
  if "$GH_BIN" auth status --hostname github.com >/dev/null 2>&1; then
    log "Existing GitHub authentication is valid"
    return
  fi

  local token="${GH_TOKEN:-${GITHUB_TOKEN:-}}"
  [[ -n "$token" ]] || fail \
    "authentication is required; export GH_TOKEN (preferred) or GITHUB_TOKEN with repository and Actions permissions"

  printf '%s' "$token" | "$GH_BIN" auth login --hostname github.com --git-protocol https --with-token
  "$GH_BIN" auth setup-git
  log "GitHub authentication configured"
}

verify_access() {
  "$GH_BIN" auth status --hostname github.com
  "$GH_BIN" api user --jq '"Authenticated as \(.login)"'
  "$GH_BIN" repo view "${REMOTE_URL#https://github.com/}" --json nameWithOwner \
    --jq '"Repository access: \(.nameWithOwner)"'
}

main() {
  ensure_cli
  ensure_remote
  authenticate
  verify_access
}

main "$@"
