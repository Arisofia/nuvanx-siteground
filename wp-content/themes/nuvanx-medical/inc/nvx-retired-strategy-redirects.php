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
 * Redirect retired strategy slugs to their approved public clinical hubs.
 */
function nvx_redirect_retired_strategy_slugs(): void {
	if ( ( defined( 'WP_CLI' ) && WP_CLI ) || is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = strtolower( trim( (string) wp_parse_url( $uri, PHP_URL_PATH ), '/' ) );

	$targets = array(
		'liposculpt-air' => '/remodelacion-corporal-laser-madrid/',
		'v-lift-awake'   => '/endolift-facial-papada-mandibula/',
	);

	if ( ! isset( $targets[ $path ] ) ) {
		return;
	}

	wp_safe_redirect( home_url( $targets[ $path ] ), 301, 'NUVANX' );
	exit;
}
add_action( 'template_redirect', 'nvx_redirect_retired_strategy_slugs', 0 );
