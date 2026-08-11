<?php
/**
 * Permanent redirects for retired working protocol names.
 *
 * LipoSculpt-Air™ and V-Lift Awake™ were internal working names that never
 * completed medical/legal publication review. They must not remain addressable
 * as treatment offers even if legacy WordPress records are still published.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get WordPress page IDs for retired strategy slugs.
 *
 * @return int[]
 */
function nvx_retired_strategy_page_ids(): array {
	static $ids = null;
	if ( null !== $ids ) {
		return $ids;
	}

	$ids   = array();
	$slugs = array( 'liposculpt-air', 'tratamiento-retirado', 'v-lift-awake' );

	foreach ( $slugs as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page ) {
			$ids[] = (int) $page->ID;
		}
	}

	// Also search for nested pages with matching post_name
	$nested_query = new WP_Query(
		array(
			'post_type'      => 'page',
			'post_name__in' => $slugs,
			'fields'         => 'ids',
			'posts_per_page' => -1,
		)
	);

	if ( $nested_query->have_posts() ) {
		foreach ( $nested_query->posts as $post_id ) {
			$ids[] = (int) $post_id;
		}
	}

	return array_unique( $ids );
}

/**
 * Build redirect URL with preserved query string.
 *
 * @param string $target The target path.
 * @param string $query  The query string.
 * @return string The full redirect URL.
 */
function nvx_build_redirect_url( $target, $query ) {
	$redirect_url = home_url( $target );
	if ( '' !== $query ) {
		$query_args = array();
		wp_parse_str( $query, $query_args );
		if ( ! empty( $query_args ) ) {
			$redirect_url = add_query_arg( $query_args, $redirect_url );
		}
	}
	return $redirect_url;
}

/**
 * Redirect retired strategy slugs to their approved public clinical hubs.
 */
function nvx_redirect_retired_strategy_slugs(): void {
	if ( ( defined( 'WP_CLI' ) && WP_CLI ) || is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = strtolower( trim( (string) wp_parse_url( $uri, PHP_URL_PATH ), '/' ) );
	$query = isset( $_SERVER['QUERY_STRING'] ) ? (string) wp_unslash( $_SERVER['QUERY_STRING'] ) : '';

	$targets = array(
		'liposculpt-air'       => '/remodelacion-corporal-laser-madrid/',
		'tratamiento-retirado' => '/tratamientos/',
		'v-lift-awake'         => '/endolift-facial-papada-mandibula/',
	);

	// Check path-based redirect (top-level pages)
	if ( isset( $targets[ $path ] ) ) {
		$redirect_url = nvx_build_redirect_url( $targets[ $path ], $query );
		wp_safe_redirect( $redirect_url, 301, 'NUVANX' );
		exit;
	}

	// Check post_name-based redirect (nested pages, other post types)
	if ( is_singular() ) {
		$queried_object = get_queried_object();
		if ( $queried_object && isset( $queried_object->post_name ) && isset( $targets[ $queried_object->post_name ] ) ) {
			$redirect_url = nvx_build_redirect_url( $targets[ $queried_object->post_name ], $query );
			wp_safe_redirect( $redirect_url, 301, 'NUVANX' );
			exit;
		}
	}
}
add_action( 'template_redirect', 'nvx_redirect_retired_strategy_slugs', 0 );
