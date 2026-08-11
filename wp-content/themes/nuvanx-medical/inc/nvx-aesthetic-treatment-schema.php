<?php
/**
 * Yoast graph extensions for the four canonical facial treatment pages.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Replace a graph node by @id or append it. */
function nvx_aesthetic_schema_upsert_node( array $graph, array $node ): array {
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

/**
 * Map string names to Schema.org typed nodes.
 *
 * @param array<int,string> $names Display names.
 * @return array<int,array{@type:string,name:string}>
 */
function nvx_aesthetic_schema_named_nodes( array $names, string $type ): array {
	$nodes = array();
	foreach ( $names as $name ) {
		$nodes[] = array(
			'@type' => $type,
			'name'  => $name,
		);
	}
	return $nodes;
}

/**
 * Build the MedicalProcedure/Service node for a facial treatment page.
 *
 * @param array<string,mixed> $schema Schema catalog entry.
 * @param array<string,mixed> $entry  Treatment catalog entry.
 */
function nvx_aesthetic_schema_procedure_node(
	array $schema,
	array $entry,
	string $permalink,
	string $organization_id
): array {
	$node = array(
		'@type'             => array( 'MedicalProcedure', 'Service' ),
		'@id'               => $permalink . '#medical-procedure',
		'name'              => $schema['name'],
		'alternateName'     => $schema['alternateName'],
		'url'               => $permalink,
		'mainEntityOfPage'  => array( '@id' => $permalink ),
		'provider'          => array( '@id' => $organization_id ),
		'description'       => $entry['description'],
		'bodyLocation'      => $schema['bodyLocation'],
		'procedureType'     => $schema['procedureType'],
		'preparation'       => $schema['preparation'],
		'howPerformed'      => $schema['howPerformed'],
		'followup'          => $schema['followup'],
		'indication'        => nvx_aesthetic_schema_named_nodes( (array) $schema['indications'], 'MedicalIndication' ),
		'relevantCondition' => nvx_aesthetic_schema_named_nodes( (array) $schema['conditions'], 'MedicalCondition' ),
		'areaServed'        => array( 'Madrid', 'Chamberí', 'Barrio de Salamanca', 'Goya' ),
	);

	if ( ! empty( $entry['price_range'] ) ) {
		$node['priceRange'] = (string) $entry['price_range'];
	}
	if ( ! empty( $entry['session_time'] ) ) {
		$time_str = (string) $entry['session_time'];
		if ( preg_match( '/(\d+)/', $time_str, $matches ) ) {
			$node['duration'] = 'PT' . $matches[1] . 'M';
		}
	}


	return $node;
}


/**
 * Build FAQ Question nodes for a treatment key.
 *
 * @param array<int,array{q:string,a:string}> $faqs FAQ catalogue rows.
 * @return array<int,array<string,mixed>>
 */
function nvx_aesthetic_schema_faq_questions( array $faqs ): array {
	$questions = array();
	foreach ( $faqs as $faq ) {
		$questions[] = array(
			'@type'          => 'Question',
			'name'           => $faq['q'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $faq['a'],
			),
		);
	}
	return $questions;
}

/**
 * Point the matching WebPage node at the MedicalProcedure entity.
 *
 * @param array<int,array<string,mixed>> $graph Yoast graph.
 * @return array<int,array<string,mixed>>
 */
function nvx_aesthetic_schema_link_webpage_main_entity( array $graph, string $permalink, string $procedure_id ): array {
	foreach ( $graph as $index => $piece ) {
		$types = isset( $piece['@type'] ) ? (array) $piece['@type'] : array();
		if ( ! in_array( 'WebPage', $types, true ) || ! isset( $piece['url'] ) ) {
			continue;
		}
		if ( trailingslashit( $piece['url'] ) !== trailingslashit( $permalink ) ) {
			continue;
		}
		$graph[ $index ]['mainEntity'] = array( '@id' => $procedure_id );
		break;
	}
	return $graph;
}

/** Add MedicalProcedure/Service and FAQPage to the existing Yoast block. */
function nvx_aesthetic_treatment_extend_yoast_graph( $graph, $context = null ) {
	unset( $context );
	if ( ! is_array( $graph ) || ! function_exists( 'nvx_aesthetic_treatment_current_key' ) ) {
		return $graph;
	}

	$key = nvx_aesthetic_treatment_current_key();
	if ( null === $key ) {
		return $graph;
	}

	$catalog        = nvx_aesthetic_treatment_catalog();
	$schema_catalog = nvx_aesthetic_treatment_schema_catalog();
	$faq_catalog    = nvx_aesthetic_treatment_faq_catalog();
	if ( empty( $catalog[ $key ] ) || empty( $schema_catalog[ $key ] ) ) {
		return $graph;
	}

	$permalink       = get_permalink( get_queried_object_id() );
	$organization    = function_exists( 'nvx_schema_find_organization' )
		? nvx_schema_find_organization( $graph )
		: array( 'id' => home_url( '/#/schema/organization/nuvanx' ) );
	$organization_id = ! empty( $organization['id'] ) ? $organization['id'] : home_url( '/#/schema/organization/nuvanx' );
	$procedure       = nvx_aesthetic_schema_procedure_node(
		$schema_catalog[ $key ],
		$catalog[ $key ],
		$permalink,
		$organization_id
	);
	$graph           = nvx_aesthetic_schema_upsert_node( $graph, $procedure );

	$questions = nvx_aesthetic_schema_faq_questions( $faq_catalog[ $key ] ?? array() );
	if ( ! empty( $questions ) ) {
		$graph = nvx_aesthetic_schema_upsert_node(
			$graph,
			array(
				'@type'      => 'FAQPage',
				'@id'        => $permalink . '#faq',
				'url'        => $permalink,
				'mainEntity' => $questions,
			)
		);
	}

	$graph = nvx_aesthetic_schema_link_webpage_main_entity( $graph, $permalink, $procedure['@id'] );

	return array_values( $graph );
}
add_filter( 'wpseo_schema_graph', 'nvx_aesthetic_treatment_extend_yoast_graph', 99, 2 );
