#!/usr/bin/env node
import assert from 'node:assert/strict';
import fs from 'node:fs';

const functionsPhp = fs.readFileSync('wp-content/themes/nuvanx-medical/functions.php', 'utf8');
const governance = fs.readFileSync(
  'wp-content/themes/nuvanx-medical/inc/nvx-native-style-governance.php',
  'utf8',
);
const integrations = fs.readFileSync(
  'wp-content/themes/nuvanx-medical/inc/nvx-integrations.php',
  'utf8',
);

assert.match(
  functionsPhp,
  /display=swap/,
  'Google Fonts request must keep font-display=swap',
);
assert.match(
  functionsPhp,
  /Structural CSS is deliberately render-blocking/,
  'header/layout/components must remain render-blocking',
);
assert.match(
  governance,
  /function nvx_theme_inline_critical_style_foundation/,
  'critical tokens/base/fonts must be inlined',
);
assert.match(
  governance,
  /wp_register_style\(\s*'nvx-fonts'\s*,\s*false\s*,\s*array\(\s*'nvx-google-fonts'\s*,\s*'nvx-critical-inline'\s*\)/,
  'inlined nvx-fonts handle must preserve its Google Fonts dependency',
);
assert.match(
  governance,
  /function nvx_theme_nonblocking_google_fonts/,
  'Google Fonts stylesheet must not block first paint',
);
assert.match(
  governance,
  /nvx-patterns/,
  'editorial pattern CSS is the only theme sheet allowed to defer',
);
assert.doesNotMatch(
  governance,
  /nvx-components['"]\s*,/,
  'nvx-components must not be print-deferred',
);
assert.match(
  integrations,
  /nvx_is_valoracion_klaviyo_excluded/,
  'Klaviyo Onsite must stay available off the valoración landing',
);
assert.match(
  integrations,
  /nvx_dequeue_klaviyo_onsite_on_valoracion/,
  'Klaviyo Onsite must be removed only on the HubSpot conversion landing',
);
assert.match(
  integrations,
  /R5dw99/,
  'Klaviyo exclusion comments must name the live onsite popup',
);

console.log('LCP_CSS_DELIVERY=PASS');
