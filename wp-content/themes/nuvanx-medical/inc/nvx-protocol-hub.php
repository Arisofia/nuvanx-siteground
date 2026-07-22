<?php
/**
 * Protocolos Signature Hub — NUVANX clinical architecture.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Whether the current request targets the Protocolos Signature page. */
function nvx_content_is_protocol_hub( string $content ): bool {
	if ( ! is_page() ) {
		return false;
	}
	return 'protocolos-signature' === (string) get_post_field( 'post_name', get_queried_object_id() );
}

/** Render one protocol card. */
function nvx_protocol_hub_card( string $title, string $body, string $path ): string {
	$html  = '<article class="nvx-catalog-card">';
	$html .= '<div class="nvx-catalog-card__main">';
	$html .= '<h3 class="nvx-catalog-card__title">' . esc_html( $title ) . '</h3>';
	$html .= '<p class="nvx-catalog-card__body">' . esc_html( $body ) . '</p>';
	$html .= '</div>';
	$html .= '<a class="nvx-catalog-card__cta" href="' . esc_url( home_url( $path ) ) . '">' . esc_html__( 'Ver protocolo', 'nuvanx-medical' ) . ' <span aria-hidden="true">→</span></a>';
	$html .= '</article>';
	return $html;
}

/** Builds the complete Protocolos Signature hub page markup. */
function nvx_protocol_hub_markup(): string {
	$html  = '<article class="nvx-brand-readable nvx-protocol-hub nvx-shell">';
	$html .= '<header class="nvx-strategy-intro">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'MEDICINA ESTÉTICA LÁSER', 'nuvanx-medical' ) . '</p>';
	$html .= '<h1 class="nvx-strategy-title">' . esc_html__( 'Protocolos Signature: Medicina estética de diagnóstico.', 'nuvanx-medical' ) . '</h1>';
	$html .= '<p class="nvx-brand-lead">' . esc_html__( 'Nuestros protocolos no son un catálogo de máquinas. Son sistemas médicos que parten del diagnóstico anatómico para justificar cada indicación y definir sus límites.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p>' . esc_html__( 'Cada protocolo conecta exploración, selección tecnológica, planificación y seguimiento. La propuesta final depende de la anatomía, el tejido predominante, los antecedentes y los objetivos realistas de cada paciente.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Solicitar valoración médica', 'nuvanx-medical' ) . '</a></p>';
	$html .= '</header>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'Nuestro estándar: La firma NUVANX', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Los Protocolos Signature expresan una forma de decidir: valoración anatómica, diagnóstico diferencial, selección de la modalidad indicada, explicación de límites y seguimiento definido para cada caso. No adaptamos pacientes a una máquina; diseñamos planes alrededor de su anatomía.', 'nuvanx-medical' ) . '</p>';
	$html .= '</section>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'Contorno Corporal y Posgestacional', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-catalog-grid">';
	$html .= nvx_protocol_hub_card(
		'Couture Sculpt™',
		'Remodelación corporal láser por unidades anatómicas. Diferencia grasa localizada, laxitud y continuidad del contorno antes de seleccionar la tecnología.',
		'/remodelacion-corporal-laser-madrid/'
	);
	$html .= nvx_protocol_hub_card(
		'Post-Maternity Contour™',
		'Valoración posgestacional de grasa subcutánea, laxitud cutánea, estrías, cicatriz y posibles alteraciones de la pared abdominal, con derivación cuando la medicina estética no es la vía adecuada.',
		'/tratamiento-postparto-abdomen-contorno-corporal-madrid/'
	);
	$html .= '</div></section>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'Arquitectura Facial y Calidad de Piel', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-catalog-grid">';
	$html .= nvx_protocol_hub_card(
		'Profile Definition™',
		'Diagnóstico diferencial del tercio inferior para distinguir grasa submentoniana, laxitud cervical y pérdida de soporte mandibular.',
		'/papada-definicion-mandibular-madrid/'
	);
	$html .= nvx_protocol_hub_card(
		'Skin Architecture™',
		'Plan médico orientado a firmeza, densidad y luminosidad de la piel sin modificar deliberadamente los volúmenes.',
		'/calidad-piel-firmeza-luminosidad-madrid/'
	);
	$html .= nvx_protocol_hub_card(
		'Surface Renewal™',
		'Renovación de la superficie cutánea mediante resurfacing médico para cicatrices de acné, estrías, textura y poros, según indicación clínica.',
		'/cicatrices-acne-poros-textura-madrid/'
	);
	$html .= nvx_protocol_hub_card(
		'Tone Correction™',
		'Abordaje médico de alteraciones pigmentarias y vasculares con parámetros seleccionados según diagnóstico y fototipo.',
		'/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/'
	);
	$html .= '</div></section>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'Tu primera valoración clínica', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'La valoración determina qué protocolo puede tener sentido, qué tecnología corresponde, qué alternativas existen y en qué situaciones es preferible esperar, derivar o no intervenir.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Iniciar valoración médica', 'nuvanx-medical' ) . '</a> <a class="nvx-brand-inline-link" href="' . esc_url( home_url( '/soluciones-medicas/' ) ) . '">' . esc_html__( 'Explorar soluciones por anatomía', 'nuvanx-medical' ) . '</a></p>';
	$html .= '</section>';
	$html .= '</article>';

	return $html;
}

/** Replaces the Protocolos Signature page content with its hub markup. */
function nvx_protocol_hub_content_filter( string $content ): string {
	if ( is_admin() || ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}
	return nvx_content_is_protocol_hub( $content ) ? nvx_protocol_hub_markup() : $content;
}
add_filter( 'the_content', 'nvx_protocol_hub_content_filter', 20 );
