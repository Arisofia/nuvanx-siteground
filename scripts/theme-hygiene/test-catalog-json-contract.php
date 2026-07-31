<?php
declare(strict_types=1);

$dataDir = dirname(__DIR__, 2) . '/wp-content/themes/nuvanx-medical/inc/data';
$expectations = array(
    'aesthetic-treatment-pages.json' => array('lips_ha', 'rhinomodeling_ha', 'tear_trough_ha'),
    'btl-detail-pages.json' => array('exion-face', 'exion-body', 'exion-fractional', 'emfusion'),
    'faq-catalog.json' => array(0),
    'home-faq-v2.json' => array(0),
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

echo "Catalog JSON contract passed.\n";
