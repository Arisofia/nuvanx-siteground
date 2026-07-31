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

$treatments = nvx_treatments_catalog_data();
nvx_contract_assert(
    ($treatments[0]['items'][0]['url'] ?? null) === 'home:/endolift-facial-papada-mandibula/',
    'Treatment URL token did not resolve through home_url().'
);
nvx_contract_assert_no_tokens($treatments, 'treatments');

echo "Catalog JSON runtime contract passed.\n";
