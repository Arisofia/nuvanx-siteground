<?php
/**
 * NUVANX structured data extensions for Yoast SEO.
 *
 * Keeps the organization, clinic locations and visible FAQ content in one
 * Yoast @graph. No standalone JSON-LD block is printed, preventing duplicate
 * Organization entities.
 *
 * @package NUVANX
 */

defined( 'ABSPATH' ) || exit;

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
			'endolift_facial' => array(
				'id'     => 1241,
				'path'   => '/endolift-facial-papada-mandibula/',
				'schema' => 'MedicalProcedure',
			),
			'exion_btl'       => array(
				'id'     => 2906,
				'path'   => '/exion-btl/',
				'schema' => 'Service',
			),
			// Path is authoritative when post ID moves between environments.
			'exilite_btl'     => array(
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
 * FAQ copy keyed by treatment registry keys (must mirror visible page content).
 *
 * @return array<string, array<int, array{q:string,a:string}>>
 */
function nvx_schema_faq_catalog() {
	return array(
		'endolift_facial' => array(
			array(
				'q' => '¿Endolift® es para cualquier papada?',
				'a' => 'No. Primero debe valorarse si predomina grasa, flacidez o exceso de piel. Esa diferencia define si Endolift® tiene sentido o si conviene otra opción.',
			),
			array(
				'q' => '¿El resultado se ve enseguida?',
				'a' => 'Puede haber cambios iniciales, pero la evolución principal suele ser progresiva. El equipo médico explica el ritmo esperable según cada caso.',
			),
			array(
				'q' => '¿Requiere baja?',
				'a' => 'Habitualmente permite retomar vida cotidiana con cuidados, aunque puede haber inflamación o sensibilidad temporal.',
			),
			array(
				'q' => '¿Puede combinarse con otros tratamientos?',
				'a' => 'Sí, cuando hay una secuencia lógica. Puede combinarse con medicina estética, EXION® BTL o cuidado de piel según evolución.',
			),
		),
		'exion_btl'       => array(
			array(
				'q' => '¿EXION® sustituye al Láser CO₂?',
				'a' => 'No. EXION® y Láser CO₂ tienen indicaciones distintas. EXION® puede trabajar radiofrecuencia fraccionada, calidad de piel y protocolos corporales; el Láser CO₂ fraccionado se orienta a renovación cutánea mediante láser ablativo fraccionado.',
			),
			array(
				'q' => '¿Qué diferencia hay entre EXION® Fractional RF, Face y Body?',
				'a' => 'Fractional RF se orienta a textura y renovación; Face a hidratación y calidad de piel; Body a firmeza corporal, textura y adiposidad localizada resistente. La elección depende del diagnóstico.',
			),
			array(
				'q' => '¿Cuántas sesiones necesito?',
				'a' => 'Depende de la zona, el aplicador, la calidad del tejido y el objetivo clínico. En consulta médica personalizada se define si EXION® tiene sentido y cuántas sesiones serían razonables.',
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
	$treatment_key = nvx_schema_resolve_treatment_key( $page_id );
	$catalog       = nvx_schema_faq_catalog();

	if ( null === $treatment_key || empty( $catalog[ $treatment_key ] ) ) {
		return null;
	}

	$entities = array();

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
		'@id'        => get_permalink( $page_id ) . '#faq',
		'url'        => get_permalink( $page_id ),
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

	$permalink = get_permalink( $page_id );

	if ( 'endolift_facial' === $key ) {
		return array(
			'@type'            => 'MedicalProcedure',
			'@id'              => $permalink . '#medical-procedure',
			'name'             => 'Endolift® facial para papada y línea mandibular',
			'alternateName'    => array( 'Endolift® facial', 'Láser endodérmico facial' ),
			'url'              => $permalink,
			'mainEntityOfPage' => array( '@id' => $permalink ),
			'owner'            => array( '@id' => $organization_id ),
			'description'      => 'Procedimiento médico con fibra láser fina bajo la piel, indicado tras valoración para mejorar firmeza, papada y definición del contorno facial en casos seleccionados.',
			'bodyLocation'     => 'Papada, línea mandibular, cuello y óvalo facial',
			'preparation'      => 'Valoración médica previa de anatomía, calidad de piel, antecedentes y expectativas.',
			'howPerformed'     => 'El equipo médico trabaja bajo la piel mediante una fibra láser fina y adapta los parámetros a la zona y al tejido.',
			'followup'         => 'Seguimiento clínico y evolución fotográfica; puede existir inflamación o sensibilidad temporal.',
		);
	}

	if ( 'exion_btl' === $key ) {
		return array(
			'@type'            => 'Service',
			'@id'              => $permalink . '#service',
			'name'             => 'EXION® BTL en Madrid',
			'serviceType'      => 'Protocolos médicos con plataforma EXION® BTL',
			'url'              => $permalink,
			'mainEntityOfPage' => array( '@id' => $permalink ),
			'provider'         => array( '@id' => $organization_id ),
			'description'      => 'Plataforma médica con aplicadores Fractional RF, Face y Body para protocolos personalizados de textura, calidad de piel, firmeza y tratamiento corporal según diagnóstico.',
			'areaServed'       => 'Madrid',
		);
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
			// Conservative description aligned with visible page intro (no numeric efficacy claims).
			'description'      => 'Plataforma médica de luz pulsada intensa (IPL) para valoración de manchas, rojeces, alteraciones pigmentarias y lesiones vasculares superficiales según diagnóstico médico.',
			'areaServed'       => 'Madrid',
		);
	}

	return null;
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

	$index = $organization['index'];

	if ( null !== $index ) {
		$graph[ $index ]['@type']          = nvx_schema_add_type( $graph[ $index ]['@type'], 'MedicalOrganization' );
		$graph[ $index ]['name']           = 'NUVANX Medicina Estética Láser';
		$graph[ $index ]['alternateName']  = array( 'NUVANX', 'NUVANX Madrid' );
		$graph[ $index ]['description']    = 'Clínicas de medicina estética láser en Madrid con sedes en Chamberí y Goya · Barrio Salamanca, diagnóstico médico, tratamientos faciales y corporales y seguimiento personalizado.';
		$graph[ $index ]['email']          = 'info@nuvanx.com';
		$graph[ $index ]['telephone']      = '+34669319836';
		$graph[ $index ]['address']        = array( $all_clinics['chamberi']['address'], $all_clinics['goya']['address'] );
		$graph[ $index ]['contactPoint']   = array(
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
		$graph[ $index ]['knowsAbout']     = array(
			'Medicina estética',
			'Medicina estética láser',
			'Endolift® facial',
			'Endoláser corporal',
			'Láser CO₂ fraccionado',
			'EXION® BTL',
			'BTL EXILITE™ IPL',
			'Thermage FLX®',
			'Medicina regenerativa',
		);

		$existing_same_as               = isset( $graph[ $index ]['sameAs'] ) ? (array) $graph[ $index ]['sameAs'] : array();
		$existing_same_as[]             = 'https://www.doctoralia.es/clinicas/nuvanx-medicina-estetica-laser';
		$graph[ $index ]['sameAs']      = array_values( array_unique( array_filter( $existing_same_as ) ) );
	}

	$clinic_keys = nvx_schema_resolve_clinic_keys( $page_id );

	if ( ! empty( $clinic_keys ) && null !== $organization['index'] ) {
		$graph[ $organization['index'] ]['subOrganization'] = $clinic_ids;

		foreach ( $clinic_keys as $key ) {
			if ( empty( $all_clinics[ $key ] ) ) {
				continue;
			}
			$clinic                         = $all_clinics[ $key ];
			$clinic['parentOrganization']   = array( '@id' => $organization['id'] );
			$graph[]                        = $clinic;
		}
	}

	$treatment = nvx_schema_treatment_node( $page_id, $organization['id'] );
	if ( null !== $treatment ) {
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
 * Strip embedded JSON-LD from post content / excerpts.
 *
 * Canonical structured data is only emitted via Yoast's @graph
 * (wpseo_schema_graph). Standalone <script type="application/ld+json">
 * blocks left in WordPress content (e.g. EXILITE, home FAQ dumps) create
 * duplicate Organization / MedicalClinic / FAQPage entities and must not
 * render. Permanent DB cleanup remains recommended on staging2.
 *
 * @param string $content HTML.
 * @return string
 */
function nvx_strip_embedded_jsonld( $content ) {
	if ( ! is_string( $content ) || '' === $content || false === stripos( $content, 'ld+json' ) ) {
		return $content;
	}

	$cleaned = preg_replace(
		'#<script\b[^>]*type\s*=\s*(["\'])application/ld\+json\1[^>]*>[\s\S]*?</script>#i',
		'',
		$content
	);

	return is_string( $cleaned ) ? $cleaned : $content;
}
add_filter( 'the_content', 'nvx_strip_embedded_jsonld', 5 );
add_filter( 'the_excerpt', 'nvx_strip_embedded_jsonld', 5 );
add_filter( 'widget_text_content', 'nvx_strip_embedded_jsonld', 5 );
