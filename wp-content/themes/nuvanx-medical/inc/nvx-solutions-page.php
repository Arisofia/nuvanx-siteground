<?php
/**
 * Canonical GitHub-managed medical solutions page.
 *
 * WordPress stores only the route marker. Visible markup is rendered from the
 * versioned template so database IDs may differ safely between environments.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load one versioned theme JSON catalog.
 *
 * The solutions template historically called this helper even though the
 * implementation and its source catalog were not committed. Keep the narrow
 * compatibility function here and delegate to the canonical validated loader.
 *
 * @return array<mixed>
 */
function nvx_theme_load_json_catalog( string $filename ): array {
	require_once __DIR__ . '/nvx-catalog-json.php';

	return nvx_catalog_json_load( $filename );
}

/**
 * Whether the current request/content belongs to the medical solutions hub.
 */
function nvx_content_is_solutions_page( string $content = '' ): bool {
	$queried_id = get_queried_object_id();
	if ( is_page() && $queried_id && (int) get_the_ID() === (int) $queried_id ) {
		$slug = (string) get_post_field( 'post_name', $queried_id );
		if ( 'soluciones-medicas' === $slug ) {
			return true;
		}
	}

	return str_contains( $content, 'NUVANX_STRATEGY_PAGE:solutions' );
}

/**
 * Replace the CMS marker/body with the canonical theme-owned template.
 *
 * @param string $content Original page content.
 */
function nvx_render_solutions_page( $content ): string {
	static $rendered = false;
	$content         = is_string( $content ) ? $content : '';

	if ( $rendered || is_admin() || ! is_main_query() || ! is_singular( 'page' ) || ! nvx_content_is_solutions_page( $content ) ) {
		return $content;
	}

	ob_start();
	get_template_part( 'template-parts/content/nvx-soluciones-medicas-github' );
	$markup = ob_get_clean();

	if ( ! is_string( $markup ) || '' === trim( $markup ) ) {
		return $content;
	}

	$rendered = true;

	return $markup;
}
add_filter( 'the_content', 'nvx_render_solutions_page', 11 );
