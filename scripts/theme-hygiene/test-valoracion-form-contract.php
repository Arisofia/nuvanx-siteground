<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$theme = $root . '/wp-content/themes/nuvanx-medical';

function nvx_valoracion_contract_assert(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

$managedPath = $theme . '/inc/nvx-valoracion-managed-page.php';
$pageShellPath = $theme . '/template-parts/content/nvx-page-shell.php';
$functionsPath = $theme . '/functions.php';
$valoracionMuPath = $root . '/wp-content/mu-plugins/nuvanx-valoracion-native-hubspot-form.php';
$contactoMuPath = $root . '/wp-content/mu-plugins/nuvanx-contacto-hubspot-form.php';
$deployPath = $root . '/tools/deploy/deploy-required-mu-plugins.sh';

foreach (array($managedPath, $pageShellPath, $functionsPath, $valoracionMuPath, $contactoMuPath, $deployPath) as $path) {
    nvx_valoracion_contract_assert(is_readable($path), 'Missing valuation/form release file: ' . $path);
}

$managed = (string) file_get_contents($managedPath);
$pageShell = (string) file_get_contents($pageShellPath);
$functions = (string) file_get_contents($functionsPath);
$valoracionMu = (string) file_get_contents($valoracionMuPath);
$deploy = (string) file_get_contents($deployPath);

nvx_valoracion_contract_assert(
    str_contains($managed, 'id="nvx-valoracion-main"')
        && str_contains($managed, 'id="nvx-hubspot-form"')
        && str_contains($managed, 'id="nvx-hubspot-native-form"')
        && str_contains($managed, '5042522a-0bc5-4381-ac3e-5aee8649b69c')
        && str_contains($managed, '147416356')
        && str_contains($managed, "add_filter( 'the_content', 'nvx_render_managed_valoracion_page', 10 )"),
    'Managed valuation page is missing its canonical hierarchy, mount or filter.'
);

nvx_valoracion_contract_assert(
    1 === substr_count($functions, "require_once get_template_directory() . '/inc/nvx-solutions-page.php';"),
    'Solutions module must be required exactly once.'
);
nvx_valoracion_contract_assert(
    1 === substr_count($functions, "require_once get_template_directory() . '/inc/nvx-valoracion-managed-page.php';"),
    'Managed valuation module must be required exactly once.'
);
nvx_valoracion_contract_assert(
    1 === substr_count($pageShell, 'nvx_content_is_solutions_page( $content )'),
    'Page shell must contain one solutions managed-hierarchy check.'
);
nvx_valoracion_contract_assert(
    1 === substr_count($pageShell, 'nvx_is_valoracion_page_request()'),
    'Page shell must contain one valuation managed-hierarchy check.'
);

nvx_valoracion_contract_assert(
    str_contains($valoracionMu, "is_page( 'valoracion' )")
        && str_contains($valoracionMu, 'nvx-hubspot-native-form')
        && str_contains($valoracionMu, 'hs-form-frame'),
    'Valoración MU plugin no longer enforces the canonical HubSpot mount.'
);

foreach (array(
    'nuvanx-valoracion-native-hubspot-form.php',
    'nuvanx-contacto-hubspot-form.php',
    "EXPECTED_ROOT='/home/customer/www/staging2.nuvanx.com/public_html'",
    "EXPECTED_ROOT='/home/customer/www/nuvanx.com/public_html'",
    'ROLLBACK_MU_COMPLETE',
    'cmp -s',
) as $needle) {
    nvx_valoracion_contract_assert(str_contains($deploy, $needle), 'MU deployment contract missing: ' . $needle);
}
nvx_valoracion_contract_assert(
    !str_contains($deploy, 'rsync -a --delete'),
    'MU deployment must never delete the full MU plugin directory.'
);

echo "Valoración form and MU deployment contract passed.\n";
