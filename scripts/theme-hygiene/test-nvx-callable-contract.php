<?php
/**
 * Static callable contract for NUVANX camelCase functions.
 *
 * Uses PHP's tokenizer instead of regular expressions so comments and string
 * literals cannot create false call sites. Every direct global call beginning
 * with nvx + uppercase must have a declaration somewhere in the active theme.
 */

declare(strict_types=1);

$root  = dirname(__DIR__, 2);
$theme = $root . '/wp-content/themes/nuvanx-medical';

if (!is_dir($theme)) {
    fwrite(STDERR, "NVX_CAMEL_CALLABLE_CONTRACT_FAILED\n- theme directory is missing\n");
    exit(1);
}

$files = [];
$directoryIterator = new RecursiveDirectoryIterator($theme, FilesystemIterator::SKIP_DOTS);
$filtered = new RecursiveCallbackFilterIterator(
    $directoryIterator,
    static function (SplFileInfo $current): bool {
        return !$current->isDir()
            || !in_array($current->getFilename(), ['vendor', 'node_modules', '.git'], true);
    }
);
$iterator = new RecursiveIteratorIterator($filtered);
foreach ($iterator as $entry) {
    if (!$entry instanceof SplFileInfo || !$entry->isFile()) {
        continue;
    }
    if ('php' !== strtolower($entry->getExtension())) {
        continue;
    }
    $files[] = $entry->getPathname();
}
sort($files);

$declared = [];
$calls    = [];

/** @param array<int, array|string> $tokens */
function nvxPreviousSignificantToken(array $tokens, int $index) {
    for ($i = $index - 1; $i >= 0; $i--) {
        $token = $tokens[$i];
        if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }
        return $token;
    }
    return null;
}

/** @param array<int, array|string> $tokens */
function nvxNextSignificantToken(array $tokens, int $index) {
    $count = count($tokens);
    for ($i = $index + 1; $i < $count; $i++) {
        $token = $tokens[$i];
        if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }
        return $token;
    }
    return null;
}

foreach ($files as $file) {
    $source = file_get_contents($file);
    if (!is_string($source)) {
        fwrite(STDERR, "NVX_CAMEL_CALLABLE_CONTRACT_FAILED\n- unable to read {$file}\n");
        exit(1);
    }

    $relative = str_replace('\\', '/', substr($file, strlen($theme) + 1));
    $tokens   = token_get_all($source);

    foreach ($tokens as $index => $token) {
        if (!is_array($token) || T_STRING !== $token[0]) {
            continue;
        }

        $name = $token[1];
        if (1 !== preg_match('/^nvx[A-Z][A-Za-z0-9_]*$/', $name)) {
            continue;
        }

        $previous = nvxPreviousSignificantToken($tokens, $index);
        $next     = nvxNextSignificantToken($tokens, $index);

        if (is_array($previous) && T_FUNCTION === $previous[0]) {
            $declared[$name][] = $relative . ':' . $token[2];
            continue;
        }

        if ('(' !== $next) {
            continue;
        }

        if (is_array($previous) && in_array($previous[0], [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NEW], true)) {
            continue;
        }

        $calls[$name][] = $relative . ':' . $token[2];
    }
}

$missing = [];
foreach ($calls as $name => $locations) {
    if (!isset($declared[$name])) {
        $missing[$name] = array_values(array_unique($locations));
    }
}
ksort($missing);

if ([] !== $missing) {
    fwrite(STDERR, 'NVX_CAMEL_CALLABLE_CONTRACT_FAILED findings=' . count($missing) . "\n");
    foreach ($missing as $name => $locations) {
        fwrite(STDERR, '- ' . $name . ' called at ' . implode(', ', $locations) . "\n");
    }
    exit(1);
}

printf(
    "NVX_CAMEL_CALLABLE_CONTRACT_OK declarations=%d calls=%d files=%d\n",
    count($declared),
    count($calls),
    count($files)
);
