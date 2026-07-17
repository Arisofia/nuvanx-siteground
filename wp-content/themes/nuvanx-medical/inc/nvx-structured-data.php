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

require_once __DIR__ . '/nvx-jsonld-content.php';

/**
 * Editorial review month label for Endolift byline (update with clinical review).
 */
if ( ! defined( 'NVX_ENDOLIFT_REVIEW_LABEL' ) ) {
	define( 'NVX_ENDOLIFT_REVIEW_LABEL', 'julio 2026' );
}

/**
 * Official public PVP catalogue (EUR, IVA 21% included).
 * Source: clinic tariff sheet. Never publish commission / internal cost notes.
 *
 * @return array{
 *   endolift: array<string, array{label:string,pvp:float,group:string}>,
 *   endolift_combo: array<string, array{label:string,pvp:float,group:string}>,
 *   laser_co2: array<string, array{label:string,pvp:float,group:string}>
 * }
 */
function nvx_tariff_catalog() {
	return array(
		'endolift'       => array(
			'ojeras'              => array(
				'label' => 'Endolift® ojeras',
				'pvp'   => 798.60,
				'group' => 'facial',
			),
			'papada'              => array(
				'label' => 'Endolift® papada',
				'pvp'   => 1064.80,
				'group' => 'facial',
			),
			'marcacion_mandibular' => array(
				'label' => 'Endolift® marcación mandibular',
				'pvp'   => 1064.80,
				'group' => 'facial',
			),
			'pomulos'             => array(
				'label' => 'Endolift® pómulos',
				'pvp'   => 1064.80,
				'group' => 'facial',
			),
			'cuello'              => array(
				'label' => 'Cuello',
				'pvp'   => 1197.90,
				'group' => 'facial',
			),
			'abdomen'             => array(
				'label' => 'Endolift® zona abdomen',
				'pvp'   => 1694.00,
				'group' => 'corporal',
			),
			'flancos'             => array(
				'label' => 'Endolift® flancos',
				'pvp'   => 1573.00,
				'group' => 'corporal',
			),
			'subescapular'        => array(
				'label' => 'Endolift® subescapular / sujetador',
				'pvp'   => 1391.50,
				'group' => 'corporal',
			),
			'brazos'              => array(
				'label' => 'Endolift® brazos',
				'pvp'   => 1331.00,
				'group' => 'corporal',
			),
			'rodillas'            => array(
				'label' => 'Endolift® rodillas',
				'pvp'   => 1197.90,
				'group' => 'corporal',
			),
			'muslos_internos'     => array(
				'label' => 'Endolift® cara interna muslos',
				'pvp'   => 1331.00,
				'group' => 'corporal',
			),
			'subgluteos'          => array(
				'label' => 'Subglúteos (bananitos)',
				'pvp'   => 1331.00,
				'group' => 'corporal',
			),
			'cartucheras'         => array(
				'label' => 'Endolift® cartucheras',
				'pvp'   => 1331.00,
				'group' => 'corporal',
			),
		),
		'endolift_combo' => array(
			'papada_cuello'        => array(
				'label' => 'Papada y cuello',
				'pvp'   => 1331.00,
				'group' => 'facial',
			),
			'marcacion_papada'     => array(
				'label' => 'Marcación mandibular y papada',
				'pvp'   => 1452.00,
				'group' => 'facial',
			),
			'full_face'            => array(
				'label' => 'Endolift® Full Face (tercio medio, inferior y cuello)',
				'pvp'   => 1694.00,
				'group' => 'facial',
			),
			'abdomen_flancos'      => array(
				'label' => 'Abdomen y flancos',
				'pvp'   => 2395.80,
				'group' => 'corporal',
			),
			'subgluteos_cartucheras' => array(
				'label' => 'Subglúteos y cartucheras',
				'pvp'   => 1633.50,
				'group' => 'corporal',
			),
			'muslos_rodilla'       => array(
				'label' => 'Cara interna de muslos y rodilla',
				'pvp'   => 1573.00,
				'group' => 'corporal',
			),
			'sujetador_brazos'     => array(
				'label' => 'Zona sujetador y brazos',
				'pvp'   => 1694.00,
				'group' => 'corporal',
			),
			'cartucheras_muslos'   => array(
				'label' => 'Cartucheras y cara interna de muslos',
				'pvp'   => 1815.00,
				'group' => 'corporal',
			),
			'cartucheras_subgluteos_muslos' => array(
				'label' => 'Cartucheras, subglúteos y cara interna de muslos',
				'pvp'   => 2286.90,
				'group' => 'corporal',
			),
		),
		'laser_co2'      => array(
			'facial'   => array(
				'label' => 'Sesión láser CO₂ facial',
				'pvp'   => 330.00,
				'group' => 'facial',
			),
			'corporal' => array(
				'label' => 'Sesión láser CO₂ corporal',
				'pvp'   => 450.00,
				'group' => 'corporal',
			),
		),
	);
}

/**
 * Lowest public Endolift® PVP (facial ojeras) — used for “desde” GEO copy/schema.
 *
 * @return float
 */
function nvx_endolift_price_from_eur() {
	$catalog = nvx_tariff_catalog();
	if ( defined( 'NVX_ENDOLIFT_PRICE_FROM_EUR' ) ) {
		return (float) NVX_ENDOLIFT_PRICE_FROM_EUR;
	}

	return (float) $catalog['endolift']['ojeras']['pvp'];
}

/**
 * Reference PVP for papada / marcación mandibular (page core indication).
 *
 * @return float
 */
function nvx_endolift_price_papada_eur() {
	return (float) nvx_tariff_catalog()['endolift']['papada']['pvp'];
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
function nvx_schema_page_registry() {
	return array(
		'clinics'    => array(
			'chamberi' => array(
				'id'   => 1543,
				'path' => '/medicina-estetica-chamberi/',
			),
			'goya'     => array(
				'id'   => 1537,
				'path' => '/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/',
			),
		),
		'clinic_hub' => array(
			'id'   => 1399,
			'path' => '/clinicas-de-medicina-estetica-nuvanx/',
		),
		'treatments' => array(
			'endolift_facial'    => array(
				'id'     => 1241,
				'path'   => '/endolift-facial-papada-mandibula/',
				'schema' => 'MedicalProcedure',
			),
			// Path is authoritative when post ID moves between environments.
			'endolaser_corporal' => array(
				'id'     => 0,
				'path'   => '/endolaser-corporal-grasa-localizada/',
				'schema' => 'MedicalProcedure',
			),
			'laser_co2'          => array(
				'id'     => 0,
				'path'   => '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/',
				'schema' => 'MedicalProcedure',
			),
			'exion_btl'          => array(
				'id'     => 2906,
				'path'   => '/exion-btl/',
				'schema' => 'Service',
			),
			'exion_face'         => array(
				'id'     => 0,
				'path'   => '/exion-face/',
				'schema' => 'Service',
			),
			'exion_body'         => array(
				'id'     => 0,
				'path'   => '/exion-body/',
				'schema' => 'Service',
			),
			'exion_fractional'   => array(
				'id'     => 0,
				'path'   => '/exion-fractional/',
				'schema' => 'Service',
			),
			'emfusion'           => array(
				'id'     => 0,
				'path'   => '/emfusion/',
				'schema' => 'Service',
			),
			'exilite_btl'        => array(
				'id'     => 0,
				'path'   => '/btl-exilite-ipl-madrid/',
				'schema' => 'Service',
			),
		),
	);
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

/**
 * Current request path relative to the site home.
 *
 * @param int $page_id Queried page ID when available.
 * @return string
 */
function nvx_schema_current_path( $page_id = 0 ) {
	if ( $page_id > 0 ) {
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
		if ( is_string( $uri ) && '' !== $uri ) {
			return nvx_schema_normalize_path( $uri );
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
	return ( '/' !== $target && 0 === strpos( $current, $target ) );
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

	// Optional editorial override: post meta `_nvx_clinic_branch` = chamberi|goya|all.
	if ( $page_id > 0 ) {
		$meta = strtolower( trim( (string) get_post_meta( $page_id, '_nvx_clinic_branch', true ) ) );
		if ( 'all' === $meta || 'both' === $meta ) {
			return array_keys( $registry['clinics'] );
		}
		if ( isset( $registry['clinics'][ $meta ] ) ) {
			return array( $meta );
		}
	}

	$matched = array();
	foreach ( $registry['clinics'] as $key => $entry ) {
		if ( (int) $entry['id'] === $page_id || nvx_schema_path_matches( $path, $entry['path'] ) ) {
			$matched[] = $key;
		}
	}
	if ( ! empty( $matched ) ) {
		return array_values( array_unique( $matched ) );
	}

	$hub = $registry['clinic_hub'];
	if (
		(int) $hub['id'] === $page_id
		|| nvx_schema_path_matches( $path, $hub['path'] )
		|| nvx_schema_is_sede_template( $page_id )
	) {
		// Hub or generic sede template without a specific branch: expose both clinics.
		return array_keys( $registry['clinics'] );
	}

	return array();
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

	return array(
		'chamberi' => array(
			'@type'      => 'MedicalClinic',
			'@id'        => home_url( '/#/schema/medical-clinic/chamberi' ),
			'name'       => 'NUVANX Medicina Estética Láser — Chamberí',
			'branchCode' => 'chamberi',
			'url'        => home_url( $registry['clinics']['chamberi']['path'] ),
			'telephone'  => '+34669319836',
			'email'      => 'info@nuvanx.com',
			'address'    => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => 'Calle de Fernández de la Hoz, 4, Bajo Derecha',
				'addressLocality' => 'Madrid',
				'addressRegion'   => 'Comunidad de Madrid',
				'postalCode'      => '28010',
				'addressCountry'  => 'ES',
			),
			'identifier' => array(
				'@type'      => 'PropertyValue',
				'propertyID' => 'Registro sanitario de la Comunidad de Madrid',
				'value'      => 'CS20144',
			),
			'hasMap'      => 'https://www.google.com/maps/search/?api=1&query=NUVANX%20Medicina%20Est%C3%A9tica%20L%C3%A1ser%20C%2F%20de%20Fern%C3%A1ndez%20de%20la%20Hoz%204%2028010%20Madrid',
			'areaServed'  => array( 'Chamberí', 'Almagro', 'Trafalgar', 'Madrid' ),
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
			'sameAs'      => array(
				'https://www.doctoralia.es/clinicas/nuvanx-medicina-estetica-laser',
			),
		),
		'goya'      => array(
			'@type'      => 'MedicalClinic',
			'@id'        => home_url( '/#/schema/medical-clinic/goya' ),
			'name'       => 'NUVANX Medicina Estética Láser — Goya · Barrio Salamanca',
			'branchCode' => 'goya',
			'url'        => home_url( $registry['clinics']['goya']['path'] ),
			'telephone'  => '+34647505107',
			'email'      => 'info@nuvanx.com',
			'address'    => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => 'Calle de Fernán González, 26',
				'addressLocality' => 'Madrid',
				'addressRegion'   => 'Comunidad de Madrid',
				'postalCode'      => '28009',
				'addressCountry'  => 'ES',
			),
			'identifier' => array(
				'@type'      => 'PropertyValue',
				'propertyID' => 'Registro sanitario de la Comunidad de Madrid',
				'value'      => 'CS20073',
			),
			'hasMap'      => 'https://www.google.com/maps/search/?api=1&query=NUVANX%20Goya%20C%2F%20de%20Fern%C3%A1n%20Gonz%C3%A1lez%2026%2028009%20Madrid',
			'areaServed'  => array( 'Goya', 'Barrio de Salamanca', 'Lista', 'Recoletos', 'Madrid' ),
			'openingHoursSpecification' => array(
				array(
					'@type'     => 'OpeningHoursSpecification',
					'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ),
					'opens'     => '11:00',
					'closes'    => '20:00',
				),
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
				'q' => '¿Cómo es la recuperación y el dolor post-tratamiento?',
				'a' => 'Anestesia local; ambulatorio. Reincorporación habitual en menos de 24 h. Edema, tirantez o hematomas leves 3–5 días (a veces hasta 7). Baja social moderada la primera semana si hay compromisos de imagen.',
			),
		),
	);
}

/**
 * Return an FAQPage node that exactly mirrors visible page content.
 *
 * Front page uses the GEO home FAQ catalogue (nvx_home_faq_v2_catalog).
 * Treatment pages use nvx_schema_faq_catalog when the same Q/A are printed in HTML.
 *
 * @param int $page_id Current page ID.
 * @return array|null
 */
function nvx_schema_faq_node( $page_id ) {
	$entities = array();
	$faq_id   = get_permalink( $page_id ) . '#faq';
	$faq_url  = get_permalink( $page_id );

	// Homepage FAQ (visible accordion + schema must stay in lockstep).
	if ( is_front_page() && function_exists( 'nvx_home_faq_v2_catalog' ) ) {
		foreach ( nvx_home_faq_v2_catalog() as $question ) {
			if ( empty( $question['q'] ) || empty( $question['a'] ) ) {
				continue;
			}
			$entities[] = array(
				'@type'          => 'Question',
				'name'           => $question['q'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $question['a'],
				),
			);
		}
		if ( empty( $entities ) ) {
			return null;
		}
		return array(
			'@type'      => 'FAQPage',
			'@id'        => home_url( '/#faq' ),
			'url'        => home_url( '/' ),
			'mainEntity' => $entities,
		);
	}

	$treatment_key = nvx_schema_resolve_treatment_key( $page_id );
	$catalog       = nvx_schema_faq_catalog();

	if ( null === $treatment_key || empty( $catalog[ $treatment_key ] ) ) {
		return null;
	}

	foreach ( $catalog[ $treatment_key ] as $question ) {
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => $question['q'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $question['a'],
			),
		);
	}

	return array(
		'@type'      => 'FAQPage',
		'@id'        => $faq_id,
		'url'        => $faq_url,
		'mainEntity' => $entities,
	);
}

/**
 * Treatment / service entity nodes keyed by registry treatment key.
 *
 * @param int    $page_id        Current page ID.
 * @param string $organization_id Organization @id.
 * @return array|null
 */
function nvx_schema_treatment_node( $page_id, $organization_id ) {
	$key = nvx_schema_resolve_treatment_key( $page_id );

	if ( null === $key ) {
		return null;
	}

	$permalink    = get_permalink( $page_id );
	$price_from   = nvx_schema_price_string( nvx_endolift_price_from_eur() );
	$price_papada = nvx_schema_price_string( nvx_endolift_price_papada_eur() );
	$label_from   = nvx_format_price_eur( nvx_endolift_price_from_eur() );
	$label_papada = nvx_format_price_eur( nvx_endolift_price_papada_eur() );

	// Entity nodes cite-able by LLMs: procedure + indications + starting offer when known.
	if ( 'endolift_facial' === $key ) {
		return array(
			'@type'            => array( 'MedicalProcedure', 'Service' ),
			'@id'              => $permalink . '#medical-procedure',
			'name'             => 'Endolift® facial para papada y línea mandibular',
			'alternateName'    => array( 'Endolift® facial', 'Láser intersticial facial' ),
			'url'              => $permalink,
			'mainEntityOfPage' => array( '@id' => $permalink ),
			'provider'         => array( '@id' => $organization_id ),
			'description'      => 'Procedimiento médico mínimamente invasivo con microfibra láser subdérmica para lipólisis selectiva y retracción térmica en papada, contorno mandibular y cuello, indicado solo tras valoración anatómica. PVP papada/marcación mandibular desde ' . $label_papada . ' €; tarifas faciales desde ' . $label_from . ' €.',
			'bodyLocation'     => array( 'Papada', 'Línea mandibular', 'Cuello', 'Óvalo facial' ),
			'procedureType'    => 'https://schema.org/MinimallyInvasiveProcedure',
			'preparation'      => 'Valoración médica presencial de anatomía, calidad de piel, grasa submentoniana, ptosis y expectativas. Exclusión de ptosis severa con exceso cutáneo que requiera cirugía.',
			'howPerformed'     => 'Tras anestesia local se inserta microfibra óptica de 200–300 micras y se aplica energía láser intersticial en patrón vectorial subdérmico adaptado a la zona.',
			'followup'         => 'Seguimiento clínico protocolizado (típicamente semanas 4 y 8 y control posterior). Reincorporación habitual en menos de 24 h; edema o inflamación pueden durar 3–7 días.',
			'indication'       => array(
				array(
					'@type' => 'MedicalIndication',
					'name'  => 'Flacidez facial leve a moderada',
				),
				array(
					'@type' => 'MedicalIndication',
					'name'  => 'Adiposidad submentoniana (papada) seleccionada',
				),
			),
			// MedicalCondition links for GEO entity extraction (mirrors visible indications).
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
			// Primary offer = papada/mandibular (page intent); “desde” ojeras is in description + HTML table.
			'offers'           => array(
				array(
					'@type'         => 'Offer',
					'@id'           => $permalink . '#offer-papada',
					'name'          => 'Endolift® papada / marcación mandibular',
					'url'           => $permalink . '#inversion-endolift',
					'priceCurrency' => 'EUR',
					'price'         => $price_papada,
					'description'   => 'PVP con IVA incluido: ' . $label_papada . ' €. Plan y presupuesto tras valoración.',
					'areaServed'    => 'Madrid',
					'seller'        => array( '@id' => $organization_id ),
				),
				array(
					'@type'         => 'Offer',
					'@id'           => $permalink . '#offer-from',
					'name'          => 'Endolift® facial — tarifa desde',
					'url'           => $permalink . '#inversion-endolift',
					'priceCurrency' => 'EUR',
					'price'         => $price_from,
					'description'   => 'PVP con IVA incluido desde ' . $label_from . ' € (ojeras). Ver tabla de tarifas en la página.',
					'areaServed'    => 'Madrid',
					'seller'        => array( '@id' => $organization_id ),
				),
			),
		);
	}

	if ( 'endolaser_corporal' === $key ) {
		return array(
			'@type'            => array( 'MedicalProcedure', 'Service' ),
			'@id'              => $permalink . '#medical-procedure',
			'name'             => 'Endoláser corporal — destrucción de grasa localizada y retracción cutánea',
			'alternateName'    => array( 'Laserlipólisis corporal', 'Endoláser Madrid' ),
			'url'              => $permalink,
			'mainEntityOfPage' => array( '@id' => $permalink ),
			'provider'         => array( '@id' => $organization_id ),
			'description'      => 'Laserlipólisis médica intervencionista: lipólisis de adipocitos y estímulo de retracción dérmica en un acto ambulatorio por zonas (abdomen, flancos, muslos, brazos, submandibular). No trata obesidad ni pérdida masiva de peso; el presupuesto se personaliza tras valoración.',
			'bodyLocation'     => array( 'Abdomen', 'Flancos', 'Cara interna de muslos', 'Rodillas', 'Brazos', 'Región submandibular' ),
			'procedureType'    => 'https://schema.org/MinimallyInvasiveProcedure',
			'preparation'      => 'Peso estable, grasa focal y flacidez leve–moderada. Exclusión de exceso cutáneo severo (derivación a cirugía excisional, p. ej. abdominoplastia).',
			'howPerformed'     => 'Bajo anestesia local se introduce fibra láser en tejido subcutáneo para lipólisis selectiva y estímulo térmico de retracción en la cuadrícula de zonas planificada.',
			'followup'         => 'Cuidados post-procedimiento y revisiones según zona y protocolo médico.',
			'indication'       => array(
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
		$co2_facial = nvx_schema_price_string( nvx_tariff_catalog()['laser_co2']['facial']['pvp'] );
		$co2_body   = nvx_schema_price_string( nvx_tariff_catalog()['laser_co2']['corporal']['pvp'] );
		$label_f    = nvx_format_price_eur( nvx_tariff_catalog()['laser_co2']['facial']['pvp'] );
		$label_b    = nvx_format_price_eur( nvx_tariff_catalog()['laser_co2']['corporal']['pvp'] );

		return array(
			'@type'            => array( 'MedicalProcedure', 'Service' ),
			'@id'              => $permalink . '#medical-procedure',
			'name'             => 'Láser CO₂ fraccionado — resurfacing epidérmico y cicatrices',
			'alternateName'    => array( 'CO₂ fraccionado Madrid', 'Resurfacing láser CO₂' ),
			'url'              => $permalink,
			'mainEntityOfPage' => array( '@id' => $permalink ),
			'provider'         => array( '@id' => $organization_id ),
			'description'      => 'Ablación fraccionada con microcolumnas de vaporización y tejido sano peri-lesional. Indicado en cicatrices atróficas de acné, poros, textura irregular y fotodaño. Downtime típico 4–7 días; remodelación colagénica 4–6 semanas. PVP sesión facial desde ' . $label_f . ' €; corporal ' . $label_b . ' € (IVA incl.).',
			'bodyLocation'     => 'Piel facial y zonas cutáneas seleccionadas',
			'procedureType'    => 'https://schema.org/PercutaneousProcedure',
			'preparation'      => 'Evaluación de fototipo, inflamación, bronceado, medicación y objetivo (cicatriz, textura, fotodaño). Compromiso con downtime y fotoprotección.',
			'howPerformed'     => 'Microhaces de CO₂ crean columnas de vaporización térmica fraccionada; el tejido circundante acelera la curación y estimula colágeno I y III.',
			'followup'         => 'Días 1–3 eritema y patrón punteado; días 4–7 descamación; desde día 7 recuperación visual habitual y remodelación progresiva 4–6 semanas.',
			'indication'       => array(
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
			'offers'           => array(
				array(
					'@type'         => 'Offer',
					'@id'           => $permalink . '#offer-facial',
					'name'          => 'Sesión láser CO₂ facial',
					'url'           => $permalink . '#tarifas-co2',
					'priceCurrency' => 'EUR',
					'price'         => $co2_facial,
					'description'   => 'PVP con IVA incluido: ' . $label_f . ' € por sesión facial de referencia.',
					'areaServed'    => 'Madrid',
					'seller'        => array( '@id' => $organization_id ),
				),
				array(
					'@type'         => 'Offer',
					'@id'           => $permalink . '#offer-corporal',
					'name'          => 'Sesión láser CO₂ corporal',
					'url'           => $permalink . '#tarifas-co2',
					'priceCurrency' => 'EUR',
					'price'         => $co2_body,
					'description'   => 'PVP con IVA incluido: ' . $label_b . ' € por sesión corporal de referencia.',
					'areaServed'    => 'Madrid',
					'seller'        => array( '@id' => $organization_id ),
				),
			),
		);
	}

	if ( 'exion_btl' === $key ) {
		return array(
			'@type'            => array( 'MedicalProcedure', 'Service' ),
			'@id'              => $permalink . '#service',
			'name'             => 'EXION® BTL en Madrid',
			'serviceType'      => 'Protocolos médicos con plataforma EXION® BTL',
			'url'              => $permalink,
			'mainEntityOfPage' => array( '@id' => $permalink ),
			'provider'         => array( '@id' => $organization_id ),
			'description'      => 'Plataforma médica BTL con aplicadores Fractional RF, Face y Body para textura, firmeza y calidad cutánea según diagnóstico. El presupuesto se cierra tras valoración médica (aplicador, zona y número de sesiones). Puede valorarse como alternativa a RF fraccionada con microagujas (p. ej. Morpheus8®) cuando la indicación lo permite.',
			'procedureType'    => 'https://schema.org/NoninvasiveProcedure',
			'areaServed'       => 'Madrid',
			'offers'           => array(
				'@type'         => 'Offer',
				'@id'           => $permalink . '#offer-valoracion',
				'name'          => 'EXION® BTL — presupuesto tras valoración',
				'url'           => $permalink . '#inversion-exion',
				'priceCurrency' => 'EUR',
				'description'   => 'PVP personalizado según aplicador (Face / Body / Fractional RF), zona y plan de sesiones. Sin tarifa fija online; se documenta en consulta médica gratuita.',
				'areaServed'    => 'Madrid',
				'seller'        => array( '@id' => $organization_id ),
			),
		);
	}

	// Detail services (Face / Body / Fractional / EMFUSION) — mirror theme registry copy.
	$btl_detail_keys = array( 'exion_face', 'exion_body', 'exion_fractional', 'emfusion' );
	if ( in_array( $key, $btl_detail_keys, true ) && function_exists( 'nvx_btl_detail_registry' ) ) {
		$slug_map = array(
			'exion_face'       => 'exion-face',
			'exion_body'       => 'exion-body',
			'exion_fractional' => 'exion-fractional',
			'emfusion'         => 'emfusion',
		);
		$slug = $slug_map[ $key ] ?? '';
		$reg  = nvx_btl_detail_registry();
		if ( $slug && ! empty( $reg[ $slug ] ) ) {
			$cfg = $reg[ $slug ];
			return array(
				'@type'            => 'Service',
				'@id'              => $permalink . '#service',
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
			'@id'              => $permalink . '#service',
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

/**
 * Director médico as Physician (E-E-A-T entity for GEO specialist queries).
 *
 * @param string $organization_id Organization @id.
 * @return array
 */
function nvx_schema_physician_director( $organization_id ) {
	$equipo    = home_url( '/equipo-medico/' );
	$colegiado = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';

	return array(
		'@type'           => array( 'Person', 'Physician' ),
		'@id'             => home_url( '/equipo-medico/#physician-rivera-tejeda' ),
		'name'            => 'José Javier Rivera Tejeda',
		'honorificPrefix' => 'Dr.',
		'jobTitle'        => 'Director médico e investigador clínico aplicado · NUVANX Madrid',
		'description'     => 'Dirección médica de NUVANX. Láser intersticial (Endolift®, laserlipólisis), CO₂ fraccionado, geometría facial con inductores y tricología. Colegiado ICOMEM ' . $colegiado . '. Perfil público en Doctoralia.',
		'url'             => $equipo,
		'worksFor'        => array( '@id' => $organization_id ),
		'hasCredential'   => array(
			array(
				'@type'              => 'EducationalOccupationalCredential',
				'credentialCategory' => 'Número de colegiado ICOMEM',
				'identifier'         => $colegiado,
				'name'               => 'Colegiado ICOMEM ' . $colegiado,
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
		'alumniOf'        => array(
			array(
				'@type' => 'CollegeOrUniversity',
				'name'  => 'Universidad Complutense de Madrid',
			),
			array(
				'@type' => 'EducationalOrganization',
				'name'  => 'AMIR',
			),
		),
		'knowsAbout'      => array(
			'Endolift® facial',
			'Laserlipólisis',
			'Endoláser corporal',
			'Láser CO₂ fraccionado',
			'Medicina estética láser',
			'Marcación mandibular con láser',
			'Inductores de colágeno',
			'Tricología médica',
			'Medicina regenerativa',
		),
		'sameAs'          => array(
			'https://www.doctoralia.es/jose-javier-rivera-tejeda/medico-estetico/madrid',
		),
		// No AggregateRating hardcode — ratings must mirror live Doctoralia.
	);
}

/**
 * Dra. Ivon Yamileth Rivera Deras — Physician + Researcher (E-E-A-T / GEO).
 *
 * @param string $organization_id Organization @id.
 * @return array
 */
function nvx_schema_physician_ivon( $organization_id ) {
	$equipo    = home_url( '/equipo-medico/' );
	$ivon_id   = home_url( '/equipo-medico/#physician-rivera-deras' );
	$colegiado = defined( 'NVX_IVON_COLEGIADO' ) ? NVX_IVON_COLEGIADO : '284621525';

	return array(
		'@type'            => array( 'Person', 'Physician' ),
		'@id'              => $ivon_id,
		'name'             => 'Ivon Yamileth Rivera Deras',
		'honorificPrefix'  => 'Dra.',
		'jobTitle'         => 'Especialista en geriatría, longevidad y well-aging · NUVANX',
		'description'      => 'Colegiada ICOMEM ' . $colegiado . '. Médico especialista (FEA) en Hospital Universitario La Paz (Recuperación Funcional / Hospital de Día Geriátrico) y Hospital Central de la Cruz Roja. Investigadora y consultora para OXON Epidemiology; coordinación científica SEMEG; colaboración EuGMS; profesora UEM. Coautora de obras de bioética y geriatría clínica. Integra well-aging basado en evidencia en NUVANX.',
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
				'credentialCategory' => 'Número de colegiado ICOMEM',
				'identifier'         => $colegiado,
				'name'               => 'Colegiada ICOMEM ' . $colegiado,
			),
		),
		'memberOf'         => array(
			array(
				'@type' => 'MedicalOrganization',
				'name'  => 'Sociedad Española de Medicina Geriátrica (SEMEG)',
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
 * Dr. Fabio Augusto Quiñónez Bareiro — Physician (geriatrics / complex patient E-E-A-T).
 *
 * @param string $organization_id Organization @id.
 * @return array
 */
function nvx_schema_physician_fabio( $organization_id ) {
	$equipo    = home_url( '/equipo-medico/' );
	$fabio_id  = home_url( '/equipo-medico/#physician-quinonez-bareiro' );
	$colegiado = defined( 'NVX_FABIO_COLEGIADO' ) ? NVX_FABIO_COLEGIADO : '282877543';

	return array(
		'@type'            => array( 'Person', 'Physician' ),
		'@id'              => $fabio_id,
		'name'             => 'Fabio Augusto Quiñónez Bareiro',
		'honorificPrefix'  => 'Dr.',
		'jobTitle'         => 'Especialista en geriatría, gerontología y paciente complejo · NUVANX',
		'description'      => 'Colegiado ICOMEM ' . $colegiado . '. FEA de Geriatría (Hospital Virgen del Valle, Toledo). Investigador CIBERFES y colaborador SEMEG. Ph.D. UAM (disfunción vascular subclínica, declinar cognitivo y fragilidad). Máster en Psicogeriatría (UAB). Refuerza medicina regenerativa y longevidad en NUVANX.',
		'url'              => $equipo . '#physician-quinonez-bareiro',
		'medicalSpecialty' => 'https://schema.org/Geriatric',
		'worksFor'         => array(
			array( '@id' => $organization_id ),
			array(
				'@type' => 'Hospital',
				'name'  => 'Hospital Virgen del Valle',
			),
		),
		'hasCredential'    => array(
			array(
				'@type'              => 'EducationalOccupationalCredential',
				'credentialCategory' => 'Número de colegiado ICOMEM',
				'identifier'         => $colegiado,
				'name'               => 'Colegiado ICOMEM ' . $colegiado,
			),
			array(
				'@type' => 'EducationalOccupationalCredential',
				'name'  => 'Doctorado (Ph.D.) — Universidad Autónoma de Madrid',
			),
			array(
				'@type' => 'EducationalOccupationalCredential',
				'name'  => 'Máster en Psicogeriatría — Universidad Autónoma de Barcelona',
			),
		),
		'memberOf'         => array(
			array(
				'@type' => 'Organization',
				'name'  => 'CIBER de Fragilidad y Envejecimiento Saludable (CIBERFES)',
			),
			array(
				'@type' => 'MedicalOrganization',
				'name'  => 'Sociedad Española de Medicina Geriátrica (SEMEG)',
			),
		),
		'alumniOf'         => array(
			array(
				'@type' => 'CollegeOrUniversity',
				'name'  => 'Universidad Autónoma de Madrid',
			),
			array(
				'@type' => 'CollegeOrUniversity',
				'name'  => 'Universidad Autónoma de Barcelona',
			),
			array(
				'@type' => 'CollegeOrUniversity',
				'name'  => 'Escuela Latinoamericana de Medicina (ELAM)',
			),
		),
		'knowsAbout'       => array(
			'Geriatría',
			'Gerontología',
			'Paciente anciano crónico complejo',
			'Fragilidad',
			'Deterioro cognitivo',
			'Envejecimiento saludable',
			'Medicina regenerativa',
			'Longevidad',
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
			'@type'  => 'Book',
			'@id'    => home_url( '/equipo-medico/#work-manual-caidas-semeg' ),
			'name'   => 'Manual de manejo de personas mayores que sufren caídas',
			'author' => array( '@id' => $author_id ),
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
			'@type'  => 'Thesis',
			'@id'    => home_url( '/equipo-medico/#work-fabio-tesis-uam' ),
			'name'   => 'Disfunción vascular sub-clínica, declinar cognitivo y fragilidad',
			'author' => array( '@id' => $author_id ),
			'inSupportOf' => 'Ph.D.',
			'sourceOrganization' => array(
				'@type' => 'CollegeOrUniversity',
				'name'  => 'Universidad Autónoma de Madrid',
			),
		),
		array(
			'@type'  => 'ScholarlyArticle',
			'@id'    => home_url( '/equipo-medico/#work-fabio-itu-delirium' ),
			'name'   => '¿Será una infección del tracto urinario?',
			'author' => array( '@id' => $author_id ),
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

	$co2_from = nvx_tariff_catalog()['laser_co2']['facial']['pvp'];

	$catalog_defs = array(
		'endolift_facial'    => array(
			'label' => 'Endolift® facial',
			'price' => nvx_endolift_price_from_eur(),
		),
		'endolaser_corporal' => array(
			'label' => 'Endoláser corporal',
			'price' => null,
		),
		'laser_co2'          => array(
			'label' => 'Láser CO₂ fraccionado',
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
	if ( is_front_page() ) {
		return true;
	}

	if ( null !== nvx_schema_resolve_treatment_key( $page_id ) ) {
		return true;
	}

	$path = nvx_schema_current_path( $page_id );

	return nvx_schema_path_matches( $path, '/equipo-medico/' );
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

	return nvx_schema_path_matches( $path, '/equipo-medico/' );
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

	return nvx_schema_path_matches( $path, '/equipo-medico/' );
}

/**
 * Add NUVANX medical locations and page entities to Yoast's canonical graph.
 *
 * @param array $graph   Yoast Schema graph.
 * @param mixed $context Yoast Meta_Tags_Context.
 * @return array
 */
function nvx_extend_yoast_schema_graph( $graph, $context ) {
	if ( is_admin() || is_feed() || ( ! is_singular( 'page' ) && ! is_front_page() ) ) {
		return $graph;
	}

	$organization = nvx_schema_find_organization( $graph );
	$all_clinics  = nvx_schema_clinics();
	$page_id      = (int) get_queried_object_id();

	if ( null === $organization['index'] ) {
		$graph[] = array(
			'@type' => array( 'Organization', 'MedicalOrganization' ),
			'@id'   => $organization['id'],
			'url'   => home_url( '/' ),
		);
		$organization['index'] = array_key_last( $graph );
	}

	$clinic_ids = array(
		array( '@id' => $all_clinics['chamberi']['@id'] ),
		array( '@id' => $all_clinics['goya']['@id'] ),
	);

	$index      = $organization['index'];
	$physicians = array();

	if ( nvx_schema_should_emit_physician( $page_id ) ) {
		$physicians[] = nvx_schema_physician_director( $organization['id'] );
	}
	if ( nvx_schema_should_emit_physician_ivon( $page_id ) ) {
		$physicians[] = nvx_schema_physician_ivon( $organization['id'] );
	}
	if ( nvx_schema_should_emit_physician_fabio( $page_id ) ) {
		$physicians[] = nvx_schema_physician_fabio( $organization['id'] );
	}

	$physician = ! empty( $physicians ) ? $physicians[0] : null;

	if ( null !== $index ) {
		// MedicalOrganization is the parent; branch MedicalClinic nodes stay separate.
		$graph[ $index ]['@type']         = nvx_schema_add_type( $graph[ $index ]['@type'], 'MedicalOrganization' );
		$graph[ $index ]['name']          = 'NUVANX Medicina Estética Láser';
		$graph[ $index ]['alternateName'] = array( 'NUVANX', 'NUVANX Madrid', 'NUVANX Medicina Estética Láser Madrid' );
		$graph[ $index ]['url']           = home_url( '/' );
		$graph[ $index ]['description']   = 'Centro médico de medicina estética láser y well-aging en Madrid (Chamberí y Goya · Barrio Salamanca). Protocolos Endolift®, endoláser, Láser CO₂ y EXION® BTL con dirección médica y criterio científico (geriatría preventiva / longevidad).';
		$graph[ $index ]['email']         = 'info@nuvanx.com';
		$graph[ $index ]['telephone']     = '+34669319836';
		// Transparent positioning vs quote-only competitors (generative engines need a band).
		$graph[ $index ]['priceRange']    = '€€€';
		$graph[ $index ]['isAcceptingNewPatients'] = true;
		$graph[ $index ]['address']       = array( $all_clinics['chamberi']['address'], $all_clinics['goya']['address'] );
		$graph[ $index ]['contactPoint']  = array(
			array(
				'@type'             => 'ContactPoint',
				'contactType'       => 'Citas — Chamberí',
				'telephone'         => '+34669319836',
				'areaServed'        => 'ES',
				'availableLanguage' => array( 'es', 'en' ),
			),
			array(
				'@type'             => 'ContactPoint',
				'contactType'       => 'Citas — Goya · Barrio Salamanca',
				'telephone'         => '+34647505107',
				'areaServed'        => 'ES',
				'availableLanguage' => array( 'es', 'en' ),
			),
		);
		$graph[ $index ]['medicalSpecialty'] = array(
			'Aesthetic Medicine',
			'Laser Medicine',
			'Geriatric Medicine',
		);
		$graph[ $index ]['knowsAbout']    = array(
			'Medicina estética',
			'Medicina estética láser',
			'Endolift® facial',
			'Marcación mandibular con láser',
			'Endoláser corporal',
			'Láser CO₂ fraccionado',
			'EXION® BTL',
			'BTL EXILITE™ IPL',
			'Thermage FLX®',
			'Medicina regenerativa',
			'Well-aging',
			'Geriatría preventiva',
			'Longevidad',
		);
		// Presencial only — no videoconsulta in schema (service not marketed as operational CTA).
		$graph[ $index ]['potentialAction'] = array(
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

	// Home: both clinics + offer catalog (competitive entity density for local + service discovery).
	if ( is_front_page() && null !== $organization['index'] ) {
		$catalog = nvx_schema_offer_catalog( $organization['id'] );
		$graph[ $organization['index'] ]['hasOfferCatalog'] = array( '@id' => $catalog['@id'] );
		$graph[ $organization['index'] ]['subOrganization'] = $clinic_ids;
		$graph[] = $catalog;

		foreach ( array( 'chamberi', 'goya' ) as $key ) {
			if ( empty( $all_clinics[ $key ] ) ) {
				continue;
			}
			$clinic                       = $all_clinics[ $key ];
			$clinic['parentOrganization'] = array( '@id' => $organization['id'] );
			if ( ! empty( $physicians ) ) {
				$clinic_employees = array();
				foreach ( $physicians as $person ) {
					$clinic_employees[] = array( '@id' => $person['@id'] );
				}
				$clinic['employee'] = $clinic_employees;
			}
			$graph[] = $clinic;
		}
	} else {
		$clinic_keys = nvx_schema_resolve_clinic_keys( $page_id );

		if ( ! empty( $clinic_keys ) && null !== $organization['index'] ) {
			$graph[ $organization['index'] ]['subOrganization'] = $clinic_ids;

			foreach ( $clinic_keys as $key ) {
				if ( empty( $all_clinics[ $key ] ) ) {
					continue;
				}
				$clinic                       = $all_clinics[ $key ];
				$clinic['parentOrganization'] = array( '@id' => $organization['id'] );
				if ( ! empty( $physicians ) ) {
					$clinic_employees = array();
					foreach ( $physicians as $person ) {
						$clinic_employees[] = array( '@id' => $person['@id'] );
					}
					$clinic['employee'] = $clinic_employees;
				}
				$graph[] = $clinic;
			}
		}
	}

	foreach ( $physicians as $person ) {
		$graph[] = $person;
	}

	// Publication nodes on equipo page only (visible authorship).
	if ( nvx_schema_path_matches( nvx_schema_current_path( $page_id ), '/equipo-medico/' ) ) {
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

	// $physician = director (first) for procedure performer/reviewedBy.
	$treatment = nvx_schema_treatment_node( $page_id, $organization['id'] );
	if ( null !== $treatment ) {
		if ( null !== $physician ) {
			$treatment['performer']  = array( '@id' => $physician['@id'] );
			$treatment['reviewedBy'] = array( '@id' => $physician['@id'] );
		}
		$graph[] = $treatment;
	}

	$faq = nvx_schema_faq_node( $page_id );
	if ( null !== $faq ) {
		$graph[] = $faq;
	}

	return $graph;
}
add_filter( 'wpseo_schema_graph', 'nvx_extend_yoast_schema_graph', 20, 2 );

/**
 * Home document title — laser clinic intent (Yoast).
 *
 * @param string $title Current title.
 * @return string
 */
function nvx_filter_front_document_title( $title ) {
	if ( ! is_front_page() ) {
		return $title;
	}

	return 'Clínica de Medicina Estética Láser en Madrid | Endolift y Láser Médico | NUVANX';
}
add_filter( 'wpseo_title', 'nvx_filter_front_document_title', 20 );

/**
 * @param string $desc Current meta description.
 * @return string
 */
function nvx_filter_front_metadesc( $desc ) {
	if ( ! is_front_page() ) {
		return $desc;
	}

	return 'NUVANX: medicina estética láser en Madrid con equipo hospitalario (well-aging y geriatría preventiva). Endolift® desde ' . nvx_format_price_eur( nvx_endolift_price_from_eur() ) . ' €, CO₂ y EXION® BTL. Valoración en Chamberí y Goya.';
}
add_filter( 'wpseo_metadesc', 'nvx_filter_front_metadesc', 20 );

/**
 * Endolift page title — transactional GEO intent (precio + especialista).
 *
 * @param string $title Current title.
 * @return string
 */
function nvx_filter_endolift_document_title( $title ) {
	if ( 'endolift_facial' !== nvx_schema_resolve_treatment_key( (int) get_queried_object_id() ) ) {
		return $title;
	}

	return 'Endolift Facial en Madrid: Precio, Resultados Reales y Doctor Especialista | NUVANX';
}
add_filter( 'wpseo_title', 'nvx_filter_endolift_document_title', 21 );

/**
 * @param string $desc Meta description.
 * @return string
 */
function nvx_filter_endolift_metadesc( $desc ) {
	if ( 'endolift_facial' !== nvx_schema_resolve_treatment_key( (int) get_queried_object_id() ) ) {
		return $desc;
	}

	$colegiado = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';
	$from      = nvx_format_price_eur( nvx_endolift_price_from_eur() );
	$papada    = nvx_format_price_eur( nvx_endolift_price_papada_eur() );

	return 'Endolift® facial en Madrid: técnica, indicaciones, comparación con lifting y PVP desde ' . $from . ' € (papada/mandíbula ' . $papada . ' €). Dr. Rivera Tejeda, ICOMEM ' . $colegiado . '. Valoración en Chamberí y Goya.';
}
add_filter( 'wpseo_metadesc', 'nvx_filter_endolift_metadesc', 21 );

/**
 * Endoláser corporal document title.
 *
 * @param string $title Current title.
 * @return string
 */
function nvx_filter_endolaser_document_title( $title ) {
	if ( 'endolaser_corporal' !== nvx_schema_resolve_treatment_key( (int) get_queried_object_id() ) ) {
		return $title;
	}

	return 'Endoláser Corporal en Madrid: Grasa Localizada y Retracción | NUVANX';
}
add_filter( 'wpseo_title', 'nvx_filter_endolaser_document_title', 21 );

/**
 * @param string $desc Meta description.
 * @return string
 */
function nvx_filter_endolaser_metadesc( $desc ) {
	if ( 'endolaser_corporal' !== nvx_schema_resolve_treatment_key( (int) get_queried_object_id() ) ) {
		return $desc;
	}

	return 'Endoláser corporal en NUVANX Madrid: laserlipólisis y retracción cutánea por zonas (abdomen, flancos, muslos, brazos). No es tratamiento de obesidad. Valoración y presupuesto personalizado en Chamberí y Goya.';
}
add_filter( 'wpseo_metadesc', 'nvx_filter_endolaser_metadesc', 21 );

/**
 * Láser CO₂ document title.
 *
 * @param string $title Current title.
 * @return string
 */
function nvx_filter_co2_document_title( $title ) {
	if ( 'laser_co2' !== nvx_schema_resolve_treatment_key( (int) get_queried_object_id() ) ) {
		return $title;
	}

	return 'Láser CO₂ Fraccionado en Madrid: Cicatrices, Textura y Downtime | NUVANX';
}
add_filter( 'wpseo_title', 'nvx_filter_co2_document_title', 21 );

/**
 * @param string $desc Meta description.
 * @return string
 */
function nvx_filter_co2_metadesc( $desc ) {
	if ( 'laser_co2' !== nvx_schema_resolve_treatment_key( (int) get_queried_object_id() ) ) {
		return $desc;
	}

	$facial = nvx_format_price_eur( nvx_tariff_catalog()['laser_co2']['facial']['pvp'] );

	return 'Láser CO₂ fraccionado en NUVANX Madrid: resurfacing para cicatrices de acné, poros y fotodaño. Downtime 4–7 días. PVP sesión facial desde ' . $facial . ' €. Valoración en Chamberí y Goya.';
}
add_filter( 'wpseo_metadesc', 'nvx_filter_co2_metadesc', 21 );

/**
 * Equipo médico document title.
 *
 * @param string $title Current title.
 * @return string
 */
function nvx_filter_equipo_document_title( $title ) {
	$path = nvx_schema_current_path( (int) get_queried_object_id() );
	if ( ! nvx_schema_path_matches( $path, '/equipo-medico/' ) ) {
		return $title;
	}

	return 'Equipo Médico NUVANX Madrid | Rivera Tejeda, Rivera Deras y Quiñónez';
}
add_filter( 'wpseo_title', 'nvx_filter_equipo_document_title', 21 );

/**
 * @param string $desc Meta description.
 * @return string
 */
function nvx_filter_equipo_metadesc( $desc ) {
	$path = nvx_schema_current_path( (int) get_queried_object_id() );
	if ( ! nvx_schema_path_matches( $path, '/equipo-medico/' ) ) {
		return $desc;
	}

	$dir   = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';
	$ivon  = defined( 'NVX_IVON_COLEGIADO' ) ? NVX_IVON_COLEGIADO : '284621525';
	$fabio = defined( 'NVX_FABIO_COLEGIADO' ) ? NVX_FABIO_COLEGIADO : '282877543';

	return 'Dr. J.J. Rivera Tejeda (ICOMEM ' . $dir . '), Dra. I.Y. Rivera Deras (ICOMEM ' . $ivon . ') y Dr. F.A. Quiñónez Bareiro (ICOMEM ' . $fabio . '). Equipo médico NUVANX Madrid.';
}
add_filter( 'wpseo_metadesc', 'nvx_filter_equipo_metadesc', 21 );

// Pages / front only: strip Schema.org payloads from post_content (shared helper).
// Non-schema ld+json and non-page views are left alone. See nvx-jsonld-content.php.
add_filter( 'the_content', 'nvx_filter_strip_embedded_jsonld', 5 );
