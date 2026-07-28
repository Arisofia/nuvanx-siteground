#!/usr/bin/env python3
from pathlib import Path


def read(path: str) -> str:
    return Path(path).read_text(encoding='utf-8')


def write(path: str, text: str) -> None:
    Path(path).write_text(text, encoding='utf-8')


def replace_region(text: str, start: str, end: str, replacement: str) -> str:
    start_index = text.index(start)
    end_index = text.index(end, start_index)
    return text[:start_index] + replacement + text[end_index:]


integrations_path = 'wp-content/themes/nuvanx-medical/inc/nvx-integrations.php'
integrations = read(integrations_path)
replacement = '''/** Whether an HTML fragment starts with an application/ld+json script. */
function nvxThemeIsJsonLdScript( string $script ): bool {
    if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
        return false;
    }

    $processor = new WP_HTML_Tag_Processor( $script );
    if ( ! $processor->next_tag( 'SCRIPT' ) ) {
        return false;
    }

    $type = $processor->get_attribute( 'type' );
    return is_string( $type ) && 'application/ld+json' === strtolower( trim( $type ) );
}

/** Whether a JSON-LD script contains Schema.org graph data. */
function nvxThemeIsSchemaJsonLdScript( string $script ): bool {
    return false !== stripos( $script, 'schema.org' )
        || false !== stripos( $script, '@graph' )
        || false !== stripos( $script, '"@type"' );
}

/**
 * Remove non-Yoast Schema.org scripts with a bounded linear scan.
 *
 * @param string $html Public document HTML.
 */
function nvxThemeNormalizeSchemaScripts( string $html ): string {
    $output = '';
    $cursor = 0;
    $length = strlen( $html );

    while ( $cursor < $length ) {
        $start = stripos( $html, '<script', $cursor );
        if ( false === $start ) {
            break;
        }

        $nameEnd = $start + 7;
        $next    = $nameEnd < $length ? $html[ $nameEnd ] : '';
        if ( '' !== $next && '>' !== $next && ! ctype_space( $next ) ) {
            $output .= substr( $html, $cursor, $nameEnd - $cursor );
            $cursor = $nameEnd;
            continue;
        }

        $close = stripos( $html, '</script>', $nameEnd );
        if ( false === $close ) {
            break;
        }

        $end    = $close + 9;
        $script = substr( $html, $start, $end - $start );
        $output .= substr( $html, $cursor, $start - $cursor );

        $isJsonLd = nvxThemeIsJsonLdScript( $script );
        $isYoast  = false !== stripos( $script, 'yoast-schema-graph' );
        $isSchema = nvxThemeIsSchemaJsonLdScript( $script );
        if ( ! $isJsonLd || $isYoast || ! $isSchema ) {
            $output .= $script;
        }

        $cursor = $end;
    }

    return $output . substr( $html, $cursor );
}

/**
 * Normalize public document markup and keep a single Yoast schema.org graph.
 *
 * Removes non-Yoast Schema.org application/ld+json blocks (embedded BlogPosting,
 * legacy MedicalClinic, FAQ dumps) while preserving the canonical yoast-schema-graph.
 */
function nvx_theme_normalize_public_document( string $html ): string {
    $html = (string) preg_replace(
        '/<meta\\s+name=["\\']viewport["\\'][^>]*>/i',
        '<meta name="viewport" content="width=device-width, initial-scale=1.0" />',
        $html,
        1
    );

    $html = str_ireplace(
        array( 'NUVANX Couture Sculpt™', 'NUVANX Contour Sculpt™', 'Couture Sculpt™', 'Contour Sculpt™' ),
        'NUVANX Contour Architecture™',
        $html
    );
    $html = str_replace(
        array( '/eye-frame-rejuvenecimiento-mirada-madrid/', '/eye-frame/' ),
        array( NVX_PATH_OJERAS_SURCO_LAGRIMAL, NVX_PATH_OJERAS_SURCO_LAGRIMAL ),
        $html
    );
    $html = str_ireplace(
        array( 'NUVANX Eye Frame™', 'Eye Frame™' ),
        'Ojeras y surco lagrimal',
        $html
    );

    if ( false !== stripos( $html, 'ld+json' ) ) {
        $html = nvxThemeNormalizeSchemaScripts( $html );
    }

    return str_replace( '<!-- NUVANX_HOME_UNIFIED_FAQ_SCHEMA -->', '', $html );
}

'''
integrations = replace_region(
    integrations,
    '/**\n * Normalize public document markup and keep a single Yoast schema.org graph.',
    "add_action(\n    'template_redirect',",
    replacement,
)
write(integrations_path, integrations)

runtime_path = 'scripts/theme-hygiene/test-runtime-bootstrap-contract.mjs'
runtime = read(runtime_path)
old = '''if (!integrations.includes('schema\\\\.org|@graph\\\\b|"@type"\\\\s*:')) {
  // Accept either escaped regex form used in PHP string
  if (!/schema\\.org\\|@graph/.test(integrations)) {
    failures.push('integrations public document normalizer must strip residual Schema.org ld+json');
  }
}
'''
new = '''for (const marker of [
  'function nvxThemeIsJsonLdScript(',
  "class_exists( 'WP_HTML_Tag_Processor' )",
  'function nvxThemeNormalizeSchemaScripts(',
  "stripos( $script, 'schema.org' )",
]) {
  if (!integrations.includes(marker)) failures.push(`safe JSON-LD normalizer marker missing: ${marker}`);
}
'''
if old not in runtime:
    raise RuntimeError('runtime regex contract marker missing')
runtime = runtime.replace(old, new, 1)
write(runtime_path, runtime)

if 'preg_replace_callback' in read(integrations_path):
    raise RuntimeError('document-wide JSON-LD regex callback remains')
