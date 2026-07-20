<?php
/**
 * Qué exigir antes de operarte — SEO Capture & Authority Page.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detect Qué exigir page.
 */
function nvx_content_is_que_exigir_page( string $content ): bool {
	if ( false !== strpos( $content, 'nvx-que-exigir-editorial' ) ) {
		return false;
	}

	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	if ( ! is_singular( 'page' ) && ! is_page() ) {
		return false;
	}

	$path = function_exists( 'nvx_schema_current_path' )
		? nvx_schema_current_path( (int) get_queried_object_id() )
		: '';

	if ( false !== strpos( $path, '/que-exigir-antes-de-operarte/' ) ) {
		return true;
	}

	return (bool) preg_match(
		'/aria-label=["\']Qué exigir antes de operarte["\']|id=["\']nvx-que-exigir-h1["\']|class=["\'][^"\']*nvx-que-exigir-hero/iu',
		$content
	);
}

/**
 * Replace content with Qué exigir authority page.
 */
function nvx_content_que_exigir_hijack( string $content ): string {
	if ( ! nvx_content_is_que_exigir_page( $content ) ) {
		return $content;
	}

	$valuation_url = function_exists( 'nvx_cta_valoracion_url' ) ? nvx_cta_valoracion_url() : home_url( '/madrid/valoracion/' );

	$html  = '<div class="nvx-que-exigir-editorial" style="max-width:800px; margin:0 auto; padding:4rem 1rem;">';
	
	// Hero
	$html .= '<h1 class="nvx-brand-hero__title" id="nvx-que-exigir-h1" style="font-size: clamp(2rem, 4vw, 2.5rem); line-height: 1.2; margin-bottom:1.5rem;">' . esc_html__( 'Qué exigir por escrito antes de operarte en una clínica estética de Madrid', 'nuvanx-medical' ) . '</h1>';
	
	// E-E-A-T Byline
	$html .= '<div class="nvx-medical-byline" style="display:flex; align-items:center; gap:0.75rem; margin-top:1rem; margin-bottom:2rem; font-size:0.875rem; color:var(--nvx-color-text-muted, #555); padding-bottom:1rem; border-bottom:1px solid #eaeaea;">';
	$html .= '<div class="nvx-medical-byline__text" style="line-height:1.4;">';
	$html .= '<strong>' . esc_html__( 'Escrito y firmado por Dr. Javier Rivera Tejeda', 'nuvanx-medical' ) . '</strong><br>';
	$html .= '<span style="font-size:0.85em; opacity:0.85;">' . esc_html__( 'Director médico NUVANX · Nº Col. ICOMEM: 282864786', 'nuvanx-medical' ) . '</span>';
	$html .= '</div></div>';

	$html .= '<div class="nvx-que-exigir-body" style="font-size:1.125rem; line-height:1.7; color:var(--nvx-color-text, #333);">';
	
	// Intro
	$html .= '<p><strong>' . esc_html__( 'Escribo esto porque lo que voy a contarte ha perjudicado a pacientes en clínicas de este mismo barrio.', 'nuvanx-medical' ) . '</strong></p>';
	$html .= '<p>' . esc_html__( 'La medicina estética y la cirugía plástica en Madrid se han llenado de franquicias y clínicas "low-cost" que invierten más en marketing que en protocolos médicos. Si estás valorando una intervención, antes de dar una señal económica, exige que te documenten por escrito los siguientes 5 puntos. Tu salud y tu resultado dependen de ello.', 'nuvanx-medical' ) . '</p>';

	// 1. El contrato clínico
	$html .= '<h2 style="font-size:1.5rem; margin-top:3rem; margin-bottom:1rem;">' . esc_html__( '1. El contrato clínico: la técnica exacta', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Huye de términos comerciales como "liposucción avanzada" o "lifting sin cirugía flash". El documento médico debe especificar la técnica real (p. ej., "Laserlipólisis subcutánea con fibra de 600 µm a 1470 nm"). Además, debe constar el nombre y número de colegiado del médico que ejecutará el procedimiento. ¿Qué ocurre si el médico principal "no puede" intervenir ese día? El contrato debe protegerte contra la rotación de plantilla.', 'nuvanx-medical' ) . '</p>';

	// 2. La anestesia
	$html .= '<h2 style="font-size:1.5rem; margin-top:3rem; margin-bottom:1rem;">' . esc_html__( '2. La anestesia: quién y cómo', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'La diferencia entre sedación consciente y anestesia general no es trivial. Pregunta abiertamente si el procedimiento requiere un anestesista cualificado en quirófano o si se realiza con sedación oral/tópica en sala blanca. En NUVANX, por ejemplo, evitamos la anestesia general realizando procedimientos mínimamente invasivos (Endolift®) que solo requieren anestesia local y/o sedación consciente.', 'nuvanx-medical' ) . '</p>';

	// 3. Fotos Antes y Después
	$html .= '<h2 style="font-size:1.5rem; margin-top:3rem; margin-bottom:1rem;">' . esc_html__( '3. Casos clínicos reales, no de catálogo', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Es habitual que algunas clínicas enseñen catálogos fotográficos proporcionados por el fabricante de la máquina (la marca del láser o de los inyectables). Exige ver casos de "Antes y Después" realizados específicamente por el médico que te va a tratar, no fotos de stock o de la franquicia central.', 'nuvanx-medical' ) . '</p>';

	// 4. Las Reseñas
	$html .= '<h2 style="font-size:1.5rem; margin-top:3rem; margin-bottom:1rem;">' . esc_html__( '4. Cómo distinguir reseñas infladas', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Revisa la brecha entre las plataformas. Si una clínica tiene 4.9 en Google (donde a menudo se pide la reseña en recepción a cambio de un descuento) pero un 2.5 en Trustpilot o foros independientes, cuidado. Una clínica con autoridad no te pide una reseña antes de ver el resultado final de tu tratamiento.', 'nuvanx-medical' ) . '</p>';

	// 5. Presupuesto cerrado
	$html .= '<h2 style="font-size:1.5rem; margin-top:3rem; margin-bottom:1rem;">' . esc_html__( '5. El presupuesto cerrado y desglosado', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( '¿Incluye el seguimiento? ¿Y las prendas de presoterapia (fajas) o la medicación postoperatoria? Un presupuesto clínico profesional no tiene "costes ocultos" ni caduca en 24 horas para forzarte a decidir.', 'nuvanx-medical' ) . '</p>';

	$html .= '<hr style="margin: 3rem 0; border:0; border-top:1px solid #eaeaea;">';
	
	// CTA Block
	$html .= '<div style="background-color: var(--nvx-color-surface-dim, #f8f8f8); padding: 2.5rem; border-radius: 8px; text-align:center;">';
	$html .= '<h3 style="font-size:1.5rem; margin-bottom:1rem;">' . esc_html__( 'La transparencia clínica no es un lujo, es tu derecho', 'nuvanx-medical' ) . '</h3>';
	$html .= '<p style="margin-bottom:2rem;">' . esc_html__( 'En NUVANX, firmamos el protocolo exacto y el presupuesto cerrado antes de cualquier procedimiento. Si quieres revisar cómo lo hacemos o buscas una segunda opinión objetiva sobre tu caso, agenda una valoración médica.', 'nuvanx-medical' ) . '</p>';
	if ( function_exists( 'nvx_cta_pair_markup' ) ) {
		$html .= nvx_cta_pair_markup( 'nvx-que-exigir-hero-ctas nvx-home-hero-ctas' );
	} else {
		$html .= '<a href="' . esc_url( $valuation_url ) . '" class="nvx-button">' . esc_html__( 'Iniciar mi valoración médica', 'nuvanx-medical' ) . '</a>';
	}
	$html .= '</div>';

	$html .= '</div></div>';

	return $html;
}
add_filter( 'the_content', 'nvx_content_que_exigir_hijack', 122 );
