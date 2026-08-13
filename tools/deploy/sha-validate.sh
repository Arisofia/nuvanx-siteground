#!/usr/bin/env bash
# SHA normalization and validation helper
# Usage: sha-validate.sh <candidate_sha>
# Outputs: NORMALIZED_SHA=<normalized_sha> or exits with error if invalid

set -euo pipefail

CANDIDATE_SHA="${1:?}"
NORMALIZED_SHA="$(printf '%s' "$CANDIDATE_SHA" | tr -d '[:space:]')"

[[ "$NORMALIZED_SHA" =~ ^[0-9a-f]{40}$ ]] || {
  echo 'Candidate must be a full lowercase 40-character SHA.' >&2
  exit 1
}

echo "NORMALIZED_SHA=$NORMALIZED_SHA"
