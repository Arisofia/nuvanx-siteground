from pathlib import Path
import sys

root = Path(sys.argv[1] if len(sys.argv) > 1 else '.')
p = root / 'wp-content/themes/nuvanx-medical/assets/js/nvx-conversion-events.js'
text = p.read_text()

replacements = [
(
"\tfunction setLegacyField(root, propertyName, value) {\n\t\tif (!root) return false;\n\t\tvar input = root.querySelector('[name=\"' + propertyName + '\"]');\n\t\tif (!input) return false;\n",
"\tfunction legacyFieldInput(root, propertyName) {\n\t\tif (!root) return null;\n\t\treturn root.querySelector('[name=\"' + propertyName + '\"], [name=\"0-1/' + propertyName + '\"]');\n\t}\n\n\tfunction concealLegacyTrackingField(root, propertyName) {\n\t\tif (propertyName.indexOf('nvx_') !== 0 && propertyName !== 'hs_google_click_id') return;\n\t\tvar input = legacyFieldInput(root, propertyName);\n\t\tif (!input) return;\n\t\tinput.setAttribute('tabindex', '-1');\n\t\tinput.setAttribute('aria-hidden', 'true');\n\t\tvar wrapper = input.closest('.hs-form-field, .field, .hs-fieldtype-text, .hs-fieldtype-hidden');\n\t\tif (wrapper) {\n\t\t\twrapper.hidden = true;\n\t\t\twrapper.setAttribute('aria-hidden', 'true');\n\t\t\twrapper.style.display = 'none';\n\t\t}\n\t}\n\n\tfunction setLegacyField(root, propertyName, value) {\n\t\tif (!root) return false;\n\t\tvar input = legacyFieldInput(root, propertyName);\n\t\tif (!input) return false;\n"
),
(
"\t\t\tFIELD_MAP[param].forEach(function (propertyName) {\n\t\t\t\tif (!consent && propertyName.indexOf('nvx_') !== 0) {\n",
"\t\t\tFIELD_MAP[param].forEach(function (propertyName) {\n\t\t\t\tconcealLegacyTrackingField(root, propertyName);\n\t\t\t\tif (!consent && propertyName.indexOf('nvx_') !== 0) {\n"
),
(
"\t\t\t\t\t\tvar nativeInput = root.querySelector('[name=\"hs_google_click_id\"]');\n",
"\t\t\t\t\t\tvar nativeInput = legacyFieldInput(root, 'hs_google_click_id');\n"
),
(
"\t\tvar emailInput = root.querySelector('[name=\"email\"]');\n",
"\t\tvar emailInput = legacyFieldInput(root, 'email');\n"
),
]

for old, new in replacements:
    count = text.count(old)
    if count != 1:
        raise SystemExit(f'Expected exactly one match, found {count}: {old[:100]!r}')
    text = text.replace(old, new)

p.write_text(text)
