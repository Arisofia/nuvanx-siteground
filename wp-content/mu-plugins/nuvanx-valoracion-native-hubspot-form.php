<?php
/**
 * Plugin Name: NUVANX Valoración Native HubSpot Form
 * Description: Integra el flujo nativo de valoración para HubSpot.
 * Version: 2026.07.13
 */

defined( 'ABSPATH' ) || exit;

if ( is_page( 'valoracion' ) ) {
    add_action( 'wp_footer', function () {
        echo '<script>window.nuvanxValoracionForm = true;</script>';
    } );
}
