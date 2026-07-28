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

/** Enqueue the final hero-layout assets before WordPress prints styles. */
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

    if ( ! is_front_page() ) {
        return;
    }

    $control_css = 'assets/css/nvx-home-hero-video-control.css';
    if ( is_readable( get_template_directory() . '/' . $control_css ) ) {
        wp_enqueue_style(
            'nvx-home-hero-video-control',
            get_template_directory_uri() . '/' . $control_css,
            array( 'nvx-hero-layout-coherence' ),
            nvx_asset_version( $control_css )
        );
    }

    $control_js = 'assets/js/nvx-home-hero-video.js';
    if ( is_readable( get_template_directory() . '/' . $control_js ) ) {
        wp_enqueue_script(
            'nvx-home-hero-video',
            get_template_directory_uri() . '/' . $control_js,
            array(),
            nvx_asset_version( $control_js ),
            true
        );
    }
}
add_action( 'wp_head', 'nvxHeroLayoutCoherenceEnqueueAssets', 1 );