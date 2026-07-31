<?php
/**
 * Shared loader for large structured catalogs stored outside PHP source.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Log a catalog integrity error when WordPress debugging is enabled. */
function nvx_catalog_log_error( string $message ): void {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'NUVANX catalog: ' . $message );
	}
}

/**
 * Load and cache a JSON catalog from inc/data.
 *
 * @return array<mixed>
 */
function nvx_catalog_json_load( string $filename ): array {
	static $catalogs = array();

	$safe_name = basename( $filename );
	if ( array_key_exists( $safe_name, $catalogs ) ) {
		return $catalogs[ $safe_name ];
	}

	$path    = __DIR__ . '/data/' . $safe_name;
	$decoded = array();

	if ( ! is_readable( $path ) ) {
		nvx_catalog_log_error( sprintf( 'JSON catalog is not readable: %s', $safe_name ) );
	} else {
		$raw = file_get_contents( $path );
		if ( false === $raw ) {
			nvx_catalog_log_error( sprintf( 'JSON catalog could not be read: %s', $safe_name ) );
		} else {
			$candidate = json_decode( $raw, true );
			if ( is_array( $candidate ) ) {
				$decoded = $candidate;
			} else {
				nvx_catalog_log_error(
					sprintf( 'Invalid JSON catalog %s: %s', $safe_name, json_last_error_msg() )
				);
			}
		}
	}

	$catalogs[ $safe_name ] = $decoded;
	return $decoded;
}

/** Decode a legacy Base64 token without passing invalid data to WordPress APIs. */
function nvx_catalog_decode_token_payload( string $payload, string $token_type ): ?string {
	$decoded = base64_decode( $payload, true );
	if ( false === $decoded ) {
		nvx_catalog_log_error( sprintf( 'Invalid %s token payload.', $token_type ) );
		return null;
	}

	return $decoded;
}

/**
 * Transform catalog values while preserving keys and nesting.
 *
 * @param mixed                   $value Catalog value.
 * @param callable                $transform String transformer.
 * @param array<string, callable> $object_resolvers Structured token resolvers.
 * @return mixed
 */
function nvx_catalog_transform_values(
	$value,
	callable $transform,
	array $object_resolvers = array()
) {
	if ( is_array( $value ) ) {
		if ( 1 === count( $value ) ) {
			$key = array_key_first( $value );
			if ( is_string( $key ) && isset( $object_resolvers[ $key ] ) ) {
				return $object_resolvers[ $key ]( $value[ $key ] );
			}
		}

		foreach ( $value as $key => $item ) {
			$value[ $key ] = nvx_catalog_transform_values( $item, $transform, $object_resolvers );
		}
		return $value;
	}

	return is_string( $value ) ? $transform( $value ) : $value;
}

/**
 * Resolve WordPress-aware values captured in JSON catalogs.
 *
 * Base prefixes are reserved and cannot be replaced by custom resolvers.
 * Legacy Base64 tokens remain supported for backwards compatibility only.
 *
 * @param array<mixed>            $catalog Catalog data.
 * @param callable|null           $claim_resolver Optional BTL claim resolver.
 * @param array<string, callable> $custom_resolvers Optional string-prefix resolvers.
 * @param array<string, callable> $object_resolvers Optional structured-token resolvers.
 * @return array<mixed>
 */
function nvx_catalog_resolve_tokens(
	array $catalog,
	?callable $claim_resolver = null,
	array $custom_resolvers = array(),
	array $object_resolvers = array()
): array {
	$prefixes = array(
		'`@nvx-t`:' => static function ( string $payload ) {
			return '' === $payload ? '' : __( $payload, 'nuvanx-medical' );
		},
		'`@nvx-url`:' => static function ( string $payload ) {
			return home_url( $payload );
		},
		'`@nvx-i18n`:' => static function ( string $payload ) {
			$decoded = nvx_catalog_decode_token_payload( $payload, 'translation' );
			return null === $decoded || '' === $decoded ? '' : __( $decoded, 'nuvanx-medical' );
		},
		'`@nvx-home`:' => static function ( string $payload ) {
			$decoded = nvx_catalog_decode_token_payload( $payload, 'home URL' );
			return null === $decoded ? '' : home_url( $decoded );
		},
	);
	$resolvers = $prefixes + $custom_resolvers;

	return nvx_catalog_transform_values(
		$catalog,
		static function ( string $value ) use ( $claim_resolver, $resolvers ) {
			foreach ( $resolvers as $prefix => $resolver ) {
				if ( 0 === strpos( $value, $prefix ) ) {
					return $resolver( substr( $value, strlen( $prefix ) ) );
				}
			}

			if ( null !== $claim_resolver && 0 === strpos( $value, '`@nvx-claim-key`:' ) ) {
				return $claim_resolver( substr( $value, strlen( '`@nvx-claim-key`:' ) ) );
			}

			if ( null !== $claim_resolver && 0 === strpos( $value, '`@nvx-claim`:' ) ) {
				$claim_key = nvx_catalog_decode_token_payload( substr( $value, strlen( '`@nvx-claim`:' ) ), 'claim' );
				return null === $claim_key || '' === $claim_key ? '' : $claim_resolver( $claim_key );
			}

			return $value;
		},
		$object_resolvers
	);
}

/**
 * Load, resolve and cache a catalog for the current request.
 *
 * Use an explicit cache key when a file can be resolved with different resolver sets.
 *
 * @param array<string, callable> $custom_resolvers String-prefix resolvers.
 * @param array<string, callable> $object_resolvers Structured-token resolvers.
 * @return array<mixed>
 */
function nvx_catalog_json_resolved(
	string $filename,
	?callable $claim_resolver = null,
	array $custom_resolvers = array(),
	array $object_resolvers = array(),
	string $cache_key = ''
): array {
	static $resolved = array();

	$key = '' === $cache_key ? basename( $filename ) : $cache_key;
	if ( ! array_key_exists( $key, $resolved ) ) {
		$resolved[ $key ] = nvx_catalog_resolve_tokens(
			nvx_catalog_json_load( $filename ),
			$claim_resolver,
			$custom_resolvers,
			$object_resolvers
		);
	}

	return $resolved[ $key ];
}

/**
 * Retain only catalog records that contain every required key.
 *
 * @param array<mixed>  $catalog Catalog records.
 * @param array<int,string> $required_keys Required keys.
 * @return array<mixed>
 */
function nvx_catalog_filter_records(
	array $catalog,
	array $required_keys,
	string $catalog_name
): array {
	$valid = array();
	foreach ( $catalog as $key => $entry ) {
		if ( ! is_array( $entry ) || array() !== array_diff( $required_keys, array_keys( $entry ) ) ) {
			nvx_catalog_log_error(
				sprintf( 'Incomplete record %s in %s.', (string) $key, $catalog_name )
			);
			continue;
		}
		$valid[ $key ] = $entry;
	}

	return $valid;
}
