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
