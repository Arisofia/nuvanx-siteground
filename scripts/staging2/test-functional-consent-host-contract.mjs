import assert from 'node:assert/strict';
import {
  NVX_VALORACION_FORM_HOST_ID,
  hasFunctionalConsentOnNativeFormHost,
} from './test-hubspot-specific-gate.mjs';

const realHost = '<div id="nvx-hubspot-native-form" class="nvx-hubspot-native-form-v2" data-nvx-hubspot-native="1" data-nvx-hubspot-eager="1" data-nvx-consent="functional"></div>';
const reorderedHost = '<div data-nvx-consent="functional" class="nvx-hubspot-native-form-v2" id="nvx-hubspot-native-form"></div>';
const strayOnly = '<section data-nvx-consent="functional"></section><div id="nvx-hubspot-native-form"></div>';
const hostWithoutConsent = '<div id="nvx-hubspot-native-form" class="nvx-hubspot-native-form-v2"></div>';

assert.equal(NVX_VALORACION_FORM_HOST_ID, 'nvx-hubspot-native-form');
assert.equal(hasFunctionalConsentOnNativeFormHost(realHost), true);
assert.equal(hasFunctionalConsentOnNativeFormHost(reorderedHost), true);
assert.equal(hasFunctionalConsentOnNativeFormHost(strayOnly), false);
assert.equal(hasFunctionalConsentOnNativeFormHost(hostWithoutConsent), false);
assert.equal(hasFunctionalConsentOnNativeFormHost(''), false);
assert.equal(hasFunctionalConsentOnNativeFormHost(null), false);

console.log('FUNCTIONAL_CONSENT_HOST_CONTRACT=PASS');
