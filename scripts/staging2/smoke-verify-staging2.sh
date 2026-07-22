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
  command -v "$command_name" >/dev/null 2>&1 || { echo "ERROR: required command unavailable: $command_name" >&2; exit 1; }
done

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT
fail() { echo "ERROR: $*" >&2; return 1; }

fetch_page() {
  local page_path="$1"
  shift
  local body_file="$TMP_DIR/body-$(echo "$page_path" | tr '/-' '__').html"
  local status
  status="$(curl --silent --show-error --connect-timeout 15 --max-time 45 --retry 2 --retry-all-errors --output "$body_file" --write-out '%{http_code}' "$BASE_URL$page_path")"
  [[ "$status" == '200' ]] || fail "$page_path returned HTTP $status"
  for expected_marker in "$@"; do
    grep -Fiq "$expected_marker" "$body_file" || fail "$page_path is missing marker: $expected_marker"
  done
  for forbidden in \
    'Protocolo en construcción clínica' 'fase de despliegue web' 'pending_medical_legal' \
    'LipoSculpt-Air' 'V-Lift Awake' 'Couture Sculpt' 'Contour Sculpt' 'Eye Frame' \
    'Sin bisturí ni puntos' 'Todo en vigilia' 'Mínima recuperación' 'Recuperación inmediata' \
    'Sin cicatrices' 'Sin inflamación' 'Sin dolor' 'Sin riesgos' 'Elimina grasa en cualquier zona' \
    'Resultado definitivo' 'Resultados garantizados' 'Una sola sesión' 'Generalmente 3–4 sesiones' \
    'Reducción del dolor' 'Eritema reducido' 'Eritema mínimo' 'Control térmico absoluto'
  do
    if grep -Fiq "$forbidden" "$body_file"; then
      fail "$page_path exposes retired, internal or prohibited marker: $forbidden"
    fi
  done
  echo "PASS page $page_path status=200 markers=$#"
}

check_redirect() {
  local source_path="$1"
  local target_path="$2"
  local headers_file="$TMP_DIR/headers-$(echo "$source_path" | tr '/-' '__').txt"
  local status location expected_location
  status="$(curl --silent --show-error --connect-timeout 15 --max-time 30 --max-redirs 0 --output /dev/null --dump-header "$headers_file" --write-out '%{http_code}' "$BASE_URL$source_path")"
  [[ "$status" == '301' ]] || fail "$source_path returned HTTP $status instead of 301"
  location="$(grep -i '^location:' "$headers_file" | tail -n 1 | cut -d: -f2- | tr -d '\r' | xargs)"
  expected_location="$BASE_URL$target_path"
  [[ "$location" == "$expected_location" ]] || fail "$source_path redirects to $location instead of $expected_location"
  echo "PASS redirect $source_path -> $target_path status=301"
}

check_redirect '/tratamientos/' '/soluciones-medicas/'
fetch_page '/soluciones-medicas/' 'Soluciones médicas para rostro, piel y contorno corporal.' 'Rostro y cuello' 'Contorno corporal' 'Cambios posgestacionales' 'Valoración de procedimientos previos'
fetch_page '/protocolos-signature/' 'Protocolos Signature: Medicina estética de diagnóstico.' 'Nuestro estándar: La firma NUVANX' 'NUVANX Contour Architecture' 'Post-Maternity Contour' 'Tu primera valoración clínica'
fetch_page '/remodelacion-corporal-laser-madrid/' 'NUVANX Contour Architecture™: El protocolo y la tecnología' 'Tres decisiones clínicas: Reducir, Redefinir, Retraer' 'Cuándo no es el tratamiento adecuado'
fetch_page '/tratamiento-postparto-abdomen-contorno-corporal-madrid/' 'Tratamiento Postparto: Abdomen y Contorno Corporal en Madrid' 'El Protocolo NUVANX Post-Maternity Contour' 'Las alteraciones del posparto' 'Preguntas frecuentes'
fetch_page '/por-que-nuvanx/' 'Por qué NUVANX. Sin retórica de marketing.' 'Responsabilidad médica y continuidad asistencial' 'Trazabilidad de productos' 'Por qué importa'
fetch_page '/inversion-medicina-estetica/' 'El presupuesto forma parte de una decisión informada.' 'Cómo leer estas tarifas' 'Qué incluye siempre el plan en NUVANX' 'Qué no encontrarás aquí'

fetch_page '/papada-definicion-mandibular-madrid/' 'Papada y mandíbula: a veces es grasa, a veces es piel.' 'Qué se valora' 'Cómo se decide el plan' 'Límites y cuándo derivamos' 'Tu primera valoración clínica'
fetch_page '/calidad-piel-firmeza-luminosidad-madrid/' 'Tu piel no necesita más cremas, necesita reconstruirse por dentro.' 'Qué se valora' 'Cómo se decide el plan' 'Límites y cuándo derivamos' 'Tu primera valoración clínica'
fetch_page '/cicatrices-acne-poros-textura-madrid/' 'Para mejorar las marcas de acné hay que romper la cicatriz, no solo pelar la piel.' 'Qué se valora' 'Cómo se decide el plan' 'Límites y cuándo derivamos' 'Tu primera valoración clínica'
fetch_page '/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/' 'Quitar una mancha es fácil; que no vuelva a salir es la parte médica.' 'Qué se valora' 'Cómo se decide el plan' 'Límites y cuándo derivamos' 'Tu primera valoración clínica'

fetch_page '/grasa-localizada-abdomen-flancos-madrid/' 'Grasa localizada en abdomen y flancos en Madrid' 'Qué se valora' 'Cómo se decide el plan' 'Límites y cuándo derivamos'
fetch_page '/flacidez-grasa-localizada-brazos-madrid/' 'Flacidez y grasa localizada en brazos en Madrid' 'Qué se valora' 'Cómo se decide el plan' 'Límites y cuándo derivamos'
fetch_page '/grasa-espalda-zona-sujetador-madrid/' 'Grasa de espalda y zona del sujetador en Madrid' 'Qué se valora' 'Cómo se decide el plan' 'Límites y cuándo derivamos'
fetch_page '/flacidez-muslos-internos-subgluteo-madrid/' 'Flacidez en muslos internos y región subglútea en Madrid' 'Qué se valora' 'Cómo se decide el plan' 'Límites y cuándo derivamos'
fetch_page '/tratamiento-rodillas-grasa-flacidez-madrid/' 'Grasa localizada y flacidez en rodillas en Madrid' 'Qué se valora' 'Cómo se decide el plan' 'Límites y cuándo derivamos'
fetch_page '/contorno-corporal-masculino-madrid/' 'Contorno corporal masculino en Madrid' 'Qué se valora' 'Cómo se decide el plan' 'Límites y cuándo derivamos'

check_redirect '/liposculpt-air/' '/remodelacion-corporal-laser-madrid/'
check_redirect '/v-lift-awake/' '/protocolos-signature/'

echo "SMOKE_VERIFY_OK base_url=$BASE_URL"
