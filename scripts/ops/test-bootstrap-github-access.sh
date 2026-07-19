#!/usr/bin/env bash

set -euo pipefail

readonly ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
readonly SUBJECT="$ROOT/scripts/ops/bootstrap-github-access.sh"
readonly TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

cat > "$TMP/gh" <<'EOF'
#!/usr/bin/env bash
set -euo pipefail
printf '%s\n' "$*" >> "${GH_TEST_LOG:?}"
case "${1:-}" in
  --version) printf 'gh version 2.test\n' ;;
  auth)
    if [[ "${2:-}" == status && ! -f "${GH_TEST_AUTH:?}" ]]; then exit 1; fi
    if [[ "${2:-}" == login ]]; then cat >/dev/null; touch "$GH_TEST_AUTH"; fi
    ;;
  api) printf 'Authenticated as test-user\n' ;;
  repo) printf 'Repository access: Arisofia/nuvanx-siteground\n' ;;
esac
EOF
chmod +x "$TMP/gh"

export GH_TEST_LOG="$TMP/commands.log"
export GH_TEST_AUTH="$TMP/authenticated"
export GH_BIN="$TMP/gh"
export GITHUB_REMOTE_URL="$(git -C "$ROOT" remote get-url origin)"

if "$SUBJECT" >"$TMP/missing-token.out" 2>&1; then
  echo 'Expected missing-token execution to fail' >&2
  exit 1
fi
grep -Fq 'export GH_TOKEN' "$TMP/missing-token.out"

GH_TOKEN='test-token' "$SUBJECT" >"$TMP/token.out"
grep -Fq 'auth login --hostname github.com --git-protocol https --with-token' "$GH_TEST_LOG"
grep -Fq 'GitHub authentication configured' "$TMP/token.out"
grep -Fq 'Repository access: Arisofia/nuvanx-siteground' "$TMP/token.out"

: > "$GH_TEST_LOG"
"$SUBJECT" >"$TMP/existing-auth.out"
if grep -Fq 'auth login' "$GH_TEST_LOG"; then
  echo 'Existing authentication unexpectedly triggered login' >&2
  exit 1
fi
grep -Fq 'Existing GitHub authentication is valid' "$TMP/existing-auth.out"

echo 'GitHub access bootstrap tests passed'
