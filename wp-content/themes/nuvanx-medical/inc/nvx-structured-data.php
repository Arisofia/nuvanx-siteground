<?php
/**
 * Structured data and SEO metadata for NUVANX.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/nvx-jsonld-content.php';

/**
 * Return the public medical organization data used by the schema graph.
 *
 * @return array<string, mixed>
 */
function nvx_schema_organization_data(): array {
	return array(
		'@type'       => array( 'Organization', 'MedicalOrganization' ),
		'@id'         => home_url( '/#organization' ),
		'name'        => 'NUVANX Medicina Estética Láser',
		'url'         => home_url( '/' ),
		'description' => 'Clínica de medicina estética láser en Madrid con diagnóstico médico, tecnologías seleccionadas e indicaciones individualizadas.',
	);
}

/**
 * Normalize one URL path with leading and trailing slashes.
 *
 * @param string $path Raw URL path.
 * @return string
 */
function nvx_schema_normalize_path( string $path ): string {
	$normalized = '/' . trim( $path, '/' ) . '/';
	return '//' === $normalized ? '/' : $normalized;
}

/**
 * Resolve the current public request path.
 *
 * @param int $post_id Optional post identifier.
 * @return string
 */
function nvx_schema_current_path( int $post_id = 0 ): string {
	if ( $post_id > 0 ) {
		$url  = get_permalink( $post_id );
		$path = is_string( $url ) ? wp_parse_url( $url, PHP_URL_PATH ) : '';
		if ( is_string( $path ) && '' !== $path ) {
			return nvx_schema_normalize_path( $path );
		}
	}

	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	$path        = wp_parse_url( $request_uri, PHP_URL_PATH );
	return nvx_schema_normalize_path( is_string( $path ) ? $path : '/' );
}

/**
 * Compare two normalized public paths.
 *
 * @param string $current Current path.
 * @param string $expected Expected path.
 * @return bool
 */
function nvx_schema_path_matches( string $current, string $expected ): bool {
	return nvx_schema_normalize_path( $current ) === nvx_schema_normalize_path( $expected );
}

/**
 * Retrieve the canonical clinic definitions used throughout the schema graph.
 *
 * @return array<string, array<string, mixed>>
 */
function nvx_schema_clinics(): array {
	$organization_id = home_url( '/#organization' );

	return array(
		'chamberi' => array(
			'@type'     => array( 'MedicalClinic', 'LocalBusiness' ),
			'@id'       => home_url( '/clinica-medicina-estetica-laser-chamberi/#medicalclinic' ),
			'name'      => 'NUVANX Chamberí',
			'url'       => home_url( '/clinica-medicina-estetica-laser-chamberi/' ),
			'parentOrganization' => array( '@id' => $organization_id ),
			'address'   => array(
				'@type'           => 'PostalAddress',
				'addressLocality' => 'Madrid',
				'addressRegion'   => 'Madrid',
				'addressCountry'  => 'ES',
			),
		),
		'goya'      => array(
			'@type'     => array( 'MedicalClinic', 'LocalBusiness' ),
			'@id'       => home_url( '/clinica-medicina-estetica-goya/#medicalclinic' ),
			'name'      => 'NUVANX Salamanca–Goya',
			'url'       => home_url( '/clinica-medicina-estetica-goya/' ),
			'parentOrganization' => array( '@id' => $organization_id ),
			'address'   => array(
				'@type'           => 'PostalAddress',
				'addressLocality' => 'Madrid',
				'addressRegion'   => 'Madrid',
				'addressCountry'  => 'ES',
			),
		),
	);
}

/**
 * Find a graph node by its @id.
 *
 * @param array<int, mixed> $graph Schema graph.
 * @param string            $id Node identifier.
 * @return int|null
 */
function nvx_schema_find_node_index( array $graph, string $id ): ?int {
	foreach ( $graph as $index => $node ) {
		if ( is_array( $node ) && isset( $node['@id'] ) && $id === (string) $node['@id'] ) {
			return $index;
		}
	}
	return null;
}

/**
 * Find the primary organization node in a Yoast graph.
 *
 * @param array<int, mixed> $graph Schema graph.
 * @return array{id:string,index:int|null}
 */
function nvx_schema_find_organization( array $graph ): array {
	$preferred_id = home_url( '/#organization' );
	$index        = nvx_schema_find_node_index( $graph, $preferred_id );
	if ( null !== $index ) {
		return array( 'id' => $preferred_id, 'index' => $index );
	}

	foreach ( $graph as $node_index => $node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		$types = isset( $node['@type'] ) ? (array) $node['@type'] : array();
		if ( in_array( 'Organization', $types, true ) || in_array( 'MedicalOrganization', $types, true ) ) {
			$id = isset( $node['@id'] ) ? (string) $node['@id'] : $preferred_id;
			return array( 'id' => $id, 'index' => $node_index );
		}
	}

	return array( 'id' => $preferred_id, 'index' => null );
}

/**
 * Build verified physician nodes associated with the current page.
 *
 * @param int    $page_id Current page identifier.
 * @param string $organization_id Organization schema identifier.
 * @return array<int, array<string, mixed>>
 */
function nvx_schema_build_physicians( int $page_id, string $organization_id ): array {
	$physicians = array();
	$catalog    = array(
		array(
			'name'       => 'Dr. Javier Rivera Tejeda',
			'path'       => '/equipo-medico/',
			'identifier' => 'javier-rivera-tejeda',
		),
	);

	foreach ( $catalog as $item ) {
		$physicians[] = array(
			'@type'       => 'Physician',
			'@id'         => home_url( $item['path'] . '#' . $item['identifier'] ),
			'name'        => $item['name'],
			'url'         => home_url( $item['path'] ),
			'worksFor'    => array( '@id' => $organization_id ),
			'memberOf'    => array( '@id' => $organization_id ),
			'mainEntityOfPage' => $page_id > 0 ? array( '@id' => get_permalink( $page_id ) ) : null,
		);
	}

	return $physicians;
}

/**
 * Enrich an organization node with clinics and physician references.
 *
 * @param array<int, mixed>                         $graph Schema graph, passed by reference.
 * @param int                                       $index Organization node index.
 * @param array<string, array<string, mixed>>       $clinics Clinic catalog.
 * @param array<int, array<string, mixed>>          $physicians Physician nodes.
 * @return void
 */
function nvx_schema_enrich_organization( array &$graph, int $index, array $clinics, array $physicians ): void {
	if ( ! isset( $graph[ $index ] ) || ! is_array( $graph[ $index ] ) ) {
		return;
	}

	$graph[ $index ]['@type']       = array_values( array_unique( array_merge( (array) ( $graph[ $index ]['@type'] ?? array() ), array( 'Organization', 'MedicalOrganization' ) ) ) );
	$graph[ $index ]['name']        = 'NUVANX Medicina Estética Láser';
	$graph[ $index ]['url']         = home_url( '/' );
	$graph[ $index ]['description'] = 'Clínica de medicina estética láser en Madrid con diagnóstico médico, tecnologías seleccionadas e indicaciones individualizadas.';
	$graph[ $index ]['subOrganization'] = array_values(
		array_map(
			static fn( array $clinic ): array => array( '@id' => $clinic['@id'] ),
			$clinics
		)
	);
	$graph[ $index ]['employee'] = array_values(
		array_map(
			static fn( array $physician ): array => array( '@id' => $physician['@id'] ),
			$physicians
		)
	);
}

/**
 * Attach the two clinic nodes to the schema graph.
 *
 * @param array<int, mixed>                   $graph Schema graph, passed by reference.
 * @param int                                 $page_id Current page identifier.
 * @param array{id:string,index:int|null}     $organization Organization reference.
 * @param array<string, array<string, mixed>> $clinics Clinic catalog.
 * @param array<int, array<string, mixed>>    $physicians Physician nodes.
 * @param array<int, array{@id:string}>       $clinic_ids Clinic references.
 * @return void
 */
function nvx_schema_attach_clinics_graph( array &$graph, int $page_id, array $organization, array $clinics, array $physicians, array $clinic_ids ): void {
	unset( $page_id, $physicians, $clinic_ids );
	foreach ( $clinics as $clinic ) {
		$clinic['parentOrganization'] = array( '@id' => $organization['id'] );
		if ( null === nvx_schema_find_node_index( $graph, (string) $clinic['@id'] ) ) {
			$graph[] = $clinic;
		}
	}
}

/**
 * Attach publication metadata when the current page represents an article.
 *
 * @param array<int, mixed>                $graph Schema graph, passed by reference.
 * @param int                              $page_id Current post identifier.
 * @param array<int, array<string, mixed>> $physicians Physician nodes.
 * @return void
 */
function nvx_schema_attach_publications( array &$graph, int $page_id, array $physicians ): void {
	if ( $page_id < 1 || ! is_singular( 'post' ) || empty( $physicians ) ) {
		return;
	}

	$graph[] = array(
		'@type'            => 'MedicalWebPage',
		'@id'              => get_permalink( $page_id ) . '#medicalwebpage',
		'url'              => get_permalink( $page_id ),
		'name'             => get_the_title( $page_id ),
		'datePublished'    => get_the_date( DATE_W3C, $page_id ),
		'dateModified'     => get_the_modified_date( DATE_W3C, $page_id ),
		'author'           => array( '@id' => $physicians[0]['@id'] ),
		'reviewedBy'       => array( '@id' => $physicians[0]['@id'] ),
		'mainEntityOfPage' => array( '@id' => get_permalink( $page_id ) ),
	);
}

/**
 * Build an ItemList schema node for the visible solution catalogue.
 *
 * @param string $organization_id Organization schema identifier.
 * @return array<string, mixed>
 */
function nvx_schema_solution_item_list( string $organization_id ): array {
	$items = array(
		array( 'name' => 'Papada y definición mandibular', 'path' => '/papada-definicion-mandibular-madrid/' ),
		array( 'name' => 'Calidad, firmeza y luminosidad de la piel', 'path' => '/calidad-piel-firmeza-luminosidad-madrid/' ),
		array( 'name' => 'Cicatrices de acné, poros y textura', 'path' => '/cicatrices-acne-poros-textura-madrid/' ),
		array( 'name' => 'Manchas, rojeces y fotodaño', 'path' => '/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/' ),
		array( 'name' => 'Grasa localizada en abdomen y flancos', 'path' => '/grasa-localizada-abdomen-flancos-madrid/' ),
		array( 'name' => 'Flacidez y grasa localizada en brazos', 'path' => '/flacidez-grasa-localizada-brazos-madrid/' ),
		array( 'name' => 'Grasa de espalda y zona del sujetador', 'path' => '/grasa-espalda-zona-sujetador-madrid/' ),
		array( 'name' => 'Flacidez en muslos internos y región subglútea', 'path' => '/flacidez-muslos-internos-subgluteo-madrid/' ),
		array( 'name' => 'Grasa localizada y flacidez en rodillas', 'path' => '/tratamiento-rodillas-grasa-flacidez-madrid/' ),
		array( 'name' => 'Contorno corporal masculino', 'path' => '/contorno-corporal-masculino-madrid/' ),
	);

	$list_items = array();
	foreach ( $items as $position => $item ) {
		$list_items[] = array(
			'@type'    => 'ListItem',
			'position' => $position + 1,
			'item'     => array(
				'@type'    => 'Service',
				'@id'      => home_url( $item['path'] . '#service' ),
				'name'     => $item['name'],
				'url'      => home_url( $item['path'] ),
				'provider' => array( '@id' => $organization_id ),
			),
		);
	}

	return array(
		'@type'         => 'ItemList',
		'@id'           => home_url( '/soluciones-medicas/#itemlist' ),
		'name'          => 'Soluciones médicas NUVANX',
		'numberOfItems' => count( $list_items ),
		'itemListElement' => $list_items,
	);
}

/**
 * Attach route-specific treatment and FAQ schema nodes.
 *
 * @param array<int, mixed>       $graph Schema graph, passed by reference.
 * @param int                     $page_id Current page identifier.
 * @param string                  $organization_id Organization schema identifier.
 * @param array<string, mixed>|null $physician Primary physician node.
 * @return void
 */
function nvx_schema_attach_treatment_and_faq( array &$graph, int $page_id, string $organization_id, ?array $physician ): void {
	$path = nvx_schema_current_path( $page_id );

	if ( nvx_schema_path_matches( $path, '/soluciones-medicas/' ) ) {
		$graph[] = nvx_schema_solution_item_list( $organization_id );
	}

	if ( nvx_schema_path_matches( $path, '/madrid/valoracion/' ) ) {
		$graph[] = array(
			'@type'       => 'Service',
			'@id'         => home_url( '/madrid/valoracion/#service' ),
			'name'        => 'Valoración médica NUVANX',
			'url'         => home_url( '/madrid/valoracion/' ),
			'provider'    => array( '@id' => $organization_id ),
			'areaServed'  => 'Madrid',
			'serviceType' => 'Valoración médica en medicina estética',
		);
	}

	if ( null !== $physician && is_page( 'equipo-medico' ) ) {
		$graph[] = array(
			'@type'      => 'ProfilePage',
			'@id'        => get_permalink( $page_id ) . '#profilepage',
			'url'        => get_permalink( $page_id ),
			'name'       => get_the_title( $page_id ),
			'mainEntity' => array( '@id' => $physician['@id'] ),
		);
	}
}

/**
 * Extend Yoast's schema graph with NUVANX medical organization entities.
 *
 * @param array<int, mixed> $graph Existing Yoast graph.
 * @return array<int, mixed>
 */
function nvx_extend_yoast_schema_graph( $graph ) {
	if ( ! is_array( $graph ) ) {
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

	$physicians = nvx_schema_build_physicians( $page_id, $organization['id'] );
	$physician  = ! empty( $physicians ) ? $physicians[0] : null;

	if ( null !== $organization['index'] ) {
		nvx_schema_enrich_organization( $graph, $organization['index'], $all_clinics, $physicians );
	}

	nvx_schema_attach_clinics_graph( $graph, $page_id, $organization, $all_clinics, $physicians, $clinic_ids );

	foreach ( $physicians as $person ) {
		$graph[] = $person;
	}

	nvx_schema_attach_publications( $graph, $page_id, $physicians );
	nvx_schema_attach_treatment_and_faq( $graph, $page_id, $organization['id'], $physician );

	return $graph;
}
add_filter( 'wpseo_schema_graph', 'nvx_extend_yoast_schema_graph', 20, 1 );

/**
 * Home document title — laser clinic intent (Yoast).
 *
 * @param string $title Current title.
 * @return string
 */
add_filter( 'the_content', 'nvxFilterStripEmbeddedJsonld', 5 );
