<?php
/**
 * Plugin Name: NUVANX Contacto HubSpot Form
 * Description: Integra el formulario de contacto en la página objetivo.
 * Version: 2026.07.13
 */

defined( 'ABSPATH' ) || exit;

if ( is_page( 'contacto' ) ) {
    add_action( 'wp_footer', function () {
        echo '<script>window.nuvanxContactForm = true;</script>';
    } );
}
