<?php
/**
 * Shared embedded JSON-LD hygiene helpers for database migrations and audits.
 *
 * The public site has one canonical Schema.org source: the governed Yoast
 * wpseo_schema_graph. Legacy Schema.org script blocks persisted in WordPress
 * post_content are therefore migratable content drift and must be removed.
 * Non-Schema application/ld+json payloads are intentionally preserved.
 *
 * @package NVX\Migrations
 */

declare( strict_types = 1 );

/** PCRE pattern for application/ld+json script blocks. */
function nvx_hygiene_jsonld_script_pattern(): string {
    return '#<script\b(?=[^>]*\btype\s*=\s*(["\'])application/ld\+json\1)[^>]*>([\s\S]*?)</script>#iu';
}

/** Whether a JSON-LD payload represents Schema.org structured data. */
function nvx_hygiene_jsonld_is_schema_payload( string $payload ): bool {
    if ( '' === trim( $payload ) ) {
        return false;
    }

    $decoded = json_decode( $payload, true );
    if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
        $encoded = wp_json_encode( $decoded );
        if ( is_string( $encoded ) && preg_match( '/schema\.org|"@graph"\s*:|"@type"\s*:/i', $encoded ) ) {
            return true;
        }
    }

    // Fail safe for legacy/minified payloads that are valid enough to identify
    // as Schema.org even when historical editor escaping prevents JSON decode.
    return (bool) preg_match( '/schema\.org|@graph\b|["\']@type["\']\s*:/i', $payload );
}

/**
 * Remove embedded Schema.org JSON-LD from an HTML value.
 *
 * @param string $html          Raw HTML/content value.
 * @param int    $removed_count Number of Schema.org blocks removed.
 * @return string Cleaned HTML; original string is returned on PCRE failure.
 */
function nvx_hygiene_strip_schema_jsonld( string $html, int &$removed_count = 0 ): string {
    $removed_count = 0;
    if ( '' === $html || false === stripos( $html, 'ld+json' ) ) {
        return $html;
    }

    $cleaned = preg_replace_callback(
        nvx_hygiene_jsonld_script_pattern(),
        static function ( array $matches ) use ( &$removed_count ): string {
            $payload = isset( $matches[2] ) ? (string) $matches[2] : '';
            if ( nvx_hygiene_jsonld_is_schema_payload( $payload ) ) {
                $removed_count++;
                return '';
            }
            return (string) $matches[0];
        },
        $html
    );

    return is_string( $cleaned ) ? $cleaned : $html;
}

/** Count embedded Schema.org JSON-LD blocks without mutating the source. */
function nvx_hygiene_count_schema_jsonld( string $html ): int {
    $removed = 0;
    nvx_hygiene_strip_schema_jsonld( $html, $removed );
    return $removed;
}
