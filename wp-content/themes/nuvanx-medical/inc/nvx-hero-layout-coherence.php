<?php
/**
 * Final cross-route hero layout contract.
 *
 * Loaded after the existing component and page-specific styles so internal
 * headers remain visually complete, while the Home keeps its media-first
 * editorial exception.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Enqueue the final hero-layout stylesheet before WordPress prints styles. */
function nvxHeroLayoutCoherenceEnqueueAssets(): void {
    $relative = 'assets/css/nvx-hero-layout-coherence.css';
    $absolute = get_template_directory() . '/' . $relative;
    if ( ! is_readable( $absolute ) ) {
        return;
    }

    $dependencies = array( 'nvx-site-coherence' );
    if ( is_front_page() && wp_style_is( 'nvx-home-structure', 'enqueued' ) ) {
        $dependencies[] = 'nvx-home-structure';
    }

    wp_enqueue_style(
        'nvx-hero-layout-coherence',
        get_template_directory_uri() . '/' . $relative,
        $dependencies,
        nvx_asset_version( $relative )
    );
}
add_action( 'wp_head', 'nvxHeroLayoutCoherenceEnqueueAssets', 1 );

if ( ! function_exists( 'nvx_hero_layout_coherence_enqueue_assets' ) ) {
    function nvx_hero_layout_coherence_enqueue_assets(): void {
        nvxHeroLayoutCoherenceEnqueueAssets();
    }
}
