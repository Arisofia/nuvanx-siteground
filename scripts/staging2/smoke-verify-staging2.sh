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

for command_name in curl grep mktemp tr cut tail xargs sleep; do
  command -v "$command_name" >/dev/null 2>&1 || { echo "ERROR: required command unavailable: $command_name" >&2; exit 1; }
done

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT
USER_AGENT='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36'
CURL_COMMON_ARGS=(
  --silent
  --show-error
  --connect-timeout 15
  --max-time 45
  --http1.1
  --compressed
  --user-agent "$USER_AGENT"
  --header 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8'
  --header 'Accept-Language: es-ES,es;q=0.9,en;q=0.7'
  --header 'Cache-Control: no-cache'
  --header 'Pragma: no-cache'
)
# fail prints an error message to stderr and returns a failure status.
fail() { echo "ERROR: $*" >&2; return 1; }

# fetch_page verifies that a page returns HTTP 200, contains all expected markers, and excludes retired, internal, or prohibited markers.
fetch_page() {
  local page_path="$1"
  shift
  local body_file="$TMP_DIR/body-$(echo "$page_path" | tr '/-' '__').html"
  local status attempt

  status='000'
  for attempt in 1 2 3 4; do
    status="$(curl "${CURL_COMMON_ARGS[@]}" --output "$body_file" --write-out '%{http_code}' "$BASE_URL$page_path")"
    if [[ "$status" == '200' ]]; then
      break
    fi
    if [[ "$status" != '202' && "$status" != '429' && ! "$status" =~ ^5[0-9][0-9]$ ]]; then
      break
    fi
    if [[ "$attempt" -lt 4 ]]; then
      sleep $(( attempt * 2 ))
    fi
  done

  [[ "$status" == '200' ]] || fail "$page_path returned HTTP $status after $attempt attempt(s)"
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
  echo "PASS page $page_path status=200 markers=$# attempts=$attempt"
}

# check_redirect verifies that a source path redirects directly to the expected target path with HTTP 301.
check_redirect() {
  local source_path="$1"
  local target_path="$2"
  local headers_file="$TMP_DIR/headers-$(echo "$source_path" | tr '/-' '__').txt"
  local status location expected_location attempt

  status='000'
  for attempt in 1 2 3 4; do
    : > "$headers_file"
    status="$(curl "${CURL_COMMON_ARGS[@]}" --max-redirs 0 --output /dev/null --dump-header "$headers_file" --write-out '%{http_code}' "$BASE_URL$source_path")"
    if [[ "$status" == '301' ]]; then
      break
    fi
    if [[ "$status" != '202' && "$status" != '429' && ! "$status" =~ ^5[0-9][0-9]$ ]]; then
      break
    fi
    if [[ "$attempt" -lt 4 ]]; then
      sleep $(( attempt * 2 ))
    fi
  done

  [[ "$status" == '301' ]] || fail "$source_path returned HTTP $status instead of 301 after $attempt attempt(s)"
  location="$(grep -i '^location:' "$headers_file" | tail -n 1 | cut -d: -f2- | tr -d '\r' | xargs)"
  expected_location="$BASE_URL$target_path"
  [[ "$location" == "$expected_location" ]] || fail "$source_path redirects to $location instead of $expected_location"
  echo "PASS redirect $source_path -> $target_path status=301 attempts=$attempt"
}

# Allow the edge cache and anti-bot layer to observe the completed immutable release.
sleep 5

check_redirect '/tratamientos/' '/soluciones-medicas/'
fetch_page '/soluciones-medicas/' 'Soluciones médicas para rostro, piel y contorno corporal.' 'Rostro y cuello' 'Contorno corporal' 'Cambios posgestacionales' 'Valoración de procedimientos previos'
fetch_page '/protocolos-signature/' 'Protocolos Signature: Medicina estética de diagnóstico.' 'Nuestro estándar: La firma NUVANX' 'NUVANX Contour Architecture' 'Post-Maternity Contour' 'Tu primera valoración clínica'
fetch_page '/remodelacion-corporal-laser-madrid/' 'NUVANX Contour Architecture™: El protocolo y la tecnología' 'Tres decisiones clínicas: Reducir, Redefinir, Retraer' 'Cuándo no es el tratamiento adecuado'
fetch_page '/tratamiento-postparto-abdomen-contorno-corporal-madrid/' 'Tratamiento Postparto: Abdomen y Contorno Corporal en Madrid' 'El Protocolo NUVANX Post-Maternity Contour' 'Las alteraciones del posparto' 'Preguntas frecuentes'
fetch_page '/por-que-nuvanx/' 'Por qué NUVANX. Sin retórica de marketing.' 'Responsabilidad médica y continuidad asistencial' 'Trazabilidad de productos' 'Por qué importa'
fetch_page '/inversion-medicina-estetica/' 'El presupuesto forma parte de una decisión informada.' 'Cómo leer estas tarifas' 'Qué incluye siempre el plan en NUVANX' 'Qué no encontrarás aquí'

MARKER_VALORA='Qué se valora'
MARKER_DECIDE='Cómo se decide el plan'
MARKER_LIMITES='Límites y cuándo derivamos'

fetch_page '/papada-definicion-mandibular-madrid/' 'Papada y definición mandibular en Madrid' "$MARKER_VALORA" "$MARKER_DECIDE" "$MARKER_LIMITES"
fetch_page '/calidad-piel-firmeza-luminosidad-madrid/' 'Calidad, firmeza y luminosidad de la piel en Madrid' "$MARKER_VALORA" "$MARKER_DECIDE" "$MARKER_LIMITES"
fetch_page '/cicatrices-acne-poros-textura-madrid/' 'Cicatrices de acné, poros y textura en Madrid' "$MARKER_VALORA" "$MARKER_DECIDE" "$MARKER_LIMITES"
fetch_page '/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/' 'Manchas, rojeces y fotodaño en Madrid' "$MARKER_VALORA" "$MARKER_DECIDE" "$MARKER_LIMITES"
fetch_page '/grasa-localizada-abdomen-flancos-madrid/' 'Grasa localizada en abdomen y flancos en Madrid' "$MARKER_VALORA" "$MARKER_DECIDE" "$MARKER_LIMITES"
fetch_page '/flacidez-grasa-localizada-brazos-madrid/' 'Flacidez y grasa localizada en brazos en Madrid' "$MARKER_VALORA" "$MARKER_DECIDE" "$MARKER_LIMITES"
fetch_page '/grasa-espalda-zona-sujetador-madrid/' 'Grasa de espalda y zona del sujetador en Madrid' "$MARKER_VALORA" "$MARKER_DECIDE" "$MARKER_LIMITES"
fetch_page '/flacidez-muslos-internos-subgluteo-madrid/' 'Flacidez en muslos internos y región subglútea en Madrid' "$MARKER_VALORA" "$MARKER_DECIDE" "$MARKER_LIMITES"
fetch_page '/tratamiento-rodillas-grasa-flacidez-madrid/' 'Grasa localizada y flacidez en rodillas en Madrid' "$MARKER_VALORA" "$MARKER_DECIDE" "$MARKER_LIMITES"
fetch_page '/contorno-corporal-masculino-madrid/' 'Contorno corporal masculino en Madrid' "$MARKER_VALORA" "$MARKER_DECIDE" "$MARKER_LIMITES"

check_redirect '/liposculpt-air/' '/remodelacion-corporal-laser-madrid/'
check_redirect '/v-lift-awake/' '/protocolos-signature/'

echo "SMOKE_VERIFY_OK base_url=$BASE_URL"
