<?php
/**
 * Rendered contract test for the dedicated /contacto/ HubSpot mount.
 */

declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
define( 'NVX_CONTACTO_HS_FORM_ID', '7cbf0df1-8c9c-4b6f-a8ed-5e065f01dc8a' );

function esc_attr( $value ): string {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}

function esc_url( $value ): string {
	return filter_var( (string) $value, FILTER_SANITIZE_URL );
}

function esc_html__( $value, $domain = null ): string {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}

function home_url( $path = '' ): string {
	return 'https://www.nuvanx.com' . (string) $path;
}

function _doing_it_wrong( $function, $message, $version ): void {
	throw new RuntimeException( (string) $message );
}

require dirname( __DIR__, 2 ) . '/wp-content/mu-plugins/nuvanx-contacto-hubspot-form.php';

$markup = nvx_contacto_hubspot_form_markup();

$checks = array(
	substr_count( $markup, 'class="hs-form-frame"' ) === 1,
	str_contains( $markup, 'data-form-id="' . NVX_CONTACTO_HS_FORM_ID . '"' ),
	! str_contains( $markup, '5042522a-0bc5-4381-ac3e-5aee8649b69c' ),
	str_contains( $markup, 'https://js-eu1.hsforms.net/forms/embed/147416356.js' ),
	str_contains( $markup, 'https://www.nuvanx.com/politica-privacidad/' ),
);

if ( in_array( false, $checks, true ) ) {
	fwrite( STDERR, "Rendered contacto HubSpot mount failed its contract.\n{$markup}\n" );
	exit( 1 );
}

fwrite( STDOUT, "Rendered contacto HubSpot mount contracts passed.\n" );
