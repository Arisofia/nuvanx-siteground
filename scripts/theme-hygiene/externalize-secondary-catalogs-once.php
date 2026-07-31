<?php
declare(strict_types=1);

function __(string $text, ?string $domain = null): string {
    return '@nvx-i18n:' . base64_encode($text);
}

function home_url(string $path = ''): string {
    return '@nvx-home:' . base64_encode($path);
}

/** @return array{0:int,1:int,2:string} */
function nvx_secondary_function_body(string $source, string $functionName): array {
    $tokens = token_get_all($source);
    $offsets = [];
    $offset = 0;
    foreach ($tokens as $index => $token) {
        $offsets[$index] = $offset;
        $offset += strlen(is_array($token) ? $token[1] : $token);
    }

    $count = count($tokens);
    for ($i = 0; $i < $count; $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) {
            continue;
        }

        $matched = false;
        for ($j = $i + 1; $j < $count; $j++) {
            if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                $matched = $tokens[$j][1] === $functionName;
                break;
            }
        }
        if (!$matched) {
            continue;
        }

        for (; $j < $count; $j++) {
            if ($tokens[$j] !== '{') {
                continue;
            }
            $start = $offsets[$j];
            $depth = 0;
            for ($k = $j; $k < $count; $k++) {
                if ($tokens[$k] === '{') {
                    $depth++;
                } elseif ($tokens[$k] === '}') {
                    $depth--;
                    if ($depth === 0) {
                        $end = $offsets[$k] + 1;
                        return [$start, $end, substr($source, $start, $end - $start)];
                    }
                }
            }
        }
    }

    throw new RuntimeException("Function not found: {$functionName}");
}

/** @return array<mixed> */
function nvx_secondary_evaluate_body(string $body, string $functionName): array {
    $closure = eval('return static function() ' . $body . ';');
    if (!$closure instanceof Closure) {
        throw new RuntimeException("Unable to evaluate {$functionName}");
    }
    $value = $closure();
    if (!is_array($value) || $value === []) {
        throw new RuntimeException("{$functionName} returned an empty catalog");
    }
    return $value;
}

/** @return array{0:int,1:int,2:string} */
function nvx_secondary_assignment(string $source, string $variable): array {
    $tokens = token_get_all($source);
    $offsets = [];
    $offset = 0;
    foreach ($tokens as $index => $token) {
        $offsets[$index] = $offset;
        $offset += strlen(is_array($token) ? $token[1] : $token);
    }

    $count = count($tokens);
    for ($i = 0; $i < $count; $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_VARIABLE || $tokens[$i][1] !== $variable) {
            continue;
        }

        $assignmentStart = $offsets[$i];
        for ($j = $i + 1; $j < $count; $j++) {
            if ($tokens[$j] === '=') {
                break;
            }
        }
        if ($j >= $count) {
            continue;
        }

        for ($j++; $j < $count; $j++) {
            $token = $tokens[$j];
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            $expressionStart = $offsets[$j];
            break;
        }

        $depth = 0;
        for ($k = $j; $k < $count; $k++) {
            $token = $tokens[$k];
            if (in_array($token, ['(', '[', '{'], true)) {
                $depth++;
            } elseif (in_array($token, [')', ']', '}'], true)) {
                $depth--;
            } elseif ($token === ';' && $depth === 0) {
                $assignmentEnd = $offsets[$k] + 1;
                $expression = substr($source, $expressionStart, $offsets[$k] - $expressionStart);
                return [$assignmentStart, $assignmentEnd, $expression];
            }
        }
    }

    throw new RuntimeException("Assignment not found: {$variable}");
}

function nvx_secondary_write_json(string $path, array $catalog): void {
    $json = json_encode(
        $catalog,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );
    if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0777, true) && !is_dir(dirname($path))) {
        throw new RuntimeException('Unable to create data directory');
    }
    if (false === file_put_contents($path, $json . "\n")) {
        throw new RuntimeException("Unable to write {$path}");
    }
}

$root = dirname(__DIR__, 2);
$directTargets = [
    [
        'path' => 'wp-content/themes/nuvanx-medical/inc/nvx-seo-metadata.php',
        'function' => 'nvx_seo_metadata_catalog',
        'json' => 'seo-metadata.json',
    ],
    [
        'path' => 'wp-content/themes/nuvanx-medical/inc/nvx-seo-metadata.php',
        'function' => 'nvx_seo_blog_post_metadata_catalog',
        'json' => 'seo-blog-post-metadata.json',
    ],
    [
        'path' => 'wp-content/themes/nuvanx-medical/inc/nvx-faq-content-v2.php',
        'function' => 'nvx_home_faq_v2_catalog',
        'json' => 'home-faq-v2.json',
    ],
];

foreach ($directTargets as $target) {
    $path = $root . '/' . $target['path'];
    $source = (string) file_get_contents($path);
    [$bodyStart, $bodyEnd, $body] = nvx_secondary_function_body($source, $target['function']);
    $catalog = nvx_secondary_evaluate_body($body, $target['function']);
    nvx_secondary_write_json(
        $root . '/wp-content/themes/nuvanx-medical/inc/data/' . $target['json'],
        $catalog
    );

    $replacement = "{\n"
        . "\trequire_once __DIR__ . '/nvx-catalog-json.php';\n\n"
        . "\treturn nvx_catalog_resolve_tokens(\n"
        . "\t\tnvx_catalog_json_load( '" . $target['json'] . "' )\n"
        . "\t);\n"
        . "}";

    $source = substr($source, 0, $bodyStart) . $replacement . substr($source, $bodyEnd);
    if (false === file_put_contents($path, $source)) {
        throw new RuntimeException("Unable to rewrite {$target['path']}");
    }
}

$schemaPath = $root . '/wp-content/themes/nuvanx-medical/inc/nvx-treatment-hub-schema.php';
$schemaSource = (string) file_get_contents($schemaPath);
[$assignmentStart, $assignmentEnd, $expression] = nvx_secondary_assignment($schemaSource, '$definitions');
$definitions = eval('return ' . $expression . ';');
if (!is_array($definitions) || $definitions === []) {
    throw new RuntimeException('Treatment hub schema definitions are empty');
}
nvx_secondary_write_json(
    $root . '/wp-content/themes/nuvanx-medical/inc/data/treatment-hub-schema.json',
    $definitions
);
$schemaReplacement = "require_once __DIR__ . '/nvx-catalog-json.php';\n\n\t\$definitions = nvx_catalog_json_load( 'treatment-hub-schema.json' );";
$schemaSource = substr($schemaSource, 0, $assignmentStart)
    . $schemaReplacement
    . substr($schemaSource, $assignmentEnd);
if (false === file_put_contents($schemaPath, $schemaSource)) {
    throw new RuntimeException('Unable to rewrite treatment hub schema definitions');
}

echo "Externalized secondary catalog data.\n";
