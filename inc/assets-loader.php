<?php
/**
 * NUVANX Global Assets Loader
 *
 * Carga global e incondicional del CSS compilado con versión dinámica anti-caché.
 *
 * @package NUVANX_SiteGround
 * @version 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function(): void {
    $theme_dir = get_template_directory();
    $theme_uri = get_template_directory_uri();
    
    $css_file = '/dist/css/main.css';
    $version  = file_exists($theme_dir . $css_file) ? (string) filemtime($theme_dir . $css_file) : '1.0.0';

    wp_enqueue_style(
        'nvx-core-design-system',
        $theme_uri . $css_file,
        [],
        $version
    );
}, 5);
