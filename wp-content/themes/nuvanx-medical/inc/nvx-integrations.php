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
require_once __DIR__ . '/nvx-13-point-renderer.php';
require_once __DIR__ . '/nvx-aesthetic-treatment-pages.php';
require_once __DIR__ . '/nvx-strategy-pages.php';
require_once __DIR__ . '/nvx-conversion-events.php';
require_once __DIR__ . '/nvx-aesthetic-hub-governance.php';

/** Prevent strategy pages from being written during normal web requests. */
remove_action( 'init', 'nvx_strategy_seed_staging2_pages', 31 );

/**
 * Returns the governed retired/deferred-page contract shared by runtime
 * redirects and the production-readiness migration.
 *
 * @return array<string,array{status:string,target:string}>
 */
function nvx_production_readiness_governed_pages(): array {
	return array(
		'liposculpt-air' => array(
			'status' => 'trash',
			'target' => '/remodelacion-corporal-laser-madrid/',
		),
		'v-lift-awake' => array(
			'status' => 'trash',
			'target' => '/protocolos-signature/',
		),
		'dr-javier-rivera-tejeda' => array(
			'status' => 'trash',
			'target' => '/equipo-medico/',
		),
		'tratamientos' => array(
			'status' => 'trash',
			'target' => '/soluciones-medicas/',
		),
		'eye-frame-rejuvenecimiento-mirada-madrid' => array(
			'status' => 'draft',
			'target' => '/soluciones-medicas/',
		),
	);
}

/** Determines whether the current request is for the Goya clinic page. */
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
		$norm = '/' . trim( $path, '/' ) . '/';
		if ( '/politica-de-privacidad/' === $norm ) {
			wp_safe_redirect( home_url( '/politica-privacidad/' ), 301 );
			exit;
		}
	},
	1
);

/** Normalize public document markup and remove duplicate front-page FAQ schema. */
function nvx_theme_normalize_public_document( string $html ): string {
	$html = (string) preg_replace(
		'/<meta\s+name=["\']viewport["\'][^>]*>/i',
		'<meta name="viewport" content="width=device-width, initial-scale=1.0" />',
		$html,
		1
	);

	$html = str_ireplace(
		array( 'NUVANX Couture Sculpt™', 'NUVANX Contour Sculpt™', 'Couture Sculpt™', 'Contour Sculpt™' ),
		'NUVANX Contour Architecture™',
		$html
	);
	$html = str_replace(
		array( '/eye-frame-rejuvenecimiento-mirada-madrid/', '/eye-frame/' ),
		array( '/ojeras-surco-lagrimal-madrid/', '/ojeras-surco-lagrimal-madrid/' ),
		$html
	);
	$html = str_ireplace(
		array( 'NUVANX Eye Frame™', 'Eye Frame™' ),
		'Ojeras y surco lagrimal',
		$html
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
require_once __DIR__ . '/nvx-editorial-seo-extension.php';
require_once __DIR__ . '/nvx-seo-production-readiness.php';
require_once __DIR__ . '/nvx-contacto-audit-fixes.php';
require_once __DIR__ . '/nvx-faq-content-v2.php';
require_once __DIR__ . '/nvx-medical-review.php';
require_once __DIR__ . '/nvx-publication-safeguards.php';
require_once __DIR__ . '/nvx-btl-clinical-governance.php';
require_once __DIR__ . '/nvx-clinical-language.php';
require_once __DIR__ . '/nvx-blog-system.php';
require_once __DIR__ . '/nvx-mobile-hero-hierarchy.php';
require_once __DIR__ . '/nvx-navigation-filters.php';
require_once __DIR__ . '/nvx-signature-phase-pages.php';

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

/** Redirects retired or deferred routes by request path. */
function nvx_redirect_governed_routes(): void {
	if ( is_admin() ) {
		return;
	}

	$request_path = isset( $_SERVER['REQUEST_URI'] )
		? (string) wp_parse_url( (string) $_SERVER['REQUEST_URI'], PHP_URL_PATH )
		: '';
	$normalized = trim( $request_path, '/' );
	$redirects  = array(
		'tratamiento-retirado' => '/tratamientos/',
	);

	foreach ( nvx_production_readiness_governed_pages() as $slug => $definition ) {
		$redirects[ $slug ] = $definition['target'];
	}

	if ( isset( $redirects[ $normalized ] ) ) {
		wp_safe_redirect( home_url( $redirects[ $normalized ] ), 301, 'NUVANX' );
		exit;
	}
}
add_action( 'template_redirect', 'nvx_redirect_governed_routes', 1 );

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
