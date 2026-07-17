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

/** Structured data extensions: one canonical Yoast graph, no duplicate output. */
require_once __DIR__ . '/nvx-structured-data.php';

/** Legal redirects, noindex for incomplete evidence / transactional pages. */
require_once __DIR__ . '/nvx-page-hygiene.php';

/** Canonical titles, descriptions, social URLs and environment robots policy. */
require_once __DIR__ . '/nvx-seo-metadata.php';

/** Canonical front-page patient-facing H1 and clinical introduction. */
require_once __DIR__ . '/nvx-home-copy.php';

/** Canonical value proposition, clinical method, valuation CTA and protocols. */
require_once __DIR__ . '/nvx-home-content-v2.php';

/** One canonical FAQ catalogue shared by visible HTML and Yoast schema. */
require_once __DIR__ . '/nvx-faq-content-v2.php';

/** Temporary clinical safeguard for BTL detail pages pending source-copy sign-off. */
require_once __DIR__ . '/nvx-btl-clinical-governance.php';

/** Final visible/schema terminology normalization for legacy content sources. */
require_once __DIR__ . '/nvx-clinical-language.php';

/** Global hero hierarchy: concise media overlay plus readable clinical introduction. */
require_once __DIR__ . '/nvx-mobile-hero-hierarchy.php';
