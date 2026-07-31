<?php
declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');

function __(string $text, ?string $domain = null): string {
    return 'i18n:' . $text;
}

function home_url(string $path = ''): string {
    return 'home:' . $path;
}

function nvx_btl_claim(string $key): string {
    return 'claim:' . $key;
}

function add_filter(...$args): bool {
    return true;
}

function add_action(...$args): bool {
    return true;
}

function add_shortcode(...$args): bool {
    return true;
}

$themeInc = dirname(__DIR__, 2) . '/wp-content/themes/nuvanx-medical/inc';

require_once $themeInc . '/nvx-aesthetic-treatment-pages.php';
require_once $themeInc . '/nvx-btl-detail-pages.php';
require_once $themeInc . '/nvx-faq-catalog.php';
require_once $themeInc . '/nvx-faq-content-v2.php';
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

$aesthetic = nvx_aesthetic_treatment_catalog();
nvx_contract_assert(
    ($aesthetic['lips_ha']['slug'] ?? null) === 'labios-acido-hialuronico-madrid',
    'Aesthetic catalog slug changed during JSON extraction.'
);
nvx_contract_assert_no_tokens($aesthetic, 'aesthetic');

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
    ($hubItems[0]['url'] ?? null) === 'home:/endolift-facial-papada-mandibula/',
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
    ($treatments[0]['items'][0]['url'] ?? null) === 'home:/endolift-facial-papada-mandibula/',
    'Treatment URL token did not resolve through home_url().'
);
nvx_contract_assert_no_tokens($treatments, 'treatments');

echo "Catalog JSON runtime contract passed.\n";
