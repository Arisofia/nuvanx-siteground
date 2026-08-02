<?php
declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

class WP_Post {
    public string $post_status = 'publish';
}

if (!function_exists('__')) {
    function __(string $text, ?string $domain = null): string {
        return 'i18n:' . $text;
    }
}

if (!function_exists('home_url')) {
    function home_url(string $path = ''): string {
        return 'home:' . $path;
    }
}

if (!function_exists('nvx_btl_claim')) {
    function nvx_btl_claim(string $key): string {
        return 'claim:' . $key;
    }
}

if (!function_exists('get_page_by_path')) {
    function get_page_by_path(string $path, $output = 'OBJECT', $post_type = 'page') {
        return null;
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($page = 0, bool $leavename = false): string {
        return '';
    }
}

if (!function_exists('add_filter')) {
    function add_filter(...$args): bool {
        return true;
    }
}

if (!function_exists('add_action')) {
    function add_action(...$args): bool {
        return true;
    }
}

if (!function_exists('add_shortcode')) {
    function add_shortcode(...$args): bool {
        return true;
    }
}

$themeInc = dirname(__DIR__, 2) . '/wp-content/themes/nuvanx-medical/inc';

require_once $themeInc . '/nvx-aesthetic-treatment-pages.php';
require_once $themeInc . '/nvx-aesthetic-medicine-page.php';
require_once $themeInc . '/nvx-btl-detail-pages.php';
require_once $themeInc . '/nvx-faq-catalog.php';
require_once $themeInc . '/nvx-faq-content-v2.php';
require_once $themeInc . '/nvx-laser-medicine-page.php';
require_once $themeInc . '/nvx-seo-metadata.php';
require_once $themeInc . '/nvx-treatment-hub-schema.php';
require_once $themeInc . '/nvx-treatments-catalog.php';

/**
 * Fail with a useful message.
 */
function nvx_contract_assert(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

/**
 * Ensure no serialized runtime token leaked into a public catalog.
 *
 * @param mixed $value
 */
$pageByPathReflection = new ReflectionFunction('get_page_by_path');
nvx_contract_assert(
    3 === $pageByPathReflection->getNumberOfParameters()
        && 1 === $pageByPathReflection->getNumberOfRequiredParameters(),
    'get_page_by_path() test stub must match the WordPress callable arity.'
);

$permalinkReflection = new ReflectionFunction('get_permalink');
nvx_contract_assert(
    2 === $permalinkReflection->getNumberOfParameters()
        && 0 === $permalinkReflection->getNumberOfRequiredParameters(),
    'get_permalink() test stub must preserve optional WordPress arguments.'
);

function nvx_contract_assert_no_tokens($value, string $path = 'catalog'): void {
    if (is_array($value)) {
        foreach ($value as $key => $item) {
            nvx_contract_assert_no_tokens($item, $path . '.' . (string) $key);
        }
        return;
    }

    if (is_string($value) && str_contains($value, '@nvx-')) {
        fwrite(STDERR, "Unresolved catalog token at {$path}: {$value}\n");
        exit(1);
    }
}

$aestheticTreatments = nvx_aesthetic_treatment_catalog();
nvx_contract_assert(
    ($aestheticTreatments['lips_ha']['slug'] ?? null) === 'labios-acido-hialuronico-madrid',
    'Aesthetic treatment catalog slug changed during JSON extraction.'
);
nvx_contract_assert_no_tokens($aestheticTreatments, 'aesthetic-treatments');

$aestheticHub = nvx_aesthetic_editorial_catalog();
nvx_contract_assert(
    ($aestheticHub['pillars'][0]['title'] ?? null) === 'i18n:1. Pérdida de soporte estructural',
    'Aesthetic hub pillar changed during JSON extraction.'
);
nvx_contract_assert(
    ($aestheticHub['treatments'][0]['url'] ?? null) === 'home:/labios-acido-hialuronico-madrid/',
    'Aesthetic hub treatment URL did not preserve fallback resolution.'
);
nvx_contract_assert_no_tokens($aestheticHub, 'aesthetic-hub');

$btl = nvx_btl_detail_registry();
nvx_contract_assert(
    ($btl['exion-face']['hub'] ?? null) === 'home:/exion-btl/',
    'BTL hub URL token did not resolve through home_url().'
);
nvx_contract_assert(
    ($btl['exion-face']['h1'] ?? null) === 'i18n:EXION® Face en Madrid: radiofrecuencia y ultrasonido',
    'BTL translation token did not preserve the original localized string.'
);
nvx_contract_assert(
    ($btl['exion-face']['mechanism']['body'][0] ?? null) === 'claim:exion_face_mech_intro',
    'BTL governed claim token did not resolve through nvx_btl_claim().'
);
nvx_contract_assert_no_tokens($btl, 'btl');

$faq = nvx_get_faq_catalog();
nvx_contract_assert(
    ($faq[0]['q'] ?? null) === '¿Cómo se solicita una valoración médica en NUVANX?',
    'FAQ catalog content changed during JSON extraction.'
);
nvx_contract_assert_no_tokens($faq, 'faq');

$homeFaq = nvx_home_faq_v2_catalog();
nvx_contract_assert(
    ($homeFaq[0]['id'] ?? null) === 'valoracion-medica',
    'Homepage FAQ identifier changed during JSON extraction.'
);
nvx_contract_assert(
    ($homeFaq[0]['q'] ?? null) === '¿Cómo se solicita una valoración médica en NUVANX?',
    'Homepage FAQ content changed during JSON extraction.'
);
nvx_contract_assert_no_tokens($homeFaq, 'home-faq');

$endoliftHomeUrl = 'home:/endolift-facial-papada-mandibula/';

$laserHub = nvx_laser_editorial_catalog();
nvx_contract_assert(
    ($laserHub['pillars'][0]['title'] ?? null) === 'i18n:1. Fototermólisis selectiva',
    'Laser hub pillar changed during JSON extraction.'
);
nvx_contract_assert(
    ($laserHub['platforms'][0]['url'] ?? null) === $endoliftHomeUrl,
    'Laser hub platform URL did not preserve published-page fallback.'
);
nvx_contract_assert_no_tokens($laserHub, 'laser-hub');

$seo = nvx_seo_metadata_catalog();
nvx_contract_assert(
    ($seo['home']['title'] ?? null) === 'Medicina Estética Láser Madrid | Endolift y CO₂ | NUVANX',
    'Homepage SEO title changed during JSON extraction.'
);
nvx_contract_assert_no_tokens($seo, 'seo');

$blogSeo = nvx_seo_blog_post_metadata_catalog();
nvx_contract_assert(
    ($blogSeo['endolift-primeras-72-horas-que-esperar']['title'] ?? null) === 'Endolift: primeras 72 horas | Qué esperar',
    'Blog SEO metadata changed during JSON extraction.'
);
nvx_contract_assert_no_tokens($blogSeo, 'blog-seo');

$hubItems = nvx_treatment_hub_schema_items('org:test');
nvx_contract_assert(
    ($hubItems[0]['url'] ?? null) === $endoliftHomeUrl,
    'Treatment hub schema URL changed during JSON extraction.'
);
nvx_contract_assert(
    ($hubItems[0]['item']['provider']['@id'] ?? null) === 'org:test',
    'Treatment hub schema provider binding changed.'
);
nvx_contract_assert(
    ($hubItems[0]['position'] ?? null) === 1,
    'Treatment hub schema ordering changed.'
);
nvx_contract_assert_no_tokens($hubItems, 'treatment-hub-schema');

$treatments = nvx_treatments_catalog_data();
nvx_contract_assert(
    ($treatments[0]['items'][0]['url'] ?? null) === $endoliftHomeUrl,
    'Treatment URL token did not resolve through home_url().'
);
nvx_contract_assert_no_tokens($treatments, 'treatments');

echo "Catalog JSON runtime contract passed.\n";
