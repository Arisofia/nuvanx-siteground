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
	$valuation_url = home_url( '/madrid/valoracion/' );
	$whatsapp_url  = 'https://wa.me/34669319836';
	$form_id       = defined( 'NVX_VALORACION_HS_FRAME_FORM_ID' ) ? NVX_VALORACION_HS_FRAME_FORM_ID : '5042522a-0bc5-4381-ac3e-5aee8649b69c';
	$portal_id     = defined( 'NVX_VALORACION_HS_FRAME_PORTAL_ID' ) ? NVX_VALORACION_HS_FRAME_PORTAL_ID : '147416356';

	$html  = '<div class="nvx-brand-page nvx-valoracion-page" id="nvx-valoracion-main" aria-labelledby="nvx-valoracion-h1">';
	$html .= '<section class="nvx-brand-hero nvx-editorial-hero nvx-canonical-page-hero" aria-labelledby="nvx-valoracion-h1">';
	$html .= '<div class="nvx-brand-hero__inner">';
	$html .= '<div class="nvx-editorial-hero__copy">';
	$html .= '<p class="nvx-eyebrow">' . esc_html__( 'VALORACIÓN MÉDICA · MADRID', 'nuvanx-medical' ) . '</p>';
	$html .= '<h1 id="nvx-valoracion-h1" class="nvx-heading">' . esc_html__( 'Valoración médica estética en Madrid', 'nuvanx-medical' ) . '</h1>';
	$html .= '<p class="nvx-brand-meta">' . esc_html__( 'Revisamos anatomía, calidad de piel, antecedentes y expectativas antes de indicar un tratamiento facial o corporal.', 'nuvanx-medical' ) . '</p>';
	$html .= '<div class="nvx-brand-actions">';
	$html .= '<a class="nvx-brand-btn nvx-brand-btn--primary" href="#nvx-hubspot-form">' . esc_html__( 'Completar solicitud', 'nuvanx-medical' ) . '</a>';
	$html .= '<a class="nvx-brand-btn nvx-brand-btn--secondary" href="' . esc_url( $whatsapp_url ) . '" target="_blank" rel="nofollow noopener">' . esc_html__( 'Contactar por WhatsApp', 'nuvanx-medical' ) . '</a>';
	$html .= '</div>';
	$html .= '</div></div></section>';

	// The priority-14 form-order filter moves this section directly after hero.
	$html .= nvx_valoracion_intro_markup();
	$html .= '<section class="nvx-brand-section nvx-hubspot-form-section nvx-form-stage" id="nvx-hubspot-form" aria-labelledby="nvx-valoracion-form-title">';
	$html .= '<div class="nvx-brand-section__inner">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'SOLICITUD DE VALORACIÓN', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-valoracion-form-title" class="nvx-brand-title">' . esc_html__( 'Cuéntanos qué quieres valorar', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-brand-body">' . esc_html__( 'Completa tus datos, indica la zona o tratamiento de interés y selecciona tu sede preferida. El equipo de NUVANX te contactará para coordinar la cita.', 'nuvanx-medical' ) . '</p>';
	$html .= '<div class="nvx-form nvx-hs-native-section" aria-label="' . esc_attr__( 'Formulario de valoración médica NUVANX', 'nuvanx-medical' ) . '">';
	$html .= '<div class="nvx-hs-native-box">';
	$html .= '<div id="nvx-hubspot-native-form" class="nvx-hubspot-native-form-v2" data-nvx-hubspot-native="1" data-form-id="' . esc_attr( $form_id ) . '" data-portal-id="' . esc_attr( $portal_id ) . '" data-page-origin="' . esc_attr__( 'Valoración médica estética en Madrid', 'nuvanx-medical' ) . '" data-page-url="' . esc_url( $valuation_url ) . '"></div>';
	$html .= '<p class="nvx-copy nvx-form-note">' . esc_html__( 'La información enviada se utiliza para gestionar tu solicitud. La indicación final depende de valoración médica y los resultados pueden variar según cada paciente.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div></div></div></section>';
	$html .= '</div>';

	return $html;
}

/**
 * Replace any legacy body or CMS marker with the canonical managed landing.
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
add_filter( 'the_content', 'nvx_render_managed_valoracion_page', 10 );
