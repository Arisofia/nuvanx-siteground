<?php
/**
 * Protocolos Signature hub — governed NUVANX clinical architecture.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Whether the current request targets the Protocolos Signature page. */
function nvx_content_is_protocol_hub( string $content ): bool {
	return is_page() && 'protocolos-signature' === (string) get_post_field( 'post_name', get_queried_object_id() );
}

/** Resolve a public page URL; return an empty string for drafts or missing pages. */
function nvx_protocol_hub_public_url( string $path ): string {
	$page = get_page_by_path( trim( $path, '/' ), OBJECT, 'page' );
	if ( ! $page instanceof WP_Post || 'publish' !== get_post_status( $page ) ) {
		return '';
	}
	$url = get_permalink( $page );
	return is_string( $url ) ? $url : '';
}

/** Render one protocol card only when its destination is published. */
function nvx_protocol_hub_card( string $title, string $body, string $path ): string {
	$url = nvx_protocol_hub_public_url( $path );
	if ( '' === $url ) {
		return '';
	}
	$html  = '<article class="nvx-catalog-card">';
	$html .= '<div class="nvx-catalog-card__main"><h3 class="nvx-catalog-card__title">' . esc_html( $title ) . '</h3><p class="nvx-catalog-card__body">' . esc_html( $body ) . '</p></div>';
	$html .= '<a class="nvx-catalog-card__cta" href="' . esc_url( $url ) . '">' . esc_html__( 'Ver protocolo', 'nuvanx-medical' ) . ' <span aria-hidden="true">→</span></a></article>';
	return $html;
}

/** Build the complete Signature hub. */
function nvx_protocol_hub_markup(): string {
	$html  = '<article class="nvx-brand-readable nvx-protocol-hub nvx-shell">';
	$html .= '<header class="nvx-strategy-intro"><p class="nvx-brand-kicker">' . esc_html__( 'MEDICINA ESTÉTICA DE DIAGNÓSTICO', 'nuvanx-medical' ) . '</p>';
	$html .= '<h1 class="nvx-strategy-title">' . esc_html__( 'Protocolos Signature NUVANX.', 'nuvanx-medical' ) . '</h1>';
	$html .= '<p class="nvx-brand-lead">' . esc_html__( 'Sistemas médicos que conectan diagnóstico anatómico, selección tecnológica, planificación y seguimiento. La tecnología no define el protocolo: la indicación determina qué herramienta puede aportar valor.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Solicitar valoración médica', 'nuvanx-medical' ) . '</a></p></header>';

	$html .= '<section class="nvx-brand-section"><h2>' . esc_html__( 'Contorno corporal y posgestacional', 'nuvanx-medical' ) . '</h2><div class="nvx-catalog-grid">';
	$html .= nvx_protocol_hub_card(
		'NUVANX Contour Architecture™',
		'Remodelación corporal por unidades anatómicas para diferenciar grasa localizada, laxitud, calidad cutánea y continuidad del contorno.',
		'/remodelacion-corporal-laser-madrid/'
	);
	$html .= nvx_protocol_hub_card(
		'NUVANX Post-Maternity Contour™',
		'Valoración posgestacional por componentes, con explicación de lo tratable y derivación cuando la medicina estética no es la vía adecuada.',
		'/tratamiento-postparto-abdomen-contorno-corporal-madrid/'
	);
	$html .= '</div></section>';

	$html .= '<section class="nvx-brand-section"><h2>' . esc_html__( 'Rostro, calidad y superficie cutánea', 'nuvanx-medical' ) . '</h2><div class="nvx-catalog-grid">';
	$html .= nvx_protocol_hub_card(
		'NUVANX Profile Definition™',
		'Diagnóstico de papada, cuello, mandíbula y mentón para diferenciar grasa, laxitud y soporte estructural.',
		'/papada-definicion-mandibular-madrid/'
	);
	$html .= nvx_protocol_hub_card(
		'NUVANX Skin Architecture™',
		'Plan médico para calidad, firmeza, densidad e hidratación según fototipo, antecedentes y profundidad del problema.',
		'/calidad-piel-firmeza-luminosidad-madrid/'
	);
	$html .= nvx_protocol_hub_card(
		'NUVANX Surface Renewal™',
		'Valoración de cicatrices, poros y textura para seleccionar una modalidad de superficie conforme al diagnóstico.',
		'/cicatrices-acne-poros-textura-madrid/'
	);
	$html .= nvx_protocol_hub_card(
		'NUVANX Tone Correction™',
		'Abordaje de manchas, rojeces y fotodaño con selección de parámetros, cuidados o derivación según lesión y fototipo.',
		'/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/'
	);
	$html .= '</div></section>';

	$html .= '<section class="nvx-brand-section"><h2>' . esc_html__( 'Tu primera valoración clínica', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'La consulta determina qué protocolo puede tener sentido, qué alternativas existen y en qué situaciones es preferible esperar, derivar o no intervenir.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Iniciar valoración médica', 'nuvanx-medical' ) . '</a> <a class="nvx-brand-inline-link" href="' . esc_url( home_url( '/soluciones-medicas/' ) ) . '">' . esc_html__( 'Explorar soluciones por anatomía', 'nuvanx-medical' ) . '</a></p></section>';
	$html .= '</article>';
	return $html;
}

/** Replace the Protocolos Signature page content with governed markup. */
function nvx_protocol_hub_content_filter( string $content ): string {
	if ( is_admin() || ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}
	return nvx_content_is_protocol_hub( $content ) ? nvx_protocol_hub_markup() : $content;
}
add_filter( 'the_content', 'nvx_protocol_hub_content_filter', 20 );
