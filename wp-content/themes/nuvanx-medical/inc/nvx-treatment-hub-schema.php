<?php
/**
 * Canonical Yoast graph extension for the treatments hub.
 *
 * Structured data is emitted through wpseo_schema_graph only. Templates must
 * never print additional application/ld+json blocks.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Replace a graph node by @id or append it. */
function nvx_treatment_hub_schema_upsert_node( array $graph, array $node ): array {
	$id = isset( $node['@id'] ) ? (string) $node['@id'] : '';

	if ( '' !== $id ) {
		foreach ( $graph as $index => $piece ) {
			if ( isset( $piece['@id'] ) && $id === (string) $piece['@id'] ) {
				$graph[ $index ] = $node;
				return $graph;
			}
		}
	}

	$graph[] = $node;
	return $graph;
}

/** Canonical services and procedures represented by the visible catalogue. */
function nvx_treatment_hub_schema_items( string $organization_id ): array {
	$definitions = array(
		array(
			'key'           => 'endolift-facial',
			'name'          => 'Endolift® Facial',
			'path'          => '/endolift-facial-papada-mandibula/',
			'types'         => array( 'MedicalProcedure', 'Service' ),
			'procedureType' => 'https://schema.org/PercutaneousProcedure',
			'description'   => 'Técnica con microfibra láser subdérmica para abordar flacidez facial, papada y definición mandibular según valoración médica.',
		),
		array(
			'key'           => 'endolaser-corporal',
			'name'          => 'Endoláser Corporal',
			'path'          => '/endolaser-corporal-grasa-localizada/',
			'types'         => array( 'MedicalProcedure', 'Service' ),
			'procedureType' => 'https://schema.org/PercutaneousProcedure',
			'description'   => 'Procedimiento láser mínimamente invasivo para grasa localizada y retracción cutánea, indicado tras exploración médica.',
		),
		array(
			'key'           => 'laser-co2-fraccionado',
			'name'          => 'Láser CO₂ Fraccionado',
			'path'          => '/laser-co2-fraccionado-madrid-textura-cicatrices-poro/',
			'types'         => array( 'MedicalProcedure', 'Service' ),
			'procedureType' => 'https://schema.org/NoninvasiveProcedure',
			'description'   => 'Resurfacing fraccionado para textura, cicatrices, poros y fotodaño con parámetros y recuperación definidos por el médico.',
		),
		array(
			'key'           => 'exion-btl',
			'name'          => 'Plataforma EXION® BTL',
			'path'          => '/exion-btl/',
			'types'         => array( 'MedicalProcedure', 'Service' ),
			'procedureType' => 'https://schema.org/NoninvasiveProcedure',
			'description'   => 'Plataforma médica con aplicadores seleccionados según la zona, la calidad de la piel y el resultado esperado.',
		),
		array(
			'key'         => 'medicina-estetica-facial',
			'name'        => 'Medicina Estética Facial',
			'path'        => '/medicina-estetica/',
			'types'       => array( 'Service' ),
			'description' => 'Servicio médico de planificación facial conservadora con técnicas seleccionadas tras diagnóstico individual.',
		),
		array(
			'key'           => 'bioestimulacion-colageno',
			'name'          => 'Bioestimulación de colágeno',
			'path'          => '/medicina-estetica/',
			'types'         => array( 'MedicalProcedure', 'Service' ),
			'procedureType' => 'https://schema.org/PercutaneousProcedure',
			'description'   => 'Protocolos de estimulación tisular definidos según la calidad cutánea, la indicación médica y los objetivos individuales.',
		),
		array(
			'key'           => 'btl-exilite-ipl',
			'name'          => 'BTL EXILITE™ IPL',
			'path'          => '/btl-exilite-ipl-madrid/',
			'types'         => array( 'MedicalProcedure', 'Service' ),
			'procedureType' => 'https://schema.org/NoninvasiveProcedure',
			'description'   => 'Luz pulsada médica con parámetros adaptados al fototipo y a la indicación clínica.',
		),
	);

	$items = array();
	foreach ( $definitions as $index => $definition ) {
		$url  = home_url( $definition['path'] );
		$item = array(
			'@type'       => $definition['types'],
			'@id'         => $url . '#' . $definition['key'],
			'name'        => $definition['name'],
			'url'         => $url,
			'provider'    => array( '@id' => $organization_id ),
			'description' => $definition['description'],
			'areaServed'  => array( 'Madrid', 'Chamberí', 'Barrio de Salamanca', 'Goya' ),
		);

		if ( ! empty( $definition['procedureType'] ) ) {
			$item['procedureType'] = array( '@id' => $definition['procedureType'] );
		}

		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $index + 1,
			'url'      => $url,
			'item'     => $item,
		);
	}

	return $items;
}

/** Add the treatments ItemList to the existing Yoast graph. */
function nvx_treatment_hub_extend_yoast_graph( $graph, $context = null ) {
	if ( ! is_array( $graph ) || ! function_exists( 'nvx_theme_is_treatments_hub' ) || ! nvx_theme_is_treatments_hub() ) {
		return $graph;
	}

	$page_id   = (int) get_queried_object_id();
	$permalink = get_permalink( $page_id );
	if ( ! is_string( $permalink ) || '' === $permalink ) {
		return $graph;
	}

	$organization = function_exists( 'nvx_schema_find_organization' )
		? nvx_schema_find_organization( $graph )
		: array( 'id' => home_url( '/#/schema/organization/nuvanx' ) );
	$organization_id = ! empty( $organization['id'] )
		? (string) $organization['id']
		: home_url( '/#/schema/organization/nuvanx' );
	$list_id = $permalink . '#treatments-list';
	$items   = nvx_treatment_hub_schema_items( $organization_id );

	$graph = nvx_treatment_hub_schema_upsert_node(
		$graph,
		array(
			'@type'           => 'ItemList',
			'@id'             => $list_id,
			'name'            => 'Protocolos e indicaciones médicas NUVANX',
			'url'             => $permalink,
			'numberOfItems'   => count( $items ),
			'itemListElement' => $items,
		)
	);

	foreach ( $graph as $index => $piece ) {
		$types = isset( $piece['@type'] ) ? (array) $piece['@type'] : array();
		$url   = isset( $piece['url'] ) ? (string) $piece['url'] : '';
		if ( in_array( 'WebPage', $types, true ) && trailingslashit( $url ) === trailingslashit( $permalink ) ) {
			$graph[ $index ]['mainEntity'] = array( '@id' => $list_id );
			break;
		}
	}

	return array_values( $graph );
}
add_filter( 'wpseo_schema_graph', 'nvx_treatment_hub_extend_yoast_graph', 99, 2 );
