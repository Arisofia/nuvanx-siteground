import assert from 'node:assert/strict';
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
