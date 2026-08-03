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

/**
 * Returns the normalized request path from REQUEST_URI.
 *
 * Unslashes and URL-sanitizes $_SERVER['REQUEST_URI'], then strips the query
 * string. Percent-encoded octets are preserved (unlike sanitize_text_field()).
 *
 * @return string Path without query string, or '' when REQUEST_URI is unset.
 */
function nvx_theme_request_path(): string {
	if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
		return '';
	}
	$raw = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
	return (string) strtok( $raw, '?' );
}
/** Goya sede: evita bucle redirect_canonical. */
function nvx_theme_is_goya_page(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( is_page( 1537 ) ) {
		return true;
	}
	$path = nvx_theme_request_path();
	return '/' . trim( $path, '/' ) . '/' === '/clinicas-de-medicina-estetica-nuvanx/medicina-estetica-goya-barrio-salamanca/';
}

add_filter(
	'language_attributes',
	function ( $output ) {
		if ( false !== strpos( $output, 'lang="es"' ) && false === strpos( $output, 'lang="es-ES"' ) ) {
			return str_replace( 'lang="es"', 'lang="es-ES"', $output );
		}
		if ( '' === $output || false === strpos( $output, 'lang=' ) ) {
			return $output . ' lang="es-ES"';
		}
		return $output;
	},
	999
);

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
		$path = nvx_theme_request_path();
		$norm = '/' . trim( $path, '/' ) . '/';
		if ( '/politica-de-privacidad/' === $norm ) {
			wp_safe_redirect( home_url( '/politica-privacidad/' ), 301 );
			exit;
		}
	},
	1
);

// Public document head contract is owned solely by nvx-document-governance.php
// (wp_head emission + Yoast suppress). Full-document buffer rewrites were retired;
// eager script/style strips use dequeue + script_loader_tag below. Page hygiene
// is required once from functions.php.

// Contact SEO/schema: nvx-contacto-valoracion-page.php (loaded from functions.php).
// Non-production OG host policy: nvx-document-governance.php.

add_action(
	'wp_head',
	function (): void {
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />' . "\n";
		/*
		Preloads de Google Fonts eliminados; dejamos que wp_enqueue_style gestione la carga.
		 * Ver Playwright tests para garantizar que las fuentes de marca se aplican correctamente. */

		if ( is_front_page() ) {
			$poster_url = content_url( '/uploads/2026/07/nvx-home-video-portada-poster.webp' );
			echo '<link rel="preload" as="image" href="' . esc_url( $poster_url ) . '" fetchpriority="high" type="image/webp" />' . "\n";
		}

		if ( ! is_404() && ! is_search() ) {
			$current_url = function_exists( 'nvx_document_governance_canonical_url' ) ? nvx_document_governance_canonical_url() : ( is_front_page() ? home_url( '/' ) : home_url( nvx_theme_request_path() ) );
			if ( '' !== $current_url ) {
				echo '<link rel="alternate" hreflang="es-ES" href="' . esc_url( $current_url ) . '" />' . "\n";
				echo '<link rel="alternate" hreflang="x-default" href="' . esc_url( $current_url ) . '" />' . "\n";
			}
		}
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
		header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
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

	// Keep available in wp-admin / CLI for configuration.
	if (
		( function_exists( 'is_admin' ) && is_admin() && ! ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) )
		|| ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() )
		|| ( defined( 'WP_CLI' ) && WP_CLI )
	) {
		return $plugins;
	}

	// Sitewide plugins use plugin => timestamp map.
	$is_map = array() !== $plugins && function_exists( 'array_is_list' ) && ! array_is_list( $plugins );
	if ( $is_map ) {
		foreach ( array_keys( $plugins ) as $plugin ) {
			if ( is_string( $plugin ) && (
				false !== strpos( $plugin, 'facebook' ) ||
				false !== strpos( $plugin, 'Facebook' )
			) ) {
				unset( $plugins[ $plugin ] );
			}
		}
		return $plugins;
	}

	return array_values(
		array_filter(
			$plugins,
			static function ( $plugin ): bool {
				return ! is_string( $plugin )
					|| ( false === strpos( $plugin, 'facebook' ) && false === strpos( $plugin, 'Facebook' ) );
			}
		)
	);
}
add_filter( 'option_active_plugins', 'nvx_theme_disable_public_facebook_pixel', 1 );
add_filter( 'site_option_active_sitewide_plugins', 'nvx_theme_disable_public_facebook_pixel', 1 );


/**
 * Campaign attribution marker for Google Ads QA (absorbed from retired MU).
 */
function nvx_theme_print_google_attribution_meta(): void {
	if ( is_admin() ) {
		return;
	}
	echo '<meta name="nuvanx-google-attribution" content="enabled" />' . "\n";
}
add_action( 'wp_head', 'nvx_theme_print_google_attribution_meta', 3 );

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
		wp_dequeue_script( 'leadin-script-loader-js' );
		wp_deregister_script( 'leadin-script-loader-js' );
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

		if ( ! is_admin() && str_contains( $tag, '<script' ) && ! str_contains( $tag, 'defer' ) && ! str_contains( $tag, 'async' ) && ! str_contains( $tag, 'type="application/ld+json"' ) && ! str_contains( $tag, 'type="application/json"' ) ) {
			return str_replace( '<script ', '<script defer ', $tag );
		}

		return $tag;
	},
	10,
	3
);

/**
 * Strip FacebookSignal and other unwanted third-party scripts from final HTML output.
 * This catches scripts injected via buffer optimization (e.g., SiteGround Optimizer)
 * that bypass WordPress enqueue hooks.
 */
add_filter(
	'template_redirect',
	static function (): void {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		ob_start(
			static function ( string $buffer ): string {
				// Remove Facebook Signal scripts and noscript tags
				$buffer = preg_replace( '/<script[^>]*facebook[^>]*>.*?<\/script>/is', '', $buffer );
				$buffer = preg_replace( '/<noscript[^>]*>.*?facebook.*?<\/noscript>/is', '', $buffer );
				
				// Remove Facebook Pixel initialization
				$buffer = preg_replace( '/<!--.*?Facebook Pixel.*?-->/is', '', $buffer );
				$buffer = preg_replace( '/<!--.*?Meta Pixel.*?-->/is', '', $buffer );
				
				// Remove _fbp cookie setting scripts
				$buffer = preg_replace( '/_fbp\s*=.*?;/is', '', $buffer );
				
				return $buffer;
			}
		);
	},
	999999
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
