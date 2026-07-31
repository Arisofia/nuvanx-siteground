<?php
/**
 * NUVANX · FAQ Catalog — Single source of truth
 *
 * Returns the canonical FAQ array used by:
 *   1. The visible HTML FAQ block (frontend)
 *   2. The Yoast FAQPage JSON-LD schema node
 *
 * Usage:
 *   $faqs = nvx_get_faq_catalog();
 *
 * To add or edit an FAQ entry, edit this file only.
 * Do NOT maintain separate copies in the Yoast SEO settings
 * or hardcode FAQ HTML elsewhere.
 *
 * @package nuvanx-medical
 * @version 2.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns the FAQ catalog as an array of ['q' => string, 'a' => string].
 *
 * @return array<int, array{q: string, a: string}>
 */
function nvx_get_faq_catalog(): array {
	require_once __DIR__ . '/nvx-catalog-json.php';

	return nvx_catalog_resolve_tokens(
		nvx_catalog_json_load( 'faq-catalog.json' ),
		null
	);
}

/**
 * Renders the FAQ section using the canonical FAQ catalog.
 */
function nvx_render_faq_block(): void {
	$faqs = nvx_get_faq_catalog();
	if ( empty( $faqs ) ) {
		return;
	}
	echo '<section class="nvx-faq" aria-labelledby="nvx-faq-heading">';
	echo '<h2 id="nvx-faq-heading">' . esc_html__( 'Preguntas frecuentes', 'nuvanx-medical' ) . '</h2>';
	echo '<p class="nvx-faq__intro">' . esc_html__( 'Información clara antes de decidir', 'nuvanx-medical' ) . '</p>';
	echo '<dl class="nvx-faq__list">';
	foreach ( $faqs as $item ) {
		echo '<div class="nvx-faq__item">';
		echo '<dt class="nvx-faq__question">' . esc_html( $item['q'] ) . '</dt>';
		echo '<dd class="nvx-faq__answer">' . esc_html( $item['a'] ) . '</dd>';
		echo '</div>';
	}
	echo '</dl>';
	echo '</section>';
}

/**
 * Builds the FAQPage JSON-LD schema for the site's FAQ catalog.
 *
 * @return array<string, mixed> The FAQPage schema with its canonical identifier and questions.
 */
function nvx_get_faqpage_schema(): array {
	$faqs        = nvx_get_faq_catalog();
	$main_entity = [];
	foreach ( $faqs as $item ) {
		$main_entity[] = [
			'@type'          => 'Question',
			'name'           => $item['q'],
			'acceptedAnswer' => [
				'@type' => 'Answer',
				'text'  => $item['a'],
			],
		];
	}
	return [
		'@type'      => 'FAQPage',
		'@id'        => home_url( '/#faqpage' ),
		'mainEntity' => $main_entity,
	];
}

/**
 * Inject FAQPage node into Yoast SEO graph on the front page.
 */
function nvx_inject_faqpage_schema_graph( array $data ): array {
	if ( is_front_page() ) {
		$data[] = nvx_get_faqpage_schema();
	}
	return $data;
}
add_filter( 'wpseo_schema_graph', 'nvx_inject_faqpage_schema_graph' );
