<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$deployPath = $root . '/.github/workflows/deploy-staging2.yml';
$manualPath = $root . '/.github/workflows/staging2-rendered-acceptance.yml';
$verifierPath = $root . '/scripts/staging2/verify-rendered-document.mjs';
$deployScriptPath = $root . '/tools/deploy/deploy-to-staging2.sh';

foreach (array($deployPath, $manualPath, $verifierPath, $deployScriptPath) as $path) {
    if (!is_readable($path)) {
        fwrite(STDERR, 'Missing rendered acceptance contract file: ' . $path . "\n");
        exit(1);
    }
}

$deploy = (string) file_get_contents($deployPath);
$manual = (string) file_get_contents($manualPath);
$verifier = (string) file_get_contents($verifierPath);
$deployScript = (string) file_get_contents($deployScriptPath);

function nvx_staging2_acceptance_assert(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

nvx_staging2_acceptance_assert(
    str_contains($deploy, "github.event_name == 'workflow_dispatch'")
        && str_contains($deploy, "inputs.confirmation == 'DEPLOY_STAGING2'")
        && str_contains($deploy, 'Run complete rendered acceptance for deployed SHA')
        && str_contains($deploy, 'EXPECTED_SHA: ${{ env.DEPLOY_SHA }}')
        && str_contains($deploy, 'node scripts/staging2/verify-rendered-document.mjs'),
    'The real deployment job must run rendered acceptance against its exact immutable DEPLOY_SHA.'
);

nvx_staging2_acceptance_assert(
    !str_contains($manual, 'workflow_run:')
        && str_contains($manual, 'workflow_dispatch:')
        && str_contains($manual, 'required: true')
        && str_contains($manual, 'EXPECTED_SHA: ${{ inputs.expected_sha }}')
        && !str_contains($manual, "github.event.inputs.expected_sha || ''")
        && str_contains($manual, 'node scripts/staging2/verify-rendered-document.mjs'),
    'Independent rendered acceptance must be manual and require one explicit full SHA.'
);

foreach (array(
    "const expectedSha = (process.env.EXPECTED_SHA || '').trim();",
    'EXPECTED_SHA must be a full lowercase 40-character SHA.',
    'AbortSignal.timeout(15000)',
    'request failed after ${attempts} attempts',
    'canonicalStylesheetHandles',
    'hsforms\\.net|hsforms\\.com|hs-scripts\\.com',
    'Rendered acceptance failed:',
    'deployed SHA ${shaMatch[1]} does not match ${expectedSha}',
    'Routes serve different deployment SHAs',
    "'/contacto/'",
    "'/soluciones-medicas/'",
    "'/madrid/valoracion/'",
) as $required) {
    nvx_staging2_acceptance_assert(
        str_contains($verifier, $required),
        'Rendered acceptance verifier missing invariant: ' . $required
    );
}

$purgeMarker = "echo '== Purge staging2 caches =='";
$purgePosition = strpos($deployScript, $purgeMarker);
$successPosition = strpos($deployScript, 'DEPLOY_STAGING2_OK');
nvx_staging2_acceptance_assert(
    false !== $purgePosition
        && false !== $successPosition
        && $purgePosition < $successPosition,
    'Mandatory cache purge must execute before deployment success.'
);
$purgePosition = (int) $purgePosition;
$successPosition = (int) $successPosition;

$purgeBlock = substr($deployScript, $purgePosition, $successPosition - $purgePosition);
nvx_staging2_acceptance_assert(
    str_contains($purgeBlock, 'wp cache flush')
        && str_contains($purgeBlock, 'wp sg purge')
        && str_contains($purgeBlock, 'opcache_reset')
        && !str_contains($purgeBlock, '|| true'),
    'Cache and opcode purge failures must reject the deployment.'
);

foreach (array(
    'assets/css/nvx-accessibility-governance.css',
    'assets/js/nvx-runtime-governance.js',
    'inc/nvx-document-governance.php',
) as $requiredAsset) {
    nvx_staging2_acceptance_assert(
        str_contains($deployScript, $requiredAsset),
        'Deployment source and destination contract missing governance asset: ' . $requiredAsset
    );
}

nvx_staging2_acceptance_assert(
    !str_contains($deploy, 'Smoke verification executed via contract verification'),
    'A placeholder smoke echo must never substitute rendered acceptance.'
);

echo "Staging2 exact-SHA rendered acceptance contract passed.\n";
