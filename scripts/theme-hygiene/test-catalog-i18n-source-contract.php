<?php
declare(strict_types=1);

if ($argc !== 2 || !is_readable($argv[1])) {
    fwrite(STDERR, "Usage: php test-catalog-i18n-source-contract.php <generated-source.php>\n");
    exit(1);
}

$expected = array();
$collect = static function ($value) use (&$collect, &$expected): void {
    if (is_array($value)) {
        foreach ($value as $item) {
            $collect($item);
        }
        return;
    }
    if (is_string($value) && str_starts_with($value, '@nvx-t:')) {
        $message = substr($value, strlen('@nvx-t:'));
        if ('' !== $message) {
            $expected[$message] = true;
        }
    }
};

$dataDir = dirname(__DIR__, 2) . '/wp-content/themes/nuvanx-medical/inc/data';
foreach (glob($dataDir . '/*.json') ?: array() as $path) {
    $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    $collect($decoded);
}

$captured = array();
function __(string $message, string $domain = 'default'): string {
    global $captured;
    if ('nuvanx-medical' === $domain && '' !== $message) {
        $captured[$message] = true;
    }
    return $message;
}

require $argv[1];

$expectedMessages = array_keys($expected);
$capturedMessages = array_keys($captured);
sort($expectedMessages, SORT_STRING);
sort($capturedMessages, SORT_STRING);

if ($expectedMessages !== $capturedMessages) {
    $missing = array_values(array_diff($expectedMessages, $capturedMessages));
    $extra = array_values(array_diff($capturedMessages, $expectedMessages));
    fwrite(STDERR, 'Generated catalog i18n source mismatch.' . PHP_EOL);
    fwrite(STDERR, 'Missing: ' . json_encode($missing, JSON_UNESCAPED_UNICODE) . PHP_EOL);
    fwrite(STDERR, 'Unexpected: ' . json_encode($extra, JSON_UNESCAPED_UNICODE) . PHP_EOL);
    exit(1);
}

echo "Catalog i18n source contract passed.\n";
