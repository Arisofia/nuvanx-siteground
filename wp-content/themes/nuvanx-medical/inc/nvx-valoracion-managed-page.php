<?php
/**
 * Canonical theme-owned valuation landing.
 *
 * The CMS stores a stable route marker only. This module renders the full
 * hierarchy and the canonical HubSpot mount so staging and production do not
 * depend on historical database HTML.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build the canonical valuation page before form-order and HubSpot MU filters.
 */
function nvx_valoracion_managed_page_markup(): string {
	// Hero image-free by design: block the featured-image injection performed by
	// nvx_ensure_hero_featured_media (the_content prio 12). The managed renderer
	// runs earlier (prio 10), so setting the flag here short-circuits that filter.
	global $nvx_page_shell_has_hero;
	$nvx_page_shell_has_hero = true;

	$valuation_url = home_url( '/madrid/valoracion/' );
	$form_id       = defined( 'NVX_VALORACION_HS_FRAME_FORM_ID' ) ? NVX_VALORACION_HS_FRAME_FORM_ID : '5042522a-0bc5-4381-ac3e-5aee8649b69c';
	$portal_id     = defined( 'NVX_VALORACION_HS_FRAME_PORTAL_ID' ) ? NVX_VALORACION_HS_FRAME_PORTAL_ID : '147416356';

	$html  = '<div class="nvx-brand-page nvx-valoracion-page" id="nvx-valoracion-main" aria-labelledby="nvx-valoracion-h1">';

	// Conversion-first page header: site header/menu -> concise page heading -> form.
	$html .= '<section class="nvx-brand-hero nvx-valoracion-hero" aria-labelledby="nvx-valoracion-h1">';
	$html .= '<div class="nvx-brand-hero__inner">';
	$html .= '<div class="nvx-brand-hero__copy">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'VALORACIÓN MÉDICA · MADRID', 'nuvanx-medical' ) . '</p>';
	$html .= '<h1 id="nvx-valoracion-h1" class="nvx-brand-hero__title">' . esc_html__( 'Valoración médica estética en Madrid', 'nuvanx-medical' ) . '</h1>';
	$html .= '</div></div></section>';

	// The form is deliberately the first content block after the heading.
	$html .= '<section class="nvx-brand-section nvx-hubspot-form-section nvx-form-stage" id="nvx-hubspot-form" aria-labelledby="nvx-valoracion-form-title">';
	$html .= '<div class="nvx-brand-section__inner">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'SOLICITUD DE VALORACIÓN', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-valoracion-form-title" class="nvx-brand-title">' . esc_html__( 'Cuéntanos qué quieres valorar', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-brand-body">' . esc_html__( 'Completa tus datos, indica la zona o tratamiento de interés y selecciona tu sede preferida. El equipo de NUVANX te contactará para coordinar la cita.', 'nuvanx-medical' ) . '</p>';
	$html .= '<div class="nvx-form nvx-hs-native-section" aria-label="' . esc_attr__( 'Formulario de valoración médica NUVANX', 'nuvanx-medical' ) . '">';
	$html .= '<div class="nvx-hs-native-box">';
	$html .= '<div id="nvx-hubspot-native-form" class="nvx-hubspot-native-form-v2" data-nvx-hubspot-native="1" data-nvx-hubspot-eager="1" data-form-id="' . esc_attr( $form_id ) . '" data-portal-id="' . esc_attr( $portal_id ) . '" data-page-origin="' . esc_attr__( 'Valoración médica estética en Madrid', 'nuvanx-medical' ) . '" data-page-url="' . esc_url( $valuation_url ) . '"></div>';
	$html .= '<p class="nvx-copy nvx-form-note">' . esc_html__( 'La información enviada se utiliza para gestionar tu solicitud. La indicación final depende de valoración médica y los resultados pueden variar según cada paciente.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div></div></div></section>';

	// Keep clinical explanation and locations after the conversion block.
	$html .= nvx_valoracion_intro_markup();

	// Runtime governance owns the HubSpot loader. Trigger the page mount once all
	// DOMContentLoaded listeners are installed so the dedicated form does not wait
	// for scroll/intersection before loading.
	$html .= '<script id="nvx-valoracion-form-eager">window.addEventListener("load",function(){var host=document.getElementById("nvx-hubspot-form");if(host){host.dispatchEvent(new Event("focusin",{bubbles:true}));}},{once:true});</script>';
	$html .= '</div>';

	return $html;
}

/**
 * Replace any prior body or CMS marker with the canonical managed landing.
 *
 * @param string $content Original page content.
 */
function nvx_render_managed_valoracion_page( $content ): string {
	$content = is_string( $content ) ? $content : '';
	if ( is_admin() || ! function_exists( 'nvx_is_valoracion_page_request' ) || ! nvx_is_valoracion_page_request() ) {
		return $content;
	}

	return nvx_valoracion_managed_page_markup();
}
add_filter( 'the_content', 'nvx_render_managed_valoracion_page', NVX_HOOK_PRIO_VALORACION_MANAGED );

/**
 * Register valoración page as page owner to prevent shell hero duplication.
 *
 * When the shell evaluates $has_managed_editorial in nvx-page-shell.php,
 * this filter ensures valoración pages are recognized as managed,
 * preventing the shell from rendering its own hero in addition to
 * the renderer's hero.
 */
add_filter(
	'nvx_page_owner',
	function ( $owner ) {
		if ( ! empty( $owner ) || is_admin() ) {
			return $owner;
		}
		if ( function_exists( 'nvx_is_valoracion_page_request' ) && nvx_is_valoracion_page_request() ) {
			return 'nvx_valoracion_managed';
		}
		return $owner;
	},
	10
);
