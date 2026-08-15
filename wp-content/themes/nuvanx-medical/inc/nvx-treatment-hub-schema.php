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
	require_once __DIR__ . '/nvx-catalog-json.php';

	// Raw JSON intentionally preserves the previous non-translated schema labels.
	$definitions = nvx_catalog_filter_records(
		nvx_catalog_json_load( 'treatment-hub-schema.json' ),
		array( 'path', 'types', 'key', 'name', 'description', 'procedureType', 'additionalFields' ),
		'treatment-hub-schema.json'
	);

	$items = array();
	foreach ( $definitions as $index => $definition ) {
		$url  = home_url( $definition['path'] );
		// Use canonical treatment entity ID instead of creating duplicate hub-specific ID
		$canonical_treatment_id = $url . '#medical-procedure';
		$item = array(
			'@type'       => $definition['types'],
			'@id'         => $canonical_treatment_id,
			'name'        => $definition['name'],
			'url'         => $url,
			'provider'    => array( '@id' => $organization_id ),
			'description' => $definition['description'],
			'areaServed'  => array( 'Madrid', 'Chamberí', 'Barrio de Salamanca', 'Goya' ),
		);

		if ( ! empty( $definition['procedureType'] ) ) {
			$item['procedureType'] = array( '@id' => $definition['procedureType'] );
		}

		if ( ! empty( $definition['additionalFields'] ) && is_array( $definition['additionalFields'] ) ) {
			foreach ( $definition['additionalFields'] as $extra_key => $extra_val ) {
				// Skip fields that are redundant (provider uses canonical @id) or invalid on MedicalProcedure/Service (priceRange, availableService)
				if ( in_array( $extra_key, array( 'provider', 'availableService', 'priceRange' ), true ) ) {
					continue;
				}
				$item[ $extra_key ] = $extra_val;
			}
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
	unset( $context );
	if ( ! is_array( $graph ) || ! function_exists( 'nvx_theme_is_treatments_hub' ) || ! nvx_theme_is_treatments_hub() ) {
		return $graph;
	}

	$page_id   = (int) get_queried_object_id();
	$permalink = get_permalink( $page_id );
	if ( ! is_string( $permalink ) || '' === $permalink ) {
		return $graph;
	}

	$organization    = function_exists( 'nvx_schema_find_organization' )
		? nvx_schema_find_organization( $graph )
		: array( 'id' => function_exists( 'nvx_schema_organization_id' ) ? nvx_schema_organization_id() : home_url( '/#organization' ) );
	$organization_id = ! empty( $organization['id'] )
		? (string) $organization['id']
		: ( function_exists( 'nvx_schema_organization_id' ) ? nvx_schema_organization_id() : home_url( '/#organization' ) );
	$list_id         = $permalink . '#treatments-list';
	$items           = nvx_treatment_hub_schema_items( $organization_id );

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
