<?php
declare(strict_types=1);

$path = dirname(__DIR__, 2) . '/docs/operations/global-document-governance.md';
if (!is_readable($path)) {
    fwrite(STDERR, "Missing global document governance operations guide.\n");
    exit(1);
}

$content = (string) file_get_contents($path);
foreach (array(
    'Required document invariants',
    'Integration invariants',
    'Accessibility invariants',
    'Media invariants',
    'Staging2 Rendered Acceptance',
) as $required) {
    if (!str_contains($content, $required)) {
        fwrite(STDERR, "Governance guide missing section: {$required}\n");
        exit(1);
    }
}

echo "Global governance documentation contract passed.\n";
