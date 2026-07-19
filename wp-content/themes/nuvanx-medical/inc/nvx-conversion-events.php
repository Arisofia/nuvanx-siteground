<?php
/**
 * Privacy-safe conversion event instrumentation.
 *
 * Captures intent clicks and successful HubSpot submissions without reading or
 * transmitting form field values.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nvx_enqueue_conversion_events(): void {
	$relative = '/assets/js/nvx-conversion-events.js';
	$path     = get_template_directory() . $relative;
	$url      = get_template_directory_uri() . $relative;
	$version  = is_readable( $path ) ? (string) filemtime( $path ) : wp_get_theme()->get( 'Version' );

	wp_enqueue_script(
		'nvx-conversion-events',
		$url,
		array(),
		$version,
		false
	);

	$config = array(
		'forms' => array(
			'valoracion' => defined( 'NVX_VALORACION_HS_FRAME_FORM_ID' )
				? sanitize_key( (string) NVX_VALORACION_HS_FRAME_FORM_ID )
				: '5042522a-0bc5-4381-ac3e-5aee8649b69c',
		),
	);

	wp_add_inline_script(
		'nvx-conversion-events',
		'window.nvxConversionEvents = ' . wp_json_encode( $config, JSON_UNESCAPED_SLASHES ) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'nvx_enqueue_conversion_events', 2 );
