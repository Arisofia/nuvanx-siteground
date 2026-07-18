#!/usr/bin/env bash
# Read-only post-promote verification against the master editorial stack.
#
# Usage:
#   BASE_URL=https://nuvanx.com bash scripts/ops/post-promote-verify.sh
#   BASE_URL=https://staging2.nuvanx.com bash scripts/ops/post-promote-verify.sh
#
# Exit 0 only if all required markers match. Does not mutate any system.

set -euo pipefail

BASE_URL="${BASE_URL:-https://nuvanx.com}"
UA="${SMOKE_UA:-Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36}"
TIMEOUT="${SMOKE_TIMEOUT:-25}"
RETRIES="${SMOKE_RETRIES:-3}"
RETRY_SLEEP="${SMOKE_RETRY_SLEEP:-3}"
FAIL=0

fetch() {
  local url="$1"
  local attempt=1
  local html=""
  while (( attempt <= RETRIES )); do
    if html="$(curl -fsSL -L --connect-timeout 10 --max-time "$TIMEOUT" -A "$UA" \
      -H 'Accept: text/html,application/xhtml+xml' \
      -H 'Accept-Language: es-ES,es;q=0.9' \
      -H 'Cache-Control: no-cache' \
      -H 'Pragma: no-cache' \
      "$url" 2>/dev/null)"; then
      if [[ ${#html} -ge 500 ]]; then
        printf '%s' "$html"
        return 0
      fi
    fi
    if (( attempt < RETRIES )); then
      sleep "$RETRY_SLEEP"
    fi
    attempt=$((attempt + 1))
  done
  return 1
}

need() {
  local name="$1" html="$2" needle="$3"
  if [[ "$html" == *"$needle"* ]]; then
    echo "  OK  [$name] $needle"
  else
    echo "  FAIL[$name] missing: $needle" >&2
    FAIL=1
  fi
}

# Legacy markers must be gone after promote (hard fail, not soft warn).
forbid() {
  local name="$1" html="$2" needle="$3"
  if [[ "$html" != *"$needle"* ]]; then
    echo "  OK  [$name] absent: $needle"
  else
    echo "  FAIL[$name] legacy still present: $needle" >&2
    FAIL=1
  fi
}

echo "Post-promote verify — $BASE_URL"
echo "Required = master editorial stack (CSS source + modules + blog cards)."
echo

echo "==> home"
if html="$(fetch "${BASE_URL%/}/")"; then
  need home "$html" 'nuvanx-medical'
  need home "$html" 'nvx-header'
  need home "$html" 'nvx-footer'
  need home "$html" 'nvx-tokens.css'
  need home "$html" 'nvx-patterns-editorial.css'
  need home "$html" 'nvx-values-section'
  need home "$html" 'data-nvx-home-copy'
  need home "$html" 'nvx-home-action-banner'
  forbid home "$html" 'nvx-tokens.min.css'
  forbid home "$html" 'nvx-pages.min.css'
  # Production must stay indexable; staging intentionally uses noindex.
  if [[ "$BASE_URL" == https://nuvanx.com* ]]; then
    if printf '%s' "$html" | grep -qiE '<meta[^>]+name=["'\'']robots["'\''][^>]+content=["'\''][^"'\'']*noindex'; then
      echo "  FAIL[home] production robots contains noindex" >&2
      FAIL=1
    else
      echo "  OK  [home] robots indexable (no noindex meta)"
    fi
  fi
else
  echo "  FAIL[home] fetch" >&2
  FAIL=1
fi

echo "==> blog"
if html="$(fetch "${BASE_URL%/}/blog/")"; then
  need blog "$html" 'nvx-blog-archive'
  need blog "$html" 'nvx-blog-card'
  need blog "$html" 'nvx-blog-grid'
  need blog "$html" 'nvx-posts.css'
  forbid blog "$html" 'nvx-journal-item__title'
  forbid blog "$html" 'nvx-posts.min.css'
else
  echo "  FAIL[blog] fetch" >&2
  FAIL=1
fi

for pair in \
  "equipo|/equipo-medico/|nvx-equipo-editorial" \
  "clinicas|/clinicas-de-medicina-estetica-nuvanx/|nvx-clinics-nav" \
  "laser|/medicina-estetica-laser/|nvx-laser-editorial" \
  "aesthetic|/medicina-estetica/|nvx-aesthetic-editorial" \
  "endolift|/endolift-facial-papada-mandibula/|nvx-endolift-editorial" \
  "tratamientos|/tratamientos/|nvx-catalog"
do
  name="${pair%%|*}"
  rest="${pair#*|}"
  path="${rest%%|*}"
  marker="${rest#*|}"
  echo "==> $name"
  if html="$(fetch "${BASE_URL%/}${path}")"; then
    need "$name" "$html" "$marker"
    need "$name" "$html" 'nvx-header'
    need "$name" "$html" 'nvx-tokens.css'
    forbid "$name" "$html" 'nvx-tokens.min.css'
  else
    echo "  FAIL[$name] fetch" >&2
    FAIL=1
  fi
done

echo
if [[ "$FAIL" -ne 0 ]]; then
  echo "POST_PROMOTE_VERIFY_FAILED" >&2
  exit 1
fi
echo "POST_PROMOTE_VERIFY_OK"
exit 0
