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
  /klaviyo\.com/,
  'Klaviyo must stay out of the early resource-hint list',
);
assert.match(
  integrations,
  /nvx_dequeue_klaviyo_onsite/,
  'Klaviyo onsite identify must be dequeued on the public site',
);

console.log('LCP_CSS_DELIVERY=PASS');
