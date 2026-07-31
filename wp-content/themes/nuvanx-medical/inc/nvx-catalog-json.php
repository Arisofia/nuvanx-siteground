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
