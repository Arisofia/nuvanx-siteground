<?php
declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
define('NVX_THEME_VERSION', 'test');

function add_action(...$args): bool { return true; }
function is_admin(): bool { return false; }
function wp_doing_ajax(): bool { return false; }
function is_feed(): bool { return false; }
function is_404(): bool { return false; }
function is_front_page(): bool { return false; }
function is_singular(): bool { return true; }
function get_queried_object_id(): int { return 42; }
function get_the_title($post = 0): string { return 'Contacto'; }
function get_the_excerpt($post = 0): string { return ''; }
function get_bloginfo(string $show = ''): string { return 'NUVANX'; }
function wp_get_document_title(): string { return ''; }
function home_url(string $path = ''): string { return 'https://staging2.nuvanx.com' . $path; }
function get_permalink($post = 0): string { return 'https://staging2.nuvanx.com/contacto/'; }
function wp_parse_url(string $url, int $component = -1) { return parse_url($url, $component); }
function attachment_url_to_postid(string $url): int { return str_contains($url, 'evidence.webp') ? 99 : 0; }
function wp_get_attachment_metadata(int $id): array { return 99 === $id ? array('width' => 1672, 'height' => 941) : array(); }
function wp_strip_all_tags(string $text, bool $remove_breaks = false): string { return strip_tags($text); }
function esc_html(string $text): string { return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'); }
function esc_attr(string $text): string { return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'); }
function esc_url(string $text): string { return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'); }
function nvx_seo_current_metadata(string $field, string $fallback = ''): string {
    return 'title' === $field
        ? 'Contacto NUVANX Madrid'
        : ('description' === $field ? 'Contacto y clínicas NUVANX en Madrid.' : $fallback);
}
function nvx_seo_current_canonical_url(): string { return 'https://staging2.nuvanx.com/contacto/'; }

$root = dirname(__DIR__, 2);
$modulePath = $root . '/wp-content/themes/nuvanx-medical/inc/nvx-document-governance.php';
$integrationsPath = $root . '/wp-content/themes/nuvanx-medical/inc/nvx-integrations.php';
$headerPath = $root . '/wp-content/themes/nuvanx-medical/header.php';
$cssPath = $root . '/wp-content/themes/nuvanx-medical/assets/css/nvx-accessibility-governance.css';
$jsPath = $root . '/wp-content/themes/nuvanx-medical/assets/js/nvx-runtime-governance.js';

foreach (array($modulePath, $integrationsPath, $headerPath, $cssPath, $jsPath) as $path) {
    if (!is_readable($path)) {
        fwrite(STDERR, 'Missing global governance asset: ' . $path . "\n");
        exit(1);
    }
}

require_once $modulePath;

function nvx_document_contract_assert(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

$module = (string) file_get_contents($modulePath);
$integrations = (string) file_get_contents($integrationsPath);
$header = (string) file_get_contents($headerPath);
$css = (string) file_get_contents($cssPath);
$js = (string) file_get_contents($jsPath);

nvx_document_contract_assert(
    1 === substr_count($header, "require_once __DIR__ . '/inc/nvx-document-governance.php';")
        && 1 === substr_count($header, 'nvx_document_governance_start();')
        && strpos($header, 'nvx_document_governance_start();') < strpos($header, '<!doctype html>'),
    'The global document contract must start exactly once before the doctype.'
);
nvx_document_contract_assert(str_contains($header, 'aria-hidden="true" inert'), 'The closed mobile navigation must be inert in the server-rendered DOM.');
nvx_document_contract_assert(
    str_contains($module, "wp_dequeue_script( 'nvx-hubspot-forms-embed' )")
        && str_contains($module, "wp_deregister_script( 'nvx-hubspot-forms-embed' )")
        && str_contains($module, "wp_add_inline_script(")
        && str_contains($module, 'nvx_document_governance_add_image_dimensions')
        && str_contains($module, 'nvx_document_governance_normalize_head'),
    'The module must own lazy integrations, image dimensions and final metadata.'
);
nvx_document_contract_assert(
    str_contains($integrations, 'nvx_document_governance_remove_retired_scripts( $html )')
        && !str_contains($integrations, '[\\s\\S]*?FacebookSignal[\\s\\S]*?')
        && !str_contains($integrations, 'window.FacebookSignal=window.FacebookSignal')
        && !str_contains($integrations, 'forms-eu1.hsforms.com')
        && !str_contains($integrations, 'js-eu1.hsforms.net'),
    'Legacy integrations must delegate safe cleanup and must not reintroduce cross-script deletion or eager HubSpot connections.'
);
nvx_document_contract_assert(
    str_contains($css, '--nvx-touch-target-min')
        && str_contains($css, '@media (max-width: 72rem)')
        && str_contains($css, '.nvx-header .nvx-nav')
        && str_contains($css, '.nvx-header .nvx-hamburger'),
    'The accessibility stylesheet must enforce touch targets and one responsive nav mode.'
);
nvx_document_contract_assert(
    str_contains($js, 'MutationObserver')
        && str_contains($js, "event.key === 'Escape'")
        && str_contains($js, 'hubspotScriptUrl')
        && str_contains($js, "document.createElement('script')"),
    'The runtime must govern focus and demand-load HubSpot.'
);

$input = '<!doctype html><html lang="es"><head>'
    . '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
    . '<style id="critical">body{display:block}</style>'
    . '<script id="sitekit">window._googlesitekitConsentCategoryMap={statistics:["analytics_storage"]};window._googlesitekitConsents={};</script>'
    . '<script>window.FacebookSignal=window.FacebookSignal||function(){};</script>'
    . '</head><body><main><h1>Contacto</h1><p>Información de contacto y sedes médicas.</p>'
    . '<img src="https://staging2.nuvanx.com/wp-content/uploads/2026/07/evidence.webp" alt="Evidencia">'
    . '</main></body></html>';

$output = nvx_document_governance_normalize_document($input);
$output = nvx_document_governance_normalize_document($output);

nvx_document_contract_assert(1 === substr_count($output, '<title>Contacto NUVANX Madrid</title>'), 'Exactly one canonical title is required.');
nvx_document_contract_assert(1 === substr_count($output, 'name="description"'), 'Exactly one meta description is required.');
nvx_document_contract_assert(1 === substr_count($output, 'rel="canonical"'), 'Exactly one canonical link is required.');
nvx_document_contract_assert(1 === substr_count($output, 'name="nvx-document-contract"'), 'Exactly one document contract marker is required.');
nvx_document_contract_assert(str_contains($output, '_googlesitekitConsentCategoryMap'), 'Site Kit consent bootstrap must survive normalization.');
nvx_document_contract_assert(str_contains($output, '<style id="critical">'), 'Styles located before a retired script must survive normalization.');
nvx_document_contract_assert(!str_contains($output, 'FacebookSignal'), 'Retired FacebookSignal code must be removed without crossing script boundaries.');
nvx_document_contract_assert(str_contains($output, 'width="1672"') && str_contains($output, 'height="941"'), 'WordPress media dimensions must be restored globally.');

echo "Global document governance contract passed.\n";
