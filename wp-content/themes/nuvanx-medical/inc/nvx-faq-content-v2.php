<?php
/**
 * Canonical home FAQ catalogue for Yoast schema.
 *
 * Visible home FAQ markup was retired with the theme-owned front-page template
 * (no the_content on home). Schema still uses this catalogue on the front page.
 *
 * GEO pattern: first sentence answers the question directly (same model as Endolift FAQ).
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @return array<int,array{id:string,q:string,a:string}> */
function nvx_home_faq_v2_catalog(): array {
	require_once __DIR__ . '/nvx-catalog-json.php';

	return nvx_catalog_filter_records(
		nvx_catalog_json_resolved( 'home-faq-v2.json' ),
		array( 'id', 'q', 'a' ),
		'home-faq-v2.json'
	);
}

/** Return whether a Schema.org @type value contains the requested type. */
function nvx_home_faq_v2_has_type( $types, string $type ): bool {
	return in_array( $type, is_array( $types ) ? $types : array( $types ), true );
}

/** Build Question nodes from the same catalogue used for schema. */
function nvx_home_faq_v2_schema_entities(): array {
	$entities = array();
	foreach ( nvx_home_faq_v2_catalog() as $faq ) {
		if ( empty( $faq['q'] ) || empty( $faq['a'] ) ) {
			continue;
		}
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => $faq['q'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $faq['a'],
			),
		);
	}
	return $entities;
}

/**
 * Pick the preferred graph index for homepage FAQ consolidation.
 *
 * Preference order: WebPage+FAQPage, FAQPage, WebPage.
 *
 * @param array<int,mixed> $graph Yoast graph.
 * @return int|string|null
 */
function nvx_home_faq_v2_preferred_schema_index( array $graph ) {
	$preferred        = null;
	$fallback_faq     = null;
	$fallback_webpage = null;

	foreach ( $graph as $index => $piece ) {
		if ( ! is_array( $piece ) || ! isset( $piece['@type'] ) ) {
			continue;
		}
		$is_faq = nvx_home_faq_v2_has_type( $piece['@type'], 'FAQPage' );
		$is_web = nvx_home_faq_v2_has_type( $piece['@type'], 'WebPage' );
		if ( $is_faq && $is_web ) {
			return $index;
		}
		if ( $is_faq && null === $fallback_faq ) {
			$fallback_faq = $index;
		}
		if ( $is_web && null === $fallback_webpage ) {
			$fallback_webpage = $index;
		}
	}

	return null !== $fallback_faq ? $fallback_faq : $fallback_webpage;
}

/**
 * Ensure the preferred node carries FAQPage + mainEntity and drop duplicates.
 *
 * @param array<int,array<string,mixed>> $graph Yoast graph.
 * @param int|string                     $preferred Preferred index.
 * @return array<int,array<string,mixed>>
 */
function nvx_home_faq_v2_apply_schema_entities( array $graph, $preferred ): array {
	$types = isset( $graph[ $preferred ]['@type'] ) && is_array( $graph[ $preferred ]['@type'] )
		? $graph[ $preferred ]['@type']
		: array( $graph[ $preferred ]['@type'] ?? 'WebPage' );
	if ( ! in_array( 'FAQPage', $types, true ) ) {
		$types[] = 'FAQPage';
	}
	$graph[ $preferred ]['@type']      = array_values( array_unique( array_filter( $types ) ) );
	$graph[ $preferred ]['mainEntity'] = nvx_home_faq_v2_schema_entities();
	$graph[ $preferred ]['url']        = $graph[ $preferred ]['url'] ?? home_url( '/' );
	$graph[ $preferred ]['@id']        = $graph[ $preferred ]['@id'] ?? home_url( '/#webpage' );

	foreach ( array_keys( $graph ) as $index ) {
		if ( $index === $preferred || ! isset( $graph[ $index ]['@type'] ) ) {
			continue;
		}
		if ( nvx_home_faq_v2_has_type( $graph[ $index ]['@type'], 'FAQPage' ) ) {
			unset( $graph[ $index ] );
		}
	}

	return array_values( $graph );
}

/**
 * Consolidate the homepage FAQ into one Yoast graph node.
 *
 * Preference order: an existing WebPage+FAQPage, an existing FAQPage, an
 * existing WebPage, or a new FAQPage. Every other FAQPage node is removed.
 */
function nvx_home_faq_v2_schema_graph( array $graph, $context = null ): array {
	unset( $context );
	if ( ! is_front_page() ) {
		return $graph;
	}

	$preferred = nvx_home_faq_v2_preferred_schema_index( $graph );
	if ( null === $preferred ) {
		$graph[]   = array(
			'@type' => array( 'WebPage', 'FAQPage' ),
			'@id'   => home_url( '/#webpage' ),
			'url'   => home_url( '/' ),
		);
		$preferred = array_key_last( $graph );
	}

	return nvx_home_faq_v2_apply_schema_entities( $graph, $preferred );
}
add_filter( 'wpseo_schema_graph', 'nvx_home_faq_v2_schema_graph', 99, 2 );
