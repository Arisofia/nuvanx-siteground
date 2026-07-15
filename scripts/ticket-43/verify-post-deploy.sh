#!/usr/bin/env bash
# Post-deploy verification for Ticket 43 editorial candidate on staging2.
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

if [[ -z "$BASIC_USER" || -z "$BASIC_PASS" ]]; then
	fail "Basic Auth credentials are required for HTTP verification."
fi

CSS_FILES=(
	"nvx-tokens.css"
	"nvx-tokens.min.css"
	"nvx-components.css"
	"nvx-components.min.css"
	"nvx-brand-home.css"
	"nvx-brand-home.min.css"
)

for file in "${CSS_FILES[@]}"; do
	[[ -s "${THEME_CSS}/${file}" ]] || fail "Missing CSS asset ${THEME_CSS}/${file}"
done
pass "All six CSS assets are present"

[[ -s "$VIDEO_PATH" ]] || fail "Hero video asset missing: $VIDEO_PATH"
pass "Hero video asset present"

if ! command -v wp >/dev/null 2>&1; then
	fail "wp-cli is required"
fi

FRONT_ID="$(wp option get page_on_front)"
[[ "$FRONT_ID" == "$POST_ID" ]] || fail "page_on_front is $FRONT_ID, expected $POST_ID"

DB_CONTENT="$(wp post get "$POST_ID" --field=post_content)"
[[ -n "$DB_CONTENT" ]] || fail "post_content empty in database"

for marker in \
	'nvx-editorial-home' \
	'id="nvx-home-main"' \
	'id="nvx-home-hero-video"' \
	'id="nvx-home-tratamientos-title"' \
	'class="nvx-index' \
	'class="nvx-index-item__number"'; do
	echo "$DB_CONTENT" | grep -q "$marker" || fail "DB missing marker: $marker"
done

for marker in \
	'nvx-editorial-home-v4' \
	'nvx-v3-' \
	'id="nvx-home-manifiesto"' \
	'Medicina estética sin ruido' \
	'Nuestro manifiesto'; do
	if echo "$DB_CONTENT" | grep -qi "$marker"; then
		fail "DB contains forbidden marker: $marker"
	fi
done
pass "Database contains semantic editorial candidate and locked production copy"

NOCACHE="nocache=$(date +%s)"
CURL_OPTS=(
	-sS
	-L
	-u "${BASIC_USER}:${BASIC_PASS}"
	-H "Cache-Control: no-cache"
)

HTTP_STATUS="$(curl -o /dev/null -w "%{http_code}" "${CURL_OPTS[@]}" "${STAGING_URL}/?nvxqa=editorial&${NOCACHE}")"
[[ "$HTTP_STATUS" == "200" ]] || fail "Home HTTP status ${HTTP_STATUS}, expected 200"

HTML="$(curl "${CURL_OPTS[@]}" "${STAGING_URL}/?nvxqa=editorial&${NOCACHE}")"
[[ -n "$HTML" ]] || fail "Empty HTML response"

MAIN_COUNT="$(printf '%s' "$HTML" | grep -o '<main' | wc -l | tr -d ' ')"
HOME_MAIN_COUNT="$(printf '%s' "$HTML" | grep -o 'id="nvx-home-main"' | wc -l | tr -d ' ')"
[[ "$MAIN_COUNT" == "1" ]] || fail "Expected one <main>, found ${MAIN_COUNT}"
[[ "$HOME_MAIN_COUNT" == "1" ]] || fail "Expected one #nvx-home-main, found ${HOME_MAIN_COUNT}"

printf '%s' "$HTML" | grep -q 'nvx-editorial-home' || fail "Rendered HTML missing semantic wrapper"
printf '%s' "$HTML" | grep -q 'id="nvx-home-hero-video"' || fail "Rendered HTML missing hero video"
printf '%s' "$HTML" | grep -q 'class="nvx-index' || fail "Rendered HTML missing editorial index"
pass "Authenticated HTTP rendering passed"

echo "All post-deploy verification checks passed."
