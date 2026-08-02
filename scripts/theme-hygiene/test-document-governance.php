<?php
declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
define('NVX_THEME_VERSION', 'test');
define('HOUR_IN_SECONDS', 3600);

$GLOBALS['nvx_test_cache'] = array();

// WordPress stubs keep signature compatibility for the module under test.
function add_action(...$args): bool {
    return true;
}
function is_admin(): bool { return false; }
function wp_doing_ajax(): bool { return false; }
function is_feed(): bool { return false; }
function is_404(): bool { return false; }
function is_front_page(): bool { return false; }
function is_singular(): bool { return true; }
function get_queried_object_id(): int { return 42; }
function get_the_title($post = 0): string {
    return 'Contacto' . substr((string) $post, 0, 0);
}
function get_the_excerpt($post = 0): string {
    return 'Breve' . substr((string) $post, 0, 0);
}
function get_bloginfo(string $show = ''): string {
    if ('description' === $show) {
        return 'Clínica NUVANX';
    }
    return 'NUVANX' . substr($show, 0, 0);
}
function wp_get_document_title(): string { return ''; }
function home_url(string $path = ''): string { return 'https://staging2.nuvanx.com' . $path; }
function get_permalink($post = 0): string {
    return 'https://staging2.nuvanx.com/contacto/' . substr((string) $post, 0, 0);
}
function wp_parse_url(string $url, int $component = -1) { return parse_url($url, $component); }
function wp_basename(string $path): string { return basename($path); }
function attachment_url_to_postid(string $url): int { return str_ends_with($url, '/evidence.webp') ? 99 : 0; }
function wp_get_attachment_metadata(int $id): array {
    if (99 !== $id) {
        return array();
    }

    return array(
        'width' => 1672,
        'height' => 941,
        'sizes' => array(
            'medium' => array(
                'file' => 'evidence-768x432.webp',
                'width' => 768,
                'height' => 432,
            ),
        ),
    );
}
function wp_cache_get(string $key, string $group = '') {
    $cacheKey = $group . ':' . $key;
    return array_key_exists($cacheKey, $GLOBALS['nvx_test_cache'])
        ? $GLOBALS['nvx_test_cache'][$cacheKey]
        : false;
}
function wp_cache_set(string $key, $value, string $group = '', int $expiration = 0): bool {
    $GLOBALS['nvx_test_cache'][$group . ':' . $key] = $value;
    return $expiration >= 0;
}
function wp_strip_all_tags(string $text, bool $remove_breaks = false): string {
    $stripped = strip_tags($text);
    return $remove_breaks ? (string) preg_replace('/\s+/', ' ', $stripped) : $stripped;
}
function esc_html(string $text): string { return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'); }
function esc_attr(string $text): string { return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'); }
function esc_url(string $text): string { return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'); }
function nvx_seo_current_metadata(string $field, string $fallback = ''): string {
    if ('title' === $field) {
        return 'Contacto NUVANX Madrid';
    }
    if ('description' === $field) {
        return 'Descripción corta.';
    }
    return $fallback;
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

preg_match('/<dialog\b[^>]*\bid="nvx-mobile-nav"[^>]*>/i', $header, $navTag);
nvx_document_contract_assert(
    isset($navTag[0])
        && str_contains($navTag[0], 'aria-hidden="true"')
        && 1 === preg_match('/\binert\b/i', $navTag[0])
        && !str_contains($navTag[0], 'role="dialog"'),
    'The closed mobile navigation must be a native dialog that is inert in the server-rendered DOM.'
);

nvx_document_contract_assert(
    str_contains($module, "wp_dequeue_script( 'nvx-hubspot-forms-embed' )")
        && str_contains($module, "wp_deregister_script( 'nvx-hubspot-forms-embed' )")
        && str_contains($module, 'nvx_document_governance_strip_eager_hubspot')
        && str_contains($module, 'PHP_OUTPUT_HANDLER_CLEANABLE | PHP_OUTPUT_HANDLER_REMOVABLE')
        && str_contains($module, 'nvx_document_governance_attachment_id')
        && str_contains($module, 'nvx_document_governance_attachment_dimensions')
        && str_contains($module, 'nvx_document_governance_normalize_head')
        && !str_contains($module, "FacebookSignal\\.[a-zA-Z0-9_$]+"),
    'The module must own complete-buffer metadata, cached dimensions and element-scoped integration cleanup.'
);
nvx_document_contract_assert(
    str_contains($integrations, 'nvx_document_governance_remove_retired_scripts( $html )')
        && str_contains($integrations, 'nvx_theme_is_eager_hubspot_embed')
        && str_contains($integrations, "return '';")
        && !str_contains($integrations, '[\\s\\S]*?FacebookSignal[\\s\\S]*?')
        && !str_contains($integrations, 'window.FacebookSignal=window.FacebookSignal')
        && !str_contains($integrations, 'forms-eu1.hsforms.com')
        && !str_contains($integrations, 'js-eu1.hsforms.net')
        && !str_contains($integrations, "str_replace( ' src=', ' defer src='"),
    'Legacy integrations must hard-block eager HubSpot tags and must not reintroduce cross-script deletion.'
);
nvx_document_contract_assert(
    str_contains($css, '--nvx-touch-target-min')
        && str_contains($css, '@media (max-width: 71.999rem)')
        && str_contains($css, '@media (min-width: 72rem)')
        && str_contains($css, ':not([inert])')
        && str_contains($css, '.nvx-header .nvx-nav')
        && str_contains($css, '.nvx-header .nvx-hamburger'),
    'The accessibility stylesheet must enforce touch targets, inert visibility and adjacent responsive modes.'
);
nvx_document_contract_assert(
    str_contains($js, 'MutationObserver')
        && str_contains($js, "event.key === 'Escape'")
        && str_contains($js, '!nav.contains(document.activeElement)')
        && str_contains($js, "existing.dataset.nvxLoaded === '1'")
        && str_contains($js, 'promise = null')
        && str_contains($js, "document.createElement('script')")
        && str_contains($js, 'resolveHubSpotScriptUrl')
        && str_contains($js, 'hubspotPortalId'),
    'The runtime must govern complete focus containment and retryable demand-loaded HubSpot.'
);
nvx_document_contract_assert(
    str_contains($module, 'hubspotPortalId')
        && str_contains($module, 'nvx_document_governance_print_fallback_meta')
        && !str_contains($module, "'hubspotScriptUrl' =>"),
    'Server config must not embed a full HubSpot script URL; solutions fallback meta is required.'
);

$input = '<!doctype html><html lang="es"><head>'
    . '<meta charset="UTF-8">'
    . '<meta name="viewport" content="width=device-width, initial-scale=1">'
    . '<meta name="viewport" content="width=device-width, initial-scale=1">'
    . '<style id="critical">body{display:block}</style>'
    . '<script id="sitekit">window._googlesitekitConsentCategoryMap={statistics:["analytics_storage"]};window._googlesitekitConsents={};</script>'
    . '<script>window.FacebookSignal=window.FacebookSignal||function(){};</script>'
    . '</head><body><main><h1>Contacto</h1><p>Información completa de contacto, clínicas y valoración médica en Madrid.</p>'
    . '<img class="original" src="https://staging2.nuvanx.com/wp-content/uploads/2026/07/evidence.webp" alt="Original">'
    . '<img class="derived" src="https://staging2.nuvanx.com/wp-content/uploads/2026/07/evidence-768x432.webp" alt="Derivada">'
    . '</main></body></html>';

$output = nvx_document_governance_normalize_document($input);
$output = nvx_document_governance_normalize_document($output);

nvx_document_contract_assert(1 === substr_count($output, '<title>Contacto NUVANX Madrid</title>'), 'Exactly one canonical title is required.');
nvx_document_contract_assert(1 === substr_count($output, 'name="viewport"'), 'Duplicate viewport declarations must collapse to one.');
nvx_document_contract_assert(1 === substr_count($output, 'name="description"'), 'Exactly one meta description is required.');
nvx_document_contract_assert(1 === substr_count($output, 'rel="canonical"'), 'Exactly one canonical link is required.');
nvx_document_contract_assert(1 === substr_count($output, 'name="nvx-document-contract"'), 'Exactly one document contract marker is required.');
nvx_document_contract_assert(str_contains($output, '_googlesitekitConsentCategoryMap'), 'Site Kit consent bootstrap must survive normalization.');
nvx_document_contract_assert(str_contains($output, '<style id="critical">'), 'Styles located before a retired script must survive normalization.');
nvx_document_contract_assert(!str_contains($output, 'FacebookSignal'), 'Retired FacebookSignal code must be removed without crossing script boundaries.');

preg_match('/<img\b[^>]*class="original"[^>]*>/i', $output, $originalTag);
preg_match('/<img\b[^>]*class="derived"[^>]*>/i', $output, $derivedTag);
nvx_document_contract_assert(
    isset($originalTag[0])
        && str_contains($originalTag[0], 'width="1672"')
        && str_contains($originalTag[0], 'height="941"'),
    'Original WordPress media dimensions must be restored.'
);
nvx_document_contract_assert(
    isset($derivedTag[0])
        && str_contains($derivedTag[0], 'width="768"')
        && str_contains($derivedTag[0], 'height="432"')
        && !str_contains($derivedTag[0], 'width="1672"'),
    'Derived WordPress media must use its exact registered dimensions.'
);

preg_match('/<meta\b(?=[^>]*name="description")[^>]*>/i', $output, $descriptionTag);
$description = isset($descriptionTag[0])
    ? nvx_document_governance_tag_attribute($descriptionTag[0], 'content')
    : '';
nvx_document_contract_assert(
    nvx_document_governance_text_length($description) >= 40
        && 'Descripción corta.' !== $description,
    'Descriptions shorter than rendered acceptance minimum must fall through to a complete candidate.'
);

echo "Global document governance contract passed.\n";
