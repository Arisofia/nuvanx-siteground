<?php
declare(strict_types=1);

define('ABSPATH', __DIR__);

function __($text, $domain = null) { return $text; }
function is_admin(): bool { return false; }
function is_page($page = null): bool {
    return $page === 2906 && ($GLOBALS['nvx_test_page_id'] ?? 0) === 2906;
}
function get_queried_object_id(): int { return (int) ($GLOBALS['nvx_test_page_id'] ?? 0); }
function remove_filter(...$args): bool { return true; }
function add_filter(...$args): bool { return true; }

require dirname(__DIR__, 2) . '/wp-content/themes/nuvanx-medical/inc/nvx-p0-publication-guard.php';

$GLOBALS['nvx_test_page_id'] = 2906;
if (!nvx_p0_is_exion_page()) {
    fwrite(STDERR, "EXION page scope failed.\n");
    exit(1);
}

$input = <<<'HTML'
<section>
  <p>EXION facial desde 300 €.</p>
  <p>Plan corporal 1.200,50 € según zona.</p>
  <p>Otra tarifa 450 EUR.</p>
  <p>Importe con espacio 1&nbsp;200 €.</p>
  <details><summary>¿EXION o Morpheus8?</summary><p>Comparativa no aprobada.</p></details>
  <details><summary>¿Cuántas sesiones?</summary><p>Según valoración médica.</p></details>
  <script>window.examplePrice = "300 €";</script>
</section>
HTML;

$output = nvx_p0_sanitize_exion_content($input);

$required = [
    'Presupuesto tras valoración médica',
    '¿Cuántas sesiones?',
    'window.examplePrice',
];
foreach ($required as $fragment) {
    if (strpos($output, $fragment) === false) {
        fwrite(STDERR, "Missing expected fragment: {$fragment}\n");
        exit(1);
    }
}

foreach (['300 €.</p>', '1.200,50 €', '450 EUR', '1 200 €', 'Morpheus8', 'window.examplePrice = "Presupuesto tras valoración médica"'] as $forbidden) {
    if (strpos($output, $forbidden) !== false) {
        fwrite(STDERR, "Forbidden EXION fragment remains: {$forbidden}\n");
        exit(1);
    }
}

if (substr_count($output, 'Presupuesto tras valoración médica') < 4) {
    fwrite(STDERR, "Not all EXION price formats were replaced.\n");
    exit(1);
}

echo "PASS: scoped EXION DOM publication guard\n";
