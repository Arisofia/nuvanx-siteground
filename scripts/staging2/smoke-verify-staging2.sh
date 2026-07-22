#!/usr/bin/env bash
# READ-ONLY: verify governed NUVANX roadmap routes, navigation and redirects.
set -Eeuo pipefail

BASE_URL="${BASE_URL:-https://staging2.nuvanx.com}"
BASE_URL="${BASE_URL%/}"
case "$BASE_URL" in
  https://staging2.nuvanx.com|https://nuvanx.com) ;;
  https://www.nuvanx.com) BASE_URL='https://nuvanx.com' ;;
  *) echo "ERROR: refusing unexpected BASE_URL: $BASE_URL" >&2; exit 1 ;;
esac

for command_name in curl grep mktemp tr cut tail xargs; do
  command -v "$command_name" >/dev/null 2>&1 || { echo "ERROR: required command unavailable: $command_name" >&2; exit 1; }
done

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT
fail() { echo "ERROR: $*" >&2; return 1; }

fetch_page() {
  local page_path="$1"; shift
  local body_file="$TMP_DIR/body-$(echo "$page_path" | tr '/-' '__').html"
  local status
  status="$(curl --silent --show-error --connect-timeout 15 --max-time 45 --retry 3 --retry-all-errors --output "$body_file" --write-out '%{http_code}' "$BASE_URL$page_path")"
  [[ "$status" == '200' ]] || fail "$page_path returned HTTP $status"
  for expected_marker in "$@"; do
    grep -Fiq "$expected_marker" "$body_file" || fail "$page_path is missing marker: $expected_marker"
  done
  for forbidden in \
    'pending_medical_legal' 'LipoSculpt-Air' 'V-Lift Awake' 'Couture Sculpt' 'Contour Sculpt' 'Eye Frame' \
    'garantizar resultados' 'control térmico absoluto' 'sin huellas quirúrgicas evidentes' 'sin bisturí ni puntos' \
    'todo en vigilia' 'mínima recuperación' 'sin cicatrices' 'resultado definitivo' 'una sola sesión' \
    'Tiny Tuck' 'AirTite' 'Mommy Makeover' 'destruyendo los adipocitos' 'forzando a la piel' \
    'obligamos a tus células' 'obligar a los fibroblastos' 'ayudar en el cierre de la diástasis' 'sin hospitalización'
  do
    if grep -Fiq "$forbidden" "$body_file"; then
      fail "$page_path exposes forbidden marker: $forbidden"
    fi
  done
  echo "PASS page $page_path status=200 markers=$#"
}

check_redirect() {
  local source_path="$1" target_path="$2"
  local headers_file="$TMP_DIR/headers-$(echo "$source_path" | tr '/-' '__').txt"
  local status location expected_location target_status
  status="$(curl --silent --show-error --connect-timeout 15 --max-time 30 --max-redirs 0 --output /dev/null --dump-header "$headers_file" --write-out '%{http_code}' "$BASE_URL$source_path")"
  [[ "$status" == '301' ]] || fail "$source_path returned HTTP $status instead of 301"
  location="$(grep -i '^location:' "$headers_file" | tail -n 1 | cut -d: -f2- | tr -d '\r' | xargs)"
  expected_location="$BASE_URL$target_path"
  [[ "$location" == "$expected_location" ]] || fail "$source_path redirects to $location instead of $expected_location"
  target_status="$(curl --silent --show-error --connect-timeout 15 --max-time 30 --max-redirs 0 --output /dev/null --write-out '%{http_code}' "$expected_location")"
  [[ "$target_status" == '200' ]] || fail "$source_path target returned HTTP $target_status"
  echo "PASS redirect $source_path -> $target_path status=301 target=200"
}

check_redirect '/tratamientos/' '/soluciones-medicas/'
check_redirect '/liposculpt-air/' '/remodelacion-corporal-laser-madrid/'
check_redirect '/v-lift-awake/' '/protocolos-signature/'
check_redirect '/eye-frame-rejuvenecimiento-mirada-madrid/' '/soluciones-medicas/'

fetch_page '/soluciones-medicas/' 'Soluciones médicas para rostro, piel y contorno corporal.' 'Rostro y cuello' 'Contorno corporal' 'Cambios posgestacionales'
fetch_page '/protocolos-signature/' 'Protocolos Signature NUVANX.' 'NUVANX Contour Architecture™' 'NUVANX Profile Definition™'
fetch_page '/remodelacion-corporal-laser-madrid/' 'Remodelación corporal láser diseñada según tu anatomía.' 'Niveles de planificación, no paquetes cerrados' 'Contour Continuity'
fetch_page '/tratamiento-postparto-abdomen-contorno-corporal-madrid/' 'Después del embarazo, “abdomen” puede significar problemas diferentes.' 'Cuándo no es el tratamiento adecuado' 'Preguntas frecuentes'
fetch_page '/papada-definicion-mandibular-madrid/' 'Papada, cuello y mandíbula forman un mismo perfil.' 'Qué puede formar parte del plan'
fetch_page '/calidad-piel-firmeza-luminosidad-madrid/' 'Calidad de piel: firmeza, densidad e hidratación no son lo mismo.' 'Tu primera valoración clínica'
fetch_page '/cicatrices-acne-poros-textura-madrid/' 'Cicatrices, poros y textura requieren diagnóstico por profundidad.' 'Preguntas frecuentes'
fetch_page '/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/' 'Manchas y rojeces no se tratan sin identificar primero la lesión.' 'Cuándo no es el tratamiento adecuado'
fetch_page '/grasa-localizada-abdomen-flancos-madrid/' 'Abdomen y flancos: grasa, piel y pared abdominal son diagnósticos distintos.' 'Límites y derivación'
fetch_page '/flacidez-grasa-localizada-brazos-madrid/' 'El brazo se valora junto con la axila y el torso.' 'Proceso de valoración'
fetch_page '/grasa-espalda-zona-sujetador-madrid/' 'Espalda, sujetador y flancos forman una misma arquitectura.' 'Objetivos clínicos posibles'
fetch_page '/flacidez-muslos-internos-subgluteo-madrid/' 'No tratamos “piernas”: estudiamos continuidad, laxitud y proporción.' 'La tecnología se decide después'
fetch_page '/tratamiento-rodillas-grasa-flacidez-madrid/' 'La región de la rodilla exige precisión y expectativas proporcionadas.' 'Qué se valora'
fetch_page '/contorno-corporal-masculino-madrid/' 'El contorno masculino se planifica según anatomía, no según una plantilla.' 'Límites y derivación'
fetch_page '/por-que-nuvanx/' 'Por qué NUVANX. Sin retórica de marketing.' 'Responsabilidad médica y continuidad asistencial' 'Por qué importa'
fetch_page '/inversion-medicina-estetica/' 'El presupuesto forma parte de una decisión informada.' 'Qué incluye siempre el plan en NUVANX' 'Qué no encontrarás aquí'

HOME_FILE="$TMP_DIR/home.html"
HOME_STATUS="$(curl --silent --show-error --connect-timeout 15 --max-time 45 --retry 3 --retry-all-errors --output "$HOME_FILE" --write-out '%{http_code}' "$BASE_URL/")"
[[ "$HOME_STATUS" == '200' ]] || fail "/ returned HTTP $HOME_STATUS"
for label in 'Soluciones' 'Protocolos Signature' 'Tecnología' 'Casos clínicos' 'Equipo médico' 'Clínicas' 'Journal' 'Contacto'; do
  grep -Fiq "$label" "$HOME_FILE" || fail "primary navigation is missing: $label"
done
if grep -Eiq '>\s*TRATAMIENTOS\s*<' "$HOME_FILE"; then
  fail 'legacy TRATAMIENTOS item is still rendered in primary navigation'
fi

echo "SMOKE_VERIFY_OK base_url=$BASE_URL"
