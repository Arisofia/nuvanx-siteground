<?php
/**
 * NUVANX · FAQ Catalog — Single source of truth
 *
 * The homepage FAQ JSON is the canonical source for visible FAQ and schema.
 * This adapter selects the stable entries used by the compact global block.
 *
 * @package nuvanx-medical
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns the global FAQ selection as ['q' => string, 'a' => string].
 *
 * @return array<int, array{q: string, a: string}>
 */
function nvx_get_faq_catalog(): array {
	static $catalog = null;

	if ( null !== $catalog ) {
		return $catalog;
	}

	require_once __DIR__ . '/nvx-catalog-json.php';
	$source = nvx_catalog_json_resolved( 'home-faq-v2.json' );
	$ids    = array(
		'valoracion-medica',
		'precio-endolift',
		'duracion-endolift',
		'sesiones-co2',
		'tecnologia-medica',
		'exion-btl',
		'tratamiento-adecuado',
		'recuperacion',
		'diferencia-estetica',
		'clinicas-madrid',
		'equipo-medico',
	);
	$by_id = array();
	foreach ( $source as $entry ) {
		if ( is_array( $entry ) && isset( $entry['id'], $entry['q'], $entry['a'] ) ) {
			$by_id[ $entry['id'] ] = $entry;
		}
	}

	$catalog = array();
	foreach ( $ids as $id ) {
		if ( isset( $by_id[ $id ] ) ) {
			$catalog[] = array(
				'q' => $by_id[ $id ]['q'],
				'a' => $by_id[ $id ]['a'],
			);
		}
	}

	return $catalog;
}

/**
 * Builds the FAQPage JSON-LD schema for the site's FAQ catalog.
 *
 * @return array<string, mixed>
 */
function nvx_get_faqpage_schema(): array {
	$faqs        = nvx_get_faq_catalog();
	$main_entity = array();
	foreach ( $faqs as $item ) {
		$main_entity[] = array(
			'@type'          => 'Question',
			'name'           => $item['q'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $item['a'],
			),
		);
	}
	return array(
		'@type'      => 'FAQPage',
		'@id'        => home_url( '/#faqpage' ),
		'mainEntity' => $main_entity,
	);
}

/** Inject FAQPage node into Yoast SEO graph on the front page. */
function nvx_inject_faqpage_schema_graph( array $data ): array {
	if ( is_front_page() ) {
		$data[] = nvx_get_faqpage_schema();
	}
	return $data;
}
add_filter( 'wpseo_schema_graph', 'nvx_inject_faqpage_schema_graph' );
