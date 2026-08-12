#!/usr/bin/env bash
set -Eeuo pipefail

: "${PROD_ROOT:?Missing PROD_ROOT}"
BASE_URL="${BASE_URL:-https://nuvanx.com}"
EXPECTED_HOST="${EXPECTED_HOST:-nuvanx.com}"
INDEXNOW_KEY="${INDEXNOW_KEY:-53546cf8077aa596b76aac664739bbb4}"
INDEXNOW_ENDPOINT="${INDEXNOW_ENDPOINT:-https://api.indexnow.org/indexnow}"
BASE_URL="${BASE_URL%/}"

fail() { printf 'FAIL %s\n' "$*" >&2; exit 1; }
pass() { printf 'PASS %s\n' "$*"; }

[[ "$INDEXNOW_KEY" =~ ^[A-Za-z0-9_-]{8,128}$ ]] || fail 'INDEXNOW_KEY_INVALID'
[[ "$BASE_URL" == "https://$EXPECTED_HOST" ]] || fail "BASE_URL_HOST_MISMATCH base=$BASE_URL expected=$EXPECTED_HOST"
command -v cmp >/dev/null || fail 'CMP_UNAVAILABLE'

cd "$PROD_ROOT"
release_sha="$(tr -d '\r\n[:space:]' < wp-content/themes/nuvanx-medical/.nvx-deploy-sha)"
[[ "$release_sha" =~ ^[0-9a-f]{40}$ ]] || fail 'PRODUCTION_DEPLOY_MARKER_INVALID'
[[ "$(wp option get home)" == "$BASE_URL" ]]
[[ "$(wp option get siteurl)" == "$BASE_URL" ]]
[[ "$(wp option get blog_public)" == '1' ]]
[[ "$(wp theme list --status=active --field=name)" == 'nuvanx-medical' ]]
pass "PRODUCTION_IDENTITY sha=$release_sha"

tmpdir="$(mktemp -d)"
trap 'rm -rf "$tmpdir"' EXIT

key_file="$PROD_ROOT/$INDEXNOW_KEY.txt"
umask 022
printf '%s' "$INDEXNOW_KEY" > "$key_file"
chmod 0644 "$key_file"
cmp -s "$key_file" <(printf '%s' "$INDEXNOW_KEY") || fail 'INDEXNOW_KEY_FILE_WRITE'

public_key_file="$tmpdir/indexnow-public-key.txt"
public_key_error="$tmpdir/indexnow-public-key.err"
set +e
public_key_http="$(curl -sS \
  --retry 3 \
  --retry-all-errors \
  --retry-delay 2 \
  --retry-max-time 45 \
  --connect-timeout 5 \
  --max-time 15 \
  --proto '=https' \
  --proto-redir '=https' \
  -o "$public_key_file" \
  -w '%{http_code}' \
  "$BASE_URL/$INDEXNOW_KEY.txt" 2>"$public_key_error")"
public_key_rc=$?
set -e

if (( public_key_rc != 0 )); then
  printf 'INDEXNOW_KEY_PUBLIC_ERROR=%s\n' "$(tr '\n' ' ' < "$public_key_error" | head -c 500)" >&2
  fail "INDEXNOW_KEY_PUBLIC_FETCH curl_rc=$public_key_rc"
fi
[[ "$public_key_http" == '200' ]] || fail "INDEXNOW_KEY_PUBLIC_HTTP status=$public_key_http"

if cmp -s "$public_key_file" <(printf '%s' "$INDEXNOW_KEY") \
  || cmp -s "$public_key_file" <(printf '%s\n' "$INDEXNOW_KEY") \
  || cmp -s "$public_key_file" <(printf '%s\r\n' "$INDEXNOW_KEY"); then
  :
else
  fail 'INDEXNOW_KEY_PUBLIC_CONTENT_MISMATCH'
fi
pass "INDEXNOW_KEY_FILE path=/$INDEXNOW_KEY.txt source=public-origin http=200"

curl -fsS --max-time 30 "$BASE_URL/sitemap_index.xml" -o "$tmpdir/sitemap-index.xml"
grep -oE '<loc>[^<]+</loc>' "$tmpdir/sitemap-index.xml" \
  | sed -E 's#</?loc>##g' \
  | sed 's/&amp;/\&/g' \
  | sort -u > "$tmpdir/children.txt"

: > "$tmpdir/urls.txt"
child_count=0
while IFS= read -r sitemap; do
  [[ -n "$sitemap" ]] || continue
  [[ "$sitemap" == "$BASE_URL"/* ]] || fail "SITEMAP_CROSS_HOST url=$sitemap"
  child_count=$((child_count + 1))
  curl -fsS --max-time 30 "$sitemap" -o "$tmpdir/child-$child_count.xml"
  grep -oE '<loc>[^<]+</loc>' "$tmpdir/child-$child_count.xml" \
    | sed -E 's#</?loc>##g' \
    | sed 's/&amp;/\&/g' >> "$tmpdir/urls.txt" || true
done < "$tmpdir/children.txt"

sort -u "$tmpdir/urls.txt" -o "$tmpdir/urls.txt"
url_count="$(grep -c "^$BASE_URL/" "$tmpdir/urls.txt" || true)"
[[ "$url_count" -ge 1 ]] || fail 'INDEXNOW_URLS_EMPTY'
[[ "$url_count" -le 10000 ]] || fail "INDEXNOW_URL_LIMIT count=$url_count"

while IFS= read -r url; do
  [[ -n "$url" ]] || continue
  [[ "$url" == "$BASE_URL/"* ]] || fail "INDEXNOW_URL_CROSS_HOST url=$url"
done < "$tmpdir/urls.txt"

php -r '
$host = $argv[1];
$key = $argv[2];
$keyLocation = $argv[3];
$urls = array_values(array_filter(array_map("trim", file($argv[4], FILE_IGNORE_NEW_LINES))));
echo json_encode([
  "host" => $host,
  "key" => $key,
  "keyLocation" => $keyLocation,
  "urlList" => $urls,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
' "$EXPECTED_HOST" "$INDEXNOW_KEY" "$BASE_URL/$INDEXNOW_KEY.txt" "$tmpdir/urls.txt" > "$tmpdir/payload.json"

response_body="$tmpdir/indexnow-response.txt"
http_code="$(curl -sS --max-time 30 \
  -o "$response_body" \
  -w '%{http_code}' \
  -H 'Content-Type: application/json; charset=utf-8' \
  --data-binary @"$tmpdir/payload.json" \
  "$INDEXNOW_ENDPOINT")"

case "$http_code" in
  200|202)
    pass "INDEXNOW_SUBMISSION http=$http_code urls=$url_count"
    ;;
  *)
    printf 'INDEXNOW_RESPONSE=%s\n' "$(tr '\n' ' ' < "$response_body" | head -c 500)" >&2
    fail "INDEXNOW_SUBMISSION http=$http_code urls=$url_count"
    ;;
esac

printf 'INDEXNOW_RELEASE_SHA=%s\n' "$release_sha"
printf 'INDEXNOW_SITEMAP_CHILDREN=%s\n' "$child_count"
printf 'INDEXNOW_URLS=%s\n' "$url_count"
printf 'INDEXNOW_HTTP=%s\n' "$http_code"
echo 'INDEXNOW_SUBMIT=PASS'
