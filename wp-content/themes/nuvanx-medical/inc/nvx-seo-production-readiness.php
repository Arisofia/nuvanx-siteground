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
 * Build FAQPage from the same BTL registry that renders the visible accordion.
 *
 * @param int $page_id Current page ID.
 * @return array|null
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
 * Final Schema graph normalization after the main NUVANX and facial filters.
 *
 * @param array $graph   Yoast Schema graph.
 * @param mixed $context Yoast context.
 * @return array
 */
function nvx_seo_production_readiness_schema_graph( $graph, $context = null ) {
	if ( ! is_array( $graph ) || is_admin() || is_feed() ) {
		return $graph;
	}

	$organization = function_exists( 'nvx_schema_find_organization' )
		? nvx_schema_find_organization( $graph )
		: array( 'index' => null, 'id' => home_url( '/#/schema/organization/nuvanx' ) );
	$organization_id = ! empty( $organization['id'] ) ? (string) $organization['id'] : home_url( '/#/schema/organization/nuvanx' );
	$clinic_refs     = array();

	foreach ( $graph as $piece ) {
		if ( ! empty( $piece['@id'] ) && nvx_seo_schema_has_type( $piece['@type'] ?? array(), 'MedicalClinic' ) ) {
			$clinic_refs[] = array( '@id' => (string) $piece['@id'] );
		}
	}

	if ( null !== $organization['index'] && isset( $graph[ $organization['index'] ] ) ) {
		$index = $organization['index'];
		$graph[ $index ]['@type'] = nvx_seo_schema_add_type( $graph[ $index ]['@type'] ?? array(), 'MedicalOrganization' );
		if ( ! empty( $clinic_refs ) ) {
			$graph[ $index ]['department'] = $clinic_refs;
			unset( $graph[ $index ]['subOrganization'] );
		}
	}

	$current_url = nvx_seo_schema_current_page_url();
	$page_id     = (int) get_queried_object_id();
	$current_key = function_exists( 'nvx_schema_resolve_treatment_key' ) ? nvx_schema_resolve_treatment_key( $page_id ) : null;
	$noninvasive_keys = array( 'exion_btl', 'exion_face', 'exion_body', 'exion_fractional', 'emfusion', 'exilite_btl' );
	$main_entity_id   = '';

	foreach ( $graph as $index => $piece ) {
		$types = $piece['@type'] ?? array();

		if ( nvx_seo_schema_has_type( $types, 'MedicalClinic' ) ) {
			$graph[ $index ]['parentOrganization'] = array( '@id' => $organization_id );
			$graph[ $index ]['priceRange']         = $graph[ $index ]['priceRange'] ?? '€€€';
			$graph[ $index ]['medicalSpecialty']   = $graph[ $index ]['medicalSpecialty'] ?? array( 'Aesthetic Medicine', 'Laser Medicine' );

			if ( ! empty( $graph[ $index ]['openingHoursSpecification'] ) && is_array( $graph[ $index ]['openingHoursSpecification'] ) ) {
				foreach ( $graph[ $index ]['openingHoursSpecification'] as $hours_index => $hours ) {
					if ( isset( $hours['dayOfWeek'] ) ) {
						$graph[ $index ]['openingHoursSpecification'][ $hours_index ]['dayOfWeek'] = nvx_seo_schema_normalize_days( $hours['dayOfWeek'] );
					}
				}
			}
		}

		$piece_url = isset( $piece['url'] ) ? trailingslashit( (string) $piece['url'] ) : '';
		if (
			null !== $current_key
			&& in_array( $current_key, $noninvasive_keys, true )
			&& '' !== $piece_url
			&& $piece_url === trailingslashit( $current_url )
			&& nvx_seo_schema_has_type( $types, 'Service' )
		) {
			$graph[ $index ]['@type']         = nvx_seo_schema_add_type( $types, 'MedicalProcedure' );
			$graph[ $index ]['procedureType'] = $graph[ $index ]['procedureType'] ?? 'https://schema.org/NoninvasiveProcedure';
			$graph[ $index ]['areaServed']    = $graph[ $index ]['areaServed'] ?? array( 'Madrid', 'Chamberí', 'Barrio de Salamanca', 'Goya' );
			if ( ! empty( $graph[ $index ]['@id'] ) ) {
				$main_entity_id = (string) $graph[ $index ]['@id'];
			}
		}
	}

	$faq = nvx_seo_schema_btl_faq_node( $page_id );
	if ( null !== $faq ) {
		$graph = nvx_seo_schema_upsert_node( $graph, $faq );
	}

	if ( '' !== $main_entity_id ) {
		foreach ( $graph as $index => $piece ) {
			$types = $piece['@type'] ?? array();
			$url   = isset( $piece['url'] ) ? trailingslashit( (string) $piece['url'] ) : '';
			if ( nvx_seo_schema_has_type( $types, 'WebPage' ) && $url === trailingslashit( $current_url ) ) {
				$graph[ $index ]['mainEntity'] = array( '@id' => $main_entity_id );
				break;
			}
		}
	}

	return array_values( $graph );
}
add_filter( 'wpseo_schema_graph', 'nvx_seo_production_readiness_schema_graph', 120, 2 );
