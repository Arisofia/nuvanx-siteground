#!/usr/bin/env bash
# Post-deploy verification for Ticket 43 on staging2.
set -Eeuo pipefail

STAGING_URL="${NUVX_STAGING_URL:-https://staging2.nuvanx.com}"
THEME_CSS="${NUVX_THEME_CSS:-wp-content/themes/nuvanx-medical/assets/css}"
POST_ID="${NUVX_POST_ID:-9}"

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

if command -v wp >/dev/null 2>&1; then
	DB_CONTENT="$(wp post get "$POST_ID" --field=post_content)"
	[[ -n "$DB_CONTENT" ]] || fail "post_content empty in database"

	echo "$DB_CONTENT" | grep -q 'id="nvx-home-manifiesto"' || fail 'DB missing #nvx-home-manifiesto'
	echo "$DB_CONTENT" | grep -q 'nvx-home-hero-video' || fail 'DB missing nvx-home-hero-video'
	if ! echo "$DB_CONTENT" | grep -qE 'id="nvx-home-tratamientos"|aria-label="Tratamientos NUVANX"'; then
		fail 'DB missing Tratamientos section marker'
	fi
	pass "Database post_content contains required anchors"
fi

NOCACHE="nocache=$(date +%s)"
HTTP_STATUS="$(curl -o /dev/null -s -w "%{http_code}" -H "Cache-Control: no-cache" "${STAGING_URL}/?${NOCACHE}")"
[[ "$HTTP_STATUS" == "200" ]] || fail "Home HTTP status ${HTTP_STATUS} (expected 200)"
pass "Home responds HTTP 200"

HTML="$(curl -sL -H "Cache-Control: no-cache" "${STAGING_URL}/?${NOCACHE}")"
[[ -n "$HTML" ]] || fail "Empty HTML response"

MAIN_COUNT="$(printf '%s' "$HTML" | grep -o '<main' | wc -l | tr -d ' ')"
HOME_MAIN_COUNT="$(printf '%s' "$HTML" | grep -o 'id="nvx-home-main"' | wc -l | tr -d ' ')"
[[ "$MAIN_COUNT" == "1" ]] || fail "Expected exactly one <main>, found ${MAIN_COUNT}"
[[ "$HOME_MAIN_COUNT" == "1" ]] || fail "Expected exactly one #nvx-home-main, found ${HOME_MAIN_COUNT}"
pass "Single main landmark and #nvx-home-main"

printf '%s' "$HTML" | grep -q 'id="nvx-home-manifiesto"' || fail 'Rendered HTML missing #nvx-home-manifiesto'
printf '%s' "$HTML" | grep -q 'nvx-home-hero-video' || fail 'Rendered HTML missing hero video'
if ! printf '%s' "$HTML" | grep -qE 'id="nvx-home-tratamientos"|aria-label="Tratamientos NUVANX"|>Tratamientos</p>'; then
	fail 'Rendered HTML missing Tratamientos section'
fi
pass "Rendered HTML contains Hero, Manifiesto and Tratamientos anchors"

echo "All verification checks passed."