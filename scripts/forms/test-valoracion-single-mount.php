<?php
declare(strict_types=1);

define('ABSPATH', __DIR__);

function esc_attr($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_url($value) { return (string) $value; }
function home_url($path = '/') { return 'https://staging2.nuvanx.com' . $path; }
function is_page($page = null): bool { return false; }
function add_action(...$args): bool { return true; }

require dirname(__DIR__, 2) . '/wp-content/mu-plugins/nuvanx-valoracion-native-hubspot-form.php';

$input = <<<'HTML'
<!doctype html><html><body>
<script src="https://js-eu1.hsforms.net/forms/embed/147416356.js"></script>
<div class="hs-form-frame" data-form-id="duplicate"></div>
<div id="nvx-hubspot-native-form" class="nvx-form-stage">
  <div><div class="hbspt-form"><p>legacy</p></div></div>
</div>
<div id="nvx-hubspot-native-form"><div class="hs-form-frame" data-form-id="duplicate-two"></div></div>
<iframe src="https://forms-eu1.hsforms.com/duplicate"></iframe>
</body></html>
HTML;

$output = nvx_valoracion_native_hubspot_enforce_single_mount($input);

$expectations = [
    'id="nvx-hubspot-native-form"' => 1,
    'class="hs-form-frame"' => 1,
    'js-eu1.hsforms.net/forms/embed/147416356.js' => 1,
    '5042522a-0bc5-4381-ac3e-5aee8649b69c' => 1,
    'nvx-hubspot-privacy' => 1,
];

foreach ($expectations as $fragment => $count) {
    $actual = substr_count($output, $fragment);
    if ($actual !== $count) {
        fwrite(STDERR, "{$fragment}: expected {$count}, found {$actual}\n");
        exit(1);
    }
}

foreach (['duplicate-two', '>legacy<', '<iframe'] as $forbidden) {
    if (strpos($output, $forbidden) !== false) {
        fwrite(STDERR, "Forbidden duplicate fragment remains: {$forbidden}\n");
        exit(1);
    }
}

echo "PASS: valoración single-mount normalizer\n";
