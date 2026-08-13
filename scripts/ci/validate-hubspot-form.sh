#!/usr/bin/env bash
set -Eeuo pipefail

FORM_ID="${HUBSPOT_FORM_ID:-5042522a-0bc5-4381-ac3e-5aee8649b69c}"
EXPECTED_PORTAL="${HUBSPOT_PORTAL:-147416356}"

: "${HUBSPOT_ACCESS_TOKEN:?Missing HUBSPOT_ACCESS_TOKEN}"

response="${RUNNER_TEMP:-/tmp}/hubspot-form-${FORM_ID}.json"
status="$(
  curl --silent --show-error \
    --connect-timeout 10 \
    --max-time 30 \
    --output "$response" \
    --write-out '%{http_code}' \
    --header "Authorization: Bearer ${HUBSPOT_ACCESS_TOKEN}" \
    "https://api.hubapi.com/marketing/v3/forms/${FORM_ID}"
)"

echo "HUBSPOT_HTTP_STATUS=$status"

if [[ "$status" != '200' ]]; then
  echo 'HUBSPOT_FORM_GATE=FAIL' >&2
  jq '{status,category,message,correlationId}' "$response" 2>/dev/null || true
  exit 1
fi

jq -e --arg id "$FORM_ID" '
  .id == $id and
  ((.archived // false) == false)
' "$response" >/dev/null

name="$(jq -r '.name // ""' "$response")"
portal="$(jq -r '.portalId // ""' "$response")"

if [[ -n "$portal" && -n "$EXPECTED_PORTAL" && "$portal" != "$EXPECTED_PORTAL" ]]; then
  echo "HUBSPOT_FORM_GATE=FAIL portal=$portal expected=$EXPECTED_PORTAL" >&2
  exit 1
fi

echo "HUBSPOT_FORM_GATE=PASS form_id=$FORM_ID form_name=$name portal=${portal:-not-returned}"
