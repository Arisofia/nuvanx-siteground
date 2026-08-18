#!/usr/bin/env bash
set -Eeuo pipefail

MODE="${1:---check}"
FORM_ID="${HUBSPOT_FORM_ID:-5042522a-0bc5-4381-ac3e-5aee8649b69c}"
PORTAL_ID="${HUBSPOT_PORTAL:-147416356}"
API_BASE="https://api.hubapi.com"

case "$MODE" in
  --check|--apply) ;;
  *) echo "Usage: $0 [--check|--apply]" >&2; exit 2 ;;
esac

: "${HUBSPOT_ACCESS_TOKEN:?Missing HUBSPOT_ACCESS_TOKEN}"
if [[ "$MODE" == '--apply' && "${NUVANX_CONFIRM:-}" != 'yes' ]]; then
  echo 'Refusing HubSpot mutation without NUVANX_CONFIRM=yes' >&2
  exit 2
fi

work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT

request() {
  local method="$1" url="$2" output="$3" body="${4:-}"
  local args=(
    --silent --show-error --connect-timeout 10 --max-time 45
    --request "$method"
    --output "$output"
    --write-out '%{http_code}'
    --header "Authorization: Bearer ${HUBSPOT_ACCESS_TOKEN}"
    --header 'Content-Type: application/json'
    "$url"
  )
  if [[ -n "$body" ]]; then
    args+=(--data-binary "@$body")
  fi
  curl "${args[@]}"
}

check_property() {
  local name="$1" out="$work/property-${name}.json"
  local status
  status="$(request GET "$API_BASE/crm/v3/properties/contacts/$name" "$out")"
  if [[ "$status" == '200' ]]; then
    local name_ok type hidden
    name_ok="$(jq -r --arg name "$name" 'if .name == $name then "1" else "0" end' "$out" 2>/dev/null || echo 0)"
    type="$(jq -r '.type // empty' "$out" 2>/dev/null || true)"
    hidden="$(jq -r '(.hidden // false) | tostring' "$out" 2>/dev/null || echo unknown)"
    if [[ "$name_ok" == '1' && "$type" == 'string' && "$hidden" == 'false' ]]; then
      return 0
    fi
    echo "HUBSPOT_PROPERTY_CONTRACT=FAIL property=$name name_match=$name_ok type=${type:-missing} hidden=$hidden" >&2
    exit 1
  fi
  if [[ "$status" == '404' ]]; then
    return 1
  fi
  echo "HUBSPOT_PROPERTY_CHECK=ERROR property=$name status=$status" >&2
  jq '{status,category,message,correlationId}' "$out" 2>/dev/null || true
  exit 1
}

create_lead_property() {
  cat > "$work/create-nvx-lead-id.json" <<'JSON'
{
  "groupName": "contactinformation",
  "label": "NUVANX Lead ID",
  "name": "nvx_lead_id",
  "description": "Identificador first-party durable de la línea de captación NUVANX. No contiene PII ni información clínica.",
  "type": "string",
  "fieldType": "text",
  "formField": true,
  "hasUniqueValue": false,
  "hidden": false
}
JSON
  local status
  status="$(request POST "$API_BASE/crm/v3/properties/contacts" "$work/create-property-response.json" "$work/create-nvx-lead-id.json")"
  [[ "$status" == '201' || "$status" == '200' ]] || {
    echo "HUBSPOT_PROPERTY_CREATE=FAIL property=nvx_lead_id status=$status" >&2
    jq '{status,category,message,correlationId}' "$work/create-property-response.json" 2>/dev/null || true
    exit 1
  }
  echo 'HUBSPOT_PROPERTY_CREATE=PASS property=nvx_lead_id'
}

required_properties=(
  nvx_lead_id
  nvx_utm_source
  nvx_utm_medium
  nvx_utm_campaign
  nvx_utm_content
  nvx_utm_term
  nvx_landing_url
  nvx_attribution_captured_at
  nvx_attribution_expires_at
  nvx_google_click_id
  nvx_google_braid
  nvx_google_wbraid
  nvx_google_gclsrc
)

missing_properties=()
for property in "${required_properties[@]}"; do
  if ! check_property "$property"; then
    missing_properties+=("$property")
  fi
done

unexpected_missing=()
for property in "${missing_properties[@]}"; do
  [[ "$property" == 'nvx_lead_id' ]] || unexpected_missing+=("$property")
done
if (( ${#unexpected_missing[@]} > 0 )); then
  printf 'HUBSPOT_PROPERTY_CONTRACT=FAIL missing_existing_properties=%s\n' "${unexpected_missing[*]}" >&2
  exit 1
fi

if printf '%s\n' "${missing_properties[@]}" | grep -Fxq 'nvx_lead_id'; then
  if [[ "$MODE" == '--apply' ]]; then
    create_lead_property
    check_property nvx_lead_id || { echo 'HUBSPOT_PROPERTY_VERIFY=FAIL property=nvx_lead_id' >&2; exit 1; }
  else
    echo 'HUBSPOT_PROPERTY_CONTRACT=FAIL missing=nvx_lead_id' >&2
  fi
fi

form="$work/form.json"
status="$(request GET "$API_BASE/marketing/v3/forms/$FORM_ID" "$form")"
[[ "$status" == '200' ]] || {
  echo "HUBSPOT_FORM_CONTRACT=FAIL status=$status form_id=$FORM_ID" >&2
  jq '{status,category,message,correlationId}' "$form" 2>/dev/null || true
  exit 1
}

if jq -e --arg portal "$PORTAL_ID" '((.portalId // "") == "" or (.portalId|tostring) == $portal) and (.archived // false) == false' "$form" >/dev/null; then
  :
else
  echo "HUBSPOT_FORM_IDENTITY=FAIL form_id=$FORM_ID portal=$PORTAL_ID" >&2
  exit 1
fi

required_form_fields=("${required_properties[@]}")

mapfile -t existing_form_fields < <(jq -r '.fieldGroups[]?.fields[]?.name // empty' "$form" | sort -u)
missing_form_fields=()
for property in "${required_form_fields[@]}"; do
  if ! printf '%s\n' "${existing_form_fields[@]}" | grep -Fxq "$property"; then
    missing_form_fields+=("$property")
  fi
done

if (( ${#missing_form_fields[@]} > 0 )); then
  if [[ "$MODE" == '--check' ]]; then
    printf 'HUBSPOT_FORM_FIELD_CONTRACT=FAIL missing=%s\n' "${missing_form_fields[*]}" >&2
  else
    cp "$form" "$work/form-working.json"
    for property in "${missing_form_fields[@]}"; do
      jq --arg name "$property" '
        .fieldGroups += [{
          groupType: "default_group",
          fields: [{
            objectTypeId: "0-1",
            name: $name,
            label: $name,
            fieldType: "text",
            hidden: true,
            required: false
          }]
        }]
      ' "$work/form-working.json" > "$work/form-next.json"
      mv "$work/form-next.json" "$work/form-working.json"
    done
    jq '{fieldGroups}' "$work/form-working.json" > "$work/form-patch.json"
    patch_status="$(request PATCH "$API_BASE/marketing/v3/forms/$FORM_ID" "$work/form-patch-response.json" "$work/form-patch.json")"
    [[ "$patch_status" == '200' ]] || {
      echo "HUBSPOT_FORM_PATCH=FAIL status=$patch_status form_id=$FORM_ID" >&2
      jq '{status,category,message,correlationId}' "$work/form-patch-response.json" 2>/dev/null || true
      exit 1
    }
    echo "HUBSPOT_FORM_PATCH=PASS added=${missing_form_fields[*]}"
  fi
fi

if [[ "$MODE" == '--check' && ( ${#missing_properties[@]} -gt 0 || ${#missing_form_fields[@]} -gt 0 ) ]]; then
  exit 1
fi

verify="$work/form-verify.json"
verify_status="$(request GET "$API_BASE/marketing/v3/forms/$FORM_ID" "$verify")"
[[ "$verify_status" == '200' ]] || { echo "HUBSPOT_FORM_VERIFY=FAIL status=$verify_status" >&2; exit 1; }
for property in "${required_form_fields[@]}"; do
  jq -e --arg name "$property" '[.fieldGroups[]?.fields[]? | select(.name == $name and (.hidden // false) == true)] | length >= 1' "$verify" >/dev/null || {
    echo "HUBSPOT_FORM_VERIFY=FAIL missing_hidden_field=$property" >&2
    exit 1
  }
done

check_property nvx_lead_id || { echo 'HUBSPOT_PROPERTY_VERIFY=FAIL property=nvx_lead_id' >&2; exit 1; }
echo "HUBSPOT_ATTRIBUTION_CONTRACT=PASS mode=${MODE#--} form_id=$FORM_ID fields=${#required_form_fields[@]}"
