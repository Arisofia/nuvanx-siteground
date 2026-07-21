<?php
/**
 * Protocolos Signature Hub — NUVANX clinical architecture.
 *
 * Pattern-based hub for the /protocolos-signature/ route.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Determines whether the current request targets the Protocolos Signature page.
 *
 * @param string $content The current page content.
 * @return bool `true` if the current request targets the Protocolos Signature page, `false` otherwise.
 */
function nvx_content_is_protocol_hub( string $content ): bool {
	if ( ! is_page() ) {
		return false;
	}
	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	return 'protocolos-signature' === $slug;
}

/**
 * Builds the Protocolos Signature hub page markup.
 *
 * @return string The complete HTML markup for the protocol hub.
 */
function nvx_protocol_hub_markup(): string {
	$view_protocol = esc_html__( 'Ver protocolo', 'nuvanx-medical' );
	$html          = '<article class="nvx-brand-readable nvx-protocol-hub nvx-shell">';
	$html         .= '<header class="nvx-strategy-intro">';
	$html         .= '<p class="nvx-brand-kicker">' . esc_html__( 'MEDICINA ESTÉTICA LÁSER', 'nuvanx-medical' ) . '</p>';
	$html         .= '<h1 class="nvx-strategy-title">' . esc_html__( 'Protocolos Signature: Medicina estética de diagnóstico.', 'nuvanx-medical' ) . '</h1>';
	$html         .= '<p class="nvx-brand-lead">' . esc_html__( 'Nuestros protocolos exclusivos no son un catálogo de máquinas. Son sistemas médicos diseñados para asegurar que cada intervención esté justificada por la anatomía del paciente.', 'nuvanx-medical' ) . '</p>';
	$html         .= '<p>' . esc_html__( 'Descubre nuestras metodologías exclusivas. En NUVANX, el diagnóstico precede a la tecnología para garantizar resultados proporcionados y una experiencia de quiet luxury.', 'nuvanx-medical' ) . '</p>';
	$html         .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Solicitar valoración médica', 'nuvanx-medical' ) . '</a></p>';
	$html         .= '</header>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'Nuestro estándar: La firma NUVANX', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Los Protocolos Signature son la máxima expresión de nuestra filosofía clínica. Cada uno de estos programas integra la valoración anatómica exhaustiva, la selección de la tecnología láser o de inducción más avanzada, y un seguimiento médico estricto. No adaptamos máquinas a los pacientes; diseñamos planes alrededor de su anatomía.', 'nuvanx-medical' ) . '</p>';
	$html .= '</section>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'Contorno Corporal', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-catalog-grid">';
	$html .= '<article class="nvx-catalog-card">';
	$html .= '<div class="nvx-catalog-card__main">';
	$html .= '<h3 class="nvx-catalog-card__title">Couture Sculpt™</h3>';
	$html .= '<p class="nvx-catalog-card__body">' . esc_html__( 'Remodelación corporal láser por unidades anatómicas. Diagnóstico y tratamiento focal para mejorar la continuidad del contorno sin imponer formas estándar.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';
	$html .= '<a class="nvx-catalog-card__cta" href="' . esc_url( home_url( '/remodelacion-corporal-laser-madrid/' ) ) . '">' . $view_protocol . ' <span aria-hidden="true">→</span></a>';
	$html .= '</article>';
	$html .= '</div></section>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'Arquitectura Facial y Calidad de Piel', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-catalog-grid">';

	$html .= '<article class="nvx-catalog-card">';
	$html .= '<div class="nvx-catalog-card__main">';
	$html .= '<h3 class="nvx-catalog-card__title">Profile Definition™</h3>';
	$html .= '<p class="nvx-catalog-card__body">' . esc_html__( 'Redefinición del perfil del tercio inferior. Diagnóstico diferencial de papada, laxitud cervical y pérdida de soporte mandibular.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';
	$html .= '<a class="nvx-catalog-card__cta" href="' . esc_url( home_url( '/papada-definicion-mandibular-madrid/' ) ) . '">' . $view_protocol . ' <span aria-hidden="true">→</span></a>';
	$html .= '</article>';

	$html .= '<article class="nvx-catalog-card">';
	$html .= '<div class="nvx-catalog-card__main">';
	$html .= '<h3 class="nvx-catalog-card__title">Skin Architecture™</h3>';
	$html .= '<p class="nvx-catalog-card__body">' . esc_html__( 'Tratamiento médico para recuperar la firmeza, densidad y luminosidad de la matriz dérmica, sin alterar volúmenes.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';
	$html .= '<a class="nvx-catalog-card__cta" href="' . esc_url( home_url( '/calidad-piel-firmeza-luminosidad-madrid/' ) ) . '">' . $view_protocol . ' <span aria-hidden="true">→</span></a>';
	$html .= '</article>';

	$html .= '<article class="nvx-catalog-card">';
	$html .= '<div class="nvx-catalog-card__main">';
	$html .= '<h3 class="nvx-catalog-card__title">Surface Renewal™</h3>';
	$html .= '<p class="nvx-catalog-card__body">' . esc_html__( 'Renovación profunda de la superficie cutánea. Tratamiento médico de cicatrices de acné, estrías y poros dilatados mediante resurfacing.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';
	$html .= '<a class="nvx-catalog-card__cta" href="' . esc_url( home_url( '/cicatrices-acne-poros-textura-madrid/' ) ) . '">' . $view_protocol . ' <span aria-hidden="true">→</span></a>';
	$html .= '</article>';

	$html .= '<article class="nvx-catalog-card">';
	$html .= '<div class="nvx-catalog-card__main">';
	$html .= '<h3 class="nvx-catalog-card__title">Tone Correction™</h3>';
	$html .= '<p class="nvx-catalog-card__body">' . esc_html__( 'Fotorejuvenecimiento de precisión. Corrección clínica del daño solar, rojeces, rosácea y léntigos bajo diagnóstico diferencial.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';
	$html .= '<a class="nvx-catalog-card__cta" href="' . esc_url( home_url( '/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/' ) ) . '">' . $view_protocol . ' <span aria-hidden="true">→</span></a>';
	$html .= '</article>';

	$html .= '</div></section>';
	$html .= '</article>';

	return $html;
}

/**
 * Replaces the Protocolos Signature page content with its hub markup.
 *
 * @param string $content The original page content.
 * @return string The hub markup for the Protocolos Signature page, or the original content for other pages.
 */
function nvx_protocol_hub_content_filter( string $content ): string {
	if ( is_admin() || ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}
	if ( ! nvx_content_is_protocol_hub( $content ) ) {
		return $content;
	}
	return nvx_protocol_hub_markup();
}
add_filter( 'the_content', 'nvx_protocol_hub_content_filter', 20 );
