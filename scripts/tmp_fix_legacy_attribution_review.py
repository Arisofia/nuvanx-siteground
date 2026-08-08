from pathlib import Path
import sys

root = Path(sys.argv[1] if len(sys.argv) > 1 else '.')
p = root / 'wp-content/themes/nuvanx-medical/assets/js/nvx-conversion-events.js'
text = p.read_text()

replacements = [
(
"\tvar legacyFormRoots = [];\n\tvar legacyEmailForAudit = '';\n",
"\tvar legacyFormRoots = [];\n\tvar legacyEmailForAudit = '';\n\tvar legacyEmailClearTimer = null;\n\n\tfunction clearLegacyEmail() {\n\t\tlegacyEmailForAudit = '';\n\t\tif (legacyEmailClearTimer) {\n\t\t\twindow.clearTimeout(legacyEmailClearTimer);\n\t\t\tlegacyEmailClearTimer = null;\n\t\t}\n\t}\n"
),
(
"\t\ttry {\n\t\t\tinput.value = value;\n\t\t\tif (value) input.setAttribute('value', value);\n",
"\t\ttry {\n\t\t\tvar prototype = Object.getPrototypeOf(input);\n\t\t\tvar descriptor = prototype ? Object.getOwnPropertyDescriptor(prototype, 'value') : null;\n\t\t\tif (descriptor && typeof descriptor.set === 'function') descriptor.set.call(input, value);\n\t\t\telse input.value = value;\n\t\t\tif (value) input.setAttribute('value', value);\n\t\t\telse input.removeAttribute('value');\n"
),
(
"\t\t\tFIELD_MAP[param].forEach(function (propertyName) {\n\t\t\t\tif (!consent && propertyName.indexOf('nvx_') !== 0) return;\n\t\t\t\tmodified = setLegacyField(root, propertyName, value) || modified;\n\t\t\t});\n",
"\t\t\tFIELD_MAP[param].forEach(function (propertyName) {\n\t\t\t\tif (!consent && propertyName.indexOf('nvx_') !== 0) {\n\t\t\t\t\tif (propertyName === 'hs_google_click_id' && clickValues.gclid) {\n\t\t\t\t\t\tvar nativeInput = root.querySelector('[name=\"hs_google_click_id\"]');\n\t\t\t\t\t\tif (nativeInput && String(nativeInput.value || '') === clickValues.gclid) {\n\t\t\t\t\t\t\tmodified = setLegacyField(root, propertyName, '') || modified;\n\t\t\t\t\t\t}\n\t\t\t\t\t}\n\t\t\t\t\treturn;\n\t\t\t\t}\n\t\t\t\tmodified = setLegacyField(root, propertyName, value) || modified;\n\t\t\t});\n"
),
(
"\tfunction refreshLegacyForms() {\n\t\tlegacyFormRoots = legacyFormRoots.filter(function (root) {\n",
"\tfunction refreshLegacyForms() {\n\t\tif (!hasMarketingConsent()) clearLegacyEmail();\n\t\tlegacyFormRoots = legacyFormRoots.filter(function (root) {\n"
),
(
"\tfunction captureLegacyEmail(formLike) {\n\t\tlegacyEmailForAudit = '';\n",
"\tfunction captureLegacyEmail(formLike) {\n\t\tclearLegacyEmail();\n"
),
(
"\t\tlegacyEmailForAudit = email;\n\t}\n\n\tasync function transmitLegacySuccess() {\n\t\tvar email = legacyEmailForAudit;\n\t\tlegacyEmailForAudit = '';\n",
"\t\tlegacyEmailForAudit = email;\n\t\tlegacyEmailClearTimer = window.setTimeout(clearLegacyEmail, 30000);\n\t}\n\n\tasync function transmitLegacySuccess() {\n\t\tvar email = legacyEmailForAudit;\n\t\tclearLegacyEmail();\n"
),
(
"\twindow.addEventListener('message', function (event) {\n\t\tif (!isTrustedHubSpotOrigin(event.origin)) return;\n\t\tvar data = event.data || {};\n\t\tif (data.type !== 'hsFormCallback' || data.eventName !== 'onFormSubmitted') return;\n",
"\twindow.addEventListener('message', function (event) {\n\t\tif (!isTrustedHubSpotOrigin(event.origin)) return;\n\t\tvar data = event.data || {};\n\t\tif (typeof data === 'string') {\n\t\t\ttry { data = JSON.parse(data); } catch (_error) { return; }\n\t\t}\n\t\tif (data.type !== 'hsFormCallback' || data.eventName !== 'onFormSubmitted') return;\n"
),
]

for old, new in replacements:
    count = text.count(old)
    if count != 1:
        raise SystemExit(f'Expected exactly one match, found {count}: {old[:80]!r}')
    text = text.replace(old, new)

p.write_text(text)
