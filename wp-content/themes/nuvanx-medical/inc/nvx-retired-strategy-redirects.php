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
	$ids = array();
	$slugs = array( 'liposculpt-air', 'v-lift-awake' );

	foreach ( $slugs as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page ) {
			$ids[] = (int) $page->ID;
		}
	}

	return $ids;
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
	$query = isset( $_SERVER['QUERY_STRING'] ) ? $_SERVER['QUERY_STRING'] : '';

	$targets = array(
		'liposculpt-air' => '/remodelacion-corporal-laser-madrid/',
		'v-lift-awake'   => '/endolift-facial-papada-mandibula/',
	);

	if ( ! isset( $targets[ $path ] ) ) {
		return;
	}

	$redirect_url = home_url( $targets[ $path ] );
	if ( '' !== $query ) {
		$query_args = array();
		wp_parse_str( wp_unslash( $query ), $query_args );
		if ( ! empty( $query_args ) ) {
			$redirect_url = add_query_arg( $query_args, $redirect_url );
		}
	}

	wp_safe_redirect( $redirect_url, 301, 'NUVANX' );
	exit;
}
add_action( 'template_redirect', 'nvx_redirect_retired_strategy_slugs', 0 );
