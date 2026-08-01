<?php
declare(strict_types=1);

$path = dirname(__DIR__, 2) . '/docs/operations/global-document-governance-checklist.md';
if (!is_readable($path)) {
    fwrite(STDERR, "Missing global governance release checklist.\n");
    exit(1);
}

$content = (string) file_get_contents($path);
foreach (array(
    'Before merge',
    'Staging2 deployment',
    'Rendered acceptance',
    'Browser validation',
    'Production promotion is prohibited',
) as $required) {
    if (!str_contains($content, $required)) {
        fwrite(STDERR, "Release checklist missing requirement: {$required}\n");
        exit(1);
    }
}

echo "Global governance release checklist passed.\n";
