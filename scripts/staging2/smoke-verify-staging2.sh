#!/usr/bin/env bash
# READ-ONLY: verify canonical NUVANX editorial routes and retired legacy URLs.
set -Eeuo pipefail

BASE_URL="${BASE_URL:-https://staging2.nuvanx.com}"
BASE_URL="${BASE_URL%/}"
case "$BASE_URL" in
  https://staging2.nuvanx.com|https://nuvanx.com) ;;
  https://www.nuvanx.com) BASE_URL='https://nuvanx.com' ;;
  *) echo "ERROR: refusing unexpected BASE_URL: $BASE_URL" >&2; exit 1 ;;
esac

# Optional exact deploy marker. When set, every fetched HTML body must expose
# meta name="nvx-deploy-sha" equal to this 40-char SHA — the cheapest detector
# for SiteGround Dynamic Cache / orphan static HTML serving a pre-deploy theme.
EXPECTED_SHA="${EXPECTED_SHA:-${DEPLOY_SHA:-}}"
if [[ -n "$EXPECTED_SHA" && ! "$EXPECTED_SHA" =~ ^[0-9a-f]{40}$ ]]; then
  echo "ERROR: EXPECTED_SHA/DEPLOY_SHA must be a full lowercase 40-character SHA when set" >&2
  exit 1
fi

for command_name in curl grep mktemp tr cut tail xargs sleep; do
  command -v "$command_name" >/dev/null 2>&1 || { echo "ERROR: required command unavailable: $command_name" >&2; exit 1; }
done

# SiteGround can challenge command-line curl traffic from shared CI ranges with
# HTTP 202. GitHub Actions therefore uses the equivalent governed Node verifier;
# the remote server-side smoke remains curl-based and independent of Node.
if [[ "${GITHUB_ACTIONS:-}" == 'true' ]]; then
  command -v node >/dev/null 2>&1 || { echo 'ERROR: Node.js is required for external smoke verification' >&2; exit 1; }
  SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
  exec node "$SCRIPT_DIR/smoke-verify-external.mjs"
fi

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

# read_header_value returns the final normalized value for one response header.
read_header_value() {
  local header_name="$1"
  local headers_file="$2"
  grep -i "^${header_name}:" "$headers_file" | tail -n 1 | cut -d: -f2- | tr -d '\r' | xargs || true
}

# fetch_page verifies that a page returns HTTP 200, contains all expected markers, and excludes retired, internal, or prohibited markers.
fetch_page() {
  local page_path="$1"
  shift
  local safe_slug body_file
  safe_slug="$(echo "$page_path" | tr '/-' '__')"
  body_file="$TMP_DIR/body-${safe_slug}.html"
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

  # Always require the deploy marker so a stale full-page cache cannot pass
  # marker-only content checks that were also true on the pre-deploy HTML.
  if ! grep -Eiq '<meta[^>]+name=["'\'']nvx-deploy-sha["'\'']' "$body_file"; then
    fail "$page_path is missing meta nvx-deploy-sha (stale full-page cache or theme head not executing)"
  fi
  if [[ -n "$EXPECTED_SHA" ]]; then
    if ! grep -Eiq "<meta[^>]+name=[\"']nvx-deploy-sha[\"'][^>]+content=[\"']${EXPECTED_SHA}[\"']|<meta[^>]+content=[\"']${EXPECTED_SHA}[\"'][^>]+name=[\"']nvx-deploy-sha[\"']" "$body_file"; then
      served_sha="$(grep -Eio 'name=["'\'']nvx-deploy-sha["'\''][^>]*content=["'\''][a-f0-9]{40}["'\'']|content=["'\''][a-f0-9]{40}["'\''][^>]*name=["'\'']nvx-deploy-sha["'\'']' "$body_file" | head -n 1 | grep -Eio '[a-f0-9]{40}' | head -n 1 || true)"
      fail "$page_path served SHA ${served_sha:-absent} instead of ${EXPECTED_SHA}"
    fi
  fi

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

# check_retired_route verifies that a legacy path is unavailable and emits no redirect.
check_retired_route() {
  local source_path="$1"
  local safe_slug headers_file status location x_redirect_by server attempt
  safe_slug="$(echo "$source_path" | tr '/-' '__')"
  headers_file="$TMP_DIR/headers-${safe_slug}.txt"
  status='000'
  for attempt in 1 2 3 4; do
    : > "$headers_file"
    status="$(curl "${CURL_COMMON_ARGS[@]}" --max-redirs 0 --output /dev/null --dump-header "$headers_file" --write-out '%{http_code}' "$BASE_URL$source_path")"
    if [[ "$status" == '404' || "$status" == '410' ]]; then
      break
    fi
    if [[ "$status" != '202' && "$status" != '429' && ! "$status" =~ ^5[0-9][0-9]$ ]]; then
      break
    fi
    if [[ "$attempt" -lt 4 ]]; then sleep $(( attempt * 2 )); fi
  done
  location="$(read_header_value 'location' "$headers_file")"
  x_redirect_by="$(read_header_value 'x-redirect-by' "$headers_file")"
  server="$(read_header_value 'server' "$headers_file")"
  [[ "$status" == '404' || "$status" == '410' ]] || fail "$source_path returned HTTP $status instead of 404/410 after $attempt attempt(s); location=${location:-none}; x_redirect_by=${x_redirect_by:-none}; server=${server:-none}"
  [[ -z "$location" ]] || fail "$source_path emitted forbidden Location header: $location"
  echo "PASS retired $source_path status=$status attempts=$attempt"
}

# check_target_page verifies that a retained final target remains publicly available.
check_target_page() {
  local target_path="$1"
  local status attempt
  status='000'
  for attempt in 1 2 3 4; do
    status="$(curl "${CURL_COMMON_ARGS[@]}" --output /dev/null --write-out '%{http_code}' "$BASE_URL$target_path")"
    if [[ "$status" == '200' ]]; then break; fi
    if [[ "$status" != '202' && "$status" != '429' && ! "$status" =~ ^5[0-9][0-9]$ ]]; then break; fi
    if [[ "$attempt" -lt 4 ]]; then sleep $(( attempt * 2 )); fi
  done
  [[ "$status" == '200' ]] || fail "$target_path returned HTTP $status instead of 200 after $attempt attempt(s)"
  echo "PASS retained target $target_path status=200 attempts=$attempt"
}

# Allow the edge cache and anti-bot layer to observe the completed immutable release.
sleep 5

for legacy_path in \
  '/mas-informacion-sobre-las-cookies/' \
  '/politica-de-cookies/' \
  '/politica-de-privacidad/' \
  '/tratamiento-retirado/' \
  '/tratamientos/' \
  '/liposculpt-air/' \
  '/v-lift-awake/' \
  '/dr-javier-rivera-tejeda/' \
  '/eye-frame-rejuvenecimiento-mirada-madrid/' \
  '/eye-frame/'
do
  check_retired_route "$legacy_path"
done

for target_path in \
  '/politica-de-cookies-ue/' \
  '/politica-privacidad/' \
  '/soluciones-medicas/' \
  '/remodelacion-corporal-laser-madrid/' \
  '/protocolos-signature/' \
  '/equipo-medico/' \
  '/ojeras-surco-lagrimal-madrid/'
do
  check_target_page "$target_path"
done
echo 'LEGACY_ROUTES_RETIRED_OK'
fetch_page '/soluciones-medicas/' 'Soluciones médicas para rostro, piel y contorno corporal.' 'Rostro y cuello' 'Contorno corporal' 'Cambios posgestacionales' 'Valoración de procedimientos previos'
fetch_page '/protocolos-signature/' 'Protocolos Signature: Medicina estética de diagnóstico.' 'Nuestro estándar: La firma NUVANX' 'NUVANX Contour Architecture' 'Post-Maternity Contour' 'Tu primera valoración clínica'
fetch_page '/remodelacion-corporal-laser-madrid/' 'NUVANX Contour Architecture™: El protocolo y la tecnología' 'Tres decisiones clínicas: Reducir, Redefinir, Retraer' 'Cuándo no es el tratamiento adecuado'
fetch_page '/tratamiento-postparto-abdomen-contorno-corporal-madrid/' 'Tratamiento Postparto: Abdomen y Contorno Corporal en Madrid' 'El Protocolo NUVANX Post-Maternity Contour' 'Las alteraciones del posparto' 'Preguntas frecuentes'
fetch_page '/por-que-nuvanx/' 'Por qué NUVANX. Sin retórica de marketing.' 'Responsabilidad médica y continuidad asistencial' 'Trazabilidad de productos' 'Por qué importa'
fetch_page '/inversion-medicina-estetica/' 'El presupuesto forma parte de una decisión informada.' 'Cómo leer estas tarifas' 'Qué incluye siempre el plan en NUVANX' 'Qué no encontrarás aquí'

MARKER_TREATMENT_PAGE='nvx-treatment-page'
MARKER_VALORA='Qué se valora'
MARKER_DECIDE='Cómo se decide el plan'
MARKER_LIMITES='Límites y cuándo derivamos'

# Technology / laser pages historically hit by fragmented SiteGround Dynamic Cache.
# Unique H1 fragments + brand hero class so a pre-deploy HTML snapshot cannot pass quietly.
fetch_page '/endolift-facial-papada-mandibula/' 'nvx-endolift-h1' 'Endolift' 'papada, mandíbula y cuello' 'nvx-brand-hero'
fetch_page '/endolaser-corporal-grasa-localizada/' 'Endoláser corporal en Madrid' 'grasa localizada y mejor contorno' 'nvx-brand-hero'
fetch_page '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/' 'Láser CO' 'textura, poros y cicatrices' 'nvx-brand-hero'
fetch_page '/exion-btl/' 'EXION' 'BTL en Madrid' 'nvx-brand-hero'

fetch_page '/papada-definicion-mandibular-madrid/' "$MARKER_TREATMENT_PAGE" "$MARKER_VALORA" "$MARKER_DECIDE" "$MARKER_LIMITES"
fetch_page '/calidad-piel-firmeza-luminosidad-madrid/' "$MARKER_TREATMENT_PAGE" "$MARKER_VALORA" "$MARKER_DECIDE" "$MARKER_LIMITES"
fetch_page '/cicatrices-acne-poros-textura-madrid/' "$MARKER_TREATMENT_PAGE" "$MARKER_VALORA" "$MARKER_DECIDE" "$MARKER_LIMITES"
fetch_page '/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/' "$MARKER_TREATMENT_PAGE" "$MARKER_VALORA" "$MARKER_DECIDE" "$MARKER_LIMITES"

# Facial injectables (catalog matrix; staging2 previews pending medical review).
MARKER_INDICATIONS='Indicaciones: Qué tratamos'
MARKER_PRECAUTIONS='Precauciones: Cuándo no tratar'
fetch_page '/labios-acido-hialuronico-madrid/' "$MARKER_TREATMENT_PAGE" 'Ácido hialurónico en labios en Madrid' "$MARKER_INDICATIONS" "$MARKER_PRECAUTIONS"
fetch_page '/rinomodelacion-sin-cirugia-madrid/' "$MARKER_TREATMENT_PAGE" 'Rinomodelación con ácido hialurónico en Madrid' "$MARKER_INDICATIONS" "$MARKER_PRECAUTIONS"
fetch_page '/ojeras-surco-lagrimal-madrid/' "$MARKER_TREATMENT_PAGE" 'Tratamiento de ojeras y surco lagrimal en Madrid' "$MARKER_INDICATIONS" "$MARKER_PRECAUTIONS"
fetch_page '/bioestimuladores-colageno-madrid/' "$MARKER_TREATMENT_PAGE" 'Bioestimuladores de colágeno en Madrid' "$MARKER_INDICATIONS" "$MARKER_PRECAUTIONS"
fetch_page '/grasa-localizada-abdomen-flancos-madrid/' 'Grasa localizada en abdomen y flancos en Madrid' "$MARKER_VALORA" "$MARKER_DECIDE" "$MARKER_LIMITES"
fetch_page '/flacidez-grasa-localizada-brazos-madrid/' 'Flacidez y grasa localizada en brazos en Madrid' "$MARKER_VALORA" "$MARKER_DECIDE" "$MARKER_LIMITES"
fetch_page '/grasa-espalda-zona-sujetador-madrid/' 'Grasa de espalda y zona del sujetador en Madrid' "$MARKER_VALORA" "$MARKER_DECIDE" "$MARKER_LIMITES"
fetch_page '/flacidez-muslos-internos-subgluteo-madrid/' 'Flacidez en muslos internos y región subglútea en Madrid' "$MARKER_VALORA" "$MARKER_DECIDE" "$MARKER_LIMITES"
fetch_page '/tratamiento-rodillas-grasa-flacidez-madrid/' 'Grasa localizada y flacidez en rodillas en Madrid' "$MARKER_VALORA" "$MARKER_DECIDE" "$MARKER_LIMITES"
fetch_page '/contorno-corporal-masculino-madrid/' 'Contorno corporal masculino en Madrid' "$MARKER_VALORA" "$MARKER_DECIDE" "$MARKER_LIMITES"


if [[ -n "$EXPECTED_SHA" ]]; then
  echo "SMOKE_VERIFY_OK base_url=$BASE_URL expected_sha=$EXPECTED_SHA"
else
  echo "SMOKE_VERIFY_OK base_url=$BASE_URL expected_sha=unset"
fi
