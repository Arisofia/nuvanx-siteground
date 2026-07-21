<?php
/**
 * Integraciones de infraestructura del tema.
 *
 * Schema canónico de clínicas: únicamente vía nvx-structured-data.php (Yoast graph).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/nvx-environment-flags.php';
require_once __DIR__ . '/nvx-visual-system.php';
require_once __DIR__ . '/nvx-external-visual-closure.php';
require_once __DIR__ . '/nvx-aesthetic-treatment-pages.php';
require_once __DIR__ . '/nvx-strategy-pages.php';
require_once __DIR__ . '/nvx-conversion-events.php';
require_once __DIR__ . '/nvx-aesthetic-hub-governance.php';

/** Goya sede: evita bucle redirect_canonical. */
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

/** Canonical privacy route. */
add_action(
	'template_redirect',
	function () {
		if ( is_admin() ) {
			return;
		}
		$path = isset( $_SERVER['REQUEST_URI'] ) ? strtok( (string) $_SERVER['REQUEST_URI'], '?' ) : '';
		$norm = '/' . trim(( $path, '/' ) . '/';
		if ( '/politica-de-privacidad/' === $norm ) {
			wp_safe_redirect( home_url( '/politica-privacidad/' ), 301 );
			exit;
		}
	},
	1
);

/**
 * Normalize public document markup and remove duplicate front-page FAQ structured data.
 *
 * @param string $html Rendered document markup.
 * @return string
 */
function nvx_theme_normalize_public_document( string $html ): string {
	$html = (string) preg_replace(
		'/<meta\s+name=["\']viewport["\'][^>]*>/i',
		'<meta name="viewport" content="width=device-width, initial-scale=1.0" />',
		$html,
		1

	);

	if ( ! is_front_page() || false === stripos( $html, 'FAQPage' ) ) {
		return $html;
	}

	$normalized = preg_replace_callback(
		'/<script\b[^>]*type=["\']application\/ld\+json["\'][^>]*>[\s\S]*?<\/script>/iu',
		static function ( array $match ): string {
			$script = $match[0];
			if ( false !== stripos( $script, 'yoast-schema-graph' ) ) {
				return $script;
			}
			return false !== stripos( $script, 'FAQPage' ) ? '' : $script;
		},
		$html
	);

	if ( is_string( $normalized ) ) {
		$html = $normalized;
	}

	return str_replace( '<!-- NUVANX_HOME_UNIFIED_FAQ_SCHEMA -->', '', $html );
}

add_action(
	'template_redirect',
	function () {
		if ( ! is_admin() ) {
			ob_start( 'nvx_theme_normalize_public_document' );
		}
	},
	0
);

require_once __DIR__ . '/nvx-structured-data.php';
require_once __DIR__ . '/nvx-aesthetic-treatment-schema.php';
require_once __DIR__ . '/nvx-page-hygiene.php';
require_once __DIR__ . '/nvx-p0-publication-guard.php';
require_once __DIR__ . '/nvx-seo-metadata.php';
require_once __DIR__ . '/nvx-seo-production-readiness.php';
require_once __DIR__ . '/nvx-contacto-audit-fixes.php';
require_once __DIR__ . '/nvx-home-copy.php';
require_once __DIR__ . '/nvx-home-content-v2.php';
require_once __DIR__ . '/nvx-faq-content-v2.php';
require_once __DIR__ . '/nvx-medical-review.php';
require_once __DIR__ . '/nvx-publication-safeguards.php';
require_once __DIR__ . '/nvx-btl-clinical-governance.php';
require_once __DIR__ . '/nvx-clinical-language.php';
require_once __DIR__ . '/nvx-blog-system.php';
require_once __DIR__ . '/nvx-mobile-hero-hierarchy.php';
require_once __DIR__ . '/nvx-navigation-filters.php';

/* GEO · Hreflang es-ES */
add_action(
	'wp_head',
	function (): void {
		$current_url = is_front_page() ? home_url( '/' ) : home_url( wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) );
		echo '<link rel="alternate" hreflang="es-ES" href="' . esc_url( $current_url ) . '" />' . "\n";
		echo '<link rel="alternate" hreflang="x-default" href="' . esc_url( $current_url ) . '" />' . "\n";
	},
	1
);

/* Clinical governance · retired treatment slugs */
add_action(
	'template_redirect',
	function (): void {
		$retired_slugs = array( 'tratamiento-retirado' );

		if ( is_singular() && in_array( get_post_field( 'post_name', get_the_ID() ), $retired_slugs, true ) ) {
			wp_safe_redirect( home_url( '/tratamientos/' ), 301 );
			exit;
		}
	}
);

/* Security headers */
add_action(
	'send_headers',
	function (): void {
		if ( headers_sent() ) {
			return;
		}
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );
	}
);

/* Meta Pixel · single-owner (dequeue SiteGround facebook-signal) */
add_action(
	'wp_enqueue_scripts',
	function (): void {
		wp_dequeue_script( 'siteground-facebook-signal' );
		wp_deregister_script( 'siteground-facebook-signal' );
	},
	100
);

add_filter(
	'script_loader_tag',
	function ( string $tag, string $handle ): string {
		if ( str_contains( $handle, 'facebook-signal' ) || str_contains( $tag, 'facebook-signal' ) ) {
			return '';
		}
		return $tag;
	},
	10,
	2
);
