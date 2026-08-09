<?php
/**
 * NUVANX structured data extensions for Yoast SEO.
 *
 * Competitive GEO/SEO entity graph: MedicalOrganization + MedicalClinic branches,
 * Physician (E-E-A-T), MedicalProcedure/Service with offers where priced, FAQPage
 * mirroring visible HTML. All via Yoast `wpseo_schema_graph` only — never a second
 * schema.org ld+json in post content.
 *
 * Positioning: transparent laser authority (cite-able prices + clinical entities),
 * not franchise discount spam and not empty "request a quote" opacity.
 *
 * @package NUVANX
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load site configuration from config.json using the cached theme loader.
 *
 * Delegates to nvx_catalog_json_resolved() when available (registers a static
 * in-process cache) so the JSON file is read at most once per request.
 *
 * @return array<string, mixed>
 */
function nvx_get_config(): array {
	if ( function_exists( 'nvx_catalog_json_resolved' ) ) {
		$cfg = nvx_catalog_json_resolved( 'config.json' );
		return is_array( $cfg ) ? $cfg : array();
	}
	// Fallback: direct read only when the loader is not yet available.
	$path = get_template_directory() . '/inc/data/config.json';
	if ( is_readable( $path ) ) {
		$decoded = json_decode( (string) file_get_contents( $path ), true );
		return is_array( $decoded ) ? $decoded : array();
	}
	return array();
}

if ( ! defined( 'NVX_CONTACT_EMAIL' ) ) {
	$config = nvx_get_config();
	define( 'NVX_CONTACT_EMAIL', $config['contact']['email'] ?? 'info@nuvanx.com' );
}
/**
 * Editorial review month label for Endolift byline (update with clinical review).
 */
if ( ! defined( 'NVX_ENDOLIFT_REVIEW_LABEL' ) ) {
	define( 'NVX_ENDOLIFT_REVIEW_LABEL', 'julio 2026' );
}

if ( ! defined( 'NVX_SD_ID_MEDICAL_PROCEDURE' ) ) {
	define( 'NVX_SD_ID_MEDICAL_PROCEDURE', '#medical-procedure' );
	define( 'NVX_SD_ENDOLIFT_FACIAL', 'Endolift® facial' );
	define( 'NVX_SD_ID_SERVICE', '#service' );
	define( 'NVX_SD_PATH_EQUIPO_MEDICO', '/equipo-medico/' );
	define( 'NVX_SD_LABEL_NUM_COLEGIADO', 'Número de colegiado ICOMEM' );
	define( 'NVX_SD_LABEL_COLEGIADO_PREFIX', 'Colegiado ICOMEM ' );
	define( 'NVX_SD_ENDOLASER_CORPORAL', 'Endoláser corporal' );
	define( 'NVX_SD_LASER_CO2_FRACCIONADO', 'Láser CO₂ fraccionado' );
	define( 'NVX_SD_MEDICINA_REGENERATIVA', 'Medicina regenerativa' );
	define( 'NVX_SD_SOCIEDAD_SEMEG', 'Sociedad Española de Medicina Geriátrica (SEMEG)' );
}

/** Build one immutable public tariff row. */
function nvxTariffItem( string $label, float $pvp, string $group ): array {
	return compact( 'label', 'pvp', 'group' );
}

/**
 * Load tariff catalog from JSON
 */
function nvx_get_tariff_catalog() {
	$catalog_file = get_template_directory() . '/inc/data/tariff-catalog.json';
	if ( file_exists( $catalog_file ) ) {
		$catalog = json_decode( file_get_contents( $catalog_file ), true );
		return is_array( $catalog ) ? $catalog : array();
	}
	return array();
}

/**
 * Official public PVP catalogue (EUR, IVA 21% included).
 * Source: clinic tariff sheet. Never publish commission / internal cost notes.
 * Now loaded from JSON tariff-catalog.json for single source of truth.
 *
 * @return array{
 *   endolift: array<string, array{label:string,pvp:float,group:string}>,
 *   endolift_combo: array<string, array{label:string,pvp:float,group:string}>,
 *   laser_co2: array<string, array{label:string,pvp:float,group:string}>
 * }
 */
function nvx_tariff_catalog() {
	$catalog = nvx_get_tariff_catalog();

	// Convert JSON format to legacy PHP format for backward compatibility
	$result = array();
	foreach ( $catalog as $category => $items ) {
		$result[ $category ] = array();
		foreach ( $items as $key => $item ) {
			$result[ $category ][ $key ] = nvxTariffItem(
				$item['label'],
				(float) $item['pvp'],
				$item['group']
			);
		}
	}

	return $result;
}

/**
 * Lowest public Endolift® PVP (facial ojeras) — used for “desde” GEO copy/schema.
 *
 * @return float
 */
function nvx_endolift_price_from_eur() {
	return 798.60; }

/**
 * Reference PVP for papada / marcación mandibular (page core indication).
 *
 * @return float
 */
function nvx_endolift_price_papada_eur() {
	return 1064.80; }

/**
 * Reference PVP for Láser CO₂ facial session — used for "desde" schema/copy.
 * Source of truth: nvx_tariff_catalog()['laser_co2']['facial']['pvp'].
 *
 * Restored Jul-2026: function was never defined; two call sites were silently
 * skipped via function_exists() guards in nvx_schema_treatment_node_laser()
 * and nvx_schema_offer_catalog(), leaving CO2 schema prices permanently empty.
 *
 * @return float
 */
function nvx_co2_price_facial_eur(): float {
	$catalog = nvx_tariff_catalog();
	return (float) ( $catalog['laser_co2']['facial']['pvp'] ?? 330.00 );
}

/**
 * Reference PVP for Láser CO₂ corporal session.
 * Source of truth: nvx_tariff_catalog()['laser_co2']['corporal']['pvp'].
 *
 * @return float
 */
function nvx_co2_price_body_eur(): float {
	$catalog = nvx_tariff_catalog();
	return (float) ( $catalog['laser_co2']['corporal']['pvp'] ?? 450.00 );
}


/**
 * Format a EUR amount for Spanish locale display (2 decimals: 1.064,80).
 *
 * @param int|float|string $amount   Amount in euros.
 * @param int              $decimals Decimal places.
 * @return string
 */
function nvx_format_price_eur( $amount, $decimals = 2 ) {
	return number_format_i18n( (float) $amount, (int) $decimals );
}

/**
 * Schema.org price string (dot decimal, two places).
 *
 * @param int|float|string $amount Amount in euros.
 * @return string
 */
function nvx_schema_price_string( $amount ) {
	return number_format( (float) $amount, 2, '.', '' );
}

/**
 * Canonical page map for schema entities.
 *
 * IDs are staging/production fallbacks only. Runtime resolution prefers
 * permalink path, page URI, page template (Sede Local) and optional post meta
 * `_nvx_clinic_branch` so content moves do not require scattered ID edits.
 *
 * @return array{
 *   clinics: array<string, array{id:int, path:string}>,
 *   clinic_hub: array{id:int, path:string},
 *   treatments: array<string, array{id:int, path:string, schema:string}>
 * }
 */
/** Build one canonical route registry entry. */
function nvxSchemaRouteEntry( int $id, string $path ): array {
	return compact( 'id', 'path' );
}

/** Build one canonical treatment registry entry.
 *
 * @param int    $id The page identifier.
 * @param string $path The treatment page path.
 * @param string $schema The treatment schema key.
 *
 * @return array The treatment registry entry.
 */
function nvxSchemaTreatmentRegistryEntry( int $id, string $path, string $schema ): array {
	return compact( 'id', 'path', 'schema' );
}

/**
 * Builds the Schema.org route registry for clinics, the clinic hub, and treatments.
 *
 * @return array The registry containing resolved clinic, clinic hub, and treatment route entries.
 */
function nvx_schema_page_registry() {
	$registry = array(
		'clinics'    => array(),
		'clinic_hub' => array(),
		'treatments' => array(),
	);

	$routes = function_exists( 'nvx_catalog_json_resolved' )
		? nvx_catalog_json_resolved( 'routes.json' )
		: array();

	foreach ( $routes as $path => $config ) {
		// Skip the loader's synthetic keys (e.g. '_error') whose value is not
		// a route config array; accessing offsets on them warns under PHP 8.
		if ( ! is_array( $config ) || ( is_string( $path ) && 0 === strpos( $path, '_' ) ) ) {
			continue;
		}
		$group = $config['schema_group'] ?? '';
		if ( ! $group ) {
			continue;
		}

		if ( isset( $config['route_alias'] ) ) {
			continue;
		}

		$id          = isset( $config['post_id'] ) ? (int) $config['post_id'] : 0;
		$schema_id   = $config['schema_id'] ?? '';
		$schema_type = $config['schema_type'] ?? 'MedicalProcedure';

		if ( 'clinics' === $group && $schema_id ) {
			$registry['clinics'][ $schema_id ] = nvxSchemaRouteEntry( $id, $path );
		} elseif ( 'clinic_hub' === $group && empty( $registry['clinic_hub'] ) ) {
			$registry['clinic_hub'] = nvxSchemaRouteEntry( $id, $path );
		} elseif ( 'treatments' === $group && $schema_id ) {
			$registry['treatments'][ $schema_id ] = nvxSchemaTreatmentRegistryEntry( $id, $path, $schema_type );
		}
	}

	// Canonical fallbacks: if routes.json is missing/malformed, downstream
	// consumers (nvx_schema_clinics, nvx_schema_resolve_clinic_keys_from_hub)
	// read fixed keys without isset() guards. Guarantee the structure exists so
	// the schema graph never emits empty url/@id or triggers undefined-key warnings.
	// Validate the path (not just the entry) so a malformed route with an empty
	// path still falls back to the canonical value; nvx_schema_clinics() feeds
	// these paths directly into home_url().
	if ( empty( $registry['clinics']['chamberi']['path'] ) ) {
		$registry['clinics']['chamberi'] = nvxSchemaRouteEntry( 1543, '/medicina-estetica-chamberi/' );
	}
	if ( empty( $registry['clinics']['goya']['path'] ) ) {
		$registry['clinics']['goya'] = nvxSchemaRouteEntry( 1537, '/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/' );
	}
	if ( empty( $registry['clinic_hub']['path'] ) ) {
		$registry['clinic_hub'] = nvxSchemaRouteEntry( 1399, '/clinicas-de-medicina-estetica-nuvanx/' );
	}

	return $registry;
}

/**
 * Normalize a path for registry comparisons.
 *
 * @param string $path URL path or page URI.
 * @return string Leading/trailing-slash form, e.g. `/foo/bar/`.
 */
function nvx_schema_normalize_path( $path ) {
	$path = (string) $path;
	$path = strtok( $path, '?' );
	$path = '/' . trim( $path, '/' ) . '/';

	return ( '/' === $path || '//' === $path ) ? '/' : $path;
}

function nvx_schema_path_from_page_id( int $page_id ): string {
	$permalink = get_permalink( $page_id );
	if ( is_string( $permalink ) && '' !== $permalink ) {
		$home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$home_path = is_string( $home_path ) ? untrailingslashit( $home_path ) : '';
		$page_path = wp_parse_url( $permalink, PHP_URL_PATH );
		$page_path = is_string( $page_path ) ? $page_path : '';

		if ( '' !== $home_path && 0 === strpos( $page_path, $home_path ) ) {
			$page_path = substr( $page_path, strlen( $home_path ) );
		}

		return nvx_schema_normalize_path( $page_path );
	}

	$uri = get_page_uri( $page_id );
	return ( is_string( $uri ) && '' !== $uri ) ? nvx_schema_normalize_path( $uri ) : '';
}

/**
 * Current request path relative to the site home.
 *
 * @param int $page_id Queried page ID when available.
 * @return string
 */
function nvx_schema_current_path( $page_id = 0 ) {
	if ( $page_id > 0 ) {
		$path = nvx_schema_path_from_page_id( (int) $page_id );
		if ( '' !== $path ) {
			return $path;
		}
	}

	$request = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	return nvx_schema_normalize_path( $request );
}

/**
 * Whether the current path matches a registered path (exact or nested).
 *
 * @param string $current Normalized current path.
 * @param string $target  Registered path.
 * @return bool
 */
function nvx_schema_path_matches( $current, $target ) {
	$current = nvx_schema_normalize_path( $current );
	$target  = nvx_schema_normalize_path( $target );

	if ( $current === $target ) {
		return true;
	}

	// Nested clinic under hub, e.g. goya under clinicas-...
	return '/' !== $target && 0 === strpos( $current, $target );
}

/**
 * Whether the page uses the Sede Local template.
 *
 * @param int $page_id Page ID.
 * @return bool
 */
function nvx_schema_is_sede_template( $page_id ) {
	if ( $page_id <= 0 || ! function_exists( 'get_page_template_slug' ) ) {
		return false;
	}

	$slug = (string) get_page_template_slug( $page_id );

	return (bool) preg_match( '#(^|/)page-sede\.php$#', $slug );
}

function nvx_schema_resolve_clinic_keys_from_meta( int $page_id, array $registry ): array {
	if ( $page_id <= 0 ) {
		return array();
	}
	$meta = strtolower( trim( (string) get_post_meta( $page_id, '_nvx_clinic_branch', true ) ) );
	if ( 'all' === $meta || 'both' === $meta ) {
		return array_keys( $registry['clinics'] );
	} elseif ( isset( $registry['clinics'][ $meta ] ) ) {
		return array( $meta );
	}
	return array();
}

function nvx_schema_resolve_clinic_keys_from_registry( int $page_id, string $path, array $registry ): array {
	$matched = array();
	foreach ( $registry['clinics'] as $key => $entry ) {
		if ( (int) $entry['id'] === $page_id || nvx_schema_path_matches( $path, $entry['path'] ) ) {
			$matched[] = $key;
		}
	}
	return ! empty( $matched ) ? array_values( array_unique( $matched ) ) : array();
}

function nvx_schema_resolve_clinic_keys_from_hub( int $page_id, string $path, array $registry ): array {
	$hub = $registry['clinic_hub'];
	if ( (int) $hub['id'] === $page_id || nvx_schema_path_matches( $path, $hub['path'] ) || nvx_schema_is_sede_template( $page_id ) ) {
		return array_keys( $registry['clinics'] );
	}
	return array();
}

/**
 * Resolve which clinic branch keys apply on the current page.
 *
 * Order: front/hub → post meta → sede template + path → path/ID registry.
 *
 * @param int $page_id Current page ID.
 * @return string[] Empty, one key, or both clinic keys (chamberi, goya).
 */
function nvx_schema_resolve_clinic_keys( $page_id ) {
	$registry = nvx_schema_page_registry();
	$path     = nvx_schema_current_path( $page_id );

	if ( is_front_page() ) {
		return array_keys( $registry['clinics'] );
	}

	$keys = nvx_schema_resolve_clinic_keys_from_meta( (int) $page_id, $registry );

	if ( empty( $keys ) ) {
		$keys = nvx_schema_resolve_clinic_keys_from_registry( (int) $page_id, $path, $registry );
	}

	if ( empty( $keys ) ) {
		$keys = nvx_schema_resolve_clinic_keys_from_hub( (int) $page_id, $path, $registry );
	}

	return $keys;
}

/**
 * Resolve a treatment registry key for the current page, if any.
 *
 * @param int $page_id Current page ID.
 * @return string|null Registry key or null.
 */
function nvx_schema_resolve_treatment_key( $page_id ) {
	$registry = nvx_schema_page_registry();
	$path     = nvx_schema_current_path( $page_id );

	foreach ( $registry['treatments'] as $key => $entry ) {
		$id_match   = ! empty( $entry['id'] ) && (int) $entry['id'] === $page_id;
		$path_match = nvx_schema_path_matches( $path, $entry['path'] );
		if ( $id_match || $path_match ) {
			return $key;
		}
	}

	return null;
}

/**
 * Return true when a Schema.org @type contains the requested type.
 *
 * @param mixed  $types Schema @type value.
 * @param string $type  Type to locate.
 * @return bool
 */
function nvx_schema_has_type( $types, $type ) {
	$types = is_array( $types ) ? $types : array( $types );

	return in_array( $type, $types, true );
}

/**
 * Append a type without discarding Yoast's original type.
 *
 * @param mixed  $types Schema @type value.
 * @param string $type  Type to append.
 * @return array
 */
function nvx_schema_add_type( $types, $type ) {
	$types = is_array( $types ) ? $types : array( $types );
	$types = array_values( array_filter( $types ) );

	if ( ! in_array( $type, $types, true ) ) {
		$types[] = $type;
	}

	return $types;
}

/**
 * Return the canonical branch definitions used by visible content and Schema.
 *
 * Coordinates are intentionally omitted until independently verified against
 * the official location records. Google accepts address as the required local
 * business location field and treats geo as recommended rather than required.
 *
 * @return array
 */
function nvx_schema_clinics() {
	$registry = nvx_schema_page_registry();
	$config   = function_exists( 'nvx_get_clinics_config' ) ? nvx_get_clinics_config() : array();

	$ch = isset( $config['chamberi'] ) ? $config['chamberi'] : array();
	$go = isset( $config['goya'] ) ? $config['goya'] : array();

	return array(
		'chamberi' => array(
			'@type'                     => 'MedicalClinic',
			'@id'                       => home_url( '/#/schema/medical-clinic/chamberi' ),
			'name'                      => $ch['name'] ?? 'NUVANX Medicina Estética Láser — Chamberí',
			'branchCode'                => 'chamberi',
			'url'                       => home_url( $registry['clinics']['chamberi']['path'] ),
			'telephone'                 => $ch['phone_href'] ?? '+34669319836',
			'email'                     => NVX_CONTACT_EMAIL,
			'address'                   => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => $ch['address'] ?? 'Calle de Fernández de la Hoz, 4, Bajo Derecha',
				'addressLocality' => $ch['locality'] ?? 'Madrid',
				'addressRegion'   => 'Comunidad de Madrid',
				'postalCode'      => $ch['postal_code'] ?? '28010',
				'addressCountry'  => 'ES',
			),
			'geo'                       => array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => $ch['latitude'] ?? 40.431204,
				'longitude' => $ch['longitude'] ?? -3.693425,
			),
			'identifier'                => array(
				'@type'      => 'PropertyValue',
				'propertyID' => 'Registro sanitario de la Comunidad de Madrid',
				'value'      => $ch['reg'] ?? 'CS20144',
			),
			'hasMap'                    => 'https://www.google.com/maps/search/?api=1&query=NUVANX%20Medicina%20Est%C3%A9tica%20L%C3%A1ser%20C%2F%20de%20Fern%C3%A1ndez%20de%20la%20Hoz%204%2028010%20Madrid',
			'areaServed'                => array( 'Chamberí', 'Almagro', 'Trafalgar', 'Madrid' ),
			'openingHoursSpecification' => array(
				array(
					'@type'     => 'OpeningHoursSpecification',
					'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ),
					'opens'     => '12:00',
					'closes'    => '20:00',
				),
				array(
					'@type'     => 'OpeningHoursSpecification',
					'dayOfWeek' => 'Saturday',
					'opens'     => '10:00',
					'closes'    => '18:00',
				),
			),
			'sameAs'                    => array(
				'https://www.doctoralia.es/clinicas/nuvanx-medicina-estetica-laser',
			),
		),
		'goya'     => array(
			'@type'                     => 'MedicalClinic',
			'@id'                       => home_url( '/#/schema/medical-clinic/goya' ),
			'name'                      => $go['name'] ?? 'NUVANX Medicina Estética Láser — Goya · Barrio Salamanca',
			'branchCode'                => 'goya',
			'url'                       => home_url( $registry['clinics']['goya']['path'] ),
			'telephone'                 => $go['phone_href'] ?? '+34647505107',
			'email'                     => NVX_CONTACT_EMAIL,
			'address'                   => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => $go['address'] ?? 'Calle de Fernán González, 26',
				'addressLocality' => $go['locality'] ?? 'Madrid',
				'addressRegion'   => 'Comunidad de Madrid',
				'postalCode'      => $go['postal_code'] ?? '28009',
				'addressCountry'  => 'ES',
			),
			'geo'                       => array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => $go['latitude'] ?? 40.423912,
				'longitude' => $go['longitude'] ?? -3.675648,
			),
			'identifier'                => array(
				'@type'      => 'PropertyValue',
				'propertyID' => 'Registro sanitario de la Comunidad de Madrid',
				'value'      => $go['reg'] ?? 'CS20073',
			),
			'hasMap'                    => 'https://www.google.com/maps/search/?api=1&query=NUVANX%20Goya%20C%2F%20de%20Fern%C3%A1n%20Gonz%C3%A1lez%2026%2028009%20Madrid',
			'areaServed'                => array( 'Goya', 'Barrio de Salamanca', 'Lista', 'Recoletos', 'Madrid' ),
			'openingHoursSpecification' => array(
				array(
					'@type'     => 'OpeningHoursSpecification',
					'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ),
					'opens'     => '11:00',
					'closes'    => '20:00',
				),
			),
			'sameAs'                    => array(
				'https://www.doctoralia.es/clinicas/nuvanx-medicina-estetica-laser',
			),
		),
	);
}

/**
 * Find the Yoast Organization node and return its index and identifier.
 *
 * @param array $graph Yoast Schema graph.
 * @return array{index:int|null,id:string}
 */
function nvx_schema_find_organization( $graph ) {
	foreach ( $graph as $index => $piece ) {
		if (
			isset( $piece['@type'], $piece['@id'] )
			&& nvx_schema_has_type( $piece['@type'], 'Organization' )
			&& ! nvx_schema_has_type( $piece['@type'], 'WebSite' )
		) {
			return array(
				'index' => $index,
				'id'    => $piece['@id'],
			);
		}
	}

	return array(
		'index' => null,
		'id'    => home_url( '/#/schema/organization/nuvanx' ),
	);
}

/**
 * FAQ copy keyed by treatment registry keys.
 * Must mirror visible page FAQs (HTML + FAQPage). Answers transactional questions
 * that generative engines cite (precio, duración, recuperación, límites).
 *
 * @return array<string, array<int, array{q:string,a:string}>>
 */
function nvx_schema_faq_catalog() {
	$from   = nvx_format_price_eur( nvx_endolift_price_from_eur() );
	$papada = nvx_format_price_eur( nvx_endolift_price_papada_eur() );

	// Only keys that render the same Q/A in visible HTML (Endolift module).
	// Do not add EXION here until the EXION page prints the same pairs.
	return array(
		'endolift_facial' => array(
			array(
				'q' => '¿Cuánto cuesta el Endolift® facial en NUVANX Madrid?',
				'a' => 'PVP con IVA incluido desde ' . $from . ' € (ojeras). Papada y marcación mandibular: ' . $papada . ' € cada una. Full Face y combos en la tabla de tarifas de la página. El presupuesto se cierra tras valoración anatómica presencial.',
			),
			array(
				'q' => '¿Endolift® es para cualquier papada o flacidez?',
				'a' => 'No. Indicado en flacidez leve–moderada y grasa submentoniana seleccionada. La ptosis severa con exceso cutáneo se deriva a cirugía plástica; no se fuerza el láser.',
			),
			array(
				'q' => '¿Cuál es la durabilidad real de los resultados del Endolift®?',
				'a' => 'Al inducir colágeno profundo, no se comporta como un relleno temporal. La firmeza suele sostenerse entre 18 meses y 3 años según envejecimiento, sol, tabaquismo y genética. El seguimiento personaliza expectativas.',
			),
			array(
				'q' => '¿El Endolift® sustituye al ácido hialurónico?',
				'a' => 'No. Planos complementarios: Endolift® tensa piel y tejido conectivo y puede reducir grasa; rellenos o inductores aportan soporte volumétrico. Criterio NUVANX: tensar primero y rellenar después solo si está indicado.',
			),
			array(
				'q' => '¿Es doloroso?',
				'a' => 'Un poco de calor y algo de presión, nada más — usamos anestesia local precisamente para que no duela. Si te preocupa el dolor, dínoslo en la consulta: se puede ajustar.',
			),
		),
	);
}

/**
 * Return an FAQPage node that exactly mirrors visible page content.
 *
 * @param int $page_id Current page ID.
 * @return array|null
 */
function nvx_schema_faq_node( $page_id ) {
	$questions = array();
	$faq_id    = get_permalink( $page_id ) . '#faq';
	$faq_url   = get_permalink( $page_id );

	if ( is_front_page() && function_exists( 'nvx_home_faq_v2_catalog' ) ) {
		$questions = nvx_home_faq_v2_catalog();
		$faq_id    = home_url( '/#faq' );
		$faq_url   = home_url( '/' );
	} else {
		$key     = nvx_schema_resolve_treatment_key( $page_id );
		$catalog = nvx_schema_faq_catalog();
		if ( null !== $key && ! empty( $catalog[ $key ] ) ) {
			$questions = $catalog[ $key ];
		}
	}

	$entities = array();
	foreach ( $questions as $q ) {
		if ( ! empty( $q['q'] ) && ! empty( $q['a'] ) ) {
			$entities[] = array(
				'@type'          => 'Question',
				'name'           => $q['q'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $q['a'],
				),
			);
		}
	}

	if ( empty( $entities ) ) {
		return null;
	}

	return array(
		'@type'      => 'FAQPage',
		'@id'        => $faq_id,
		'url'        => $faq_url,
		'mainEntity' => $entities,
	);
}

function nvx_schema_treatment_node_laser( string $key, string $permalink, string $organization_id ): ?array {
	$label_from   = nvx_format_price_eur( nvx_endolift_price_from_eur() );
	$label_papada = nvx_format_price_eur( nvx_endolift_price_papada_eur() );
	$label_f      = function_exists( 'nvx_co2_price_facial_eur' ) ? nvx_format_price_eur( nvx_co2_price_facial_eur() ) : '';
	$label_b      = function_exists( 'nvx_co2_price_body_eur' ) ? nvx_format_price_eur( nvx_co2_price_body_eur() ) : '';

	if ( 'endolift_facial' === $key ) {
		return array(
			'@type'             => array( 'MedicalProcedure', 'Service' ),
			'@id'               => $permalink . NVX_SD_ID_MEDICAL_PROCEDURE,
			'name'              => 'Endolift® facial para papada y línea mandibular',
			'alternateName'     => array( NVX_SD_ENDOLIFT_FACIAL, 'Láser intersticial facial' ),
			'url'               => $permalink,
			'mainEntityOfPage'  => array( '@id' => $permalink ),
			'provider'          => array( '@id' => $organization_id ),
			'description'       => 'Procedimiento médico mínimamente invasivo con microfibra láser subdérmica para lipólisis selectiva y retracción térmica en papada, contorno mandibular y cuello, indicado solo tras valoración anatómica. PVP papada/marcación mandibular desde ' . $label_papada . ' €; tarifas faciales desde ' . $label_from . ' €.',
			'bodyLocation'      => array( 'Papada', 'Línea mandibular', 'Cuello', 'Óvalo facial' ),
			'procedureType'     => 'https://schema.org/MinimallyInvasiveProcedure',
			'preparation'       => 'Valoración médica presencial de anatomía, calidad de piel, grasa submentoniana, ptosis y expectativas. Exclusión de ptosis severa con exceso cutáneo que requiera cirugía.',
			'howPerformed'      => 'Tras anestesia local se inserta microfibra óptica de 200–300 micras y se aplica energía láser intersticial en patrón vectorial subdérmico adaptado a la zona.',
			'followup'          => 'Seguimiento clínico protocolizado (típicamente semanas 4 y 8 y control posterior). Reincorporación habitual en menos de 24 h; edema o inflamación pueden durar 3–7 días.',
			'indication'        => array(
				array(
					'@type' => 'MedicalIndication',
					'name'  => 'Flacidez facial leve a moderada',
				),
				array(
					'@type' => 'MedicalIndication',
					'name'  => 'Adiposidad submentoniana (papada) seleccionada',
				),
			),
			'relevantCondition' => array(
				array(
					'@type' => 'MedicalCondition',
					'name'  => 'Flacidez facial',
				),
				array(
					'@type' => 'MedicalCondition',
					'name'  => 'Adiposidad submentoniana',
				),
			),
		);
	}

	if ( 'endolaser_corporal' === $key ) {
		return array(
			'@type'             => array( 'MedicalProcedure', 'Service' ),
			'@id'               => $permalink . NVX_SD_ID_MEDICAL_PROCEDURE,
			'name'              => 'Endoláser corporal — destrucción de grasa localizada y retracción cutánea',
			'alternateName'     => array( 'Laserlipólisis corporal', 'Endoláser Madrid' ),
			'url'               => $permalink,
			'mainEntityOfPage'  => array( '@id' => $permalink ),
			'provider'          => array( '@id' => $organization_id ),
			'description'       => 'Laserlipólisis médica intervencionista: lipólisis de adipocitos y estímulo de retracción dérmica en un acto ambulatorio por zonas (abdomen, flancos, muslos, brazos, submandibular). No trata obesidad ni pérdida masiva de peso; el presupuesto se personaliza tras valoración.',
			'bodyLocation'      => array( 'Abdomen', 'Flancos', 'Cara interna de muslos', 'Rodillas', 'Brazos', 'Región submandibular' ),
			'procedureType'     => 'https://schema.org/MinimallyInvasiveProcedure',
			'preparation'       => 'Peso estable, grasa focal y flacidez leve–moderada. Exclusión de exceso cutáneo severo (derivación a cirugía excisional, p. ej. abdominoplastia).',
			'howPerformed'      => 'Bajo anestesia local se introduce fibra láser en tejido subcutáneo para lipólisis selectiva y estímulo térmico de retracción en la cuadrícula de zonas planificada.',
			'followup'          => 'Cuidados post-procedimiento y revisiones según zona y protocolo médico.',
			'indication'        => array(
				array(
					'@type' => 'MedicalIndication',
					'name'  => 'Adiposidad localizada resistente a dieta y ejercicio',
				),
				array(
					'@type' => 'MedicalIndication',
					'name'  => 'Flacidez cutánea leve a moderada asociada a pérdida de volumen local',
				),
			),
			'relevantCondition' => array(
				array(
					'@type' => 'MedicalCondition',
					'name'  => 'Adiposidad localizada',
				),
				array(
					'@type' => 'MedicalCondition',
					'name'  => 'Flacidez cutánea corporal leve-moderada',
				),
			),
		);
	}

	if ( 'laser_co2' === $key ) {
		return array(
			'@type'             => array( 'MedicalProcedure', 'Service' ),
			'@id'               => $permalink . NVX_SD_ID_MEDICAL_PROCEDURE,
			'name'              => 'Láser CO₂ fraccionado — resurfacing epidérmico y cicatrices',
			'alternateName'     => array( 'CO₂ fraccionado Madrid', 'Resurfacing láser CO₂' ),
			'url'               => $permalink,
			'mainEntityOfPage'  => array( '@id' => $permalink ),
			'provider'          => array( '@id' => $organization_id ),
			'description'       => 'Ablación fraccionada con microcolumnas de vaporización y tejido sano peri-lesional. Indicado en cicatrices atróficas de acné, poros, textura irregular y fotodaño. Downtime típico 4–7 días; remodelación colagénica 4–6 semanas. PVP sesión facial desde ' . $label_f . ' €; corporal ' . $label_b . ' € (IVA incl.).',
			'bodyLocation'      => 'Piel facial y zonas cutáneas seleccionadas',
			'procedureType'     => 'https://schema.org/PercutaneousProcedure',
			'preparation'       => 'Evaluación de fototipo, inflamación, bronceado, medicación y objetivo (cicatriz, textura, fotodaño). Compromiso con downtime y fotoprotección.',
			'howPerformed'      => 'Microhaces de CO₂ crean columnas de vaporización térmica fraccionada; el tejido circundante acelera la curación y estimula colágeno I y III.',
			'followup'          => 'Días 1–3 eritema y patrón punteado; días 4–7 descamación; desde día 7 recuperación visual habitual y remodelación progresiva 4–6 semanas.',
			'indication'        => array(
				array(
					'@type' => 'MedicalIndication',
					'name'  => 'Cicatrices atróficas de acné',
				),
				array(
					'@type' => 'MedicalIndication',
					'name'  => 'Poros dilatados y textura irregular',
				),
				array(
					'@type' => 'MedicalIndication',
					'name'  => 'Fotodaño y elastosis solar',
				),
			),
			'relevantCondition' => array(
				array(
					'@type' => 'MedicalCondition',
					'name'  => 'Cicatrices atróficas de acné',
				),
				array(
					'@type' => 'MedicalCondition',
					'name'  => 'Fotodaño cutáneo',
				),
			),
		);
	}

	return null;
}

function nvx_schema_treatment_node_btl( string $key, string $permalink, string $organization_id ): ?array {
	if ( 'exion_btl' === $key ) {
		return array(
			'@type'            => array( 'MedicalProcedure', 'Service' ),
			'@id'              => $permalink . NVX_SD_ID_SERVICE,
			'name'             => 'EXION® BTL en Madrid',
			'serviceType'      => 'Protocolos médicos con plataforma EXION® BTL',
			'url'              => $permalink,
			'mainEntityOfPage' => array( '@id' => $permalink ),
			'provider'         => array( '@id' => $organization_id ),
			'description'      => 'Plataforma médica BTL con aplicadores Fractional RF, Face y Body para protocolos de textura, firmeza y calidad cutánea según diagnóstico. El presupuesto se documenta tras la valoración médica según aplicador, zona y plan de sesiones.',
			'procedureType'    => 'https://schema.org/NoninvasiveProcedure',
			'areaServed'       => 'Madrid',
		);
	}

	$btl_detail_keys = array( 'exion_face', 'exion_body', 'exion_fractional', 'emfusion' );
	if ( in_array( $key, $btl_detail_keys, true ) && function_exists( 'nvx_btl_detail_registry' ) ) {
		$slug_map = array(
			'exion_face'       => 'exion-face',
			'exion_body'       => 'exion-body',
			'exion_fractional' => 'exion-fractional',
			'emfusion'         => 'emfusion',
		);
		$slug     = $slug_map[ $key ] ?? '';
		$reg      = nvx_btl_detail_registry();
		if ( $slug && ! empty( $reg[ $slug ] ) && is_array( $reg[ $slug ] ) ) {
			$cfg = $reg[ $slug ];
			return array(
				'@type'            => 'Service',
				'@id'              => $permalink . NVX_SD_ID_SERVICE,
				'name'             => $cfg['schema_name'],
				'serviceType'      => $cfg['schema_type'],
				'url'              => $permalink,
				'mainEntityOfPage' => array( '@id' => $permalink ),
				'provider'         => array( '@id' => $organization_id ),
				'description'      => $cfg['schema_desc'],
				'areaServed'       => 'Madrid',
			);
		}
	}

	if ( 'exilite_btl' === $key ) {
		return array(
			'@type'            => 'Service',
			'@id'              => $permalink . NVX_SD_ID_SERVICE,
			'name'             => 'BTL EXILITE™ IPL en Madrid',
			'serviceType'      => 'Protocolos médicos con plataforma BTL EXILITE™ IPL',
			'url'              => $permalink,
			'mainEntityOfPage' => array( '@id' => $permalink ),
			'provider'         => array( '@id' => $organization_id ),
			'description'      => 'Luz pulsada intensa (IPL) para manchas, rojeces y lesiones pigmentarias o vasculares superficiales seleccionadas tras diagnóstico. No es un láser.',
			'areaServed'       => 'Madrid',
		);
	}

	return null;
}

function nvx_schema_treatment_node( $page_id, $organization_id ) {
	$key = nvx_schema_resolve_treatment_key( $page_id );
	if ( null === $key ) {
		return null;
	}

	$permalink = get_permalink( $page_id );

	$laser_node = nvx_schema_treatment_node_laser( $key, $permalink, $organization_id );
	if ( null !== $laser_node ) {
		return $laser_node;
	}

	return nvx_schema_treatment_node_btl( $key, $permalink, $organization_id );
}

/**
 * Director médico as Physician (E-E-A-T entity for GEO specialist queries).
 *
 * @param string $organization_id Organization @id.
 * @return array
 */
function nvx_schema_physician_director( $organization_id ) {
	$equipo      = home_url( NVX_SD_PATH_EQUIPO_MEDICO );
	$director_id = home_url( NVX_SD_PATH_EQUIPO_MEDICO . '#physician-rivera-tejeda' );
	$colegiado   = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';

	return array(
		'@type'            => array( 'Person', 'Physician' ),
		'@id'              => $director_id,
		'name'             => 'José Javier Rivera Tejeda',
		'honorificPrefix'  => 'Dr.',
		'jobTitle'         => 'Director médico e investigador clínico aplicado · NUVANX Madrid',
		'description'      => 'Dirección médica de NUVANX. Láser intersticial (Endolift®, laserlipólisis), CO₂ fraccionado, geometría facial con inductores y tricología. ' . NVX_SD_LABEL_COLEGIADO_PREFIX . $colegiado . '. Perfil público en Doctoralia.',
		'url'              => $equipo . '#physician-rivera-tejeda',
		'medicalSpecialty' => array( 'Aesthetic Medicine', 'Laser Medicine' ),
		'worksFor'         => array( '@id' => $organization_id ),
		'hasCredential'    => array(
			array(
				'@type'              => 'EducationalOccupationalCredential',
				'credentialCategory' => NVX_SD_LABEL_NUM_COLEGIADO,
				'identifier'         => $colegiado,
				'name'               => NVX_SD_LABEL_COLEGIADO_PREFIX . $colegiado,
			),
			array(
				'@type' => 'EducationalOccupationalCredential',
				'name'  => 'Máster Universitario en Medicina Estética — Universidad Complutense de Madrid',
			),
			array(
				'@type' => 'EducationalOccupationalCredential',
				'name'  => 'Máster en Tricología y Cirugía Capilar — AMIR',
			),
		),
		'alumniOf'         => array(
			array(
				'@type' => 'CollegeOrUniversity',
				'name'  => 'Universidad Complutense de Madrid',
			),
			array(
				'@type' => 'EducationalOrganization',
				'name'  => 'AMIR',
			),
		),
		'knowsAbout'       => array(
			NVX_SD_ENDOLIFT_FACIAL,
			'Laserlipólisis',
			NVX_SD_ENDOLASER_CORPORAL,
			NVX_SD_LASER_CO2_FRACCIONADO,
			'Medicina estética láser',
			'Marcación mandibular con láser',
			'Inductores de colágeno',
			'Tricología médica',
			NVX_SD_MEDICINA_REGENERATIVA,
		),
		'sameAs'           => array(
			'https://www.doctoralia.es/jose-javier-rivera-tejeda/medico-estetico/madrid',
		),
	);
}

/**
 * Dra. Ivon Yamileth Rivera Deras — Physician + Researcher (E-E-A-T / GEO).
 *
 * @param string $organization_id Organization @id.
 * @return array
 */
function nvx_schema_physician_ivon( $organization_id ) {
	$equipo    = home_url( NVX_SD_PATH_EQUIPO_MEDICO );
	$ivon_id   = home_url( NVX_SD_PATH_EQUIPO_MEDICO . '#physician-rivera-deras' );
	$colegiado = defined( 'NVX_IVON_COLEGIADO' ) ? NVX_IVON_COLEGIADO : '284621525';

	return array(
		'@type'            => array( 'Person', 'Physician' ),
		'@id'              => $ivon_id,
		'name'             => 'Ivon Yamileth Rivera Deras',
		'honorificPrefix'  => 'Dra.',
		'jobTitle'         => 'Especialista en geriatría, longevidad y well-aging · NUVANX',
		'description'      => NVX_SD_LABEL_COLEGIADO_PREFIX . $colegiado . '. Médico especialista (FEA) en Hospital Universitario La Paz (Recuperación Funcional / Hospital de Día Geriátrico) y Hospital Central de la Cruz Roja. Investigadora y consultora para OXON Epidemiology; coordinación científica SEMEG; colaboración EuGMS; profesora UEM. Coautora de obras de bioética y geriatría clínica. Integra well-aging basado en evidencia en NUVANX.',
		'url'              => $equipo . '#physician-rivera-deras',
		'medicalSpecialty' => 'https://schema.org/Geriatric',
		'worksFor'         => array(
			array( '@id' => $organization_id ),
			array(
				'@type' => 'Hospital',
				'name'  => 'Hospital Universitario La Paz',
			),
			array(
				'@type' => 'Hospital',
				'name'  => 'Hospital Central de la Cruz Roja San José y Santa Adela',
			),
		),
		'hasCredential'    => array(
			array(
				'@type'              => 'EducationalOccupationalCredential',
				'credentialCategory' => NVX_SD_LABEL_NUM_COLEGIADO,
				'identifier'         => $colegiado,
				'name'               => 'Colegiada ICOMEM ' . $colegiado,
			),
		),
		'memberOf'         => array(
			array(
				'@type' => 'MedicalOrganization',
				'name'  => NVX_SD_SOCIEDAD_SEMEG,
			),
			array(
				'@type' => 'Organization',
				'name'  => 'European Geriatric Medicine Society (EuGMS)',
			),
			array(
				'@type' => 'Organization',
				'name'  => 'OXON Epidemiology',
			),
		),
		'alumniOf'         => array(
			array(
				'@type' => 'CollegeOrUniversity',
				'name'  => 'Universidad Europea de Madrid',
			),
		),
		'knowsAbout'       => array(
			'Geriatría',
			'Well-aging',
			'Longevidad',
			'Medicina preventiva del envejecimiento',
			'Deterioro cognitivo',
			'Recuperación funcional geriátrica',
			'Real-World Evidence',
		),
	);
}

/**
 * Dr. Fabio Quiñónez Bareiro — Physician + Researcher (E-E-A-T / GEO).
 *
 * @param string $organization_id Organization @id.
 * @return array
 */
function nvx_schema_physician_fabio( $organization_id ) {
	$equipo    = home_url( NVX_SD_PATH_EQUIPO_MEDICO );
	$fabio_id  = home_url( NVX_SD_PATH_EQUIPO_MEDICO . '#physician-quinonez-bareiro' );
	$colegiado = defined( 'NVX_FABIO_COLEGIADO' ) ? NVX_FABIO_COLEGIADO : '282877543';

	return array(
		'@type'            => array( 'Person', 'Physician' ),
		'@id'              => $fabio_id,
		'name'             => 'Fabio Augusto Quiñónez Bareiro',
		'honorificPrefix'  => 'Dr.',
		'jobTitle'         => 'Especialista en geriatría, gerontología y paciente complejo · NUVANX',
		'description'      => NVX_SD_LABEL_COLEGIADO_PREFIX . $colegiado . '. Doctor por la UAM e investigador en el CIBERFES. FEA en Geriatría (Hospital Virgen del Valle, Toledo). Experto en fisiología del envejecimiento y paciente complejo. Integra longevidad y medicina regenerativa en NUVANX.',
		'url'              => $equipo . '#physician-quinonez-bareiro',
		'medicalSpecialty' => 'https://schema.org/Geriatric',
		'worksFor'         => array(
			array( '@id' => $organization_id ),
			array(
				'@type' => 'Hospital',
				'name'  => 'Hospital Virgen del Valle (Toledo)',
			),
		),
		'hasCredential'    => array(
			array(
				'@type'              => 'EducationalOccupationalCredential',
				'credentialCategory' => NVX_SD_LABEL_NUM_COLEGIADO,
				'identifier'         => $colegiado,
				'name'               => 'Colegiado ICOMEM ' . $colegiado,
			),
		),
		'memberOf'         => array(
			array(
				'@type' => 'MedicalOrganization',
				'name'  => 'CIBER de Fragilidad y Envejecimiento Saludable (CIBERFES)',
			),
			array(
				'@type' => 'MedicalOrganization',
				'name'  => NVX_SD_SOCIEDAD_SEMEG,
			),
		),
		'alumniOf'         => array(
			array(
				'@type' => 'CollegeOrUniversity',
				'name'  => 'Universidad Autónoma de Madrid',
			),
		),
		'knowsAbout'       => array(
			'Geriatría',
			'Gerontología',
			'Paciente complejo',
			'Fragilidad',
			'Deterioro cognitivo',
			'Longevidad',
			'Fisiología del envejecimiento',
		),
	);
}

/**
 * Creative works authored by Dra. Ivon (equipo page graph density).
 *
 * @param string $author_id Physician @id.
 * @return array<int, array>
 */
function nvx_schema_ivon_publications( $author_id ) {
	return array(
		array(
			'@type'  => 'Book',
			'@id'    => home_url( '/equipo-medico/#work-inmortalidad-sin-juventud' ),
			'name'   => 'El tormento de la inmortalidad sin juventud',
			'author' => array( '@id' => $author_id ),
		),
		array(
			'@type'     => 'Book',
			'@id'       => home_url( '/equipo-medico/#work-manual-caidas-semeg' ),
			'name'      => 'Manual de manejo de personas mayores que sufren caídas',
			'author'    => array( '@id' => $author_id ),
			'publisher' => array(
				'@type' => 'Organization',
				'name'  => 'Sociedad Española de Medicina Geriátrica (SEMEG)',
			),
		),
	);
}

/**
 * Creative works / thesis associated with Dr. Fabio (equipo page graph density).
 *
 * @param string $author_id Physician @id.
 * @return array<int, array>
 */
function nvx_schema_fabio_publications( $author_id ) {
	return array(
		array(
			'@type'              => 'Thesis',
			'@id'                => home_url( '/equipo-medico/#work-fabio-tesis-uam' ),
			'name'               => 'Disfunción vascular sub-clínica, declinar cognitivo y fragilidad',
			'author'             => array( '@id' => $author_id ),
			'inSupportOf'        => 'Ph.D.',
			'sourceOrganization' => array(
				'@type' => 'CollegeOrUniversity',
				'name'  => 'Universidad Autónoma de Madrid',
			),
		),
		array(
			'@type'       => 'ScholarlyArticle',
			'@id'         => home_url( '/equipo-medico/#work-fabio-itu-delirium' ),
			'name'        => '¿Será una infección del tracto urinario?',
			'author'      => array( '@id' => $author_id ),
			'description' => 'Diagnósticos diferenciales entre delírium e infección en el anciano.',
		),
	);
}

/**
 * Service catalog for home graph — cite-able list of protocols (with starting price when known).
 * No retail InStock spam; offers are informational reference tariffs.
 *
 * @param string $organization_id Organization @id.
 * @return array
 */
function nvx_schema_offer_catalog( $organization_id ) {
	$registry = nvx_schema_page_registry();
	$items    = array();
	$co2_from = function_exists( 'nvx_co2_price_facial_eur' ) ? nvx_co2_price_facial_eur() : null;

	$catalog_defs = array(
		'endolift_facial'    => array(
			'label' => NVX_SD_ENDOLIFT_FACIAL,
			'price' => nvx_endolift_price_from_eur(),
		),
		'endolaser_corporal' => array(
			'label' => NVX_SD_ENDOLASER_CORPORAL,
			'price' => null,
		),
		'laser_co2'          => array(
			'label' => NVX_SD_LASER_CO2_FRACCIONADO,
			'price' => $co2_from,
		),
		'exion_btl'          => array(
			'label' => 'EXION® BTL',
			'price' => null,
		),
		'exion_face'         => array(
			'label' => 'EXION® Face',
			'price' => null,
		),
		'exion_body'         => array(
			'label' => 'EXION® Body',
			'price' => null,
		),
		'exion_fractional'   => array(
			'label' => 'EXION® Fractional RF',
			'price' => null,
		),
		'emfusion'           => array(
			'label' => 'EMFUSION®',
			'price' => null,
		),
		'exilite_btl'        => array(
			'label' => 'BTL EXILITE™ IPL',
			'price' => null,
		),
	);

	foreach ( $catalog_defs as $key => $def ) {
		if ( empty( $registry['treatments'][ $key ]['path'] ) ) {
			continue;
		}
		$url   = home_url( $registry['treatments'][ $key ]['path'] );
		$offer = array(
			'@type'       => 'Offer',
			'itemOffered' => array(
				'@type' => 'Service',
				'name'  => $def['label'],
				'url'   => $url,
			),
			'url'         => $url,
			'areaServed'  => 'Madrid',
			'seller'      => array( '@id' => $organization_id ),
		);
		if ( null !== $def['price'] && $def['price'] > 0 ) {
			$offer['priceCurrency'] = 'EUR';
			$offer['price']         = nvx_schema_price_string( $def['price'] );
			$offer['description']   = 'Tarifa de referencia desde ' . nvx_format_price_eur( $def['price'] ) . ' € (presupuesto tras valoración).';
		}
		$items[] = $offer;
	}

	return array(
		'@type'           => 'OfferCatalog',
		'@id'             => home_url( '/#/schema/offer-catalog' ),
		'name'            => 'Protocolos médicos láser NUVANX',
		'itemListElement' => $items,
		'provider'        => array( '@id' => $organization_id ),
	);
}

/**
 * Whether director Physician should appear (home, equipo, treatment).
 *
 * @param int $page_id Current page ID.
 * @return bool
 */
function nvx_schema_should_emit_physician( $page_id ) {
	if ( is_front_page() || null !== nvx_schema_resolve_treatment_key( $page_id ) ) {
		return true;
	}

	$path = nvx_schema_current_path( $page_id );

	return nvx_schema_path_matches( $path, NVX_SD_PATH_EQUIPO_MEDICO ) || nvx_schema_path_matches( $path, '/dr-javier-rivera-tejeda/' );
}

/**
 * Whether Dra. Ivon Physician should appear (equipo + home for org trust; not every treatment).
 *
 * @param int $page_id Current page ID.
 * @return bool
 */
function nvx_schema_should_emit_physician_ivon( $page_id ) {
	if ( is_front_page() ) {
		return true;
	}

	$path = nvx_schema_current_path( $page_id );

	return nvx_schema_path_matches( $path, NVX_SD_PATH_EQUIPO_MEDICO );
}

/**
 * Whether Dr. Fabio Physician should appear (equipo + home for org trust).
 *
 * @param int $page_id Current page ID.
 * @return bool
 */
function nvx_schema_should_emit_physician_fabio( $page_id ) {
	if ( is_front_page() ) {
		return true;
	}

	$path = nvx_schema_current_path( $page_id );

	return nvx_schema_path_matches( $path, NVX_SD_PATH_EQUIPO_MEDICO ) || nvx_schema_path_matches( $path, '/dr-fabio-quinonez-bareiro/' );
}

/**
 * Builds array of physician nodes to emit for the current page.
 */
function nvx_schema_build_physicians( int $page_id, string $org_id ): array {
	$physicians = array();
	if ( nvx_schema_should_emit_physician( $page_id ) ) {
		$physicians[] = nvx_schema_physician_director( $org_id );
	}
	if ( nvx_schema_should_emit_physician_ivon( $page_id ) ) {
		$physicians[] = nvx_schema_physician_ivon( $org_id );
	}
	if ( nvx_schema_should_emit_physician_fabio( $page_id ) ) {
		$physicians[] = nvx_schema_physician_fabio( $org_id );
	}
	return $physicians;
}

/**
 * Enriches the main Organization node in Yoast schema graph.
 */
function nvx_schema_enrich_organization( array &$graph, int $index, array $all_clinics, array $physicians ): void {
	$cfg          = function_exists( 'nvx_get_clinics_config' ) ? nvx_get_clinics_config() : array();
	$chamberi_tel = $cfg['chamberi']['phone_href'] ?? '+34669319836';
	$goya_tel     = $cfg['goya']['phone_href'] ?? '+34647505107';

	$graph[ $index ]['@type']                  = nvx_schema_add_type( $graph[ $index ]['@type'], 'MedicalOrganization' );
	$graph[ $index ]['name']                   = 'NUVANX Medicina Estética Láser';
	$graph[ $index ]['alternateName']          = array( 'NUVANX', 'NUVANX Madrid', 'NUVANX Medicina Estética Láser Madrid' );
	$graph[ $index ]['url']                    = home_url( '/' );
	$graph[ $index ]['description']            = 'Centro médico de medicina estética láser y well-aging en Madrid (Chamberí y Goya · Barrio Salamanca). Protocolos Endolift®, endoláser, Láser CO₂ y EXION® BTL con dirección médica y criterio científico (geriatría preventiva / longevidad).';
	$graph[ $index ]['email']                  = NVX_CONTACT_EMAIL;
	$graph[ $index ]['telephone']              = $chamberi_tel;
	$graph[ $index ]['priceRange']             = '€€€';
	$graph[ $index ]['isAcceptingNewPatients'] = true;
	$graph[ $index ]['address']                = array( $all_clinics['chamberi']['address'], $all_clinics['goya']['address'] );
	$graph[ $index ]['contactPoint']           = array(
		array(
			'@type'             => 'ContactPoint',
			'contactType'       => 'Citas — Chamberí',
			'telephone'         => $chamberi_tel,
			'areaServed'        => 'ES',
			'availableLanguage' => array( 'es', 'en' ),
		),
		array(
			'@type'             => 'ContactPoint',
			'contactType'       => 'Citas — Goya · Barrio Salamanca',
			'telephone'         => $goya_tel,
			'areaServed'        => 'ES',
			'availableLanguage' => array( 'es', 'en' ),
		),
	);
	$graph[ $index ]['medicalSpecialty']       = array( 'Aesthetic Medicine', 'Laser Medicine', 'Geriatric Medicine' );
	$graph[ $index ]['knowsAbout']             = array(
		'Medicina estética',
		'Medicina estética láser',
		NVX_SD_ENDOLIFT_FACIAL,
		'Marcación mandibular con láser',
		NVX_SD_ENDOLASER_CORPORAL,
		NVX_SD_LASER_CO2_FRACCIONADO,
		'EXION® BTL',
		'BTL EXILITE™ IPL',
		NVX_SD_MEDICINA_REGENERATIVA,
		'Well-aging',
		'Geriatría preventiva',
		'Longevidad',
	);
	$graph[ $index ]['potentialAction']        = array(
		'@type'  => 'ReserveAction',
		'name'   => 'Reserva de valoración diagnóstica',
		'target' => array(
			'@type'          => 'EntryPoint',
			'urlTemplate'    => home_url( '/madrid/valoracion/' ),
			'inLanguage'     => 'es',
			'actionPlatform' => array(
				'https://schema.org/DesktopWebPlatform',
				'https://schema.org/MobileWebPlatform',
			),
		),
		'result' => array(
			'@type' => 'Reservation',
			'name'  => 'Cita médica presencial',
		),
	);

	if ( ! empty( $physicians ) ) {
		$employee_refs = array();
		foreach ( $physicians as $person ) {
			$employee_refs[] = array( '@id' => $person['@id'] );
		}
		$graph[ $index ]['employee'] = $employee_refs;
	}

	$existing_same_as          = isset( $graph[ $index ]['sameAs'] ) ? (array) $graph[ $index ]['sameAs'] : array();
	$existing_same_as[]        = 'https://www.doctoralia.es/clinicas/nuvanx-medicina-estetica-laser';
	$graph[ $index ]['sameAs'] = array_values( array_unique( array_filter( $existing_same_as ) ) );
}

/**
 * Clinic branch keys to attach for the current page.
 *
 * Home and equipo get both branches; other pages use path/meta resolution.
 *
 * @param int $page_id Current page ID.
 * @return string[]
 */
function nvx_schema_clinic_keys_for_page( int $page_id ): array {
	if ( is_front_page() ) {
		return array( 'chamberi', 'goya' );
	}

	$path = nvx_schema_current_path( $page_id );
	if ( nvx_schema_path_matches( $path, NVX_SD_PATH_EQUIPO_MEDICO ) ) {
		return array( 'chamberi', 'goya' );
	}

	return nvx_schema_resolve_clinic_keys( $page_id );
}

/**
 * Attaches clinic sub-organizations and offer catalog to the Yoast schema graph.
 *
 * subOrganization / department refs only include clinics actually appended to
 * the graph (no dangling @id when a single branch page is rendered).
 */
function nvx_schema_attach_clinics_graph( array &$graph, int $page_id, array $organization, array $all_clinics, array $physicians ): void {
	if ( null === $organization['index'] ) {
		return;
	}

	$clinic_keys = nvx_schema_clinic_keys_for_page( $page_id );
	if ( empty( $clinic_keys ) ) {
		return;
	}

	if ( is_front_page() ) {
		$catalog = nvx_schema_offer_catalog( $organization['id'] );
		$graph[ $organization['index'] ]['hasOfferCatalog'] = array( '@id' => $catalog['@id'] );
		$graph[] = $catalog;
	}

	$clinic_employees = array();
	foreach ( $physicians as $person ) {
		if ( ! empty( $person['@id'] ) ) {
			$clinic_employees[] = array( '@id' => $person['@id'] );
		}
	}

	$clinic_refs = array();
	foreach ( $clinic_keys as $key ) {
		if ( empty( $all_clinics[ $key ] ) ) {
			continue;
		}
		$clinic                       = $all_clinics[ $key ];
		$clinic['parentOrganization'] = array( '@id' => $organization['id'] );
		if ( ! empty( $clinic_employees ) ) {
			$clinic['employee'] = $clinic_employees;
		}
		$clinic_refs[] = array( '@id' => $clinic['@id'] );
		$graph[]       = $clinic;
	}

	if ( ! empty( $clinic_refs ) ) {
		$graph[ $organization['index'] ]['subOrganization'] = $clinic_refs;
	}
}

/**
 * Attaches publication nodes for team members if on equipo page.
 */
function nvx_schema_attach_publications( array &$graph, int $page_id, array $physicians ): void {
	if ( ! nvx_schema_path_matches( nvx_schema_current_path( $page_id ), NVX_SD_PATH_EQUIPO_MEDICO ) ) {
		return;
	}
	foreach ( $physicians as $person ) {
		if ( empty( $person['@id'] ) ) {
			continue;
		}
		if ( false !== strpos( $person['@id'], 'rivera-deras' ) ) {
			foreach ( nvx_schema_ivon_publications( $person['@id'] ) as $work ) {
				$graph[] = $work;
			}
		}
		if ( false !== strpos( $person['@id'], 'quinonez-bareiro' ) ) {
			foreach ( nvx_schema_fabio_publications( $person['@id'] ) as $work ) {
				$graph[] = $work;
			}
		}
	}
}

/**
 * Link WebPage.mainEntity to an entity @id when the page URL matches.
 *
 * This is the single graph-linking implementation shared by both schema passes.
 *
 * @param array  $graph     Schema graph.
 * @param string $pageUrl   Canonical page URL.
 * @param string $entityId  Entity node @id.
 * @return array Updated schema graph.
 */
function nvxSchemaLinkWebpageMainEntity( array $graph, string $pageUrl, string $entityId ): array {
	if ( '' === $pageUrl || '' === $entityId ) {
		return $graph;
	}

	$target = trailingslashit( $pageUrl );
	foreach ( $graph as $index => $piece ) {
		$types = isset( $piece['@type'] ) ? (array) $piece['@type'] : array();
		$url   = isset( $piece['url'] ) ? trailingslashit( (string) $piece['url'] ) : '';
		if ( in_array( 'WebPage', $types, true ) && $url === $target ) {
			$graph[ $index ]['mainEntity'] = array( '@id' => $entityId );
			break;
		}
	}

	return $graph;
}

/**
 * Attaches treatment and FAQ nodes to schema graph when applicable.
 */
function nvx_schema_attach_treatment_and_faq( array &$graph, int $page_id, string $org_id, ?array $physician ): void {
	$treatment = nvx_schema_treatment_node( $page_id, $org_id );
	if ( null !== $treatment ) {
		if ( null !== $physician ) {
			$treatment['performer']  = array( '@id' => $physician['@id'] );
			$treatment['reviewedBy'] = array( '@id' => $physician['@id'] );
		}
		$graph[] = $treatment;
		if ( ! empty( $treatment['@id'] ) && ! empty( $treatment['url'] ) ) {
			$graph = nvxSchemaLinkWebpageMainEntity( $graph, (string) $treatment['url'], (string) $treatment['@id'] );
		}
	}

	$faq = nvx_schema_faq_node( $page_id );
	if ( null !== $faq ) {
		$graph[] = $faq;
	}
}

/**
 * Add NUVANX medical locations and page entities to Yoast's canonical graph.
 *
 * @param array $graph Yoast Schema graph.
 * @return array
 */
function nvx_extend_yoast_schema_graph( $graph ) {
	if ( is_admin() || is_feed() || ( ! is_singular( 'page' ) && ! is_front_page() ) ) {
		return $graph;
	}

	$organization = nvx_schema_find_organization( $graph );
	$all_clinics  = nvx_schema_clinics();
	$page_id      = (int) get_queried_object_id();

	if ( null === $organization['index'] ) {
		$graph[]               = array(
			'@type' => array( 'Organization', 'MedicalOrganization' ),
			'@id'   => $organization['id'],
			'url'   => home_url( '/' ),
		);
		$organization['index'] = array_key_last( $graph );
	}

	$physicians = nvx_schema_build_physicians( $page_id, $organization['id'] );
	$physician  = ! empty( $physicians ) ? $physicians[0] : null;

	if ( null !== $organization['index'] ) {
		nvx_schema_enrich_organization( $graph, $organization['index'], $all_clinics, $physicians );
	}

	nvx_schema_attach_clinics_graph( $graph, $page_id, $organization, $all_clinics, $physicians );

	foreach ( $physicians as $person ) {
		$graph[] = $person;
	}

	nvx_schema_attach_publications( $graph, $page_id, $physicians );
	nvx_schema_attach_treatment_and_faq( $graph, $page_id, $organization['id'], $physician );

	return $graph;
}
add_filter( 'wpseo_schema_graph', 'nvx_extend_yoast_schema_graph', 20, 1 );

/**
 * Deduplicate Schema.org @id entries across the graph.
 *
 * @param array $graph Yoast Schema graph.
 * @return array
 */
function nvx_schema_deduplicate_ids( $graph ) {
	if ( ! is_array( $graph ) ) {
		return $graph;
	}

	$seen = array();
	foreach ( $graph as $key => $node ) {
		if ( isset( $node['@id'] ) && is_string( $node['@id'] ) ) {
			$id = $node['@id'];
			if ( isset( $seen[ $id ] ) ) {
				unset( $graph[ $key ] );
			} else {
				$seen[ $id ] = true;
			}
		}
	}

	return array_values( $graph );
}
add_filter( 'wpseo_schema_graph', 'nvx_schema_deduplicate_ids', PHP_INT_MAX, 1 );
