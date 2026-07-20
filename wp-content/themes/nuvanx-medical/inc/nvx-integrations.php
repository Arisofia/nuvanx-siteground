<?php
/**
 * Integraciones de infraestructura del tema (sin parches de presentación).
 *
 * Schema canónico de clínicas: únicamente vía nvx-structured-data.php → Yoast graph.
 * No emitir JSON-LD LocalBusiness duplicado aquí (evita horarios incorrectos y URLs hardcodeadas).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Environment-specific flags must register before front-end enqueue hooks run. */
require_once __DIR__ . '/nvx-environment-flags.php';

/** Canonical color, icon, typography-role and numbering closure. */
require_once __DIR__ . '/nvx-visual-system.php';

/** Terminal bridge for third-party widgets and unresolved legacy role aliases. */
require_once __DIR__ . '/nvx-external-visual-closure.php';

/** Canonical facial treatment content, metadata and staging page seeding. */
require_once __DIR__ . '/nvx-aesthetic-treatment-pages.php';

/** Strategy-led authority, investment and protected protocol-review routes. */
require_once __DIR__ . '/nvx-strategy-pages.php';

/** Privacy-safe intent and successful-form conversion events. */
require_once __DIR__ . '/nvx-conversion-events.php';

/** Final route and clinical wording guard for the aesthetic medicine hub. */
require_once __DIR__ . '/nvx-aesthetic-hub-governance.php';

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

/** Redirect the legacy privacy slug to the canonical P0 route. */
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

/**
 * Normalizes public document markup and removes duplicate front-page FAQ structured data.
 *
 * @param string $html The rendered document markup.
 * @return string The normalized document markup.
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

/** Structured data extensions: one canonical Yoast graph, no duplicate output. */
require_once __DIR__ . '/nvx-structured-data.php';

/** Facial treatment MedicalProcedure and FAQ nodes inside the same Yoast graph. */
require_once __DIR__ . '/nvx-aesthetic-treatment-schema.php';

/** Legal redirects, noindex and shared page-hygiene helpers. */
require_once __DIR__ . '/nvx-page-hygiene.php';

/** Canonical P0 publication rules replace the legacy all-in-one runtime filter. */
require_once __DIR__ . '/nvx-p0-publication-guard.php';

/** Canonical titles, descriptions, social URLs and environment robots policy. */
require_once __DIR__ . '/nvx-seo-metadata.php';

/** Production index headers and final MedicalOrganization graph normalization. */
require_once __DIR__ . '/nvx-seo-production-readiness.php';

/** Validated /contacto/ social image, local schema, visible copy and hours. */
require_once __DIR__ . '/nvx-contacto-audit-fixes.php';

/** Canonical front-page patient-facing H1 and clinical introduction. */
require_once __DIR__ . '/nvx-home-copy.php';

/** Canonical value proposition, clinical method, valuation CTA and protocols. */
require_once __DIR__ . '/nvx-home-content-v2.php';

/** One canonical FAQ catalogue shared by visible HTML and Yoast schema. */
require_once __DIR__ . '/nvx-faq-content-v2.php';

/** Visible and schema review provenance, only after explicit approval metadata. */
require_once __DIR__ . '/nvx-medical-review.php';

/** Narrow safeguards for generic actions, SLA wording and governed public claims. */
require_once __DIR__ . '/nvx-publication-safeguards.php';

/** Temporary clinical safeguard for BTL detail pages pending source-copy sign-off. */
require_once __DIR__ . '/nvx-btl-clinical-governance.php';

/** Final visible/schema terminology normalization for legacy content sources. */
require_once __DIR__ . '/nvx-clinical-language.php';

/** Journal archive, taxonomy, search and single-post presentation. */
require_once __DIR__ . '/nvx-blog-system.php';

/** Global hero hierarchy. */
require_once __DIR__ . '/nvx-mobile-hero-hierarchy.php';

/** Navigation filters for dynamic menu injection. */
require_once __DIR__ . '/nvx-navigation-filters.php';

/* -----------------------------------------------------------------------
 * 1. GEO · Hreflang for es-ES (only one locale currently, but declared
 *    explicitly so Google understands the canonical locale).
 * --------------------------------------------------------------------- */
add_action(
	'wp_head',
	function (): void {
		$current_url = is_front_page() ? home_url( '/' ) : home_url( wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) );
		echo '<link rel="alternate" hreflang="es-ES" href="' . esc_url( $current_url ) . '" />' . "\n";
		echo '<link rel="alternate" hreflang="x-default" href="' . esc_url( $current_url ) . '" />' . "\n";
	},
	1
);

/* -----------------------------------------------------------------------
 * 2. Clinical Governance · Disable treatments not offered.
 *    Guard against legacy pages returning 200 for non-offered services.
 *    Redirect to treatments index if a slug is not in the active catalog.
 * --------------------------------------------------------------------- */
add_action(
	'template_redirect',
	function (): void {
		// Slugs of pages that have been retired / not offered.
		$retired_slugs = [
			'tratamiento-retirado',
		];

		if ( is_singular() && in_array( get_post_field( 'post_name', get_the_ID() ), $retired_slugs, true ) ) {
			wp_safe_redirect( home_url( '/tratamientos/' ), 301 );
			exit;
		}
	}
);

/* -----------------------------------------------------------------------
 * 3. Security Headers (complement to SiteGround / .htaccess).
 *    These fire from PHP for any response not covered by server config.
 * --------------------------------------------------------------------- */
add_action(
	'send_headers',
	function (): void {
		// Prevent theme / plugin output from setting conflicting headers.
		if ( headers_sent() ) {
			return;
		}
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );
	}
);

/* -----------------------------------------------------------------------
 * 4. Meta Pixel governance — single-owner enforcement.
 *    Dequeue the SiteGround Optimizer facebook-signal asset if Meta
 *    for WordPress plugin is active (prevents ReferenceError #166).
 *    Long-term: GTM is the single owner; both WP plugin and this guard
 *    should be removed once GTM-only flow is confirmed in production.
 * --------------------------------------------------------------------- */
add_action(
	'wp_enqueue_scripts',
	function (): void {
		// Dequeue SiteGround's optimized facebook-signal to prevent
		// ReferenceError: FacebookSignal is not defined (issue #166).
		wp_dequeue_script( 'siteground-facebook-signal' );
		wp_deregister_script( 'siteground-facebook-signal' );
	},
	100 // Late priority to run after SiteGround Optimizer enqueues.
);

/**
 * Remove SiteGround Optimizer's facebook-signal from its inline script
 * handles and from the HTML output by filtering script_loader_tag.
 */
add_filter(
	'script_loader_tag',
	function ( string $tag, string $handle ): string {
		if ( str_contains( $handle, 'facebook-signal' ) || str_contains( $tag, 'facebook-signal' ) ) {
			return ''; // Suppress entirely.
		}
		return $tag;
	},
	10,
	2
);
