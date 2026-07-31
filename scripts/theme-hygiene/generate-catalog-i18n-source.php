<?php
/**
 * Generate temporary PHP source containing JSON-catalog translations.
 *
 * Pipe this output to a PHP file before running `wp i18n make-pot`.
 *
 * Only readable `@nvx-t:` values are extracted. Legacy `@nvx-i18n:` Base64
 * tokens are forbidden by the catalog review contract and must not be added.
 */
declare(strict_types=1);

$data_dir = dirname(__DIR__, 2) . '/wp-content/themes/nuvanx-medical/inc/data';
$strings  = array();

$collect = static function ($value) use (&$collect, &$strings): void {
    if (is_array($value)) {
        foreach ($value as $item) {
            $collect($item);
        }
        return;
    }

    if (is_string($value) && str_starts_with($value, '@nvx-t:')) {
        $message = substr($value, 7);
        if ('' !== $message) {
            $strings[$message] = true;
        }
    }
};

foreach (glob($data_dir . '/*.json') ?: array() as $path) {
    $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    $collect($decoded);
}

ksort($strings, SORT_NATURAL | SORT_FLAG_CASE);
echo "<?php\n";
echo "/** Generated extraction source. Do not load at runtime. */\n";
foreach (array_keys($strings) as $message) {
    echo '__( ' . var_export($message, true) . ", 'nuvanx-medical' );\n";
}
