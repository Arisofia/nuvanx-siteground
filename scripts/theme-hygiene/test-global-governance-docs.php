<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$documents = array(
    array(
        'path' => $root . '/docs/operations/global-document-governance.md',
        'missing' => 'Missing global document governance operations guide.',
        'errorPrefix' => 'Governance guide missing section: ',
        'required' => array(
            'Required document invariants',
            'Integration invariants',
            'Accessibility invariants',
            'Media invariants',
            'Staging2 Rendered Acceptance',
        ),
        'success' => 'Global governance documentation contract passed.',
    ),
    array(
        'path' => $root . '/docs/operations/global-document-governance-checklist.md',
        'missing' => 'Missing global governance release checklist.',
        'errorPrefix' => 'Release checklist missing requirement: ',
        'required' => array(
            'Before merge',
            'Staging2 deployment',
            'Rendered acceptance',
            'Browser validation',
            'Production promotion is prohibited',
        ),
        'success' => 'Global governance release checklist passed.',
    ),
);

foreach ($documents as $document) {
    if (!is_readable($document['path'])) {
        fwrite(STDERR, $document['missing'] . "\n");
        exit(1);
    }

    $content = (string) file_get_contents($document['path']);
    foreach ($document['required'] as $required) {
        if (!str_contains($content, $required)) {
            fwrite(STDERR, $document['errorPrefix'] . $required . "\n");
            exit(1);
        }
    }

    echo $document['success'] . "\n";
}
