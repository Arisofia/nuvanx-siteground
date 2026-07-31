<?php
declare(strict_types=1);

function __(string $text, ?string $domain = null): string {
    return '@nvx-i18n:' . base64_encode($text);
}

function home_url(string $path = ''): string {
    return '@nvx-home:' . base64_encode($path);
}

function nvx_btl_claim(string $key): string {
    return '@nvx-claim:' . base64_encode($key);
}

/** @return array{0:int,1:int,2:string} */
function nvx_extract_function_body(string $source, string $functionName): array {
    $tokens = token_get_all($source);
    $offsets = [];
    $offset = 0;

    foreach ($tokens as $index => $token) {
        $offsets[$index] = $offset;
        $offset += strlen(is_array($token) ? $token[1] : $token);
    }

    $count = count($tokens);
    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];
        if (!is_array($token) || $token[0] !== T_FUNCTION) {
            continue;
        }

        $matched = false;
        for ($j = $i + 1; $j < $count; $j++) {
            $candidate = $tokens[$j];
            if (is_array($candidate) && $candidate[0] === T_STRING) {
                $matched = $candidate[1] === $functionName;
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

            $bodyStart = $offsets[$j];
            $depth = 0;
            for ($k = $j; $k < $count; $k++) {
                if ($tokens[$k] === '{') {
                    $depth++;
                } elseif ($tokens[$k] === '}') {
                    $depth--;
                    if ($depth === 0) {
                        $bodyEnd = $offsets[$k] + 1;
                        return [$bodyStart, $bodyEnd, substr($source, $bodyStart, $bodyEnd - $bodyStart)];
                    }
                }
            }
        }
    }

    throw new RuntimeException("Function not found: {$functionName}");
}

/** @return array<mixed> */
function nvx_evaluate_catalog_body(string $body, string $functionName): array {
    $closure = eval('return static function() ' . $body . ';');
    if (!$closure instanceof Closure) {
        throw new RuntimeException("Unable to evaluate {$functionName}");
    }

    $catalog = $closure();
    if (!is_array($catalog) || $catalog === []) {
        throw new RuntimeException("{$functionName} did not return a non-empty array");
    }

    return $catalog;
}

function nvx_write_catalog_json(string $path, array $catalog): void {
    $json = json_encode(
        $catalog,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );

    if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0777, true) && !is_dir(dirname($path))) {
        throw new RuntimeException('Unable to create catalog directory');
    }

    if (false === file_put_contents($path, $json . "\n")) {
        throw new RuntimeException("Unable to write {$path}");
    }
}

$root = dirname(__DIR__, 2);
$targets = [
    [
        'path' => 'wp-content/themes/nuvanx-medical/inc/nvx-aesthetic-treatment-pages.php',
        'function' => 'nvx_aesthetic_treatment_catalog',
        'json' => 'aesthetic-treatment-pages.json',
        'claims' => false,
    ],
    [
        'path' => 'wp-content/themes/nuvanx-medical/inc/nvx-btl-detail-pages.php',
        'function' => 'nvx_btl_detail_registry',
        'json' => 'btl-detail-pages.json',
        'claims' => true,
    ],
    [
        'path' => 'wp-content/themes/nuvanx-medical/inc/nvx-faq-catalog.php',
        'function' => 'nvx_get_faq_catalog',
        'json' => 'faq-catalog.json',
        'claims' => false,
    ],
    [
        'path' => 'wp-content/themes/nuvanx-medical/inc/nvx-treatments-catalog.php',
        'function' => 'nvx_treatments_catalog_data',
        'json' => 'treatments-catalog.json',
        'claims' => false,
    ],
];

$loader = <<<'PHP'
<?php
/**
 * Shared loader for large structured catalogs stored outside PHP source.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Load and cache a JSON catalog from inc/data.
 *
 * @return array<mixed>
 */
function nvx_catalog_json_load( string $filename ): array {
    static $catalogs = array();

    $safe_name = basename( $filename );
    if ( isset( $catalogs[ $safe_name ] ) ) {
        return $catalogs[ $safe_name ];
    }

    $path = __DIR__ . '/data/' . $safe_name;
    if ( ! is_readable( $path ) ) {
        return array();
    }

    $decoded = json_decode( (string) file_get_contents( $path ), true );
    if ( ! is_array( $decoded ) ) {
        return array();
    }

    $catalogs[ $safe_name ] = $decoded;
    return $decoded;
}

/**
 * Transform catalog leaf values while preserving keys and nesting.
 *
 * @param mixed    $value Catalog value.
 * @param callable $transform String transformer.
 * @return mixed
 */
function nvx_catalog_transform_values( $value, callable $transform ) {
    if ( is_array( $value ) ) {
        foreach ( $value as $key => $item ) {
            $value[ $key ] = nvx_catalog_transform_values( $item, $transform );
        }
        return $value;
    }

    return is_string( $value ) ? $transform( $value ) : $value;
}

/**
 * Resolve tokens captured from WordPress-aware catalog declarations.
 *
 * @param array<mixed>  $catalog Catalog data.
 * @param callable|null $claim_resolver Optional BTL claim resolver.
 * @return array<mixed>
 */
function nvx_catalog_resolve_tokens( array $catalog, ?callable $claim_resolver = null ): array {
    return nvx_catalog_transform_values(
        $catalog,
        static function ( string $value ) use ( $claim_resolver ) {
            $prefixes = array(
                '@nvx-i18n:' => static function ( string $payload ) {
                    return __( (string) base64_decode( $payload, true ), 'nuvanx-medical' );
                },
                '@nvx-home:' => static function ( string $payload ) {
                    return home_url( (string) base64_decode( $payload, true ) );
                },
            );

            foreach ( $prefixes as $prefix => $resolver ) {
                if ( 0 === strpos( $value, $prefix ) ) {
                    return $resolver( substr( $value, strlen( $prefix ) ) );
                }
            }

            if ( null !== $claim_resolver && 0 === strpos( $value, '@nvx-claim:' ) ) {
                $claim_key = (string) base64_decode( substr( $value, 11 ), true );
                return $claim_resolver( $claim_key );
            }

            return $value;
        }
    );
}
PHP;

$loaderPath = $root . '/wp-content/themes/nuvanx-medical/inc/nvx-catalog-json.php';
if (false === file_put_contents($loaderPath, $loader . "\n")) {
    throw new RuntimeException('Unable to write shared catalog loader');
}

foreach ($targets as $target) {
    $path = $root . '/' . $target['path'];
    $source = file_get_contents($path);
    if ($source === false) {
        throw new RuntimeException("Unable to read {$target['path']}");
    }

    [$bodyStart, $bodyEnd, $body] = nvx_extract_function_body($source, $target['function']);
    $catalog = nvx_evaluate_catalog_body($body, $target['function']);
    nvx_write_catalog_json(
        $root . '/wp-content/themes/nuvanx-medical/inc/data/' . $target['json'],
        $catalog
    );

    $resolver = $target['claims']
        ? "static function ( string \$key ) { return nvx_btl_claim( \$key ); }"
        : 'null';

    $replacement = "{\n"
        . "\trequire_once __DIR__ . '/nvx-catalog-json.php';\n\n"
        . "\treturn nvx_catalog_resolve_tokens(\n"
        . "\t\tnvx_catalog_json_load( '" . $target['json'] . "' ),\n"
        . "\t\t" . $resolver . "\n"
        . "\t);\n"
        . "}";

    $rewritten = substr($source, 0, $bodyStart)
        . $replacement
        . substr($source, $bodyEnd);

    if (false === file_put_contents($path, $rewritten)) {
        throw new RuntimeException("Unable to rewrite {$target['path']}");
    }
}

foreach ($targets as $target) {
    $jsonPath = $root . '/wp-content/themes/nuvanx-medical/inc/data/' . $target['json'];
    $decoded = json_decode((string) file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($decoded) || $decoded === []) {
        throw new RuntimeException("Invalid generated catalog: {$target['json']}");
    }
}

echo "Externalized four legacy catalogs.\n";
