#!/usr/bin/env bash
# READ-ONLY: verify canonical NUVANX editorial routes and retired redirects.
set -Eeuo pipefail

BASE_URL="${BASE_URL:-https://staging2.nuvanx.com}"
BASE_URL="${BASE_URL%/}"
case "$BASE_URL" in
  https://staging2.nuvanx.com|https://nuvanx.com) ;;
  https://www.nuvanx.com) BASE_URL='https://nuvanx.com' ;;
  *) echo "ERROR: refusing unexpected BASE_URL: $BASE_URL" >&2; exit 1 ;;
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
  local page_path="$1"
  shift
  local body_file="$TMP_DIR/body-$(echo "$page_path" | tr '/-' '__').html"
  local status
  status="$(curl --silent --show-error --connect-timeout 15 --max-time 45 --retry 2 --retry-all-errors \
    --output "$body_file" --write-out '%{http_code}' "$BASE_URL$page_path")"
  [[ "$status" == '200' ]] || fail "$page_path returned HTTP $status"

  for expected_marker in "$@"; do
    grep -Fiq "$expected_marker" "$body_file" || fail "$page_path is missing marker: $expected_marker"
  done

  for forbidden in \
    'Protocolo en construcción clínica' \
    'fase de despliegue web' \
    'pending_medical_legal' \
    'LipoSculpt-Air' \
    'V-Lift Awake'
  do
    if grep -Fiq "$forbidden" "$body_file"; then
      fail "$page_path exposes retired or internal marker: $forbidden"
    fi
  done

  echo "PASS page $page_path status=200 markers=$#"
}

check_redirect() {
  local source_path="$1"
  local target_path="$2"
  local headers_file="$TMP_DIR/headers-$(echo "$source_path" | tr '/-' '__').txt"
  local status location expected_location
  status="$(curl --silent --show-error --connect-timeout 15 --max-time 30 --max-redirs 0 \
    --output /dev/null --dump-header "$headers_file" --write-out '%{http_code}' "$BASE_URL$source_path")"
  [[ "$status" == '301' ]] || fail "$source_path returned HTTP $status instead of 301"
  location="$(grep -i '^location:' "$headers_file" | tail -n 1 | cut -d: -f2- | tr -d '\r' | xargs)"
  expected_location="$BASE_URL$target_path"
  [[ "$location" == "$expected_location" ]] || fail "$source_path redirects to $location instead of $expected_location"
  echo "PASS redirect $source_path -> $target_path status=301"
}

check_redirect '/tratamientos/' '/soluciones-medicas/'
fetch_page '/soluciones-medicas/' \
  'Soluciones médicas para rostro, piel y contorno corporal.' \
  'Rostro y cuello' \
  'Contorno corporal' \
  'Cambios posgestacionales' \
  'Valoración de procedimientos previos'
fetch_page '/protocolos-signature/' \
  'Protocolos Signature: Medicina estética de diagnóstico.' \
  'Nuestro estándar: La firma NUVANX' \
  'Contorno Corporal y Posgestacional' \
  'Post-Maternity Contour' \
  'Tu primera valoración clínica'
fetch_page '/remodelacion-corporal-laser-madrid/' \
  'Couture Sculpt™: El protocolo y la tecnología' \
  'Tres decisiones clínicas: Reducir, Redefinir, Retraer'
fetch_page '/tratamiento-postparto-abdomen-contorno-corporal-madrid/' \
  'Tratamiento Postparto: Abdomen y Contorno Corporal en Madrid' \
  'El Protocolo NUVANX Post-Maternity Contour' \
  'Las alteraciones del posparto' \
  'Preguntas frecuentes'
fetch_page '/por-que-nuvanx/' \
  'Por qué NUVANX. Sin retórica de marketing.' \
  'Responsabilidad médica y continuidad asistencial' \
  'Trazabilidad de productos' \
  'Por qué importa'
fetch_page '/inversion-medicina-estetica/' \
  'El presupuesto forma parte de una decisión informada.' \
  'Cómo leer estas tarifas' \
  'Qué incluye siempre el plan en NUVANX' \
  'Qué no encontrarás aquí'

check_redirect '/liposculpt-air/' '/remodelacion-corporal-laser-madrid/'
check_redirect '/v-lift-awake/' '/protocolos-signature/'

echo "SMOKE_VERIFY_OK base_url=$BASE_URL"
