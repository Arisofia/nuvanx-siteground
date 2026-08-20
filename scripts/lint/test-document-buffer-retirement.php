<?php
/**
 * Blocking gate for the public document-buffer retirement.
 */

declare(strict_types=1);

$root        = dirname(__DIR__, 2);
$governance  = $root . '/wp-content/themes/nuvanx-medical/inc/nvx-document-buffer-governance.php';
$environment = $root . '/wp-content/themes/nuvanx-medical/inc/nvx-environment-flags.php';

if (!is_file($governance) || !is_file($environment)) {
    fwrite(STDERR, "DOCUMENT_BUFFER_RETIREMENT=FAIL reason=missing_runtime\n");
    exit(1);
}

$governanceSource = file_get_contents($governance);
$environmentSource = file_get_contents($environment);
if (!is_string($governanceSource) || !is_string($environmentSource)) {
    fwrite(STDERR, "DOCUMENT_BUFFER_RETIREMENT=FAIL reason=unreadable_source\n");
    exit(1);
}

$requiredGovernanceFragments = [
    '$hook->callbacks[999999]',
    "__DIR__ . '/nvx-integrations.php'",
    'new ReflectionFunction',
    '$hook->remove_filter( \'template_redirect\', $callback, 999999 );',
    "add_action( 'wp_loaded', 'nvx_retire_legacy_document_buffer', -999999 );",
];

$failures = [];
foreach ($requiredGovernanceFragments as $fragment) {
    if (!str_contains($governanceSource, $fragment)) {
        $failures[] = 'missing_fragment=' . $fragment;
    }
}

if (!str_contains($environmentSource, "require_once __DIR__ . '/nvx-document-buffer-governance.php';")) {
    $failures[] = 'governance_not_loaded';
}

if (str_contains($governanceSource, "remove_all_actions( 'template_redirect'")) {
    $failures[] = 'broad_template_redirect_removal_forbidden';
}
if (str_contains($governanceSource, 'remove_all_filters')) {
    $failures[] = 'broad_filter_removal_forbidden';
}
if (str_contains($governanceSource, 'ob_end_clean') || str_contains($governanceSource, 'ob_clean')) {
    $failures[] = 'foreign_output_buffer_cleanup_forbidden';
}

if ([] !== $failures) {
    fwrite(STDERR, "DOCUMENT_BUFFER_RETIREMENT=FAIL\n" . implode("\n", $failures) . "\n");
    exit(1);
}

echo "DOCUMENT_BUFFER_RETIREMENT=PASS source_scoped=1 priority_scoped=1 fail_closed=1 broad_cleanup=0\n";
