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
require_once __DIR__ . '/nvx-aesthetic-treatment-pages.php';
require_once __DIR__ . '/nvx-strategy-pages.php';
require_once __DIR__ . '/nvx-signature-phase-pages.php';

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
		$norm = '/' . trim( $path, '/' ) . '/';
		if ( '/politica-de-privacidad/' === $norm ) {
			wp_safe_redirect( home_url( '/politica-privacidad/' ), 301 );
			exit;
		}
	},
	1
);

/**
 * Apply preg_replace without ever collapsing a failed match to "".
 *
 * Casting null (PCRE error / backtrack limit) to string yields empty output and
 * was observed as HTTP 200 + Content-Length 0 on large public documents.
 *
 * @param string|string[] $pattern Pattern.
 * @param string|string[] $replacement Replacement.
 * @param string          $subject Subject.
 * @param int             $limit Limit.
 */
function nvx_safe_preg_replace( $pattern, $replacement, string $subject, int $limit = -1 ): string {
	$result = preg_replace( $pattern, $replacement, $subject, $limit );
	return is_string( $result ) ? $result : $subject;
}

/**
 * Normalize public document markup and remove duplicate front-page FAQ structured data.
 *
 * @param string $html Rendered document markup.
 * @return string
 */
function nvx_theme_normalize_public_document( string $html ): string {
	if ( '' === $html ) {
		return $html;
	}

	$html = nvx_safe_preg_replace(
		'/<meta\s+name=["\']viewport["\'][^>]*>/i',
		'<meta name="viewport" content="width=device-width, initial-scale=1.0" />',
		$html,
		1
	);

	if ( false !== stripos( $html, '2026/06/nvx-home-video-' ) ) {
		$html = str_replace(
			'/uploads/2026/06/nvx-home-video-',
			'/uploads/2026/07/nvx-home-video-',
			$html
		);
	}

	if ( function_exists( 'nvx_document_governance_remove_retired_scripts' ) ) {
		$html = nvx_document_governance_remove_retired_scripts( $html );
	}

	if ( ! is_admin() ) {
		if ( false !== stripos( $html, 'accounts.google.com/gsi' ) ) {
			$html = nvx_safe_preg_replace(
				'/<script\b[^>]*accounts\.google\.com\/gsi[^>]*>[\s\S]*?<\/script>/iu',
				'',
				$html
			);
		}
		if ( false !== stripos( $html, 'sign-in-with-google' ) ) {
			$html = nvx_safe_preg_replace(
				'/<script\b[^>]*sign-in-with-google[^>]*>[\s\S]*?<\/script>/iu',
				'',
				$html
			);
			$html = nvx_safe_preg_replace(
				'/<style\b[^>]*googlesitekit-sign-in-with-google[^>]*>[\s\S]*?<\/style>/iu',
				'',
				$html
			);
		}

		$html = nvx_safe_preg_replace(
			'/<link\s+rel=["\']stylesheet["\']\s+id=["\']nvx-(?:mobile-hero-hierarchy|canonical-page-hero|full-site-ui-governance|editorial-coherence|site-coherence|ui-regressions|hero-layout-coherence|integrations)-css["\'][^>]*>/i',
			'',
			$html
		);
	}

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

// Public document head contract is owned solely by nvx-document-governance.php
// (wp_head emission + Yoast suppress). Page hygiene is required once from
// functions.php.

require_once __DIR__ . '/nvx-structured-data.php';
require_once __DIR__ . '/nvx-aesthetic-treatment-schema.php';
require_once __DIR__ . '/nvx-seo-metadata.php';
require_once __DIR__ . '/nvx-seo-production-readiness.php';
require_once __DIR__ . '/nvx-staging2-canonical-closure.php';
require_once __DIR__ . '/nvx-contacto-audit-fixes.php';
require_once __DIR__ . '/nvx-faq-content-v2.php';
require_once __DIR__ . '/nvx-medical-review.php';
require_once __DIR__ . '/nvx-btl-clinical-governance.php';
require_once __DIR__ . '/nvx-blog-system.php';
require_once __DIR__ . '/nvx-navigation-filters.php';

add_action(
	'wp_head',
	function (): void {
		echo '<link rel="preconnect" href="https://fonts.googleapis.com" />' . "\n";
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />' . "\n";
		echo '<link rel="preload" as="font" href="https://fonts.gstatic.com/s/manrope/v20/xn7gYHE41ni1AdIRggexSvfedN4.woff2" type="font/woff2" crossorigin />' . "\n";
		echo '<link rel="preload" as="font" href="https://fonts.gstatic.com/s/playfairdisplay/v40/nuFiD-vYSZviVYUb_rj3ij__anPXDTzYgEM86xQ.woff2" type="font/woff2" crossorigin />' . "\n";

		if ( is_front_page() ) {
			$poster_url = content_url( '/uploads/2026/07/nvx-home-video-portada-poster.webp' );
			echo '<link rel="preload" as="image" href="' . esc_url( $poster_url ) . '" fetchpriority="high" type="image/webp" />' . "\n";
		}

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

/**
 * Whether a script src/handle is an eager HubSpot forms embed that must not download.
 *
 * Only the handle and primary src are inspected. Matching against the full tag
 * body is unsafe: inline configuration or optimizer rewrites can mention
 * hsforms domains without being an eager embed, and would drop legitimate
 * runtime scripts.
 */
function nvx_theme_is_eager_hubspot_embed( string $handle, string $src = '', string $tag = '' ): bool {
	unset( $tag );
	if ( 'nvx-hubspot-forms-embed' === $handle ) {
		return true;
	}

	$src_lower = strtolower( $src );
	return str_contains( $src_lower, 'hsforms.net' )
		|| str_contains( $src_lower, 'hsforms.com' )
		|| str_contains( $src_lower, 'hs-scripts.com' );
}

/**
 * Keep official Meta Pixel (FacebookSignal) off public HTML.
 *
 * Stripping via full-document buffer was a workaround. Deactivate the plugin
 * for front requests so FacebookSignal never enqueues (acceptance rejects it).
 *
 * @param mixed $plugins Active plugin basenames.
 * @return mixed
 */
function nvx_theme_disable_public_facebook_pixel( $plugins ) {
	if ( ! is_array( $plugins ) ) {
		return $plugins;
	}
	if ( is_admin() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return $plugins;
	}

	return array_values(
		array_filter(
			$plugins,
			static function ( $plugin ): bool {
				return ! is_string( $plugin )
					|| false === strpos( $plugin, 'official-facebook-pixel/' );
			}
		)
	);
}
add_filter( 'option_active_plugins', 'nvx_theme_disable_public_facebook_pixel', 1 );
add_filter( 'site_option_active_sitewide_plugins', 'nvx_theme_disable_public_facebook_pixel', 1 );

/*
 * Single owner for eager third-party script strips on the public front end.
 * HubSpot forms embed: one dequeue after normal enqueues (100) + script_loader_tag hard-block below.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_dequeue_script( 'siteground-facebook-signal' );
		wp_deregister_script( 'siteground-facebook-signal' );
		wp_dequeue_script( 'facebook-for-wordpress-pixel' );
		wp_deregister_script( 'facebook-for-wordpress-pixel' );
		wp_dequeue_script( 'googlesitekit-sign-in-with-google' );
		wp_deregister_script( 'googlesitekit-sign-in-with-google' );
		wp_dequeue_script( 'nvx-hubspot-forms-embed' );
		wp_deregister_script( 'nvx-hubspot-forms-embed' );
	},
	100
);

add_filter(
	'script_loader_tag',
	static function ( string $tag, string $handle, string $src = '' ): string {
		if (
			str_contains( $handle, 'facebook-signal' )
			|| str_contains( $handle, 'facebook-for-wordpress' )
			|| str_contains( $tag, 'facebook-signal' )
			|| str_contains( $tag, 'FacebookSignal' )
		) {
			return '';
		}

		if ( is_admin() ) {
			return $tag;
		}

		if ( str_contains( $src, 'accounts.google.com/gsi' ) || str_contains( $handle, 'sign-in-with-google' ) ) {
			return '';
		}

		// Hard-block eager HubSpot embeds: defer still downloads the script.
		if ( nvx_theme_is_eager_hubspot_embed( $handle, $src, $tag ) ) {
			return '';
		}

		return $tag;
	},
	10,
	3
);

add_filter(
	'wp_resource_hints',
	static function ( $urls, $relation_type ) {
		if ( ! is_array( $urls ) ) {
			return $urls;
		}

		$relation = (string) $relation_type;
		if ( ! in_array( $relation, array( 'dns-prefetch', 'preconnect', 'prefetch', 'prerender' ), true ) ) {
			return $urls;
		}

		return array_values(
			array_filter(
				$urls,
				static function ( $url ): bool {
					$href = is_array( $url ) ? (string) ( $url['href'] ?? '' ) : (string) $url;
					$href = strtolower( $href );
					return ! str_contains( $href, 'hsforms' )
						&& ! str_contains( $href, 'hs-scripts.com' );
				}
			)
		);
	},
	10,
	2
);
