#!/usr/bin/env bash
# Smoke-verify key staging2 pages after theme deploy.
# Anchors on stable structural markers (classes / ids / data attributes only).
# Do not require human-readable editorial copy — copy changes must not fail CI.
#
# Usage:
#   BASE_URL=https://staging2.nuvanx.com bash scripts/staging2/smoke-verify-staging2.sh

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
  # Bash substring match avoids SIGPIPE/broken-pipe on huge HTML pipes.
  if [[ "$html" == *"$needle"* ]]; then
    echo "  OK  [$name] $needle"
  else
    echo "  FAIL[$name] missing structural marker: $needle" >&2
    FAIL=1
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
echo "Markers are structural only (classes / ids / data-*)."
echo

# Home — values pillars + post-values action banner (stable ids/classes)
check_page "/" "home" \
  'nvx-values-section' \
  'class="nvx-values"' \
  'id="nvx-post-values-action-banner"' \
  'data-nvx-action-banner="post-values"' \
  'nvx-home-action-banner'

# Treatments catalog
check_page "/tratamientos/" "tratamientos" \
  'nvx-catalog' \
  'nvx-logo-cloud'

# Endolift editorial hub
check_page "/endolift-facial-papada-mandibula/" "endolift" \
  'nvx-endolift-editorial' \
  'nvx-endolift-hero' \
  'id="nvx-endolift-h1"'

# Laser hub editorial
check_page "/medicina-estetica-laser/" "laser-hub" \
  'nvx-laser-editorial' \
  'nvx-laser-hero' \
  'id="nvx-laser-h1"'

# Aesthetic medicine hub (injectables)
check_page "/medicina-estetica/" "aesthetic-hub" \
  'nvx-brand-page--medicina-estetica' \
  'id="nvx-med-h1"'

# Soft: full aesthetic rebuild present when module is live
if html_aes="$(fetch "${BASE_URL%/}/medicina-estetica/" 2>/dev/null || true)"; then
  if [[ "$html_aes" == *'nvx-aesthetic-editorial'* ]]; then
    require "aesthetic-hub" "$html_aes" 'nvx-aesthetic-editorial'
    require "aesthetic-hub" "$html_aes" 'nvx-aes-hero'
  else
    echo "  WARN[aesthetic-hub] nvx-aesthetic-editorial not present (module may be absent on this ref)"
  fi
fi

# Theme handle present in document (stylesheet or body class)
check_page "/" "theme-asset-ref" 'nuvanx-medical'

echo
if [[ "$FAIL" -ne 0 ]]; then
  echo "SMOKE_VERIFY_FAILED" >&2
  exit 1
fi

echo "SMOKE_VERIFY_OK"
exit 0
