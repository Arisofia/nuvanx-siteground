<?php
/**
 * Plugin Name: NUVANX Contacto HubSpot Form
 * Description: Mounts the dedicated HubSpot contact form on /contacto/ and enforces the temporary trust-claims publication policy.
 * Version: 2026.07.19
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'NVX_CONTACTO_HS_PORTAL_ID' ) ) {
	define( 'NVX_CONTACTO_HS_PORTAL_ID', '147416356' );
}
if ( ! defined( 'NVX_CONTACTO_HS_REGION' ) ) {
	define( 'NVX_CONTACTO_HS_REGION', 'eu1' );
}

/**
 * Render the dedicated contact form. The form ID must be supplied in wp-config.php
 * so deployments cannot silently reuse the medical-assessment form.
 */
function nvx_contacto_hubspot_form_markup(): string {
	$form_id = defined( 'NVX_CONTACTO_HS_FORM_ID' ) ? strtolower( trim( (string) NVX_CONTACTO_HS_FORM_ID ) ) : '';

	if ( '5042522a-0bc5-4381-ac3e-5aee8649b69c' === $form_id ) {
		_doing_it_wrong( __FUNCTION__, 'The assessment form cannot be used on /contacto/.', '2026.07.18' );
		$form_id = '';
	}

	if ( 1 !== preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $form_id ) ) {
		return '<div class="nvx-form-status" role="status"><p>'
			. esc_html__( 'El formulario de contacto no está disponible temporalmente. Puedes contactar con cualquiera de nuestras clínicas por teléfono o WhatsApp.', 'nuvanx-medical' )
			. '</p></div>';
	}

	$portal_id = preg_replace( '/\D+/', '', (string) NVX_CONTACTO_HS_PORTAL_ID );
	$region    = preg_replace( '/[^a-z0-9-]/i', '', (string) NVX_CONTACTO_HS_REGION );
	$script    = 'https://js-' . $region . '.hsforms.net/forms/embed/' . $portal_id . '.js';

	return '<script src="' . esc_url( $script ) . '" defer></script>'
		. '<div class="hs-form-frame" data-region="' . esc_attr( $region ) . '" data-form-id="' . esc_attr( $form_id ) . '" data-portal-id="' . esc_attr( $portal_id ) . '"></div>'
		. '<p class="nvx-form__privacy-note">' . esc_html__( 'Al enviar tus datos aceptas la', 'nuvanx-medical' ) . ' '
		. '<a href="' . esc_url( home_url( '/politica-privacidad/' ) ) . '">' . esc_html__( 'Política de privacidad', 'nuvanx-medical' ) . '</a>.</p>';
}

/**
 * Remove quantitative trust badges until every figure has an approved evidence
 * owner, source, calculation period and refresh process. This prevents the
 * unverified 3,500+, 4.8/5, 15+ and 89% claims from reaching rendered HTML.
 */
function nvx_remove_unverified_quantitative_trust_badges( string $content ): string {
	if ( false === strpos( $content, 'nvx-trust-badges' ) ) {
		return $content;
	}

	$filtered = preg_replace(
		'#<section\b[^>]*class=(['\"])[^'\"]*\bnvx-trust-badges\b[^'\"]*\1[^>]*>.*?</section>#isu',
		'',
		$content
	);

	return is_string( $filtered ) ? $filtered : $content;
}
add_filter( 'the_content', 'nvx_remove_unverified_quantitative_trust_badges', 22 );
