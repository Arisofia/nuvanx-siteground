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

/** Add MedicalProcedure/Service and FAQPage to the existing Yoast block. */
function nvx_aesthetic_treatment_extend_yoast_graph( $graph, $context = null ) {
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

	$page_id      = get_queried_object_id();
	$permalink    = get_permalink( $page_id );
	$entry        = $catalog[ $key ];
	$schema       = $schema_catalog[ $key ];
	$organization = function_exists( 'nvx_schema_find_organization' )
		? nvx_schema_find_organization( $graph )
		: array( 'id' => home_url( '/#/schema/organization/nuvanx' ) );
	$organization_id = ! empty( $organization['id'] ) ? $organization['id'] : home_url( '/#/schema/organization/nuvanx' );

	$indications = array();
	foreach ( $schema['indications'] as $name ) {
		$indications[] = array(
			'@type' => 'MedicalIndication',
			'name'  => $name,
		);
	}
	$conditions = array();
	foreach ( $schema['conditions'] as $name ) {
		$conditions[] = array(
			'@type' => 'MedicalCondition',
			'name'  => $name,
		);
	}

	$procedure_id = $permalink . '#medical-procedure';
	$procedure    = array(
		'@type'            => array( 'MedicalProcedure', 'Service' ),
		'@id'              => $procedure_id,
		'name'             => $schema['name'],
		'alternateName'    => $schema['alternateName'],
		'url'              => $permalink,
		'mainEntityOfPage' => array( '@id' => $permalink ),
		'provider'         => array( '@id' => $organization_id ),
		'description'      => $entry['description'],
		'bodyLocation'     => $schema['bodyLocation'],
		'procedureType'    => $schema['procedureType'],
		'preparation'      => $schema['preparation'],
		'howPerformed'     => $schema['howPerformed'],
		'followup'         => $schema['followup'],
		'indication'       => $indications,
		'relevantCondition'=> $conditions,
		'areaServed'       => array( 'Madrid', 'Chamberí', 'Barrio de Salamanca', 'Goya' ),
	);
	$graph = nvx_aesthetic_schema_upsert_node( $graph, $procedure );

	$questions = array();
	foreach ( $faq_catalog[ $key ] ?? array() as $faq ) {
		$questions[] = array(
			'@type'          => 'Question',
			'name'           => $faq['q'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $faq['a'],
			),
		);
	}
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

	foreach ( $graph as $index => $piece ) {
		$types = isset( $piece['@type'] ) ? (array) $piece['@type'] : array();
		if ( in_array( 'WebPage', $types, true ) && isset( $piece['url'] ) && trailingslashit( $piece['url'] ) === trailingslashit( $permalink ) ) {
			$graph[ $index ]['mainEntity'] = array( '@id' => $procedure_id );
			break;
		}
	}

	return array_values( $graph );
}
add_filter( 'wpseo_schema_graph', 'nvx_aesthetic_treatment_extend_yoast_graph', 99, 2 );
