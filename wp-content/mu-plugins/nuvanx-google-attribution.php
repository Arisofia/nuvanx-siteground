<?php
/**
 * Plugin Name: NUVANX Google Attribution
 * Description: Registra atribución de campañas de Google para NUVANX.
 * Version: 2026.07.13
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_admin() ) {
    add_action( 'wp_head', function () {
        echo '<meta name="nuvanx-google-attribution" content="enabled" />';
    } );
}
