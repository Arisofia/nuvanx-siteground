<?php
/**
 * Integraciones de infraestructura del tema (sin parches de presentación).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Goya (1537): evita bucle redirect_canonical. */
function nvx_theme_is_goya_page(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( is_page( 1537 ) ) {
		return true;
	}
	$path = isset( $_SERVER['REQUEST_URI'] ) ? strtok( (string) $_SERVER['REQUEST_URI'], '?' ) : '';
	return '/' . trim( $path, '/' ) . '/' === '/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/';
}

add_filter(
	'redirect_canonical',
	function ( $redirect_url ) {
		return nvx_theme_is_goya_page() ? false : $redirect_url;
	},
	9999,
	1
);

add_action(
	'template_redirect',
	function () {
		if ( nvx_theme_is_goya_page() ) {
			remove_action( 'template_redirect', 'redirect_canonical' );
		}
	},
	-999999
);

/** Redirect 301 slug antiguo privacidad. */
add_action(
	'template_redirect',
	function () {
		if ( is_admin() ) {
			return;
		}
		$path = isset( $_SERVER['REQUEST_URI'] ) ? strtok( (string) $_SERVER['REQUEST_URI'], '?' ) : '';
		$norm = '/' . trim( $path, '/' ) . '/';
		if ( $norm === '/politica-de-privacidad/' ) {
			wp_safe_redirect( home_url( '/politica-privacidad/' ), 301 );
			exit;
		}
	},
	1
);

/** Viewport accesible (zoom móvil). */
add_action(
	'template_redirect',
	function () {
		if ( is_admin() ) {
			return;
		}
		ob_start(
			function ( $html ) {
				return preg_replace(
					'/<meta\s+name=["\']viewport["\'][^>]*>/i',
					'<meta name="viewport" content="width=device-width, initial-scale=1.0" />',
					$html,
					1
				);
			}
		);
	},
	0
);