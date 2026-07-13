<?php
defined( 'ABSPATH' ) || exit;

function nvx_asset_version( string $relative_path ): string {
    $absolute_path = get_template_directory() . '/' . ltrim( $relative_path, '/' );

    return file_exists( $absolute_path ) ? (string) filemtime( $absolute_path ) : wp_get_theme()->get( 'Version' );
}

function nvx_enqueue_theme_assets(): void {
    $uri = get_template_directory_uri();

    wp_enqueue_style( 'nvx-tokens', $uri . '/assets/css/nvx-tokens.css', array(), nvx_asset_version( 'assets/css/nvx-tokens.css' ) );
    wp_enqueue_style( 'nvx-base', $uri . '/assets/css/nvx-base.css', array( 'nvx-tokens' ), nvx_asset_version( 'assets/css/nvx-base.css' ) );
    wp_enqueue_style( 'nvx-site-layout', $uri . '/assets/css/nvx-site-layout.css', array( 'nvx-base' ), nvx_asset_version( 'assets/css/nvx-site-layout.css' ) );
}

add_action( 'wp_enqueue_scripts', 'nvx_enqueue_theme_assets' );
