import assert from 'node:assert/strict';
import fs from 'node:fs';

const managedPage = fs.readFileSync(
  'wp-content/themes/nuvanx-medical/inc/nvx-valoracion-managed-page.php',
  'utf8',
);
const mountGovernance = fs.readFileSync(
  'wp-content/themes/nuvanx-medical/inc/nvx-hero-and-forms.php',
  'utf8',
);
const runtime = fs.readFileSync(
  'wp-content/themes/nuvanx-medical/assets/js/nvx-runtime-governance.js',
  'utf8',
);

const managedHost = managedPage.match(/<div id="nvx-hubspot-native-form"[^>]*>/)?.[0] || '';
assert.ok(managedHost, 'Managed valoración page must render the canonical HubSpot host');
assert.doesNotMatch(
  managedHost,
  /data-(?:form|portal)-id=/,
  'Presentation host must not repeat the HubSpot identity owned by its canonical child mount',
);

const declarativeMounts = mountGovernance.match(/class="hs-form-frame"/g) || [];
assert.equal(declarativeMounts.length, 1, 'Mount governance must define exactly one declarative HubSpot frame');
assert.match(runtime, /\/forms\/embed\/' \+ portalId \+ '\.js'/, 'Runtime must load the declarative portal embed');
assert.doesNotMatch(runtime, /window\.hbspt\.forms\.create\s*\(/, 'Runtime must not imperatively duplicate declarative mounts');
assert.match(
  runtime,
  /removeLegacyHubSpotV2Scripts/,
  'Runtime must actively remove legacy HubSpot v2 loaders before declarative embed initialization',
);
assert.doesNotMatch(
  runtime,
  /script\[src\*="forms\/embed\/"\], script\[src\*="forms\/v2\.js"\]/,
  'Runtime must not treat legacy forms/v2.js as a valid loaded declarative embed',
);
assert.match(
  runtime,
  /normalizeNativeHubSpotMounts/,
  'Runtime must normalize stale HubSpot hosts before loading the embed',
);
assert.match(
  mountGovernance,
  /nvx_valoracion_sanitize_hubspot_host_opening/,
  'Mount governance must sanitize stale HubSpot identity attributes from the presentation host',
);

console.log('HUBSPOT_SINGLE_MOUNT_STATIC=PASS hosts=1 declarative_mounts=1 imperative_creates=0');
