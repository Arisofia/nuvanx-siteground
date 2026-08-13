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

	$path   = __DIR__ . '/data/' . $safe_name;
	$result = array(
		'_error' => false,
	);

	if ( ! file_exists( $path ) ) {
		error_log( sprintf( '[nvx_catalog_json_load] Missing JSON file: %s', $path ) );
		$result['_error']       = 'missing_file';
		$catalogs[ $safe_name ] = $result;
		return $result;
	}

	$json = file_get_contents( $path );
	$data = json_decode( $json, true );

	if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $data ) ) {
		error_log(
			sprintf(
				'[nvx_catalog_json_load] Malformed JSON "%s": %s',
				$path,
				json_last_error_msg()
			)
		);
		$result['_error']       = 'malformed_json';
		$catalogs[ $safe_name ] = $result;
		return $result;
	}

	$catalogs[ $safe_name ] = array_merge( $result, $data );
	return $catalogs[ $safe_name ];
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
 * Built-in string-prefix resolvers (longest-prefix match applied later).
 *
 * @return array<string, callable>
 */
function nvx_catalog_builtin_token_resolvers(): array {
	$translate = static function ( string $payload ) {
		return '' === $payload ? '' : __( $payload, 'nuvanx-medical' );
	};

	return array(
		'@nvx-t:'   => $translate,
		// Legacy typo accepted during hydration so an editorial token can never leak to HTML.
		'@nvx:t:'   => $translate,
		'@nvx-url:' => static function ( string $payload ) {
			return home_url( $payload );
		},
	);
}

/**
 * Return one canonical tariff as a display-ready euro amount.
 */
function nvx_catalog_tariff_display_price( array $tariffs, string $group, string $key ): string {
	if ( ! isset( $tariffs[ $group ][ $key ]['pvp'] ) || ! is_numeric( $tariffs[ $group ][ $key ]['pvp'] ) ) {
		return '';
	}

	$amount   = (float) $tariffs[ $group ][ $key ]['pvp'];
	$decimals = abs( $amount - round( $amount ) ) < 0.005 ? 0 : 2;
	return number_format( $amount, $decimals, ',', '.' ) . ' €';
}

/**
 * Reconcile catalog copy that exposes prices with the canonical tariff catalog.
 *
 * The editorial JSON remains responsible for copy and structure; published PVPs
 * are hydrated from tariff-catalog.json so hubs/FAQs cannot become a second
 * source of truth.
 *
 * @param array<mixed> $catalog Resolved catalog.
 * @return array<mixed>
 */
function nvx_catalog_apply_tariff_truth( array $catalog, string $filename ): array {
	$safe_name = basename( $filename );

	if ( 'exion-page.json' === $safe_name ) {
		$tariffs    = nvx_catalog_json_load( 'tariff-catalog.json' );
		$fractional = nvx_catalog_tariff_display_price( $tariffs, 'exion', 'exion_fractional_cara' );
		$face       = nvx_catalog_tariff_display_price( $tariffs, 'exion', 'exion_face_sesion' );
		$body       = nvx_catalog_tariff_display_price( $tariffs, 'exion', 'exion_body_sesion' );

		if ( '' === $fractional || '' === $face || '' === $body ) {
			nvx_catalog_log_error( 'Unable to hydrate EXION hub prices from tariff-catalog.json.' );
			return $catalog;
		}

		if ( isset( $catalog['investment']['body'] ) ) {
			$catalog['investment']['body'] = sprintf(
				/* translators: 1: Fractional RF price, 2: EXION Face price, 3: EXION Body price. */
				__( 'El plan y presupuesto se determinan tras la valoración médica presencial en Chamberí o Salamanca–Goya. Tarifas de referencia vigentes: desde %1$s/sesión (Fractional RF), %2$s/sesión (Face) y %3$s/sesión (Body). El presupuesto definitivo se documenta tras valoración anatómica presencial. El protocolo incluye:', 'nuvanx-medical' ),
				$fractional,
				$face,
				$body
			);
		}

		if ( isset( $catalog['faq']['items'] ) && is_array( $catalog['faq']['items'] ) ) {
			foreach ( $catalog['faq']['items'] as $index => $faq ) {
				if ( ! is_array( $faq ) || empty( $faq['q'] ) || false === stripos( (string) $faq['q'], 'cuánto cuesta EXION' ) ) {
					continue;
				}
				$catalog['faq']['items'][ $index ]['a'] = sprintf(
					/* translators: 1: Fractional RF price, 2: EXION Face price, 3: EXION Body price. */
					__( 'Las tarifas de referencia vigentes parten desde %1$s/sesión (Fractional RF), %2$s/sesión (Face) y %3$s/sesión (Body). El presupuesto definitivo se documenta tras valoración anatómica presencial.', 'nuvanx-medical' ),
					$fractional,
					$face,
					$body
				);
				break;
			}
		}
	}

	if ( 'endolaser-page.json' === $safe_name ) {
		if ( isset( $catalog['planning']['body'] ) ) {
			$catalog['planning']['body'] = __( 'El presupuesto se calcula por zona o combinación de zonas según el tarifario vigente y se documenta tras la valoración médica presencial. La planificación incluye valoración de extensión, calidad cutánea y seguimiento clínico según el protocolo indicado.', 'nuvanx-medical' );
		}

		if ( isset( $catalog['faq']['items'] ) && is_array( $catalog['faq']['items'] ) ) {
			foreach ( $catalog['faq']['items'] as $index => $faq ) {
				if ( ! is_array( $faq ) || empty( $faq['q'] ) || false === stripos( (string) $faq['q'], 'cuánto cuesta' ) ) {
					continue;
				}
				$catalog['faq']['items'][ $index ]['a'] = __( 'El presupuesto depende de la zona o combinación de zonas indicada. NUVANX aplica el tarifario vigente y entrega el presupuesto documentado tras la valoración médica presencial.', 'nuvanx-medical' );
				break;
			}
		}
	}

	return $catalog;
}

/**
 * Apply runtime governance corrections that depend on canonical code data.
 *
 * @param array<mixed> $catalog Resolved catalog.
 * @return array<mixed>
 */
function nvx_catalog_apply_runtime_truth( array $catalog, string $filename ): array {
	$safe_name = basename( $filename );
	$catalog   = nvx_catalog_apply_tariff_truth( $catalog, $safe_name );

	if ( 'equipo-medico-page.json' === $safe_name && isset( $catalog['rivera']['quote']['author'] ) ) {
		$colegiado = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';
		$catalog['rivera']['quote']['author'] = sprintf( (string) $catalog['rivera']['quote']['author'], $colegiado );
	}

	if ( 'btl-detail-pages.json' === $safe_name ) {
		if ( isset( $catalog['exion-face']['mechanism']['items'][2]['body'] ) ) {
			$catalog['exion-face']['mechanism']['items'][2]['body'] = __( 'El sistema de IA monitoriza la impedancia cutánea y ajusta automáticamente la entrega de energía para limitar puntos calientes y mejorar el control térmico y el confort durante el procedimiento.', 'nuvanx-medical' );
		}
		if ( isset( $catalog['emfusion']['clinical_data']['downtime'] ) ) {
			$catalog['emfusion']['clinical_data']['downtime'] = __( 'Recuperación habitualmente mínima; pueden aparecer reacciones cutáneas transitorias según sensibilidad y protocolo.', 'nuvanx-medical' );
		}
	}

	return $catalog;
}

/**
 * Resolve a single catalog string token via prefix resolvers and claim tokens.
 *
 * @param array<string, callable> $resolvers Prefix => resolver map (longest first).
 */
function nvx_catalog_resolve_token_value(
	string $value,
	?callable $claim_resolver,
	array $resolvers
): string {
	$resolved = $value;

	foreach ( $resolvers as $prefix => $resolver ) {
		if ( 0 === strpos( $value, $prefix ) ) {
			$resolved = $resolver( substr( $value, strlen( $prefix ) ) );
			break;
		}
	}

	if ( $resolved === $value && null !== $claim_resolver && 0 === strpos( $value, '@nvx-claim-key:' ) ) {
		$resolved = $claim_resolver( substr( $value, strlen( '@nvx-claim-key:' ) ) );
	}

	return $resolved;
}

/**
 * Resolve WordPress-aware values captured in JSON catalogs.
 *
 * Supported string prefixes: @nvx-t: (i18n), @nvx-url: (home_url), @nvx-claim-key: (BTL claims).
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
	$resolvers = nvx_catalog_builtin_token_resolvers() + $custom_resolvers;
	uksort(
		$resolvers,
		static function ( string $left, string $right ): int {
			return strlen( $right ) <=> strlen( $left );
		}
	);

	return nvx_catalog_transform_values(
		$catalog,
		static function ( string $value ) use ( $claim_resolver, $resolvers ) {
			return nvx_catalog_resolve_token_value( $value, $claim_resolver, $resolvers );
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

	$locale = '';
	if ( function_exists( 'determine_locale' ) ) {
		$locale = (string) determine_locale();
	}
	if ( '' === $locale && function_exists( 'get_locale' ) ) {
		$locale = (string) get_locale();
	}

	$custom_keys = array_keys( $custom_resolvers );
	$object_keys = array_keys( $object_resolvers );
	sort( $custom_keys, SORT_STRING );
	sort( $object_keys, SORT_STRING );
	$resolver_signature = implode( ',', $custom_keys )
		. '|' . implode( ',', $object_keys )
		. '|' . ( null === $claim_resolver ? '0' : '1' );
	$base_key           = '' === $cache_key
		? basename( $filename ) . '|' . $resolver_signature
		: $cache_key;
	$key                = $base_key . '|locale:' . $locale;

	if ( ! array_key_exists( $key, $resolved ) ) {
		$catalog = nvx_catalog_resolve_tokens(
			nvx_catalog_json_load( $filename ),
			$claim_resolver,
			$custom_resolvers,
			$object_resolvers
		);
		$resolved[ $key ] = nvx_catalog_apply_runtime_truth( $catalog, $filename );
	}

	return $resolved[ $key ];
}

/**
 * Supply neutral defaults for optional aesthetic presentation fields.
 *
 * These values are optional in the renderer and are omitted from visible output
 * when empty. Normalizing their shape here prevents an otherwise complete
 * clinical record from being discarded solely because an optional presentation
 * field is absent from the source JSON.
 *
 * @param array<mixed> $entry Catalog record.
 * @return array<mixed>
 */
function nvx_catalog_apply_optional_defaults( array $entry, string $catalog_name ): array {
	if ( 'aesthetic-treatment-pages.json' !== $catalog_name ) {
		return $entry;
	}

	$defaults = array(
		'brands'       => array(),
		'duration'     => '',
		'session_time' => '',
		'anesthesia'   => '',
		'techniques'   => array(),
		'price_range'  => '',
		'sessions'     => '',
		'downtime'     => '',
	);

	foreach ( $defaults as $key => $default ) {
		if ( ! array_key_exists( $key, $entry ) ) {
			$entry[ $key ] = $default;
		}
	}

	return $entry;
}

/**
 * Retain only catalog records that contain every required key.
 *
 * @param array<mixed>      $catalog Catalog records.
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
		if ( is_array( $entry ) ) {
			$entry = nvx_catalog_apply_optional_defaults( $entry, $catalog_name );
		}
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
