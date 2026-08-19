<?php
/**
 * Trusted migration gate for the public document-buffer retirement.
 *
 * Before the runtime lands this test is intentionally non-blocking. As soon as
 * the governed runtime file exists, every invariant becomes mandatory.
 */

declare(strict_types=1);

$root       = dirname(__DIR__, 2);
$governance = $root . '/wp-content/themes/nuvanx-medical/inc/nvx-document-buffer-governance.php';
$gtm        = $root . '/wp-content/themes/nuvanx-medical/inc/nvx-gtm-integration.php';

if (!is_file($governance)) {
    echo "DOCUMENT_BUFFER_RETIREMENT=MIGRATION_PENDING\n";
    exit(0);
}

$governanceSource = file_get_contents($governance);
$gtmSource        = file_get_contents($gtm);
if (!is_string($governanceSource) || !is_string($gtmSource)) {
    fwrite(STDERR, "DOCUMENT_BUFFER_RETIREMENT=FAIL reason=unreadable_source\n");
    exit(1);
}

$requiredGovernanceFragments = [
    '$hook->callbacks[999999]',
    "__DIR__ . '/nvx-integrations.php'",
    'new ReflectionFunction',
    "$hook->remove_filter( 'template_redirect', $callback, 999999 );",
];

$failures = [];
foreach ($requiredGovernanceFragments as $fragment) {
    if (!str_contains($governanceSource, $fragment)) {
        $failures[] = 'missing_fragment=' . $fragment;
    }
}

if (!str_contains($gtmSource, "require_once __DIR__ . '/nvx-document-buffer-governance.php';")) {
    $failures[] = 'governance_not_loaded_after_integrations';
}

if (str_contains($governanceSource, "remove_all_actions( 'template_redirect'")) {
    $failures[] = 'broad_template_redirect_removal_forbidden';
}
if (str_contains($governanceSource, 'ob_end_clean')) {
    $failures[] = 'foreign_output_buffer_cleanup_forbidden';
}

if ([] !== $failures) {
    fwrite(STDERR, "DOCUMENT_BUFFER_RETIREMENT=FAIL\n" . implode("\n", $failures) . "\n");
    exit(1);
}

echo "DOCUMENT_BUFFER_RETIREMENT=PASS source_scoped=1 priority_scoped=1 broad_cleanup=0\n";
