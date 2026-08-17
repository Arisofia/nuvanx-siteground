import assert from 'node:assert/strict';
import fs from 'node:fs';

const conversionEvents = fs.readFileSync(
  'wp-content/themes/nuvanx-medical/assets/js/nvx-conversion-events.js',
  'utf8',
);
const functionsPhp = fs.readFileSync('wp-content/themes/nuvanx-medical/functions.php', 'utf8');
const governance = fs.readFileSync(
  'wp-content/themes/nuvanx-medical/inc/nvx-native-style-governance.php',
  'utf8',
);
const integrations = fs.readFileSync(
  'wp-content/themes/nuvanx-medical/inc/nvx-integrations.php',
  'utf8',
);
const components = fs.readFileSync(
  'wp-content/themes/nuvanx-medical/assets/css/nvx-components.css',
  'utf8',
);

assert.match(
  functionsPhp,
  /display=swap/,
  'Google Fonts request must keep font-display=swap',
);
assert.match(
  functionsPhp,
  /consolidates the complete local\s+\/\/ theme stack into one inline critical bundle/,
  'theme stylesheet registration must document the public inline delivery',
);
assert.match(
  governance,
  /function nvx_theme_critical_stylesheet_files/,
  'the critical bundle must have a route-aware source manifest',
);
assert.match(
  governance,
  /assets\/css\/nvx-site-layout\.css/,
  'layout CSS must be part of the inline bundle',
);
assert.match(
  governance,
  /assets\/css\/nvx-components\.css/,
  'component CSS must be part of the inline bundle',
);
assert.match(
  governance,
  /assets\/css\/nvx-patterns-editorial\.css/,
  'interior hero CSS must be reserved before deferred assets',
);
assert.match(
  governance,
  /assets\/css\/nvx-accessibility-governance\.css/,
  'accessibility CSS must be part of the inline bundle',
);
assert.match(
  governance,
  /assets\/css\/nvx-home-v3\.css/,
  'home CSS must be included only on the front page',
);
assert.match(
  governance,
  /assets\/css\/nvx-posts\.css/,
  'blog CSS must be included on editorial routes',
);
assert.match(
  governance,
  /assets\/css\/nvx-soluciones-medicas\.css/,
  'solutions CSS must be included on its route',
);
assert.match(
  governance,
  /assets\/css\/nvx-cases-holding\.css/,
  'patient-cases CSS must be included on its route',
);
assert.match(
  governance,
  /function nvx_theme_inline_critical_style_foundation/,
  'the stylesheet bundle must be emitted inline',
);
assert.match(
  governance,
  /function nvx_theme_dequeue_late_local_styles/,
  'late template styles must not recreate local stylesheet links',
);
assert.match(
  governance,
  /function nvx_theme_nonblocking_google_fonts/,
  'Google Fonts stylesheet must not block first paint',
);
assert.match(
  integrations,
  /function nvx_theme_is_klaviyo_asset/,
  'Klaviyo assets must be identified on all public routes',
);
assert.match(
  integrations,
  /function nvx_dequeue_public_klaviyo_onsite/,
  'Klaviyo Onsite must be removed globally from the public frontend',
);
assert.match(
  integrations,
  /function nvx_theme_defer_auxiliary_script_tags/,
  'Complianz and Joinchat scripts must be deferred',
);
assert.match(
  integrations,
  /function nvx_theme_defer_auxiliary_style_tags/,
  'Complianz and Joinchat styles must be non-blocking',
);
assert.match(
  components,
  /\.nvx-brand-microcopy--dark/,
  'dark hero microcopy must meet the requested AA contrast token',
);
assert.match(
  governance,
  /home-hero geometry reservation/,
  'front-page critical CSS must reserve home hero geometry',
);
assert.match(
  governance,
  /interior-hero first paint/,
  'interior brand heroes must reserve the dark stage in the inline bundle',
);
assert.match(
  integrations,
  /klaviyojs/,
  'the official plugin handle klaviyojs must be dequeued',
);

assert.match(
  conversionEvents,
  /AW-18236597403\/qut3CLWflOAcEJvJ8fdD/,
  'phone/WhatsApp clicks must send the official Ads click conversion',
);
assert.match(
  conversionEvents,
  /joinchat/,
  'Joinchat widget clicks must count as WhatsApp conversions',
);

console.log('LCP_CSS_DELIVERY=PASS');
