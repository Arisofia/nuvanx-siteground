#!/usr/bin/env python3
from pathlib import Path

boundary = Path('scripts/production/verify-production-boundary.mjs')
text = boundary.read_text(encoding='utf-8')

old_import = "} from './deploy-identity-contract.mjs';\n"
new_import = old_import + "import { hasLegacyValoracionDirectForm } from './valoracion-form-contract.mjs';\n"
if text.count(old_import) != 1:
    raise SystemExit(f'expected one deploy identity import tail, found {text.count(old_import)}')
text = text.replace(old_import, new_import, 1)

old_js = "    if (html.includes('nvx-valoracion-direct-form') || /<form\\b[^>]*data-nvx-direct-form/i.test(html)) {\n      issues.push('Legacy first-party valoración form still present');\n    }"
new_js = "    if (hasLegacyValoracionDirectForm(html)) {\n      issues.push('Legacy first-party valoración form still present');\n    }"
if text.count(old_js) != 1:
    raise SystemExit(f'expected one JS legacy form check, found {text.count(old_js)}')
text = text.replace(old_js, new_js, 1)

require_body = '''require_body() {
  local body="$1" route="$2" marker="$3"
  grep -Fq "$marker" "$body" || {
    echo "PRODUCTION_ORIGIN_FAIL route=$route reason=missing_string marker=$marker" >&2
    return 1
  }
}
'''
structural_fn = require_body + '''
legacy_valoracion_direct_form_count() {
  local body="$1"
  php -r '
    $html = @file_get_contents($argv[1]);
    if (!is_string($html)) { fwrite(STDERR, "legacy_form_body_unreadable\\n"); exit(2); }
    $html = preg_replace("~<script\\b[^>]*>.*?</script\\s*>|<style\\b[^>]*>.*?</style\\s*>~is", "", $html);
    if (!is_string($html)) { fwrite(STDERR, "legacy_form_strip_failed\\n"); exit(2); }
    preg_match_all("~<form\\b[^>]*>~i", $html, $forms);
    $count = 0;
    foreach ($forms[0] as $tag) {
      if (preg_match("~(?:^|[^a-z0-9_-])nvx-valoracion-direct-form(?:[^a-z0-9_-]|$)~i", $tag)
          || preg_match("~\\bdata-nvx-direct-form(?:\\s*=|\\s|/?>)~i", $tag)) {
        ++$count;
      }
    }
    echo $count;
  ' -- "$body"
}
'''
if text.count(require_body) != 1:
    raise SystemExit(f'expected one require_body helper, found {text.count(require_body)}')
text = text.replace(require_body, structural_fn, 1)

old_shell = '''      ! grep -Fiq 'nvx-valoracion-direct-form' "$body" \\
        || { echo "PRODUCTION_ORIGIN_FAIL route=$route reason=legacy_direct_form_marker" >&2; cleanup; exit 1; }
'''
new_shell = '''      legacy_direct_forms="$(legacy_valoracion_direct_form_count "$body")"
      [[ "$legacy_direct_forms" == '0' ]] \\
        || { echo "PRODUCTION_ORIGIN_FAIL route=$route reason=legacy_direct_form count=$legacy_direct_forms" >&2; cleanup; exit 1; }
'''
if text.count(old_shell) != 1:
    raise SystemExit(f'expected one origin legacy grep, found {text.count(old_shell)}')
text = text.replace(old_shell, new_shell, 1)
boundary.write_text(text, encoding='utf-8')

contract_module = Path('scripts/production/valoracion-form-contract.mjs')
contract_module.write_text(r'''function stripScriptAndStyleText(html) {
  return String(html || '')
    .replace(/<script\b[^>]*>[\s\S]*?<\/script\s*>/gi, '')
    .replace(/<style\b[^>]*>[\s\S]*?<\/style\s*>/gi, '');
}

export function legacyValoracionDirectFormTags(html) {
  const markup = stripScriptAndStyleText(html);
  const formTags = markup.match(/<form\b[^>]*>/gi) || [];
  return formTags.filter((tag) => {
    const classMarker = /\bclass\s*=\s*(?:"[^"]*\bnvx-valoracion-direct-form\b[^"]*"|'[^']*\bnvx-valoracion-direct-form\b[^']*')/i.test(tag);
    const dataMarker = /\bdata-nvx-direct-form(?:\s*=\s*(?:"[^"]*"|'[^']*'|[^\s>]+))?(?=\s|\/?>)/i.test(tag);
    return classMarker || dataMarker;
  });
}

export function hasLegacyValoracionDirectForm(html) {
  return legacyValoracionDirectFormTags(html).length > 0;
}
''', encoding='utf-8')

test_file = Path('scripts/production/test-valoracion-form-contract.mjs')
test_file.write_text(r'''import assert from 'node:assert/strict';
import {
  hasLegacyValoracionDirectForm,
  legacyValoracionDirectFormTags,
} from './valoracion-form-contract.mjs';

const allowed = [
  '<style>.nvx-valoracion-direct-form{display:grid}.x form.nvx-valoracion-direct-form{gap:1rem}</style><div id="nvx-hubspot-native-form"><div class="hs-form-frame"></div></div>',
  '<script>const template = "<form class=\\"nvx-valoracion-direct-form\\"></form>";</script><div id="nvx-hubspot-native-form"></div>',
  '<div class="nvx-valoracion-direct-form">non-form marker</div>',
  '<form class="contact-form" method="post"><input name="email"></form>',
  '<div id="nvx-hubspot-native-form"><div class="hs-form-frame" data-form-id="5042522a-0bc5-4381-ac3e-5aee8649b69c" data-portal-id="147416356"></div></div>',
];

const blocked = [
  '<form class="nvx-valoracion-direct-form" method="post"></form>',
  '<form class="foo nvx-valoracion-direct-form bar" method="post"></form>',
  '<form data-nvx-direct-form method="post"></form>',
  '<FORM DATA-NVX-DIRECT-FORM="1" class="other"></FORM>',
];

for (const [index, html] of allowed.entries()) {
  assert.equal(hasLegacyValoracionDirectForm(html), false, `allowed case ${index + 1} must not be classified as a legacy form`);
  assert.equal(legacyValoracionDirectFormTags(html).length, 0, `allowed case ${index + 1} must have zero structural matches`);
}

for (const [index, html] of blocked.entries()) {
  assert.equal(hasLegacyValoracionDirectForm(html), true, `blocked case ${index + 1} must be classified as a legacy form`);
  assert.equal(legacyValoracionDirectFormTags(html).length, 1, `blocked case ${index + 1} must have one structural match`);
}

console.log(`VALORACION_FORM_CONTRACT_TEST=PASS allowed=${allowed.length} blocked=${blocked.length}`);
''', encoding='utf-8')

release = Path('scripts/ci/test-release-regression-contract.sh')
rtext = release.read_text(encoding='utf-8')
old_vars = 'BOUNDARY="$ROOT/scripts/production/verify-production-boundary.mjs"\n'
new_vars = old_vars + 'VALORACION_FORM_CONTRACT="$ROOT/scripts/production/valoracion-form-contract.mjs"\nVALORACION_FORM_CONTRACT_TEST="$ROOT/scripts/production/test-valoracion-form-contract.mjs"\n'
if rtext.count(old_vars) != 1:
    raise SystemExit(f'expected one boundary variable, found {rtext.count(old_vars)}')
rtext = rtext.replace(old_vars, new_vars, 1)

old_required = '"$DEPLOY" "$WORKFLOW" "$BOUNDARY" "$ENV_FLAGS"'
new_required = '"$DEPLOY" "$WORKFLOW" "$BOUNDARY" "$VALORACION_FORM_CONTRACT" "$VALORACION_FORM_CONTRACT_TEST" "$ENV_FLAGS"'
if rtext.count(old_required) != 1:
    raise SystemExit(f'expected one required-file sequence, found {rtext.count(old_required)}')
rtext = rtext.replace(old_required, new_required, 1)

old_boundary_assert = "grep -Fq 'EXPECTED_HOST=${expectedHost}' \"$BOUNDARY\" || fail 'boundary_origin_expected_host_not_wired'\n"
new_boundary_assert = old_boundary_assert + "grep -Fq \"from './valoracion-form-contract.mjs'\" \"$BOUNDARY\" || fail 'boundary_valoracion_structural_contract_not_wired'\n"
if rtext.count(old_boundary_assert) != 1:
    raise SystemExit(f'expected one boundary host assertion, found {rtext.count(old_boundary_assert)}')
rtext = rtext.replace(old_boundary_assert, new_boundary_assert, 1)

anchor = "pass_assert 'boundary-identity-semantics'\n"
insert = anchor + "\nnode \"$VALORACION_FORM_CONTRACT_TEST\" || fail 'valoracion_form_structural_boundary_behavior'\npass_assert 'valoracion-form-structural-boundary'\n"
if rtext.count(anchor) != 1:
    raise SystemExit(f'expected one boundary identity assertion anchor, found {rtext.count(anchor)}')
rtext = rtext.replace(anchor, insert, 1)
release.write_text(rtext, encoding='utf-8')

print('VALORACION_BOUNDARY_STRUCTURAL_PATCH=PASS files=4')
