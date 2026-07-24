<?php
/**
 * Route guard for the Equipo Médico content renderer.
 *
 * The original renderer historically accepted generic copy markers such as
 * "equipo especialista". Those phrases also appear on unrelated institutional
 * pages, so content text must never be used as sufficient routing evidence.
 *
 * @package nuvanx-medical
 */

defined( 'ABSPATH' ) || exit;

/** Whether the current request is the canonical Equipo Médico page. */
function nvx_equipo_route_guard_matches(): bool {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	if ( ! is_singular( 'page' ) && ! is_page() ) {
		return false;
	}

	if ( is_page( 'equipo-medico' ) ) {
		return true;
	}

	$path = function_exists( 'nvx_schema_current_path' )
		? nvx_schema_current_path( (int) get_queried_object_id() )
		: '';

	return is_string( $path )
		&& function_exists( 'nvx_schema_path_matches' )
		&& nvx_schema_path_matches( $path, '/equipo-medico/' );
}

/** Apply the Equipo renderer only to its canonical route. */
function nvx_equipo_route_guard_render( string $content ): string {
	if ( ! nvx_equipo_route_guard_matches() ) {
		return $content;
	}

	return function_exists( 'nvx_content_restructure_equipo_page' )
		? nvx_content_restructure_equipo_page( $content )
		: $content;
}

remove_filter( 'the_content', 'nvx_content_restructure_equipo_page', 19 );
add_filter( 'the_content', 'nvx_equipo_route_guard_render', 19 );
