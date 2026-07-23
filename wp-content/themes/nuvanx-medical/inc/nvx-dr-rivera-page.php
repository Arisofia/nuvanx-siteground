<?php
/**
 * Dr. Rivera Tejeda — E-E-A-T Medical Authority Page.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detect Dr. Rivera page.
 */
function nvx_content_is_dr_rivera_page( string $content ): bool {
	if ( false !== strpos( $content, 'nvx-dr-rivera-editorial' ) ) {
		return false; // Already processed.
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

	if ( false !== strpos( $path, '/dr-javier-rivera-tejeda/' ) ) {
		return true;
	}

	return (bool) preg_match(
		'/aria-label=["\']Dr. Javier Rivera Tejeda["\']|id=["\']nvx-dr-rivera-h1["\']|class=["\'][^"\']*nvx-dr-rivera-hero/iu',
		$content
	);
}

/**
 * Replace content with Dr. Rivera authority page.
 */
function nvx_content_dr_rivera_hijack( string $content ): string {
	if ( ! nvx_content_is_dr_rivera_page( $content ) ) {
		return $content;
	}

	$valuation_url = function_exists( 'nvx_cta_valoracion_url' ) ? nvx_cta_valoracion_url() : home_url( '/madrid/valoracion/' );

	// E-E-A-T Avatar and Manifest
	$avatar = esc_url( home_url( '/wp-content/themes/nuvanx-medical/assets/images/dr-rivera-avatar.jpg' ) );

	$html  = '<div class="nvx-dr-rivera-editorial">';

	$html .= '<div class="nvx-dr-rivera-header">';
	$html .= '<img src="' . $avatar . '" alt="Dr. Javier Rivera Tejeda" class="nvx-dr-rivera-avatar" onerror="this.style.display=\'none\'">';
	$html .= '<p class="nvx-eyebrow nvx-dr-rivera-kicker">' . esc_html__( 'Dirección Médica NUVANX', 'nuvanx-medical' ) . '</p>';
	$html .= '<h1 class="nvx-heading nvx-dr-rivera-h1" id="nvx-dr-rivera-h1">' . esc_html__( 'Dr. Javier Rivera Tejeda', 'nuvanx-medical' ) . '</h1>';
	$html .= '<p class="nvx-lead nvx-dr-rivera-lead">' . esc_html__( 'Nº Colegiado ICOMEM: 282864786 · Especialista en Medicina Estética Láser e Ingeniería Tisular', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';

	// Manifiesto Clínico
	$html .= '<blockquote class="nvx-blockquote">';
	$html .= '<p>' . esc_html__( 'Mi criterio de indicación clínica es innegociable: si no hay razón médica objetiva para un tratamiento, no lo recomiendo. Hay clínicas que venden su catálogo de máquinas; en NUVANX, yo te vendo el diagnóstico anatómico.', 'nuvanx-medical' ) . '</p>';
	$html .= '</blockquote>';

	$html .= '<div class="nvx-dr-rivera-body">';
	$html .= '<p>' . esc_html__( 'Como Director Médico de NUVANX Medicina Estética Láser, mi enfoque se centra en la geriatría preventiva y la regeneración tisular sin procedimientos quirúrgicos invasivos. Personalmente ejecuto los tratamientos de alta complejidad energética y acompaño al paciente durante toda la curva de recuperación.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';

	// Procedimientos que ejecuta
	$html .= '<h2 class="nvx-dr-rivera-list-title">' . esc_html__( 'Procedimientos de Alta Complejidad Ejecutados Personalmente:', 'nuvanx-medical' ) . '</h2>';
	$html .= '<ul class="nvx-dr-rivera-list">';
	$html .= '<li><strong>' . esc_html__( 'Endolift® Facial y Corporal:', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'Laserlipólisis y retracción cutánea intersticial a 1470 nm.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Láser CO₂ Fraccionado Quirúrgico:', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'Resurfacing profundo para fotodaño severo y secuelas cicatriciales.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'EXION® Fractional RF:', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'Radiofrecuencia fraccionada con impedancia en tiempo real para matriz extracelular.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Inyectables Estructurales (Allergan / Merz):', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'Reposicionamiento volumétrico y neuromodulación bajo trazabilidad absoluta.', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul>';

	// CTA
	if ( function_exists( 'nvx_cta_pair_markup' ) ) {
		$html .= '<div class="nvx-dr-rivera-cta">';
		$html .= nvx_cta_pair_markup( 'nvx-dr-rivera-hero-ctas nvx-home-hero-ctas' );
		$html .= '</div>';
	} else {
		$html .= '<p class="nvx-dr-rivera-cta"><a href="' . esc_url( $valuation_url ) . '" class="nvx-button">' . esc_html__( 'Iniciar mi valoración médica', 'nuvanx-medical' ) . '</a></p>';
	}

	$html .= '</div>';

	return $html;
}
// Hijack antiguo desactivado; la autoridad de Rivera se gestiona en /equipo-medico/.
// add_filter( 'the_content', 'nvx_content_dr_rivera_hijack', 121 );
