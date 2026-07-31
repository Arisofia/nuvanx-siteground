<?php
declare(strict_types=1);

$dataDir = dirname(__DIR__, 2) . '/wp-content/themes/nuvanx-medical/inc/data';
$expectations = array(
    'aesthetic-medicine-page.json' => array('pillars', 'treatments', 'faqs'),
    'aesthetic-treatment-pages.json' => array('lips_ha', 'rhinomodeling_ha', 'tear_trough_ha'),
    'btl-detail-pages.json' => array('exion-face', 'exion-body', 'exion-fractional', 'emfusion'),
    'home-faq-v2.json' => array(0),
    'laser-medicine-page.json' => array('pillars', 'platforms', 'faqs'),
    'nvx-soluciones-medicas-groups.json' => array(0),
    'seo-blog-post-metadata.json' => array('endolift-primeras-72-horas-que-esperar'),
    'seo-metadata.json' => array('home', 'tratamientos', 'clinicas'),
    'treatment-hub-schema.json' => array(0),
    'treatments-catalog.json' => array(0),
);

foreach ($expectations as $filename => $requiredKeys) {
    $path = $dataDir . '/' . $filename;
    if (!is_readable($path)) {
        fwrite(STDERR, "Missing catalog: {$filename}\n");
        exit(1);
    }

    $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($decoded) || $decoded === array()) {
        fwrite(STDERR, "Empty catalog: {$filename}\n");
        exit(1);
    }

    foreach ($requiredKeys as $key) {
        if (!array_key_exists($key, $decoded)) {
            fwrite(STDERR, "Missing key {$key} in {$filename}\n");
            exit(1);
        }
    }
}

$solutionsPath = $dataDir . '/nvx-soluciones-medicas-groups.json';
$solutionGroups = json_decode(
    (string) file_get_contents($solutionsPath),
    true,
    512,
    JSON_THROW_ON_ERROR
);
$expectedGroupIds = array('rostro-cuello', 'piel-superficie', 'contorno-corporal', 'planes-especificos');
$actualGroupIds = array();
$totalSolutions = 0;
$requiredGroupFields = array('id', 'index', 'eyebrow', 'title', 'intro', 'surface', 'solutions');
$requiredSolutionFields = array('protocol', 'title', 'question', 'limit', 'path');

if (4 !== count($solutionGroups)) {
    fwrite(STDERR, "Solutions catalog must contain exactly four groups.\n");
    exit(1);
}

foreach ($solutionGroups as $groupIndex => $group) {
    if (!is_array($group)) {
        fwrite(STDERR, "Invalid solutions group at index {$groupIndex}.\n");
        exit(1);
    }
    foreach ($requiredGroupFields as $field) {
        if (!array_key_exists($field, $group)) {
            fwrite(STDERR, "Missing group field {$field} at index {$groupIndex}.\n");
            exit(1);
        }
    }
    if (!in_array($group['surface'], array('light', 'soft', 'dark', 'base'), true)) {
        fwrite(STDERR, "Invalid solutions surface for {$group['id']}.\n");
        exit(1);
    }
    if (!is_array($group['solutions']) || array() === $group['solutions']) {
        fwrite(STDERR, "Solutions group {$group['id']} has no cards.\n");
        exit(1);
    }

    $actualGroupIds[] = $group['id'];
    foreach ($group['solutions'] as $solutionIndex => $solution) {
        if (!is_array($solution)) {
            fwrite(STDERR, "Invalid solution {$solutionIndex} in {$group['id']}.\n");
            exit(1);
        }
        foreach ($requiredSolutionFields as $field) {
            if (!array_key_exists($field, $solution) || '' === trim((string) $solution[$field])) {
                fwrite(STDERR, "Missing solution field {$field} in {$group['id']}.\n");
                exit(1);
            }
        }
        $path = (string) $solution['path'];
        if (!str_starts_with($path, '/') || !str_ends_with($path, '/')) {
            fwrite(STDERR, "Non-canonical solution path {$path}.\n");
            exit(1);
        }
        ++$totalSolutions;
    }
}

if ($expectedGroupIds !== $actualGroupIds) {
    fwrite(STDERR, "Solutions group order or identifiers changed.\n");
    exit(1);
}
if (14 !== $totalSolutions) {
    fwrite(STDERR, "Solutions catalog must contain exactly fourteen cards.\n");
    exit(1);
}

$themeRoot = dirname(__DIR__, 2) . '/wp-content/themes/nuvanx-medical';
$solutionsModule = (string) file_get_contents($themeRoot . '/inc/nvx-solutions-page.php');
$solutionsTemplate = (string) file_get_contents(
    $themeRoot . '/template-parts/content/nvx-soluciones-medicas-github.php'
);
if (!str_contains($solutionsModule, 'return nvx_catalog_json_load( $filename );')) {
    fwrite(STDERR, "Solutions module is not delegated to the canonical JSON loader.\n");
    exit(1);
}
if (!str_contains($solutionsTemplate, "nvx_theme_load_json_catalog( 'nvx-soluciones-medicas-groups.json' )")) {
    fwrite(STDERR, "Solutions template is not bound to the versioned group catalog.\n");
    exit(1);
}

echo "Catalog JSON contract passed.\n";
