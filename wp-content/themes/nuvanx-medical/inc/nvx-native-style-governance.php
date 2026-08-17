<?php
/**
 * Native WordPress style governance for fully theme-owned templates.
 *
 * Gutenberg styles remain available everywhere except templates whose complete
 * markup and component styling are owned by the theme. No core enqueue action
 * is removed globally.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Whether the current request is the treatments hub page by slug. */
function nvx_theme_is_treatments_hub_page(): bool {
	return is_page() && 'tratamientos' === get_post_field( 'post_name', get_queried_object_id() );
}

/** Whether the theme owns the complete body markup for the current page. */
function nvx_theme_owns_complete_page_markup(): bool {
	return is_front_page() || nvx_theme_is_treatments_hub_page();
}

/**
 * Inline the small, universally required CSS foundation.
 *
 * Source files remain canonical and linted. Delivery inlines three tiny files
 * so first paint does not wait on extra stylesheets. Structural CSS
 * (header/layout/components) stays render-blocking on purpose.
 */
function nvx_theme_inline_critical_style_foundation(): void {
	if ( is_admin() ) {
		return;
	}

	$relative_files = array(
		'assets/css/nvx-fonts.css',
		'assets/css/nvx-tokens.css',
		'assets/css/nvx-base.css',
	);
	$critical_css = '';

	foreach ( $relative_files as $relative_file ) {
		$absolute_file = get_template_directory() . '/' . $relative_file;
		if ( ! is_readable( $absolute_file ) ) {
			return;
		}

		$contents = file_get_contents( $absolute_file );
		if ( false === $contents || '' === trim( $contents ) ) {
			return;
		}
		$critical_css .= "\n/* " . basename( $relative_file ) . " */\n" . $contents;
	}

	foreach ( array( 'nvx-fonts', 'nvx-tokens', 'nvx-base' ) as $handle ) {
		wp_dequeue_style( $handle );
		wp_deregister_style( $handle );
	}

	wp_register_style( 'nvx-critical-inline', false, array(), NVX_THEME_VERSION );
	wp_enqueue_style( 'nvx-critical-inline' );
	wp_add_inline_style( 'nvx-critical-inline', $critical_css );

	wp_register_style( 'nvx-fonts', false, array( 'nvx-critical-inline' ), NVX_THEME_VERSION );
	wp_register_style( 'nvx-tokens', false, array( 'nvx-fonts' ), NVX_THEME_VERSION );
	wp_register_style( 'nvx-base', false, array( 'nvx-tokens' ), NVX_THEME_VERSION );
	wp_enqueue_style( 'nvx-fonts' );
	wp_enqueue_style( 'nvx-tokens' );
	wp_enqueue_style( 'nvx-base' );
}
add_action( 'wp_enqueue_scripts', 'nvx_theme_inline_critical_style_foundation', 20 );

/**
 * Start Google Fonts immediately without blocking first paint.
 *
 * display=swap is already on the request URL. Structural theme CSS never
 * uses this path, so a blocked onload cannot collapse the header or form.
 *
 * @param string $html   Generated stylesheet tag.
 * @param string $handle Registered stylesheet handle.
 * @param string $href   Stylesheet URL.
 * @param string $media  Original media attribute.
 */
function nvx_theme_nonblocking_google_fonts( string $html, string $handle, string $href, string $media ): string {
	unset( $media );
	if ( 'nvx-google-fonts' !== $handle || '' === $href ) {
		return $html;
	}

	$safe_href = esc_url( $href );
	$id        = esc_attr( $handle . '-css' );

	return '<link rel="preload" as="style" href="' . $safe_href . '" />' . "\n"
		. '<link rel="stylesheet" id="' . $id . '" href="' . $safe_href . '" media="print" onload="this.onload=null;this.media=\'all\'" />' . "\n"
		. '<noscript><link rel="stylesheet" id="' . esc_attr( $handle . '-css-noscript' ) . '" href="' . $safe_href . '" /></noscript>' . "\n";
}
add_filter( 'style_loader_tag', 'nvx_theme_nonblocking_google_fonts', 20, 4 );

/**
 * Defer editorial pattern CSS. It is never required for first paint.
 *
 * @param string $html   Generated stylesheet tag.
 * @param string $handle Registered stylesheet handle.
 */
function nvx_theme_defer_editorial_css( string $html, string $handle ): string {
	if ( 'nvx-patterns' !== $handle || is_admin() ) {
		return $html;
	}

	$deferred = str_replace(
		array( "rel='stylesheet'", 'rel="stylesheet"' ),
		array( "rel='stylesheet' media='print' onload=\"this.media='all'\"", 'rel="stylesheet" media="print" onload="this.media=\'all\'"' ),
		$html
	);
	if ( $deferred === $html ) {
		return $html;
	}

	return $deferred . '<noscript>' . $html . '</noscript>';
}
add_filter( 'style_loader_tag', 'nvx_theme_defer_editorial_css', 20, 2 );

/** Dequeue block styles only when the rendered page contains no block markup. */
function nvx_theme_dequeue_native_block_styles(): void {
	if ( is_admin() || ! nvx_theme_owns_complete_page_markup() ) {
		return;
	}

	$handles = array(
		'global-styles',
		'classic-theme-styles',
		'wp-block-library',
		'wp-block-library-theme',
		'core-block-supports',
	);

	foreach ( $handles as $handle ) {
		wp_dequeue_style( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'nvx_theme_dequeue_native_block_styles', 100 );

/**
 * Dequeue stylesheet handles that are no longer shipped with the theme.
 * Prevents broken enqueues stored in the database or third-party plugins.
 */
function nvx_theme_dequeue_retired_stylesheet_handles(): void {
	if ( is_admin() ) {
		return;
	}

	$handles = array(
		'nvx-mobile-hero-hierarchy',
		'nvx-canonical-page-hero',
		'nvx-full-site-ui-governance',
		'nvx-editorial-coherence',
		'nvx-site-coherence',
		'nvx-ui-regressions',
		'nvx-hero-layout-coherence',
		'nvx-integrations',
	);

	foreach ( $handles as $handle ) {
		wp_dequeue_style( $handle );
		wp_deregister_style( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'nvx_theme_dequeue_retired_stylesheet_handles', 999 );
add_action( 'wp_head', 'nvx_theme_dequeue_retired_stylesheet_handles', 1 );
