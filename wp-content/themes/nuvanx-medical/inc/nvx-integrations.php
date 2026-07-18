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

/**
 * Normaliza el documento público sin crear una segunda fuente de metadata.
 *
 * Mantiene un único viewport accesible y elimina únicamente FAQPage JSON-LD
 * independiente del grafo de Yoast. El catálogo canónico del tema ya sustituye
 * o crea el nodo FAQPage dentro de `yoast-schema-graph`; conservar ambos bloques
 * produciría entidades duplicadas con respuestas distintas.
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

/** Viewport accesible y deduplicación defensiva de FAQ schema. */
add_action(
	'template_redirect',
	function () {
		if ( is_admin() ) {
			return;
		}
		ob_start( 'nvx_theme_normalize_public_document' );
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

/** Visible and schema review provenance, only after explicit approval metadata. */
require_once __DIR__ . '/nvx-medical-review.php';

/** Temporary clinical safeguard for BTL detail pages pending source-copy sign-off. */
require_once __DIR__ . '/nvx-btl-clinical-governance.php';

/** Final visible/schema terminology normalization for legacy content sources. */
require_once __DIR__ . '/nvx-clinical-language.php';

/** Journal archive, taxonomy, search and single-post presentation. */
require_once __DIR__ . '/nvx-blog-system.php';

/** Global hero hierarchy: concise media overlay plus readable clinical introduction. */
require_once __DIR__ . '/nvx-mobile-hero-hierarchy.php';
