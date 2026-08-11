<?php
/**
 * Canonical WebSite schema ownership.
 *
 * Yoast and the NUVANX medical graph can both contribute the canonical
 * `/#website` node. Merge those contributions before the final @id dedupe so
 * output does not depend on filter insertion order and Yoast properties such as
 * SearchAction/inLanguage are not lost.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Merge duplicate canonical WebSite nodes into their first graph position.
 *
 * @param array $graph Yoast schema graph.
 * @return array
 */
function nvx_schema_merge_canonical_website_nodes( $graph ) {
	if ( ! is_array( $graph ) || is_admin() || is_feed() || ! is_front_page() ) {
		return $graph;
	}

	$website_id = home_url( '/#website' );
	$first_key  = null;
	$merged     = array();

	foreach ( $graph as $key => $node ) {
		if ( ! is_array( $node ) || ( $node['@id'] ?? '' ) !== $website_id ) {
			continue;
		}

		$types = isset( $node['@type'] ) ? (array) $node['@type'] : array();
		if ( ! in_array( 'WebSite', $types, true ) ) {
			continue;
		}

		if ( null === $first_key ) {
			$first_key = $key;
			$merged    = $node;
			continue;
		}

		// Later NUVANX additions may override scalar editorial fields while fields
		// present only in Yoast's original node remain intact.
		$merged = array_replace( $merged, $node );
		unset( $graph[ $key ] );
	}

	if ( null !== $first_key ) {
		$graph[ $first_key ] = $merged;
	}

	return array_values( $graph );
}
add_filter( 'wpseo_schema_graph', 'nvx_schema_merge_canonical_website_nodes', 21, 1 );
