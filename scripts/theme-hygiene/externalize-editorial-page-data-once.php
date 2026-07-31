<?php
declare(strict_types=1);

function __(string $text, ?string $domain = null): string {
    return '@nvx-i18n:' . base64_encode($text);
}

function nvx_laser_page_url(string $path): string {
    return '@nvx-laser-url:' . base64_encode($path);
}

/** @param array<int, string> $alts */
function nvx_aesthetic_resolve_treatment_url(string $primary, array $alts = array()): string {
    return '@nvx-aesthetic-url:' . base64_encode(
        json_encode(array($primary, $alts), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
    );
}

/** @return array{start:int,end:int,expression:string} */
function nvx_editorial_assignment(string $source, string $variable): array {
    $tokens = token_get_all($source);
    $offsets = array();
    $offset = 0;

    foreach ($tokens as $index => $token) {
        $offsets[$index] = $offset;
        $offset += strlen(is_array($token) ? $token[1] : $token);
    }

    $count = count($tokens);
    for ($i = 0; $i < $count; $i++) {
        if (!is_array($tokens[$i]) || T_VARIABLE !== $tokens[$i][0] || $variable !== $tokens[$i][1]) {
            continue;
        }

        $start = $offsets[$i];
        for ($j = $i + 1; $j < $count && '=' !== $tokens[$j]; $j++) {
        }
        if ($j >= $count) {
            continue;
        }

        for ($j++; $j < $count; $j++) {
            $token = $tokens[$j];
            if (is_array($token) && in_array($token[0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), true)) {
                continue;
            }
            $expressionStart = $offsets[$j];
            break;
        }

        $depth = 0;
        for ($k = $j; $k < $count; $k++) {
            $token = $tokens[$k];
            if (in_array($token, array('(', '[', '{'), true)) {
                $depth++;
            } elseif (in_array($token, array(')', ']', '}'), true)) {
                $depth--;
            } elseif (';' === $token && 0 === $depth) {
                return array(
                    'start' => $start,
                    'end' => $offsets[$k] + 1,
                    'expression' => substr($source, $expressionStart, $offsets[$k] - $expressionStart),
                );
            }
        }
    }

    throw new RuntimeException("Assignment not found: {$variable}");
}

/** @return array<mixed> */
function nvx_editorial_evaluate(string $expression, string $label): array {
    $value = eval('return ' . $expression . ';');
    if (!is_array($value) || array() === $value) {
        throw new RuntimeException("Empty editorial dataset: {$label}");
    }
    return $value;
}

function nvx_editorial_write_json(string $path, array $data): void {
    $json = json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );
    if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0777, true) && !is_dir(dirname($path))) {
        throw new RuntimeException('Unable to create data directory');
    }
    if (false === file_put_contents($path, $json . "\n")) {
        throw new RuntimeException("Unable to write {$path}");
    }
}

/**
 * @param array<string, string> $assignments Variable name => catalog key.
 */
function nvx_editorial_externalize(
    string $root,
    string $relativePath,
    string $jsonFilename,
    array $assignments,
    string $helperNeedle,
    string $helperCode,
    string $helperFunction
): void {
    $path = $root . '/' . $relativePath;
    $source = (string) file_get_contents($path);
    $replacements = array();
    $catalog = array();

    foreach ($assignments as $variable => $key) {
        $assignment = nvx_editorial_assignment($source, $variable);
        $catalog[$key] = nvx_editorial_evaluate($assignment['expression'], $relativePath . ':' . $variable);
        $replacements[] = array(
            'start' => $assignment['start'],
            'end' => $assignment['end'],
            'replacement' => $variable . " = {$helperFunction}()['{$key}'] ?? array();",
        );
    }

    usort(
        $replacements,
        static fn(array $left, array $right): int => $right['start'] <=> $left['start']
    );
    foreach ($replacements as $replacement) {
        $source = substr($source, 0, $replacement['start'])
            . $replacement['replacement']
            . substr($source, $replacement['end']);
    }

    if (!str_contains($source, $helperNeedle)) {
        throw new RuntimeException("Helper insertion point not found in {$relativePath}");
    }
    $source = str_replace($helperNeedle, $helperCode . "\n\n" . $helperNeedle, $source, $count);
    if (1 !== $count) {
        throw new RuntimeException("Unexpected helper insertion count in {$relativePath}: {$count}");
    }

    nvx_editorial_write_json(
        $root . '/wp-content/themes/nuvanx-medical/inc/data/' . $jsonFilename,
        $catalog
    );
    if (false === file_put_contents($path, $source)) {
        throw new RuntimeException("Unable to rewrite {$relativePath}");
    }
}

$root = dirname(__DIR__, 2);

$laserHelper = <<<'PHP'
/**
 * Canonical static data for the laser medicine editorial hub.
 *
 * @return array<string, array<mixed>>
 */
function nvx_laser_editorial_catalog(): array {
	static $catalog = null;

	if ( is_array( $catalog ) ) {
		return $catalog;
	}

	require_once __DIR__ . '/nvx-catalog-json.php';
	$catalog = nvx_catalog_resolve_tokens(
		nvx_catalog_json_load( 'laser-medicine-page.json' ),
		null,
		array(
			'@nvx-laser-url:' => static function ( string $payload ) {
				return nvx_laser_page_url( (string) base64_decode( $payload, true ) );
			},
		)
	);

	return $catalog;
}
PHP;

nvx_editorial_externalize(
    $root,
    'wp-content/themes/nuvanx-medical/inc/nvx-laser-medicine-page.php',
    'laser-medicine-page.json',
    array('$pillars' => 'pillars', '$platforms' => 'platforms', '$faqs' => 'faqs'),
    "/**\n * Full editorial body.\n */",
    $laserHelper,
    'nvx_laser_editorial_catalog'
);

$aestheticHelper = <<<'PHP'
/**
 * Canonical static data for the aesthetic medicine editorial hub.
 *
 * @return array<string, array<mixed>>
 */
function nvx_aesthetic_editorial_catalog(): array {
	static $catalog = null;

	if ( is_array( $catalog ) ) {
		return $catalog;
	}

	require_once __DIR__ . '/nvx-catalog-json.php';
	$catalog = nvx_catalog_resolve_tokens(
		nvx_catalog_json_load( 'aesthetic-medicine-page.json' ),
		null,
		array(
			'@nvx-aesthetic-url:' => static function ( string $payload ) {
				$arguments = json_decode( (string) base64_decode( $payload, true ), true );
				$primary   = is_array( $arguments ) && isset( $arguments[0] ) ? (string) $arguments[0] : '';
				$alts      = is_array( $arguments ) && isset( $arguments[1] ) && is_array( $arguments[1] )
					? $arguments[1]
					: array();
				return nvx_aesthetic_resolve_treatment_url( $primary, $alts );
			},
		)
	);

	return $catalog;
}
PHP;

nvx_editorial_externalize(
    $root,
    'wp-content/themes/nuvanx-medical/inc/nvx-aesthetic-medicine-page.php',
    'aesthetic-medicine-page.json',
    array('$pillars' => 'pillars', '$treatments' => 'treatments', '$faqs' => 'faqs'),
    "/**\n * Diagnosis pillars section.\n */",
    $aestheticHelper,
    'nvx_aesthetic_editorial_catalog'
);

echo "Externalized editorial page datasets.\n";
