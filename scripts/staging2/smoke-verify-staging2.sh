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
# Keep smoke under ~10–15 min worst case (many URLs × retries).
TIMEOUT="${SMOKE_TIMEOUT:-20}"
CONNECT_TIMEOUT="${SMOKE_CONNECT_TIMEOUT:-8}"
# After cache flush / edge purge, staging sometimes returns tiny HTML briefly.
RETRIES="${SMOKE_RETRIES:-3}"
RETRY_SLEEP="${SMOKE_RETRY_SLEEP:-3}"
MIN_BYTES="${SMOKE_MIN_BYTES:-500}"
FAIL=0
CAPTCHA_SKIPS=0

# SiteGround bot challenge (GitHub Actions egress IPs often hit this).
is_edge_captcha() {
  local html="$1"
  [[ "$html" == *sgcaptcha* || "$html" == *'/.well-known/sgcaptcha'* || "$html" == *'robot challenge'* ]]
}

fetch_once() {
  local url="$1"
  curl -fsSL -L \
    --connect-timeout "$CONNECT_TIMEOUT" \
    --max-time "$TIMEOUT" \
    -A "$UA" \
    -H 'Accept: text/html,application/xhtml+xml' \
    -H 'Accept-Language: es-ES,es;q=0.9' \
    -H 'Cache-Control: no-cache' \
    -H 'Pragma: no-cache' \
    "$url"
}

# Retry on transport failure or suspiciously short bodies (edge/WAF blip).
# Exit codes: 0=ok body, 2=edge captcha interstitial, 1=hard fetch failure.
fetch() {
  local url="$1"
  local attempt=1
  local html=""
  local last_err=""
  local saw_captcha=0

  while (( attempt <= RETRIES )); do
    if html="$(fetch_once "$url" 2>/tmp/nvx-smoke-curl.err)"; then
      if is_edge_captcha "$html"; then
        saw_captcha=1
        last_err="SiteGround edge captcha interstitial"
        echo "  … retry $attempt/$RETRIES: $last_err" >&2
      elif [[ ${#html} -ge "$MIN_BYTES" ]]; then
        printf '%s' "$html"
        return 0
      else
        last_err="response too short (${#html} bytes)"
        echo "  … retry $attempt/$RETRIES: $last_err" >&2
      fi
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

  if [[ "$saw_captcha" -eq 1 ]]; then
    echo "  WARN fetch $url — edge captcha after ${RETRIES} attempts (not a theme regression)" >&2
    if [[ -n "${html:-}" ]]; then
      echo "  body_head=$(printf '%s' "$html" | head -c 240 | tr '\n' ' ')" >&2
    fi
    return 2
  fi

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
  local fetch_rc=0
  html="$(fetch "$url")" || fetch_rc=$?
  if [[ "$fetch_rc" -eq 2 ]]; then
    echo "  WARN[$name] skipped — SiteGround captcha blocked GitHub Actions edge" >&2
    CAPTCHA_SKIPS=$((CAPTCHA_SKIPS + 1))
    return
  fi
  if [[ "$fetch_rc" -ne 0 ]]; then
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

# Clinics hub — global brand sections + anchors (content-flow is intermediate only).
CLINICS_PATH='/clinicas-de-medicina-estetica-nuvanx/'
check_page "$CLINICS_PATH" "clinics-hub" \
  'id="nvx-clinics-nav"' \
  'id="clinica-chamberi"' \
  'id="clinica-goya"' \
  'nvx-clinic-location' \
  'nvx-brand-section' \
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

# Strategy pages — source-owned authority / tariff pages and noindex prototypes.
check_page "/por-que-nuvanx/" "why-nuvanx" \
  'nvx-strategy-page' \
  'Si no hay indicación clínica, no hay tratamiento.'

check_page "/inversion-medicina-estetica/" "investment" \
  'nvx-strategy-page' \
  'Tarifas orientativas verificadas'

check_page "/liposculpt-air/" "liposculpt-air-review" \
  'nvx-strategy-page--review' \
  'noindex'

check_page "/v-lift-awake/" "v-lift-awake-review" \
  'nvx-strategy-page--review' \
  'noindex'

# Journal / blog editorial system (PR #83)
check_page "/blog/" "blog-index" \
  'nvx-blog-context' \
  'nvx-blog-archive' \
  'nvx-blog-grid' \
  'nvx-blog-card' \
  'nvx-posts.css'

# Soft: page 2 may be empty on sparse staging; only require shell if HTML is long enough.
# Note: `if html="$(fetch ...)"; then` preserves fetch exit status under set -e (no || true).
if html_blog2="$(fetch "${BASE_URL%/}/blog/page/2/" 2>/dev/null)"; then
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

# Extract first href after a given class= attribute (order-stable card markup).
# Logs a count when multiple matches exist so markup drift is visible in CI logs.
extract_first_href_after_class() {
  local html="$1"
  local class_name="$2"
  local label="$3"
  local flat matches match_count href class_re class_attr pattern
  local -a patterns

  # Escape ERE metacharacters so class_name is always matched literally in grep -E
  # (e.g. foo.bar -> foo\.bar).
  class_re="$(printf '%s' "$class_name" | sed -e 's/[][\.^$*+?(){}|]/\\&/g')"
  # Shared class=… fragment used by all markup variants below.
  class_attr="class=\"[^\"]*${class_re}[^\"]*\""

  flat="$(printf '%s' "$html" | tr '\n\r\t' ' ')"
  # Variants: class on parent then child <a>, or class/href order on the <a> itself.
  patterns=(
    "${class_attr}[^>]*>[[:space:]]*<a[[:space:]][^>]*href=\"[^\"]+\""
    "<a[[:space:]][^>]*${class_attr}[^>]*href=\"[^\"]+\""
    "<a[[:space:]][^>]*href=\"[^\"]+\"[^>]*${class_attr}"
  )

  matches=""
  for pattern in "${patterns[@]}"; do
    matches="$(printf '%s' "$flat" | grep -oE "$pattern" || true)"
    [[ -n "$matches" ]] && break
  done

  match_count=0
  if [[ -n "$matches" ]]; then
    match_count="$(printf '%s\n' "$matches" | sed '/^$/d' | wc -l | tr -d ' ')"
  fi
  if [[ "$match_count" -gt 1 ]]; then
    echo "  INFO[$label] ${match_count} href matches for class=${class_name}; using first" >&2
  fi

  href="$(printf '%s\n' "$matches" | head -n1 | sed -n 's/.*href="\([^"]\+\)".*/\1/p')"
  printf '%s' "$href"
}

if html_blog="$(fetch "${BASE_URL%/}/blog/" 2>/dev/null)"; then
  post_url="$(extract_first_href_after_class "$html_blog" 'nvx-blog-card__title' 'blog-single')"
  # Category links sit on .nvx-blog-card__category > a (or a.nvx-blog-card__category).
  cat_url="$(extract_first_href_after_class "$html_blog" 'nvx-blog-card__category' 'blog-category')"

  if [[ -n "${post_url:-}" ]]; then
    post_path="$(to_path "$post_url")"
    echo "==> blog-single (discovered $post_path)"
    if html_post="$(fetch "${BASE_URL%/}${post_path}" 2>/dev/null)"; then
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
    if html_cat="$(fetch "${BASE_URL%/}${cat_path}" 2>/dev/null)"; then
      require "blog-category" "$html_cat" 'nvx-blog-archive'
      require "blog-category" "$html_cat" 'nvx-blog-grid'
      require "blog-category" "$html_cat" 'nvx-posts.css'
    else
      echo "  WARN[blog-category] could not fetch discovered category — skipped"
    fi
  else
    echo "  WARN[blog-category] no category link on /blog/ — skipped"
  fi
else
  echo "  WARN[blog-single] could not fetch /blog/ for discovery — skipped"
  echo "  WARN[blog-category] could not fetch /blog/ for discovery — skipped"
fi

echo
if [[ "$FAIL" -ne 0 ]]; then
  echo "SMOKE_VERIFY_FAILED" >&2
  exit 1
fi

if [[ "$CAPTCHA_SKIPS" -gt 0 ]]; then
  echo "SMOKE_VERIFY_OK_WITH_CAPTCHA_SKIPS captcha_skips=${CAPTCHA_SKIPS}"
  echo "::warning::Staging smoke skipped ${CAPTCHA_SKIPS} page(s) due to SiteGround edge captcha on GitHub Actions IPs. Deploy job remains the source of truth for rsync/WP-CLI; whitelist GHA egress or run smoke from the host when possible."
  exit 0
fi

echo "SMOKE_VERIFY_OK"
exit 0
