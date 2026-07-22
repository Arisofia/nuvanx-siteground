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
	$html .= '<h1 class="nvx-strategy-title">' . esc_html__( 'Le pusimos nombre a nuestra forma de hacer las cosas. Esto es lo que hay detrás.', 'nuvanx-medical' ) . '</h1>';
	$html .= '<p class="nvx-brand-lead">' . esc_html__( 'Podríamos haberte enseñado directamente el nombre de cada máquina. Preferimos ponerle nombre a lo que conseguimos contigo, no al aparato — porque lo que compras no es un láser, es un resultado pensado para tu cuerpo.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p>' . esc_html__( 'Cada protocolo conecta exploración, selección tecnológica, planificación y seguimiento. La propuesta final depende de la anatomía, el tejido predominante, los antecedentes y los objetivos realistas de cada paciente.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Solicitar valoración médica', 'nuvanx-medical' ) . '</a></p>';
	$html .= '</header>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'Nuestro estándar: La firma NUVANX', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'No adaptamos tu cuerpo a lo que hace la máquina. Elegimos la máquina que le conviene a tu cuerpo. Parece obvio, pero es al revés de como funciona la mayoría de clínicas.', 'nuvanx-medical' ) . '</p>';
	$html .= '</section>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'Contorno Corporal y Posgestacional', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-catalog-grid">';
	$html .= nvx_protocol_hub_card(
		'NUVANX Contour Sculpt™',
		'Para cuando quieres que tu cuerpo se vea como una sola pieza, no como zonas tratadas por separado.',
		'/remodelacion-corporal-laser-madrid/'
	);
	$html .= nvx_protocol_hub_card(
		'Post-Maternity Contour™',
		'Para el cuerpo después de tener un hijo — sin fingir que fue como antes, sin conformarte tampoco.',
		'/tratamiento-postparto-abdomen-contorno-corporal-madrid/'
	);
	$html .= '</div></section>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'Arquitectura Facial y Calidad de Piel', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-catalog-grid">';
	$html .= nvx_protocol_hub_card(
		'Profile Definition™',
		'Para la papada y la mandíbula que no te terminan de convencer en las fotos.',
		'/papada-definicion-mandibular-madrid/'
	);
	$html .= nvx_protocol_hub_card(
		'NUVANX Eye Frame™',
		'Para la mirada de cansancio constante, diferenciando si es sombra, venitas, bolsas o piel fina.',
		'/eye-frame-rejuvenecimiento-mirada-madrid/'
	);
	$html .= nvx_protocol_hub_card(
		'Skin Architecture™',
		'Para cuando la piel ya no brilla como antes, aunque no tengas ni una arruga.',
		'/calidad-piel-firmeza-luminosidad-madrid/'
	);
	$html .= nvx_protocol_hub_card(
		'Surface Renewal™',
		'Para las marcas del acné que ya no quieres seguir tapando con maquillaje.',
		'/cicatrices-acne-poros-textura-madrid/'
	);
	$html .= nvx_protocol_hub_card(
		'Tone Correction™',
		'Para las manchas y rojeces que el sol dejó y que ninguna crema quita.',
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
