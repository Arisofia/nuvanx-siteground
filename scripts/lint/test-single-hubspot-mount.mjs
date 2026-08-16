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
const runtimeConfig = fs.readFileSync(
  'wp-content/themes/nuvanx-medical/inc/nvx-document-governance.php',
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
assert.match(
  runtimeConfig,
  /'hubspotPortalId'\s*=>\s*\(string\) \$hubspot_config\['portal_id'\]/,
  'Runtime configuration must include a portal ID when output buffering is bypassed',
);
assert.match(
  runtimeConfig,
  /'hubspotFormId'\s*=>\s*\(string\) \$hubspot_config\['form_id'\]/,
  'Runtime configuration must include a form ID when output buffering is bypassed',
);

// Dedicated conversion route must recover from an optimizer dropping/delaying
// nvx-runtime-governance without creating a second form or portal loader.
assert.match(
  managedPage,
  /function nvx_valoracion_hubspot_bootstrap_markup\(\): string/,
  'Managed valoración page must retain its deterministic HubSpot recovery bootstrap',
);
assert.match(
  managedPage,
  /frames\[i\]\.remove\(\)/,
  'Valoration bootstrap must remove extra HubSpot frames inside the canonical host',
);
assert.match(
  managedPage,
  /#nvx-hubspot-forms-runtime,script\[data-nvx-hubspot-canonical=/,
  'Valoration bootstrap must reuse an existing canonical loader before injecting one',
);
assert.match(
  managedPage,
  /script\.dataset\.nvxHubspotCanonical="1"/,
  'Valoration bootstrap fallback loader must identify itself as the canonical recovery owner',
);
assert.doesNotMatch(
  managedPage,
  /https:\/\/js-eu1\.hsforms\.net\/forms\/embed\/147416356\.js/,
  'Managed PHP must not expose a literal eager hsforms URL that consent/optimizer scanners can rewrite',
);

console.log('HUBSPOT_SINGLE_MOUNT_STATIC=PASS hosts=1 declarative_mounts=1 imperative_creates=0 runtime_identity_fallback=1 managed_recovery_bootstrap=1');
