import fs from 'node:fs';

const bridgePath = 'wp-content/themes/nuvanx-medical/inc/nvx-hubspot-secure-attribution.php';
const mode = fs.existsSync(bridgePath) ? 'v2' : 'legacy';

console.log(`ATTRIBUTION_GATE_MIGRATION mode=${mode}`);
await import(mode === 'v2' ? './test-attribution-contract-v2.mjs' : './test-attribution-contract-legacy.mjs');
