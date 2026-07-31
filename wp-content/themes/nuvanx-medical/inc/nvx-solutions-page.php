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
 * Whether the current request/content belongs to the medical solutions hub.
 */
function nvx_content_is_solutions_page( string $content = '' ): bool {
	if ( is_page() ) {
		$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
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
	$content = is_string( $content ) ? $content : '';
	if ( is_admin() || ! is_singular( 'page' ) || ! nvx_content_is_solutions_page( $content ) ) {
		return $content;
	}

	ob_start();
	get_template_part( 'template-parts/content/nvx-soluciones-medicas-github' );
	$markup = ob_get_clean();

	return is_string( $markup ) && '' !== trim( $markup ) ? $markup : $content;
}
add_filter( 'the_content', 'nvx_render_solutions_page', 11 );
