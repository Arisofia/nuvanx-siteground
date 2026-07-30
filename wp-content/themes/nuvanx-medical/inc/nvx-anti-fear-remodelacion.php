<?php
/**
 * Landing "Remodelación corporal sin anestesia general" · Anti-Fear model.
 *
 * Path: /remodelacion-corporal-sin-anestesia-madrid/
 * Objetivo: captación de pacientes con miedo a anestesia general/liposucción,
 * explicando límites y alternativas mínimamente invasivas (Endoláser / Endolift®)
 * dentro del protocolo NUVANX Contour Architecture™.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Detecta la landing anti-fear por slug/ruta. */
function nvx_content_is_anti_fear_remodelacion_page(): bool {
    if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( ! is_singular( 'page' ) && ! is_page() ) ) {
        return false;
    }

    if ( function_exists( 'nvx_schema_current_path' ) && function_exists( 'nvx_schema_path_matches' ) ) {
        $path = nvx_schema_current_path( (int) get_queried_object_id() );
        if ( nvx_schema_path_matches( $path, '/remodelacion-corporal-sin-anestesia-madrid/' ) ) {
            return true;
        }
    }

    $slug = (string) get_post_field( 'post_name', get_queried_object_id() );
    return 'remodelacion-corporal-sin-anestesia-madrid' === $slug;
}

/** Hero de la landing: miedo vs alternativas. */
function nvx_anti_fear_remodelacion_hero_markup(): string {
    $valoracion = function_exists( 'nvx_cta_valoracion_url' )
        ? nvx_cta_valoracion_url()
        : home_url( '/madrid/valoracion/' );

    $html  = '<section class="nvx-brand-hero nvx-brand-hero--laser nvx-editorial-hero" aria-labelledby="nvx-anti-fear-h1" aria-label="' . esc_attr__( 'Remodelación corporal sin anestesia general', 'nuvanx-medical' ) . '">';
    $html .= '<div class="nvx-brand-hero__inner">';
    $html .= '<div class="nvx-editorial-hero__copy">';
    $html .= '<p class="nvx-eyebrow">' . esc_html__( 'NUVANX · Contour Architecture™', 'nuvanx-medical' ) . '</p>';
    $html .= '<h1 class="nvx-heading" id="nvx-anti-fear-h1">' . esc_html__( 'Remodelación corporal sin anestesia general en Madrid.', 'nuvanx-medical' ) . '</h1>';
    $html .= '<p class="nvx-lead">' . esc_html__( 'Comprendemos que el entorno quirúrgico, la anestesia general, los tiempos de recuperación prolongados y las cicatrices pueden suponer una barrera para el paciente. Si tu principal freno es el paso por quirófano y la anestesia general, debes saber que no todos los escenarios requieren cirugía. Nuestra primera responsabilidad es diagnosticar la estructura de tu grasa, el grado de laxitud cutánea y el estado de la pared abdominal. Solo con ese mapa anatómico confirmamos si existe indicación clínica para un láser mínimamente invasivo, o si, por el contrario, la cirugía es la única vía honesta.', 'nuvanx-medical' ) . '</p>';
    $html .= '<p class="nvx-brand-meta">' . esc_html__( 'Chamberí · Salamanca–Goya · Protocolo NUVANX Contour Architecture™', 'nuvanx-medical' ) . '</p>';
    $html .= '<div class="nvx-brand-actions"><a class="nvx-btn nvx-btn--primary" href="' . esc_url( $valoracion ) . '">' . esc_html__( 'Solicitar valoración médica', 'nuvanx-medical' ) . '</a></div>';
    $html .= '</div></div></section>';

    return $html;
}

/** Cuerpo editorial básico de la landing Anti-Fear. */
function nvx_anti_fear_remodelacion_body_markup(): string {
    $contour_url  = home_url( '/remodelacion-corporal-laser-madrid/' );
    $endolift_url = home_url( '/endolift-facial-papada-mandibula/' );
    $endolaser_url = home_url( '/endolaser-corporal-grasa-localizada/' );

    $html  = '<article class="nvx-brand-page nvx-strategy-page nvx-anti-fear-remodelacion">';
    // Sección 1: Por qué da miedo operarse
    $html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner">';
    $html .= '<h2 class="nvx-brand-title">' . esc_html__( 'Inquietudes frente a la liposucción quirúrgica', 'nuvanx-medical' ) . '</h2>';
    $html .= '<p class="nvx-brand-body nvx-editorial-body--measure">' . esc_html__( 'Comprendemos que el entorno quirúrgico, la anestesia general, los tiempos de recuperación prolongados y las cicatrices pueden suponer una barrera para el paciente. En NUVANX priorizamos la intervención médica menos invasiva que resulte viable y eficaz para el paciente. Y cuando la cirugía es estrictamente necesaria para tu anatomía, te lo comunicamos con absoluta claridad.', 'nuvanx-medical' ) . '</p>';
    $html .= '</div></section>';

    // Sección 2: Diagnóstico anatómico — antes de hablar de técnicas
    $html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner">';
    $html .= '<h2 class="nvx-brand-title">' . esc_html__( 'El diagnóstico anatómico precede a la selección de la técnica.', 'nuvanx-medical' ) . '</h2>';
    $html .= '<p class="nvx-brand-body nvx-editorial-body--measure">' . esc_html__( 'Antes de plantear una intervención médica, diferenciamos clínicamente la adiposidad subcutánea de la grasa visceral, evaluamos la elasticidad y laxitud de la piel, y examinamos la integridad de la pared abdominal (diástasis, hernias). A partir de estos datos, elaboramos un diagnóstico estructural individualizado.', 'nuvanx-medical' ) . '</p>';
    $html .= '<p class="nvx-brand-body">' . esc_html__( 'Este diagnóstico constituye el pilar de NUVANX Contour Architecture™: si el predominio es visceral o existe un gran exceso cutáneo, se descarta el tratamiento láser por su falta de indicación médica. Si es grasa focal y la piel puede acompañar, entonces hablamos de procedimientos mínimamente invasivos.', 'nuvanx-medical' ) . '</p>';
    $html .= '<p class="nvx-brand-body"><a class="nvx-brand-inline-link" href="' . esc_url( $contour_url ) . '">' . esc_html__( 'Ver NUVANX Contour Architecture™', 'nuvanx-medical' ) . '</a></p>';
    $html .= '</div></section>';

    // Sección 3: Cuándo hablamos de cirugía y cuándo no
    $html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner">';
    $html .= '<h2 class="nvx-brand-title">' . esc_html__( 'Cuándo hablamos de cirugía y cuándo no', 'nuvanx-medical' ) . '</h2>';
    $html .= '<ul class="nvx-check-list">';
    $html .= '<li>' . esc_html__( 'La adiposidad visceral y la flacidez cutánea severa son indicaciones quirúrgicas. No utilizamos láser en pacientes cuyo cuadro clínico requiere una abdominoplastia.', 'nuvanx-medical' ) . '</li>';
    $html .= '<li>' . esc_html__( 'Grasa subcutánea focal, pliegues concretos y flacidez leve–moderada sí pueden ser candidatos a endoláser corporal o Endolift®, tras exploración.', 'nuvanx-medical' ) . '</li>';
    $html .= '<li>' . esc_html__( 'Si la anatomía del paciente indica la necesidad de una cirugía plástica, se le informa objetivamente, sin forzar indicaciones de tratamientos mínimamente invasivos que no le aportarían resultados reales.', 'nuvanx-medical' ) . '</li>';
    $html .= '</ul>';
    $html .= '</div></section>';

    // Sección 4: Cómo funciona el protocolo en NUVANX
    $html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner">';
    $html .= '<h2 class="nvx-brand-title">' . esc_html__( 'Cómo funciona el protocolo en NUVANX', 'nuvanx-medical' ) . '</h2>';
    $html .= '<ol class="nvx-editorial-grid-list">';
    $html .= '<li class="nvx-editorial-grid-item"><h3 class="nvx-editorial-grid-item__title">' . esc_html__( '1. Valoración anatómica', 'nuvanx-medical' ) . '</h3><p class="nvx-editorial-body">' . esc_html__( 'Se estudian grasa subcutánea, grasa visceral, laxitud, calidad cutánea y pared abdominal (diástasis/hernias) según la zona. El objetivo terapéutico no es el tratamiento de la obesidad o la pérdida de peso sistémica, sino la remodelación de contornos anatómicos específicos.', 'nuvanx-medical' ) . '</p></li>';
    $html .= '<li class="nvx-editorial-grid-item"><h3 class="nvx-editorial-grid-item__title">' . esc_html__( '2. Decisión: láser, cirugía o esperar', 'nuvanx-medical' ) . '</h3><p class="nvx-editorial-body">' . esc_html__( 'El equipo médico evalúa si existe una indicación real para Endoláser o Endolift®, si el caso requiere derivación quirúrgica, o si se desaconseja cualquier procedimiento.', 'nuvanx-medical' ) . '</p></li>';
    $html .= '<li class="nvx-editorial-grid-item"><h3 class="nvx-editorial-grid-item__title">' . esc_html__( '3. Plan de procedimiento y recuperación', 'nuvanx-medical' ) . '</h3><p class="nvx-editorial-body">' . esc_html__( 'En caso de indicarse un abordaje láser, se detallan el uso de anestesia local, la duración del procedimiento, el grado de edema, las molestias postoperatorias y los plazos médicos de recuperación.', 'nuvanx-medical' ) . '</p></li>';
    $html .= '</ol>';
    $html .= '</div></section>';

    // Sección 5: Enlaces a Endolift y Endoláser
    $html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner">';
    $html .= '<h2 class="nvx-brand-title">' . esc_html__( 'Tecnologías mínimamente invasivas que pueden formar parte del plan (cuando toca)', 'nuvanx-medical' ) . '</h2>';
    $html .= '<ul class="nvx-editorial-grid-list">';
    $html .= '<li class="nvx-editorial-grid-item"><h3 class="nvx-editorial-grid-item__title">' . esc_html__( 'Endolift® facial', 'nuvanx-medical' ) . '</h3><p class="nvx-editorial-body">' . esc_html__( 'Microfibra láser subdérmica para papada, mandíbula y cuello en casos seleccionados, bajo anestesia local, sin anestesia general.', 'nuvanx-medical' ) . '</p><p><a class="nvx-brand-inline-link" href="' . esc_url( $endolift_url ) . '">' . esc_html__( 'Ver página de Endolift® facial', 'nuvanx-medical' ) . '</a></p></li>';
    $html .= '<li class="nvx-editorial-grid-item"><h3 class="nvx-editorial-grid-item__title">' . esc_html__( 'Endoláser corporal', 'nuvanx-medical' ) . '</h3><p class="nvx-editorial-body">' . esc_html__( 'Laserlipólisis corporal para grasa localizada y retracción cutánea cuando la anatomía y la pared abdominal lo permiten, también bajo anestesia local.', 'nuvanx-medical' ) . '</p><p><a class="nvx-brand-inline-link" href="' . esc_url( $endolaser_url ) . '">' . esc_html__( 'Ver página de Endoláser corporal', 'nuvanx-medical' ) . '</a></p></li>';
    $html .= '</ul>';
    $html .= '</div></section>';

    // CTA final
    $valoracion = function_exists( 'nvx_cta_valoracion_url' )
        ? nvx_cta_valoracion_url()
        : home_url( '/madrid/valoracion/' );

    $html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner">';
    $html .= '<h2 class="nvx-brand-title">' . esc_html__( 'El abordaje clínico transparente ante el paciente.', 'nuvanx-medical' ) . '</h2>';
    $html .= '<p class="nvx-brand-body nvx-editorial-body--measure">' . esc_html__( 'No utilizamos la tecnología para esquivar la cirugía cuando esta es necesaria, ni te prometemos resultados irreales para calmar tu miedo. Te aseguramos un diagnóstico anatómico preciso: qué podemos resolver mediante láser ambulatorio, qué está fuera de nuestros límites, y cuál es tu plan médico real.', 'nuvanx-medical' ) . '</p>';
    $html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( $valoracion ) . '">' . esc_html__( 'Solicitar valoración médica', 'nuvanx-medical' ) . '</a></p>';
    $html .= '</div></section>';

    $html .= '</article>';
    return $html;
}

/** Filtro de contenido: sustituye la landing Anti-Fear por el markup gobernado. */
function nvx_anti_fear_remodelacion_filter_content( string $content ): string {
    if ( is_admin() || ! is_main_query() || ! in_the_loop() || ! nvx_content_is_anti_fear_remodelacion_page() ) {
        return $content;
    }

    $hero = nvx_anti_fear_remodelacion_hero_markup();
    $body = nvx_anti_fear_remodelacion_body_markup();

    if ( preg_match( '/(<div class="nvx-brand-page[^"]*"[^>]*>)/iu', $content, $wrap ) ) {
        return $wrap[1] . $hero . $body . '</div>';
    }

    return $hero . $body;
}
add_filter( 'the_content', 'nvx_anti_fear_remodelacion_filter_content', 23 );
