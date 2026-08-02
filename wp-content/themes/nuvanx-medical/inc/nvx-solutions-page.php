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
 * Load one versioned theme JSON catalog (canonical theme-wide helper).
 *
 * Delegates to nvx_catalog_json_load so Signature, solutions and other
 * catalogues share one validation path.
 *
 * @param string $filename Basename under inc/data/.
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
 * Force the dedicated solutions template for the public hub route.
 *
 * Prefer the page template over the_content injection: nested output buffers
 * on this route have produced HTTP 200 with an empty body on staging2.
 *
 * @param string $template Resolved template path.
 */
function nvx_solutions_template_include( string $template ): string {
	if ( is_admin() || ! is_page() ) {
		return $template;
	}

	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	if ( 'soluciones-medicas' !== $slug ) {
		return $template;
	}

	$dedicated = get_template_directory() . '/templates/page-soluciones-medicas.php';
	return is_readable( $dedicated ) ? $dedicated : $template;
}
add_filter( 'template_include', 'nvx_solutions_template_include', 99 );

/**
 * Replace the CMS marker/body with the canonical theme-owned template.
 *
 * Kept for non-template contexts (excerpts, SEO, secondary loops) and as a
 * fallback when the dedicated page template is unavailable.
 *
 * @param string $content Original page content.
 */
function nvx_render_solutions_page( $content ): string {
	$content = is_string( $content ) ? $content : '';

	if ( is_admin() || ! is_main_query() || ! is_singular( 'page' ) || ! nvx_content_is_solutions_page( $content ) ) {
		return $content;
	}

	// Dedicated page template already rendered the hub.
	if ( function_exists( 'is_page_template' ) && is_page_template( 'templates/page-soluciones-medicas.php' ) ) {
		return $content;
	}

	$template = get_template_directory() . '/template-parts/content/nvx-soluciones-medicas-github.php';
	if ( ! is_readable( $template ) ) {
		return $content;
	}

	$level_before = ob_get_level();
	ob_start();
	include $template;
	$markup = ob_get_clean();
	// Never leave an orphan buffer if include aborted early.
	while ( ob_get_level() > $level_before ) {
		ob_end_clean();
	}

	return is_string( $markup ) && '' !== trim( $markup ) ? $markup : $content;
}
add_filter( 'the_content', 'nvx_render_solutions_page', 11 );

/**
 * Enqueue the canonical medical solutions page stylesheet on its route.
 */
function nvx_enqueue_solutions_page_assets(): void {
	if ( is_admin() || ! is_singular( 'page' ) || ! nvx_content_is_solutions_page() ) {
		return;
	}

	$css_relative = '/assets/css/nvx-soluciones-medicas.css';
	$css_path     = get_template_directory() . $css_relative;
	if ( ! file_exists( $css_path ) ) {
		return;
	}

	$version = function_exists( 'nvx_asset_version' )
		? nvx_asset_version( $css_relative )
		: ( (string) filemtime( $css_path ) );

	wp_enqueue_style(
		'nvx-soluciones-medicas',
		get_template_directory_uri() . $css_relative,
		array( 'nvx-components', 'nvx-patterns' ),
		$version
	);
}
add_action( 'wp_enqueue_scripts', 'nvx_enqueue_solutions_page_assets', 20 );
