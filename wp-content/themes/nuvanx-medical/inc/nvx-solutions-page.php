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
 * Must remain idempotent so pre-body filter calls (e.g. SEO, excerpts) do not
 * consume the single template render before visible body output.
 *
 * @param string $content Original page content.
 */
function nvx_render_solutions_page( $content ): string {
	$content = is_string( $content ) ? $content : '';

	if ( is_admin() || ! is_main_query() || ! is_singular( 'page' ) || ! nvx_content_is_solutions_page( $content ) ) {
		return $content;
	}

	$template = get_template_directory() . '/template-parts/content/nvx-soluciones-medicas-github.php';
	if ( ! is_readable( $template ) ) {
		return $content;
	}

	try {
		ob_start();
		// Prefer direct include so a missing template-part does not fail silently.
		include $template;
		$markup = ob_get_clean();
	} catch ( Throwable $e ) {
		if ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'NUVANX solutions page render failed: ' . $e->getMessage() );
		}
		return $content;
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
