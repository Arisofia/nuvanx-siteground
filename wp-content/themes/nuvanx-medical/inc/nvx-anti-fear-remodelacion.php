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
function nvx_content_is_anti_fear_remodelacion_page( string $content ): bool {
    if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        return false;
    }
    if ( ! is_singular( 'page' ) && ! is_page() ) {
        return false;
    }

    // Ruta canónica.
    if ( function_exists( 'nvx_schema_current_path' ) ) {
        $path = nvx_schema_current_path( (int) get_queried_object_id() );
        if ( function_exists( 'nvx_schema_path_matches' ) && nvx_schema_path_matches( $path, '/remodelacion-corporal-sin-anestesia-madrid/' ) ) {
            return true;
        }
    }

    // Fallback por slug.
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
    $html .= '<p class="nvx-lead">' . esc_html__( 'Si tu principal freno es el paso por quirófano y la anestesia general, debes saber que no todos los escenarios requieren cirugía. Nuestra primera responsabilidad es diagnosticar la estructura de tu grasa, el grado de laxitud cutánea y el estado de la pared abdominal. Solo con ese mapa anatómico confirmamos si existe indicación clínica para un láser mínimamente invasivo, o si, por el contrario, la cirugía es la única vía honesta.', 'nuvanx-medical' ) . '</p>';
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

    $html  = '<article class="nvx-brand-readable nvx-strategy-page nvx-shell nvx-anti-fear-remodelacion">';
    // Sección 1: Por qué da miedo operarse
    $html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner">';
    $html .= '<h2 class="nvx-brand-title">' . esc_html__( 'Por qué da miedo la liposucción clásica', 'nuvanx-medical' ) . '</h2>';
    $html .= '<p class="nvx-brand-body nvx-editorial-body--measure">' . esc_html__( 'Entendemos la barrera de entrada: el quirófano, la recuperación prolongada, la anestesia general y la incertidumbre frente a las cicatrices. En NUVANX no somos una clínica de cirugía estética intentando llenar un quirófano, sino un equipo médico buscando la intervención mínima viable. Y cuando la cirugía es estrictamente necesaria para tu anatomía, te lo comunicamos con absoluta claridad.', 'nuvanx-medical' ) . '</p>';
    $html .= '</div></section>';

    // Sección 2: Diagnóstico anatómico — antes de hablar de técnicas
    $html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner">';
    $html .= '<h2 class="nvx-brand-title">' . esc_html__( 'Lo primero no es elegir técnica. Es entender tu anatomía.', 'nuvanx-medical' ) . '</h2>';
    $html .= '<p class="nvx-brand-body nvx-editorial-body--measure">' . esc_html__( 'Antes de decidir si tiene sentido un láser, una liposucción o nada, diferenciamos grasa subcutánea (la que se pellizca) de grasa visceral (la que da tripa dura), miramos cuánto sobra o no sobra piel y revisamos la pared abdominal (diástasis, hernias). Con esos datos, tu caso deja de ser “barriguita” y pasa a ser un mapa clínico concreto.', 'nuvanx-medical' ) . '</p>';
    $html .= '<p class="nvx-brand-body">' . esc_html__( 'Ese mapa es el núcleo de NUVANX Contour Architecture™: si el problema principal es visceral o de exceso cutáneo, te lo decimos y no te proponemos un láser. Si es grasa focal y la piel puede acompañar, entonces hablamos de procedimientos mínimamente invasivos.', 'nuvanx-medical' ) . '</p>';
    $html .= '<p class="nvx-brand-body"><a class="nvx-brand-inline-link" href="' . esc_url( $contour_url ) . '">' . esc_html__( 'Ver NUVANX Contour Architecture™', 'nuvanx-medical' ) . '</a></p>';
    $html .= '</div></section>';

    // Sección 3: Cuándo hablamos de cirugía y cuándo no
    $html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner">';
    $html .= '<h2 class="nvx-brand-title">' . esc_html__( 'Cuándo hablamos de cirugía y cuándo no', 'nuvanx-medical' ) . '</h2>';
    $html .= '<ul class="nvx-check-list">';
    $html .= '<li>' . esc_html__( 'Grasa visceral, tripa dura o exceso cutáneo importante son territorio de cirugía. No prometemos que un láser haga lo que requiere una abdominoplastia.', 'nuvanx-medical' ) . '</li>';
    $html .= '<li>' . esc_html__( 'Grasa subcutánea focal, pliegues concretos y flacidez leve–moderada sí pueden ser candidatos a endoláser corporal o Endolift®, tras exploración.', 'nuvanx-medical' ) . '</li>';
    $html .= '<li>' . esc_html__( 'Si la anatomía dice que lo honesto es derivar a cirugía, se dice. La tecnología no se fuerza para “evitar el miedo” a costa del resultado.', 'nuvanx-medical' ) . '</li>';
    $html .= '</ul>';
    $html .= '</div></section>';

    // Sección 4: Cómo funciona el protocolo en NUVANX
    $html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner">';
    $html .= '<h2 class="nvx-brand-title">' . esc_html__( 'Cómo funciona el protocolo en NUVANX', 'nuvanx-medical' ) . '</h2>';
    $html .= '<ol class="nvx-editorial-grid-list">';
    $html .= '<li class="nvx-editorial-grid-item"><h3 class="nvx-editorial-grid-item__title">' . esc_html__( '1. Valoración anatómica', 'nuvanx-medical' ) . '</h3><p class="nvx-editorial-body">' . esc_html__( 'Se estudian grasa subcutánea, grasa visceral, laxitud, calidad cutánea y pared abdominal (diástasis/hernias) según la zona. El objetivo no es “quitar kilos”, sino mejorar unidades anatómicas cuando tiene sentido.', 'nuvanx-medical' ) . '</p></li>';
    $html .= '<li class="nvx-editorial-grid-item"><h3 class="nvx-editorial-grid-item__title">' . esc_html__( '2. Decisión: láser, cirugía o esperar', 'nuvanx-medical' ) . '</h3><p class="nvx-editorial-body">' . esc_html__( 'Con los datos sobre la mesa, se decide si hay indicación para endoláser / Endolift®, si conviene derivar a cirugía plástica o si es mejor no intervenir. No se ofrece una técnica solo porque cause menos miedo.', 'nuvanx-medical' ) . '</p></li>';
    $html .= '<li class="nvx-editorial-grid-item"><h3 class="nvx-editorial-grid-item__title">' . esc_html__( '3. Plan de procedimiento y recuperación', 'nuvanx-medical' ) . '</h3><p class="nvx-editorial-body">' . esc_html__( 'Si se indica un láser, se explican anestesia local, duración, edema, molestias esperables y reincorporación social. Recuperación honesta: ni drama, ni “cero inflamación”.', 'nuvanx-medical' ) . '</p></li>';
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
    $html .= '<h2 class="nvx-brand-title">' . esc_html__( 'Tu miedo es legítimo. Tu decisión merece información real.', 'nuvanx-medical' ) . '</h2>';
    $html .= '<p class="nvx-brand-body nvx-editorial-body--measure">' . esc_html__( 'No utilizamos la tecnología para esquivar la cirugía cuando esta es necesaria, ni te prometemos resultados irreales para calmar tu miedo. Te aseguramos un diagnóstico anatómico preciso: qué podemos resolver mediante láser ambulatorio, qué está fuera de nuestros límites, y cuál es tu plan médico real.', 'nuvanx-medical' ) . '</p>';
    $html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( $valoracion ) . '">' . esc_html__( 'Solicitar valoración médica', 'nuvanx-medical' ) . '</a></p>';
    $html .= '</div></section>';

    $html .= '</article>';
    return $html;
}

/** Filtro de contenido: sustituye la landing Anti-Fear por el markup gobernado. */
function nvx_anti_fear_remodelacion_filter_content( string $content ): string {
    if ( is_admin() || ! is_main_query() || ! in_the_loop() ) {
        return $content;
    }
    if ( ! nvx_content_is_anti_fear_remodelacion_page( $content ) ) {
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
