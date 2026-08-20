import fs from 'node:fs';

const bridgePath = 'wp-content/themes/nuvanx-medical/inc/nvx-hubspot-secure-attribution.php';
const integrationPath = 'wp-content/themes/nuvanx-medical/inc/nvx-attribution-integration.php';
const bridge = fs.existsSync(bridgePath) ? fs.readFileSync(bridgePath, 'utf8') : '';
const mode = !bridge
  ? 'legacy'
  : /Runtime Contract v3\./.test(bridge)
    ? 'v3'
    : 'v2';

console.log(`ATTRIBUTION_GATE_MIGRATION mode=${mode}`);
if (mode === 'legacy') {
  await import('./test-attribution-contract-legacy.mjs');
} else if (mode === 'v3') {
  await import('./test-attribution-contract-v3.mjs');
} else {
  await import('./test-attribution-contract-v2.mjs');
}

if (mode !== 'legacy' && fs.existsSync(integrationPath)) {
  await import('./test-attribution-integration-wiring.mjs');
}
if (mode === 'v2') {
  await import('./test-hubspot-v4-hidden-lineage.mjs');
  await import('./test-lead-captured-server-relay.mjs');
}
if (mode === 'v3') {
  await import('./test-hubspot-v4-live-schema-v3.mjs');
  await import('./test-lead-captured-server-relay.mjs');
}
