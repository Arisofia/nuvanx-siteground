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

/** Whether the theme owns the complete body markup for the current page. */
function nvx_theme_owns_complete_page_markup(): bool {
	return is_front_page()
		|| ( function_exists( 'nvxCasesPageIsCurrent' ) && nvxCasesPageIsCurrent() );
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
