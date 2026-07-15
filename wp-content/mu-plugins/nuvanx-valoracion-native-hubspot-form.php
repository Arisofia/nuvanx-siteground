<?php
/**
 * Plugin Name: NUVANX Valoración Native HubSpot Form
 * Description: Monta el frame HubSpot en /madrid/valoracion/ y emite flag de página.
 * Version: 2026.07.15
 *
 * Estilos: solo tema global (nvx-components). Sin CSS por page-id ni !important.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'NVX_VALORACION_HS_FRAME_PORTAL_ID' ) ) {
	define( 'NVX_VALORACION_HS_FRAME_PORTAL_ID', '147416356' );
}
if ( ! defined( 'NVX_VALORACION_HS_FRAME_FORM_ID' ) ) {
	define( 'NVX_VALORACION_HS_FRAME_FORM_ID', '5042522a-0bc5-4381-ac3e-5aee8649b69c' );
}
if ( ! defined( 'NVX_VALORACION_HS_FRAME_REGION' ) ) {
	define( 'NVX_VALORACION_HS_FRAME_REGION', 'eu1' );
}

/**
 * @return bool
 */
function nvx_valoracion_native_hubspot_is_target_page(): bool {
	return is_page( 2636 ) || is_page( 'valoracion' );
}

/**
 * @return string
 */
function nvx_valoracion_native_hubspot_mount_markup(): string {
	$portal_id     = esc_attr( NVX_VALORACION_HS_FRAME_PORTAL_ID );
	$form_id       = esc_attr( NVX_VALORACION_HS_FRAME_FORM_ID );
	$region        = esc_attr( NVX_VALORACION_HS_FRAME_REGION );
	$portal_script = esc_url( 'https://js-eu1.hsforms.net/forms/embed/' . NVX_VALORACION_HS_FRAME_PORTAL_ID . '.js' );
	$disclaimer    = '<p class="nvx-copy">Al facilitar tus datos aceptas la <a class="nvx-text-link" href="' . esc_url( home_url( '/politica-privacidad/' ) ) . '">Política de privacidad</a>.</p>';

	return '<script src="' . $portal_script . '" defer></script>'
		. '<div class="hs-form-frame" data-region="' . $region . '" data-form-id="' . $form_id . '" data-portal-id="' . $portal_id . '"></div>'
		. $disclaimer;
}

/**
 * @param string $html Full page HTML.
 * @return string
 */
function nvx_valoracion_native_hubspot_ensure_mount_script( string $html ): string {
	if (
		false === stripos( $html, 'id="nvx-hubspot-native-form"' )
		&& false === stripos( $html, "id='nvx-hubspot-native-form'" )
	) {
		return $html;
	}

	if ( preg_match( '/forms\/embed\/' . preg_quote( NVX_VALORACION_HS_FRAME_PORTAL_ID, '/' ) . '\.js/i', $html ) ) {
		return $html;
	}

	$portal_script = esc_url( 'https://js-eu1.hsforms.net/forms/embed/' . NVX_VALORACION_HS_FRAME_PORTAL_ID . '.js' );
	$script_tag    = '<script src="' . $portal_script . '" defer></script>';

	$replaced = preg_replace(
		'/(<div[^>]*id=["\']nvx-hubspot-native-form["\'][^>]*>)(\s*<div class="hs-form-frame")/is',
		'$1' . $script_tag . '$2',
		$html,
		1
	);

	if ( is_string( $replaced ) && $replaced !== $html ) {
		return $replaced;
	}

	$mount    = nvx_valoracion_native_hubspot_mount_markup();
	$replaced = preg_replace(
		'/(<div[^>]*id=["\']nvx-hubspot-native-form["\'][^>]*>)\s*<\/div>/is',
		'$1' . $mount . '</div>',
		$html,
		1
	);

	return is_string( $replaced ) ? $replaced : $html;
}

add_action(
	'template_redirect',
	static function () {
		if ( ! nvx_valoracion_native_hubspot_is_target_page() ) {
			return;
		}
		ob_start( 'nvx_valoracion_native_hubspot_ensure_mount_script' );
	},
	1
);

add_action(
	'wp_footer',
	static function () {
		if ( ! nvx_valoracion_native_hubspot_is_target_page() ) {
			return;
		}
		echo '<script>window.nuvanxValoracionForm = true;</script>' . "\n";
	},
	20
);
