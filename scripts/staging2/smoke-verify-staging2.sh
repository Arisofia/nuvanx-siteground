#!/usr/bin/env bash
# READ-ONLY: verify the canonical NUVANX portfolio/protocol routes and redirects.
set -Eeuo pipefail

BASE_URL="${BASE_URL:-https://staging2.nuvanx.com}"
BASE_URL="${BASE_URL%/}"

case "$BASE_URL" in
  https://staging2.nuvanx.com|https://nuvanx.com) ;;
  https://www.nuvanx.com) BASE_URL='https://nuvanx.com' ;;
  *)
    echo "ERROR: refusing unexpected BASE_URL: $BASE_URL" >&2
    exit 1
    ;;
esac

for command_name in curl grep mktemp tr cut tail xargs; do
  command -v "$command_name" >/dev/null 2>&1 || {
    echo "ERROR: required command unavailable: $command_name" >&2
    exit 1
  }
done

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

fail() {
  echo "ERROR: $*" >&2
  return 1
}

fetch_page() {
  local path="$1"
  local expected_marker="$2"
  local body_file="$TMP_DIR/body-$(echo "$path" | tr '/-' '__').html"
  local status

  status="$(curl --silent --show-error \
    --connect-timeout 15 \
    --max-time 45 \
    --retry 2 \
    --retry-all-errors \
    --output "$body_file" \
    --write-out '%{http_code}' \
    "$BASE_URL$path")"

  [[ "$status" == '200' ]] || fail "$path returned HTTP $status"
  grep -Fiq "$expected_marker" "$body_file" || fail "$path is missing marker: $expected_marker"

  for forbidden in \
    'Protocolo en construcción clínica' \
    'fase de despliegue web' \
    'LipoSculpt-Air' \
    'V-Lift Awake' \
    'Post-Maternity Contour'
  do
    if grep -Fiq "$forbidden" "$body_file"; then
      fail "$path exposes retired or unpublished marker: $forbidden"
    fi
  done

  echo "PASS page $path status=200 marker=$expected_marker"
}

check_redirect() {
  local source_path="$1"
  local target_path="$2"
  local headers_file="$TMP_DIR/headers-$(echo "$source_path" | tr '/-' '__').txt"
  local status location expected_location

  status="$(curl --silent --show-error \
    --connect-timeout 15 \
    --max-time 30 \
    --max-redirs 0 \
    --output /dev/null \
    --dump-header "$headers_file" \
    --write-out '%{http_code}' \
    "$BASE_URL$source_path")"

  [[ "$status" == '301' ]] || fail "$source_path returned HTTP $status instead of 301"
  location="$(grep -i '^location:' "$headers_file" | tail -n 1 | cut -d: -f2- | tr -d '\r' | xargs)"
  expected_location="$BASE_URL$target_path"
  [[ "$location" == "$expected_location" ]] || fail "$source_path redirects to $location instead of $expected_location"

  echo "PASS redirect $source_path -> $target_path status=301"
}

fetch_page '/tratamientos/' 'Portafolio clínico.'
fetch_page '/protocolos-signature/' 'Protocolos Signature'
fetch_page '/remodelacion-corporal-laser-madrid/' 'Couture Sculpt'

check_redirect '/liposculpt-air/' '/remodelacion-corporal-laser-madrid/'
check_redirect '/v-lift-awake/' '/papada-definicion-mandibular-madrid/'
check_redirect '/tratamiento-postparto-abdomen-contorno-corporal-madrid/' '/protocolos-signature/'

echo "SMOKE_VERIFY_OK base_url=$BASE_URL"
