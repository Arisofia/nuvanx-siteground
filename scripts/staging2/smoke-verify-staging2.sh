#!/usr/bin/env bash
# Smoke-verify key staging2 pages after theme deploy.
# Usage:
#   BASE_URL=https://staging2.nuvanx.com bash scripts/staging2/smoke-verify-staging2.sh
# Exit 0 only if all required markers are present.

set -euo pipefail

BASE_URL="${BASE_URL:-https://staging2.nuvanx.com}"
UA="${SMOKE_UA:-Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36}"
TIMEOUT="${SMOKE_TIMEOUT:-45}"
FAIL=0

fetch() {
  local url="$1"
  curl -fsSL -L \
    --max-time "$TIMEOUT" \
    -A "$UA" \
    -H 'Accept: text/html,application/xhtml+xml' \
    -H 'Accept-Language: es-ES,es;q=0.9' \
    "$url"
}

require() {
  local name="$1"
  local html="$2"
  local needle="$3"
  if printf '%s' "$html" | grep -Fq -- "$needle"; then
    echo "  OK  [$name] $needle"
  else
    echo "  FAIL[$name] missing: $needle" >&2
    FAIL=1
  fi
}

forbid() {
  local name="$1"
  local html="$2"
  local needle="$3"
  if printf '%s' "$html" | grep -Fq -- "$needle"; then
    echo "  FAIL[$name] forbidden marker present: $needle" >&2
    FAIL=1
  else
    echo "  OK  [$name] absent: $needle"
  fi
}

check_page() {
  local path="$1"
  local name="$2"
  shift 2
  local url="${BASE_URL%/}${path}"
  echo "==> $name ($url)"
  local html
  if ! html="$(fetch "$url")"; then
    echo "  FAIL[$name] HTTP fetch failed" >&2
    FAIL=1
    return
  fi
  if [[ ${#html} -lt 500 ]]; then
    echo "  FAIL[$name] response too short (${#html} bytes)" >&2
    FAIL=1
    return
  fi
  local needle
  for needle in "$@"; do
    require "$name" "$html" "$needle"
  done
}

echo "Staging2 smoke verify — ${BASE_URL}"
echo

# Home — values + post-values action banner
check_page "/" "home" \
  'nvx-values-section' \
  'nvx-home-action-banner' \
  'Recupera la armonía' \
  'Reservar valoración gratuita'

# Treatments catalog
check_page "/tratamientos/" "tratamientos" \
  'nvx-catalog' \
  'nvx-logo-cloud'

# Endolift editorial
check_page "/endolift-facial-papada-mandibula/" "endolift" \
  'nvx-endolift-editorial' \
  'nvx-endolift-hero' \
  'Endolift'

# Laser hub editorial
check_page "/medicina-estetica-laser/" "laser-hub" \
  'nvx-laser-editorial' \
  'nvx-laser-hero' \
  'nvx-laser-h1'

# Aesthetic medicine hub (injectables) — present after PR #26
check_page "/medicina-estetica/" "aesthetic-hub" \
  'nvx-brand-page--medicina-estetica' \
  'nvx-med-h1'

# Soft aesthetic markers: warn only if aesthetic module not yet merged
if html_aes="$(fetch "${BASE_URL%/}/medicina-estetica/" 2>/dev/null || true)"; then
  if printf '%s' "$html_aes" | grep -Fq 'nvx-aesthetic-editorial'; then
    require "aesthetic-hub" "$html_aes" 'nvx-aesthetic-editorial'
    require "aesthetic-hub" "$html_aes" 'nvx-aes-hero'
  else
    echo "  WARN[aesthetic-hub] nvx-aesthetic-editorial not live yet (merge PR #26 if expected)"
  fi
fi

# Sanity: theme stylesheet path referenced somewhere
check_page "/" "theme-asset-ref" 'nuvanx-medical'

echo
if [[ "$FAIL" -ne 0 ]]; then
  echo "SMOKE_VERIFY_FAILED" >&2
  exit 1
fi

echo "SMOKE_VERIFY_OK"
exit 0
