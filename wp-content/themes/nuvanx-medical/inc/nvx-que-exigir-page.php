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

    $html  = '<div class="nvx-que-exigir-editorial">';
    
    // Hero
    $html .= '<h1 class="nvx-heading nvx-que-exigir-h1" id="nvx-que-exigir-h1">' . esc_html__( 'Qué exigir por escrito antes de operarte en una clínica estética de Madrid', 'nuvanx-medical' ) . '</h1>';
    
    // E-E-A-T Byline
    $html .= '<div class="nvx-medical-byline nvx-medical-byline--border">';
    $html .= '<div class="nvx-medical-byline__text">';
    $html .= '<strong>' . esc_html__( 'Escrito y firmado por Dr. Javier Rivera Tejeda', 'nuvanx-medical' ) . '</strong><br>';
    $html .= '<span class="nvx-medical-byline__title">' . esc_html__( 'Director médico NUVANX · Nº Col. ICOMEM: 282864786', 'nuvanx-medical' ) . '</span>';
    $html .= '</div></div>';

    $html .= '<div class="nvx-que-exigir-body">';
    
    // Intro
    $html .= '<p><strong>' . esc_html__( 'Esta guía está redactada con el propósito de ofrecer criterios clínicos objetivos a los pacientes.', 'nuvanx-medical' ) . '</strong></p>';
    $html .= '<p>' . esc_html__( 'La elección de un centro médico debe basarse en la seguridad, la trazabilidad y la responsabilidad profesional. Antes de iniciar cualquier procedimiento, recomendamos verificar que el centro documenta por escrito los siguientes puntos para proteger la salud y la previsibilidad del resultado.', 'nuvanx-medical' ) . '</p>';

    // 1. El contrato clínico
    $html .= '<h2 class="nvx-que-exigir-h2">' . esc_html__( '1. El contrato clínico: la técnica exacta', 'nuvanx-medical' ) . '</h2>';
    $html .= '<p>' . esc_html__( 'Es fundamental que el documento médico especifique la técnica exacta (por ejemplo, "Laserlipólisis subcutánea con fibra de 600 µm a 1470 nm") en lugar de denominaciones exclusivamente comerciales. Asimismo, debe constar la identidad y número de colegiado del facultativo responsable, garantizando la continuidad asistencial.', 'nuvanx-medical' ) . '</p>';

    // 2. La anestesia
    $html .= '<h2 class="nvx-que-exigir-h2">' . esc_html__( '2. La anestesia: quién y cómo', 'nuvanx-medical' ) . '</h2>';
    $html .= '<p>' . esc_html__( 'La diferencia entre sedación consciente y anestesia general no es trivial. Pregunta abiertamente si el procedimiento requiere un anestesista cualificado en quirófano o si se realiza con sedación oral/tópica en sala blanca. En NUVANX, por ejemplo, evitamos la anestesia general realizando procedimientos mínimamente invasivos (Endolift®) que solo requieren anestesia local y/o sedación consciente.', 'nuvanx-medical' ) . '</p>';

    // 3. Fotos Antes y Después
    $html .= '<h2 class="nvx-que-exigir-h2">' . esc_html__( '3. Casos clínicos reales, no de catálogo', 'nuvanx-medical' ) . '</h2>';
    $html .= '<p>' . esc_html__( 'Es habitual que algunas clínicas enseñen catálogos fotográficos proporcionados por el fabricante de la máquina (la marca del láser o de los inyectables). Exige ver casos de "Antes y Después" realizados específicamente por el médico que te va a tratar, no fotos de stock o de la franquicia central.', 'nuvanx-medical' ) . '</p>';

    // 4. Las Reseñas
    $html .= '<h2 class="nvx-que-exigir-h2">' . esc_html__( '4. Criterios de evaluación del centro', 'nuvanx-medical' ) . '</h2>';
    $html .= '<p>' . esc_html__( 'Recomendamos verificar la consistencia de las valoraciones clínicas en diferentes plataformas. Un centro médico de rigor prioriza la evolución y seguridad del paciente por encima de la inmediatez comercial.', 'nuvanx-medical' ) . '</p>';

    // 5. Presupuesto cerrado
    $html .= '<h2 class="nvx-que-exigir-h2">' . esc_html__( '5. El presupuesto cerrado y desglosado', 'nuvanx-medical' ) . '</h2>';
    $html .= '<p>' . esc_html__( '¿Incluye el seguimiento? ¿Y las prendas de presoterapia (fajas) o la medicación postoperatoria? Un presupuesto clínico profesional no tiene "costes ocultos" ni caduca en 24 horas para forzarte a decidir.', 'nuvanx-medical' ) . '</p>';

    $html .= '<hr class="nvx-que-exigir-hr">';
    
    // CTA Block
    $html .= '<div class="nvx-que-exigir-cta-box">';
    $html .= '<h3 class="nvx-que-exigir-cta-title">' . esc_html__( 'Compromiso con la transparencia clínica', 'nuvanx-medical' ) . '</h3>';
    $html .= '<p class="nvx-que-exigir-cta-text">' . esc_html__( 'En NUVANX, firmamos el protocolo exacto y el presupuesto cerrado antes de cualquier procedimiento. Si quieres revisar cómo lo hacemos o buscas una segunda opinión objetiva sobre tu caso, agenda una valoración médica.', 'nuvanx-medical' ) . '</p>';
    if ( function_exists( 'nvx_cta_pair_markup' ) ) {
        $html .= nvx_cta_pair_markup( 'nvx-que-exigir-hero-ctas nvx-home-hero-ctas' );
    } else {
        $html .= '<a href="' . esc_url( $valuation_url ) . '" class="nvx-button">' . esc_html__( 'Iniciar mi valoración médica', 'nuvanx-medical' ) . '</a>';
    }
    $html .= '</div>';

    $html .= '</div>';

    return $html;
}
add_filter( 'the_content', 'nvx_content_que_exigir_hijack', 122 );

// Load the cross-page route guard after all earlier page renderers are registered.
require_once get_template_directory() . '/inc/nvx-equipo-route-guard.php';
