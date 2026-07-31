<?php
declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
define('WP_DEBUG', false);

$translation_calls = 0;
function __(string $text, ?string $domain = null): string {
    global $translation_calls;
    $translation_calls++;
    return 'i18n:' . $text;
}
function home_url(string $path = ''): string { return 'home:' . $path; }
function nvx_btl_claim(string $key): string { return 'claim:' . $key; }
function add_filter(...$args): bool { return true; }

$inc = dirname(__DIR__, 2) . '/wp-content/themes/nuvanx-medical/inc';
require_once $inc . '/nvx-catalog-json.php';
require_once $inc . '/nvx-faq-content-v2.php';
require_once $inc . '/nvx-faq-catalog.php';

function nvx_review_assert(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

$legacy_prefixes = array('@nvx-i18n:', '@nvx-home:', '@nvx-claim:');
foreach (glob($inc . '/data/*.json') ?: array() as $path) {
    $raw = (string) file_get_contents($path);
    foreach ($legacy_prefixes as $prefix) {
        nvx_review_assert(!str_contains($raw, $prefix), basename($path) . " contains legacy token {$prefix}");
    }
}

$general = nvx_get_faq_catalog();
nvx_review_assert(11 === count($general), 'Global FAQ selection must contain eleven stable entries.');
nvx_review_assert(
    str_contains($general[10]['a'] ?? '', 'ICOMEM 282864786'),
    'Global and homepage medical-team FAQ must use the same approved registration text.'
);

$home = nvx_home_faq_v2_catalog();
$recovery = array_values(array_filter($home, static fn($item) => ($item['id'] ?? '') === 'recuperacion-real-laser'));
nvx_review_assert(1 === count($recovery), 'Recovery FAQ is missing.');
nvx_review_assert(
    !str_contains($recovery[0]['a'], '70%') && !str_contains($recovery[0]['a'], 'garantizamos'),
    'Recovery FAQ must not contain an unsupported quantified guarantee.'
);

$translation_calls = 0;
$invalid = nvx_catalog_resolve_tokens(
    array(
        'translation' => '@nvx-i18n:not-valid***',
        'url' => '@nvx-home:not-valid***',
        'claim' => '@nvx-claim:not-valid***',
    ),
    static fn(string $key): string => 'claim:' . $key
);
nvx_review_assert(array('', '', '') === array_values($invalid), 'Invalid Base64 tokens must resolve safely.');
nvx_review_assert(0 === $translation_calls, 'Invalid translation tokens must not call gettext.');

$filtered = nvx_catalog_filter_records(
    array(
        'valid' => array('slug' => 'ok', 'description' => 'ok'),
        'invalid' => array('slug' => 'missing-description'),
    ),
    array('slug', 'description'),
    'contract-test'
);
nvx_review_assert(array('valid') === array_keys($filtered), 'Incomplete catalog records must be filtered.');

$treatment_data = json_decode(
    (string) file_get_contents($inc . '/data/treatments-catalog.json'),
    true,
    512,
    JSON_THROW_ON_ERROR
);
$meta = array();
foreach ($treatment_data as $section) {
    foreach ($section['items'] ?? array() as $item) {
        $meta[] = substr((string) ($item['meta'] ?? ''), 0, 2);
    }
}
nvx_review_assert(
    array('01','02','03','04','05','06','07','08','09','10','11','12') === $meta,
    'Treatment card numbering must be continuous.'
);

echo "Catalog review contract passed.\n";
