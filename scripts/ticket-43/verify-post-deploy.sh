#!/usr/bin/env bash
# Post-deploy verification for Ticket 43 on staging2.
set -Eeuo pipefail

STAGING_URL="${NUVX_STAGING_URL:-https://staging2.nuvanx.com}"
THEME_CSS="${NUVX_THEME_CSS:-wp-content/themes/nuvanx-medical/assets/css}"
POST_ID="${NUVX_POST_ID:-9}"
BASIC_USER="${NUVX_BASIC_USER:-${STAGING_BASIC_USER:-}}"
BASIC_PASS="${NUVX_BASIC_PASS:-${STAGING_BASIC_PASSWORD:-}}"
VIDEO_PATH="${NUVX_HERO_VIDEO_PATH:-wp-content/uploads/2026/07/nvx-home-video-portada-hero-12s-720p.mp4}"

fail() {
	echo "VERIFY FAIL: $1" >&2
	exit 1
}

pass() {
	echo "VERIFY OK: $1"
}

if [[ "${NUVX_EXPECTED_CSS_SHA:-}" != "" ]]; then
	ACTUAL_CSS="$(sha256sum "${THEME_CSS}/nvx-brand-home.css" | awk '{print $1}')"
	[[ "$ACTUAL_CSS" == "$NUVX_EXPECTED_CSS_SHA" ]] || fail "CSS sha256 mismatch (expected ${NUVX_EXPECTED_CSS_SHA}, got ${ACTUAL_CSS})"
	pass "CSS sha256 matches candidate"
fi

if [[ "${NUVX_EXPECTED_MIN_SHA:-}" != "" ]]; then
	ACTUAL_MIN="$(sha256sum "${THEME_CSS}/nvx-brand-home.min.css" | awk '{print $1}')"
	[[ "$ACTUAL_MIN" == "$NUVX_EXPECTED_MIN_SHA" ]] || fail "MIN CSS sha256 mismatch (expected ${NUVX_EXPECTED_MIN_SHA}, got ${ACTUAL_MIN})"
	pass "MIN CSS sha256 matches candidate"
fi

if [[ -f "${VIDEO_PATH}" ]]; then
	pass "Hero video asset present on server"
else
	fail "Hero video asset missing: ${VIDEO_PATH}"
fi

if command -v wp >/dev/null 2>&1; then
	DB_CONTENT="$(wp post get "$POST_ID" --field=post_content)"
	[[ -n "$DB_CONTENT" ]] || fail "post_content empty in database"

	echo "$DB_CONTENT" | grep -q 'nvx-editorial-home-v3' || fail 'DB missing nvx-editorial-home-v3'
	echo "$DB_CONTENT" | grep -q 'nvx-home-hero-video' || fail 'DB missing nvx-home-hero-video'
	echo "$DB_CONTENT" | grep -q 'EXPERIENCIA NUVANX:' || fail 'DB missing production hero copy'
	if echo "$DB_CONTENT" | grep -qi 'sin ruido'; then
		fail 'DB contains rejected V2 copy'
	fi
	if ! echo "$DB_CONTENT" | grep -qE 'id="nvx-home-tratamientos"|aria-label="Tratamientos NUVANX"'; then
		fail 'DB missing Tratamientos section marker'
	fi
	pass "Database post_content contains required V3 anchors and production copy"

	wp eval-file scripts/ticket-43/verify-rendered-content.php
	pass "Server-side rendered content validation passed"
fi

NOCACHE="nocache=$(date +%s)"
CURL_OPTS=(-sL -H "Cache-Control: no-cache")

if [[ -n "$BASIC_USER" && -n "$BASIC_PASS" ]]; then
	CURL_OPTS+=(-u "${BASIC_USER}:${BASIC_PASS}")
	HTTP_STATUS="$(curl -o /dev/null -w "%{http_code}" "${CURL_OPTS[@]}" "${STAGING_URL}/?${NOCACHE}")"
	[[ "$HTTP_STATUS" == "200" ]] || fail "Home HTTP status ${HTTP_STATUS} (expected 200)"
	pass "Home responds HTTP 200 with basic auth"

	HTML="$(curl "${CURL_OPTS[@]}" "${STAGING_URL}/?${NOCACHE}")"
	[[ -n "$HTML" ]] || fail "Empty HTML response"

	MAIN_COUNT="$(printf '%s' "$HTML" | grep -o '<main' | wc -l | tr -d ' ')"
	HOME_MAIN_COUNT="$(printf '%s' "$HTML" | grep -o 'id="nvx-home-main"' | wc -l | tr -d ' ')"
	[[ "$MAIN_COUNT" == "1" ]] || fail "Expected exactly one <main>, found ${MAIN_COUNT}"
	[[ "$HOME_MAIN_COUNT" == "1" ]] || fail "Expected exactly one #nvx-home-main, found ${HOME_MAIN_COUNT}"
	pass "Public HTML contains single main landmark and #nvx-home-main"
else
	echo "VERIFY SKIP: HTTP basic auth credentials not configured; using WP-CLI rendered checks only."
fi

echo "All verification checks passed."