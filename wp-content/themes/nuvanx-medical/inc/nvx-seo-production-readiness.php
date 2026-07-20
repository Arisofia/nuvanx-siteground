<?php
/**
 * Production SEO readiness guard and final Yoast Schema graph normalization.
 *
 * Staging and every non-public host remain noindex at both meta and HTTP-header
 * level. Production keeps page-level hygiene rules while exposing one coherent
 * MedicalOrganization graph with branch clinics, medical procedures and FAQs
 * sourced from the same visible-content registries.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add an HTTP-level noindex guard outside the public production host.
 *
 * This complements the Yoast and Core robots filters in nvx-seo-metadata.php.
 * It intentionally does not emit an `index` header in production because
 * page-level noindex directives must remain authoritative for transactional and
 * unpublished-evidence pages.
 *
 * @param array<string,string> $headers Response headers.
 * @return array<string,string>
 */
function nvx_seo_nonproduction_x_robots_headers( $headers ): array {
	$headers = is_array( $headers ) ? $headers : array();

	if ( function_exists( 'nvx_seo_is_nonproduction_environment' ) && nvx_seo_is_nonproduction_environment() ) {
		$headers['X-Robots-Tag'] = 'noindex, nofollow, noarchive, nosnippet';
	}

	return $headers;
}
add_filter( 'wp_headers', 'nvx_seo_nonproduction_x_robots_headers', 100 );

/** Add a Schema.org type without discarding existing types. */
function nvx_seo_schema_add_type( $types, string $type ): array {
	if ( function_exists( 'nvx_schema_add_type' ) ) {
		return nvx_schema_add_type( $types, $type );
	}

	$types = is_array( $types ) ? $types : array( $types );
	$types = array_values( array_filter( $types ) );
	if ( ! in_array( $type, $types, true ) ) {
		$types[] = $type;
	}

	return $types;
}

/** Return whether a Schema.org type is present. */
function nvx_seo_schema_has_type( $types, string $type ): bool {
	if ( function_exists( 'nvx_schema_has_type' ) ) {
		return nvx_schema_has_type( $types, $type );
	}

	return in_array( $type, is_array( $types ) ? $types : array( $types ), true );
}

/** Replace a graph node by @id or append it. */
function nvx_seo_schema_upsert_node( array $graph, array $node ): array {
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

/** Normalize dayOfWeek values to canonical Schema.org URLs. */
function nvx_seo_schema_normalize_days( $days ) {
	$map = array(
		'Monday'    => 'https://schema.org/Monday',
		'Tuesday'   => 'https://schema.org/Tuesday',
		'Wednesday' => 'https://schema.org/Wednesday',
		'Thursday'  => 'https://schema.org/Thursday',
		'Friday'    => 'https://schema.org/Friday',
		'Saturday'  => 'https://schema.org/Saturday',
		'Sunday'    => 'https://schema.org/Sunday',
	);

	$normalize = static function ( $day ) use ( $map ) {
		$day = (string) $day;
		return $map[ $day ] ?? $day;
	};

	return is_array( $days ) ? array_values( array_map( $normalize, $days ) ) : $normalize( $days );
}

/** Current canonical page URL for graph matching. */
function nvx_seo_schema_current_page_url(): string {
	if ( is_front_page() ) {
		return home_url( '/' );
	}

	$page_id = (int) get_queried_object_id();
	$url     = $page_id > 0 ? get_permalink( $page_id ) : '';

	return is_string( $url ) && '' !== $url ? $url : home_url( '/' );
}

/**
 * Builds an FAQPage schema node from the BTL FAQ registry for the current page.
 *
 * @param int $page_id Current page ID used to resolve the treatment FAQ set.
 * @return array|null The FAQPage schema node, or null when no applicable FAQs exist.
 */
function nvx_seo_schema_btl_faq_node( int $page_id ): ?array {
	if ( ! function_exists( 'nvx_schema_resolve_treatment_key' ) || ! function_exists( 'nvx_btl_detail_registry' ) ) {
		return null;
	}

	$key      = nvx_schema_resolve_treatment_key( $page_id );
	$slug_map = array(
		'exion_face'       => 'exion-face',
		'exion_body'       => 'exion-body',
		'exion_fractional' => 'exion-fractional',
		'emfusion'         => 'emfusion',
	);
	$slug     = isset( $slug_map[ $key ] ) ? $slug_map[ $key ] : '';
	$registry = nvx_btl_detail_registry();
	$faqs     = '' !== $slug && ! empty( $registry[ $slug ]['faqs'] ) ? $registry[ $slug ]['faqs'] : array();

	if ( empty( $faqs ) ) {
		return null;
	}

	$questions = array();
	foreach ( $faqs as $faq ) {
		if ( empty( $faq['q'] ) || empty( $faq['a'] ) ) {
			continue;
		}
		$questions[] = array(
			'@type'          => 'Question',
			'name'           => wp_strip_all_tags( (string) $faq['q'] ),
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => wp_kses_post( (string) $faq['a'] ),
			),
		);
	}

	if ( empty( $questions ) ) {
		return null;
	}

	$url = nvx_seo_schema_current_page_url();
	return array(
		'@type'      => 'FAQPage',
		'@id'        => $url . '#faq',
		'url'        => $url,
		'mainEntity' => $questions,
	);
}

/**
 * Adds clinic references and the MedicalOrganization type to the organization node.
 *
 * @param array $graph The Schema.org graph.
 * @param string $organization_id The organization identifier used by related nodes.
 * @return array The enriched Schema.org graph.
 */

/**
 * Adds organization relationships and default metadata to MedicalClinic nodes.
 *
 * @param array $graph The Schema.org graph.
 * @param string $organization_id The parent organization identifier.
 * @return array The enriched Schema.org graph.
 */

/**
 * Promotes the matching noninvasive service to a MedicalProcedure.
 *
 * @param array $graph The Schema.org graph.
 * @param string $current_url The canonical URL of the current page.
 * @param int $page_id The current page identifier.
 * @return array{0: array, 1: string} The updated graph and the promoted procedure identifier.
 */

/**
 * Links the matching WebPage node to the promoted main entity.
 *
 * @param array $graph The Schema.org graph.
 * @param string $current_url The canonical URL of the current page.
 * @param string $main_entity_id The identifier of the main entity.
 * @return array The updated Schema.org graph.
 */
function nvx_seo_production_readiness_schema_graph( $graph, $context = null ) {
	if ( ! is_array( $graph ) || is_admin() || is_feed() ) {
		return $graph;
	}

	$organization = function_exists( 'nvx_schema_find_organization' )
		? nvx_schema_find_organization( $graph )
		: array( 'index' => null, 'id' => home_url( '/#/schema/organization/nuvanx' ) );
	$organization_id = ! empty( $organization['id'] ) ? (string) $organization['id'] : home_url( '/#/schema/organization/nuvanx' );

	$graph = _nvx_seo_schema_enrich_organization( $graph, $organization_id );
	$graph = _nvx_seo_schema_enrich_clinics( $graph, $organization_id );

	$current_url = nvx_seo_schema_current_page_url();
	$page_id     = (int) get_queried_object_id();

	list( $graph, $main_entity_id ) = _nvx_seo_schema_promote_services( $graph, $current_url, $page_id );

	$faq = nvx_seo_schema_btl_faq_node( $page_id );
	if ( null !== $faq ) {
		$graph = nvx_seo_schema_upsert_node( $graph, $faq );
	}

	$graph = _nvx_seo_schema_link_main_entity( $graph, $current_url, $main_entity_id );

	return array_values( $graph );
}
add_filter( 'wpseo_schema_graph', 'nvx_seo_production_readiness_schema_graph', 120, 2 );
