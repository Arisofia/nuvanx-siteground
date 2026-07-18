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
# After cache flush / edge purge, staging sometimes returns tiny HTML briefly.
RETRIES="${SMOKE_RETRIES:-6}"
RETRY_SLEEP="${SMOKE_RETRY_SLEEP:-5}"
MIN_BYTES="${SMOKE_MIN_BYTES:-500}"
FAIL=0

fetch_once() {
  local url="$1"
  curl -fsSL -L \
    --max-time "$TIMEOUT" \
    -A "$UA" \
    -H 'Accept: text/html,application/xhtml+xml' \
    -H 'Accept-Language: es-ES,es;q=0.9' \
    -H 'Cache-Control: no-cache' \
    -H 'Pragma: no-cache' \
    "$url"
}

# Retry on transport failure or suspiciously short bodies (edge/WAF blip).
fetch() {
  local url="$1"
  local attempt=1
  local html=""
  local last_err=""

  while (( attempt <= RETRIES )); do
    if html="$(fetch_once "$url" 2>/tmp/nvx-smoke-curl.err)"; then
      if [[ ${#html} -ge "$MIN_BYTES" ]]; then
        printf '%s' "$html"
        return 0
      fi
      last_err="response too short (${#html} bytes)"
      echo "  … retry $attempt/$RETRIES: $last_err" >&2
    else
      last_err="HTTP fetch failed"
      if [[ -s /tmp/nvx-smoke-curl.err ]]; then
        last_err="$last_err ($(tr '\n' ' ' </tmp/nvx-smoke-curl.err | head -c 160))"
      fi
      echo "  … retry $attempt/$RETRIES: $last_err" >&2
    fi
    if (( attempt < RETRIES )); then
      sleep "$RETRY_SLEEP"
    fi
    attempt=$((attempt + 1))
  done

  echo "  FINAL_FAIL fetch $url — $last_err" >&2
  if [[ -n "${html:-}" ]]; then
    echo "  body_head=$(printf '%s' "$html" | head -c 240 | tr '\n' ' ')" >&2
  fi
  return 1
}

require() {
  local name="$1"
  local html="$2"
  local needle="$3"
  if [[ "$html" == *"$needle"* ]]; then
    echo "  OK  [$name] $needle"
  else
    echo "  FAIL[$name] missing structural marker: $needle" >&2
    FAIL=1
  fi
}

forbid() {
  local name="$1"
  local html="$2"
  local needle="$3"
  if [[ "$html" == *"$needle"* ]]; then
    echo "  FAIL[$name] forbidden legacy marker present: $needle" >&2
    FAIL=1
  else
    echo "  OK  [$name] legacy marker absent: $needle"
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
    echo "  FAIL[$name] could not fetch a usable HTML body" >&2
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
echo "Retries=${RETRIES} sleep=${RETRY_SLEEP}s min_bytes=${MIN_BYTES}"
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

# Clinics hub — full section shell, accessible anchors and current CSS stack.
CLINICS_PATH='/clinicas-de-medicina-estetica-nuvanx/'
check_page "$CLINICS_PATH" "clinics-hub" \
  'nvx-clinics-content-flow' \
  'id="nvx-clinics-nav"' \
  'id="clinica-chamberi"' \
  'id="clinica-goya"' \
  'nvx-tokens.css' \
  'nvx-site-layout.css' \
  'nvx-patterns-editorial.css'

if html_clinics="$(fetch "${BASE_URL%/}${CLINICS_PATH}" 2>/dev/null || true)"; then
  forbid "clinics-hub" "$html_clinics" 'nvx-tokens.min.css'
  forbid "clinics-hub" "$html_clinics" 'nvx-layout.min.css'
  forbid "clinics-hub" "$html_clinics" 'nvx-patterns.min.css'
  forbid "clinics-hub" "$html_clinics" 'class="nvx-brand-readable nvx-brand-readable--wide"><div aria-labelledby="nvx-clinics-h1"'
fi

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

# Equipo authority markers (triple Physician page)
check_page "/equipo-medico/" "equipo" \
  'nvx-equipo-editorial' \
  'physician-rivera-tejeda' \
  'physician-rivera-deras' \
  'physician-quinonez-bareiro'

# Journal / blog editorial system (PR #83)
check_page "/blog/" "blog-index" \
  'nvx-blog-context' \
  'nvx-blog-archive' \
  'nvx-blog-grid' \
  'nvx-blog-card' \
  'nvx-posts.css'

# Soft: page 2 may be empty on sparse staging; only require shell if HTML is long enough.
if html_blog2="$(fetch "${BASE_URL%/}/blog/page/2/" 2>/dev/null || true)"; then
  if [[ ${#html_blog2} -ge "$MIN_BYTES" ]]; then
    require "blog-page-2" "$html_blog2" 'nvx-blog-archive'
    require "blog-page-2" "$html_blog2" 'nvx-posts.css'
  else
    echo "  WARN[blog-page-2] short/empty response — skipped"
  fi
else
  echo "  WARN[blog-page-2] could not fetch — skipped"
fi

# Search must use journal shell and post-only query (template markers).
check_page "/?s=endolift" "blog-search" \
  'nvx-blog-context' \
  'nvx-blog-archive' \
  'nvx-posts.css'

# Discover first article card + category from /blog/ for deeper markers.
to_path() {
  # Absolute URL → path; leave relative paths intact.
  local u="$1"
  if [[ "$u" == http://* || "$u" == https://* ]]; then
    printf '%s' "$u" | sed -E 's#https?://[^/]+##'
  else
    printf '%s' "$u"
  fi
}

if html_blog="$(fetch "${BASE_URL%/}/blog/" 2>/dev/null || true)"; then
  post_url="$(printf '%s' "$html_blog" | tr '\n' ' ' | sed -n 's/.*class="nvx-blog-card__title"><a href="\([^"]\+\)".*/\1/p' | head -n1 || true)"
  cat_url="$(printf '%s' "$html_blog" | tr '\n' ' ' | sed -n 's/.*class="nvx-blog-card__category"><a href="\([^"]\+\)".*/\1/p' | head -n1 || true)"

  if [[ -n "${post_url:-}" ]]; then
    post_path="$(to_path "$post_url")"
    echo "==> blog-single (discovered $post_path)"
    if html_post="$(fetch "${BASE_URL%/}${post_path}" 2>/dev/null || true)"; then
      require "blog-single" "$html_post" 'nvx-blog-single'
      require "blog-single" "$html_post" 'nvx-blog-context'
      require "blog-single" "$html_post" 'nvx-posts.css'
    else
      echo "  WARN[blog-single] could not fetch discovered post — skipped"
    fi
  else
    echo "  WARN[blog-single] no card title link on /blog/ — skipped"
  fi

  if [[ -n "${cat_url:-}" ]]; then
    cat_path="$(to_path "$cat_url")"
    echo "==> blog-category (discovered $cat_path)"
    if html_cat="$(fetch "${BASE_URL%/}${cat_path}" 2>/dev/null || true)"; then
      require "blog-category" "$html_cat" 'nvx-blog-archive'
      require "blog-category" "$html_cat" 'nvx-blog-grid'
      require "blog-category" "$html_cat" 'nvx-posts.css'
    else
      echo "  WARN[blog-category] could not fetch discovered category — skipped"
    fi
  else
    echo "  WARN[blog-category] no category link on /blog/ — skipped"
  fi
fi

echo
if [[ "$FAIL" -ne 0 ]]; then
  echo "SMOKE_VERIFY_FAILED" >&2
  exit 1
fi

echo "SMOKE_VERIFY_OK"
exit 0
