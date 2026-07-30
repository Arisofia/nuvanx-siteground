<?php
/**
 * Láser CO₂ fraccionado page — resurfacing, cicatrices, downtime.
 *
 * Wire-frame: Hero → Ablación fraccionada → Indicaciones → Downtime → Tarifas PVP → CTA.
 * Does not repeat Endolift / Endoláser body or laser hub catalog.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Singular context for CO₂ rewrite.
 */
function nvxCo2IsSingularContext(): bool {
    if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        return false;
    }

    return is_singular( 'page' ) || is_page();
}

/**
 * Detect Láser CO₂ fraccionado detail page only (not home/hub cards).
 */
function nvxContentIsCo2Page( string $content ): bool {
    if ( false !== strpos( $content, 'nvx-co2-editorial' ) ) {
        return false;
    }

    if ( ! nvxCo2IsSingularContext() ) {
        return false;
    }

    if ( is_front_page() || is_home() ) {
        return false;
    }

    $path = function_exists( 'nvx_schema_current_path' )
        ? nvx_schema_current_path( (int) get_queried_object_id() )
        : '';

    if ( is_string( $path ) && false !== strpos( $path, 'laser-co2-fraccionado' ) ) {
        return true;
    }

    return (bool) preg_match(
        '/aria-label=["\']Láser CO₂ NUVANX["\']|id=["\']nvx-co2-h1["\']|class=["\'][^"\']*nvx-co2-hero/iu',
        $content
    );
}

/**
 * Hero copy.
 */
function nvxCo2HeroCopyMarkup(): string {
    $price_facial = function_exists( 'nvx_tariff_catalog' )
        ? nvx_format_price_eur( nvx_tariff_catalog()['laser_co2']['facial']['pvp'] )
        : number_format_i18n( 330, 2 );

    $html  = '<div class="nvx-editorial-hero__copy-copy">';
    $html .= '<p class="nvx-eyebrow">' . esc_html__( 'NUVANX · Medicina estética láser', 'nuvanx-medical' ) . '</p>';
    $html .= '<h1 class="nvx-heading" id="nvx-co2-h1">' . esc_html__( 'Láser CO₂ fraccionado en Madrid: textura, poros y cicatrices de acné', 'nuvanx-medical' ) . '</h1>';

    // E-E-A-T Medical Authority Byline
    $html .= '<div class="nvx-medical-byline">';
    $html .= '<div class="nvx-medical-byline__text">';
    $html .= '<strong>' . esc_html__( 'Escrito y revisado por Dr. Javier Rivera Tejeda', 'nuvanx-medical' ) . '</strong><br>';
    $html .= '<span class="nvx-medical-byline__title">' . esc_html__( 'Director médico NUVANX · Fecha de última revisión: julio 2026', 'nuvanx-medical' ) . '</span>';
    $html .= '</div></div>';
    $html .= '<p class="nvx-lead">' . esc_html__( 'Protocolos de resurfacing fraccionado para mejorar irregularidades de textura y cicatrices, con un plan de recuperación realista (eritema y descamación según profundidad).', 'nuvanx-medical' ) . '</p>';
    $html .= '<p class="nvx-lead">' . esc_html(
        sprintf(
            /* translators: %s: facial session PVP */
            __( 'Parámetros de potencia, profundidad y densidad ajustados por el equipo médico. PVP sesión facial desde %s € (IVA incl.).', 'nuvanx-medical' ),
            $price_facial
        )
    ) . '</p>';

    if ( function_exists( 'nvx_cta_pair_markup' ) ) {
        $html .= nvx_cta_pair_markup( 'nvx-co2-hero-ctas nvx-home-hero-ctas' );
    }

    $html .= '<p class="nvx-brand-meta">' . esc_html__( 'Chamberí · Salamanca–Goya · No es un peeling cosmético superficial', 'nuvanx-medical' ) . '</p>';
    $html .= '</div>';

    return $html;
}

/**
 * Builds the CO₂ laser editorial body markup, including treatment information, indications, recovery phases, and reference pricing.
 *
 * @return string The generated editorial body HTML.
 */
function nvxCo2EditorialBodyMarkup(): string {
    $catalog      = function_exists( 'nvx_tariff_catalog' ) ? nvx_tariff_catalog() : array();
    $price_facial = ! empty( $catalog['laser_co2']['facial']['pvp'] )
        ? nvx_format_price_eur( $catalog['laser_co2']['facial']['pvp'] )
        : number_format_i18n( 330, 2 );
    $price_body   = ! empty( $catalog['laser_co2']['corporal']['pvp'] )
        ? nvx_format_price_eur( $catalog['laser_co2']['corporal']['pvp'] )
        : number_format_i18n( 450, 2 );

    $html  = '<div class="nvx-co2-editorial nvx-editorial-page">';

    // A. Science of fractional ablation.
    $html .= '<section class="nvx-editorial-section nvx-co2-science" aria-labelledby="nvx-co2-science-title">';
    $html .= '<div class="nvx-editorial-section__inner">';
    $html .= '<p class="nvx-editorial-kicker">' . esc_html__( 'Mecanismo', 'nuvanx-medical' ) . '</p>';
    $html .= '<h2 id="nvx-co2-science-title" class="nvx-editorial-heading">' . esc_html__( 'La ciencia de la ablación fraccionada', 'nuvanx-medical' ) . '</h2>';
    $html .= '<p class="nvx-editorial-body nvx-editorial-body--measure">' . esc_html__( 'El láser genera micro-columnas ablativas controladas en el tejido, preservando la piel circundante. Este daño térmico fraccionado induce una respuesta fisiológica de regeneración dérmica.', 'nuvanx-medical' ) . '</p>';
    $html .= '<p class="nvx-editorial-body nvx-editorial-body--measure">' . esc_html__( 'Ese tejido peri-lesional acelera la curación y estimula una respuesta de neocolagénesis (colágeno tipo I y III). No es un peeling cosmético superficial: es una intervención de alto impacto que exige planificación médica y compromiso con el downtime.', 'nuvanx-medical' ) . '</p>';
    $html .= '</div></section>';

    // B. Indications.
    $html .= '<section class="nvx-editorial-section nvx-co2-indications" aria-labelledby="nvx-co2-ind-title">';
    $html .= '<div class="nvx-editorial-section__inner">';
    $html .= '<p class="nvx-editorial-kicker">' . esc_html__( 'Indicaciones', 'nuvanx-medical' ) . '</p>';
    $html .= '<h2 id="nvx-co2-ind-title" class="nvx-editorial-heading">' . esc_html__( 'Indicaciones terapéuticas principales', 'nuvanx-medical' ) . '</h2>';
    $html .= '<p class="nvx-editorial-body nvx-editorial-body--measure">' . esc_html__( 'El equipo médico (dirección Dr. Rivera Tejeda) ajusta potencia, profundidad fraccional y densidad de haces según fototipo, objetivo y tolerancia al downtime.', 'nuvanx-medical' ) . '</p>';
    $html .= '<ul class="nvx-editorial-grid-list">';
    $inds = array(
        array(
            'title' => __( 'Cicatrices atróficas de acné', 'nuvanx-medical' ),
            'body'  => __( 'Elevación y remodelación de depresiones (boxcar, rolling) con fraccionamiento calibrado a la profundidad de la cicatriz.', 'nuvanx-medical' ),
        ),
        array(
            'title' => __( 'Poros dilatados y textura irregular', 'nuvanx-medical' ),
            'body'  => __( 'Nivelación de la superficie cutánea dañada por inflamación previa y mejora de la regularidad del relieve.', 'nuvanx-medical' ),
        ),
        array(
            'title' => __( 'Fotodaño y elastosis solar', 'nuvanx-medical' ),
            'body'  => __( 'Tratamiento de pigmento anómalo y piel asfixiada o envejecida por UV, orientado a luminosidad y calidad dérmica tras la regeneración.', 'nuvanx-medical' ),
        ),
    );
    foreach ( $inds as $ind ) {
        $html .= '<li class="nvx-editorial-grid-item">';
        $html .= '<h3 class="nvx-editorial-grid-item__title">' . esc_html( $ind['title'] ) . '</h3>';
        $html .= '<p class="nvx-editorial-body">' . esc_html( $ind['body'] ) . '</p>';
        $html .= '</li>';
    }
    $html .= '</ul></div></section>';

    // C. Recovery timeline (unique — not on Endolift FAQ).
    $html .= '<section class="nvx-editorial-section nvx-co2-downtime" aria-labelledby="nvx-co2-down-title">';
    $html .= '<div class="nvx-editorial-section__inner">';
    $html .= '<p class="nvx-editorial-kicker">' . esc_html__( 'Downtime médico', 'nuvanx-medical' ) . '</p>';
    $html .= '<h2 id="nvx-co2-down-title" class="nvx-editorial-heading">' . esc_html__( 'Cronología real de la recuperación', 'nuvanx-medical' ) . '</h2>';
    $html .= '<p class="nvx-editorial-body nvx-editorial-body--measure">' . esc_html__( 'El CO₂ fraccionado exige compromiso con la curación. Los plazos siguientes son orientativos y dependen de la profundidad del protocolo.', 'nuvanx-medical' ) . '</p>';
    $html .= '<ol class="nvx-editorial-timeline">';
    $phases = array(
        array(
            'n'     => '01',
            'title' => __( 'Días 1 a 3', 'nuvanx-medical' ),
            'body'  => __( 'Eritema intenso y sensación de calor, similar a una quemadura solar. Esta respuesta inflamatoria aguda es esperable y necesaria.', 'nuvanx-medical' ),
        ),
        array(
            'n'     => '02',
            'title' => __( 'Días 4 a 7', 'nuvanx-medical' ),
            'body'  => __( 'Fase de descamación epidérmica. El tejido necrótico superficial se desprende, revelando una piel en regeneración. Es imperativo no forzar el desprendimiento.', 'nuvanx-medical' ),
        ),
        array(
            'n'     => '03',
            'title' => __( 'Día 7 en adelante', 'nuvanx-medical' ),
            'body'  => __( 'Resolución clínica del eritema superficial. La remodelación profunda del colágeno continuará de forma subclínica durante meses.', 'nuvanx-medical' ),
        ),
    );
    foreach ( $phases as $phase ) {
        $html .= '<li class="nvx-editorial-timeline__item">';
        $html .= '<span class="nvx-editorial-timeline__n">' . esc_html( $phase['n'] ) . '</span>';
        $html .= '<h3 class="nvx-editorial-timeline__title">' . esc_html( $phase['title'] ) . '</h3>';
        $html .= '<p class="nvx-editorial-body">' . esc_html( $phase['body'] ) . '</p>';
        $html .= '</li>';
    }
    $html .= '</ol></div></section>';

    // D. PVP reference (clinic tariff — facial 330 / body 450).
    $html .= '<section class="nvx-editorial-section nvx-co2-pricing" aria-labelledby="nvx-co2-price-title" id="tarifas-co2">';
    $html .= '<div class="nvx-editorial-section__inner">';
    $html .= '<p class="nvx-editorial-kicker">' . esc_html__( 'Tarifas públicas', 'nuvanx-medical' ) . '</p>';
    $html .= '<h2 id="nvx-co2-price-title" class="nvx-editorial-heading">' . esc_html__( 'PVP sesión Láser CO₂ (IVA incluido)', 'nuvanx-medical' ) . '</h2>';
    $html .= '<div class="nvx-editorial-price-table-wrap">';
    $html .= '<table class="nvx-editorial-price-table">';
    $html .= '<caption class="nvx-editorial-price-table__cap">' . esc_html__( 'Tarifa clínica de referencia. Profundidad y zonas pueden modular el plan y el número de sesiones.', 'nuvanx-medical' ) . '</caption>';
    $html .= '<thead><tr><th scope="col">' . esc_html__( 'Sesión', 'nuvanx-medical' ) . '</th><th scope="col">' . esc_html__( 'PVP', 'nuvanx-medical' ) . '</th></tr></thead><tbody>';
    $html .= '<tr><th scope="row">' . esc_html__( 'Láser CO₂ facial', 'nuvanx-medical' ) . '</th><td>' . esc_html( $price_facial ) . '&nbsp;€</td></tr>';
    $html .= '<tr><th scope="row">' . esc_html__( 'Láser CO₂ corporal', 'nuvanx-medical' ) . '</th><td>' . esc_html( $price_body ) . '&nbsp;€</td></tr>';
    $html .= '</tbody></table></div>';
    $html .= '<p class="nvx-editorial-body nvx-editorial-body--measure"><em>' . esc_html__( 'La indicación, el fototipo y el downtime esperable se confirman en valoración presencial antes de cualquier sesión.', 'nuvanx-medical' ) . '</em></p>';
    $html .= '</div></section>';

    // Closing valoración CTA: site-wide nvx-cta-banner in footer.php (not page-local).

    $html .= '</div>';

    return $html;
}

/**
 * Rebuilds the CO₂ treatment page with a dedicated hero section and editorial body.
 *
 * Preserves matching hero media and the outer page wrapper when available.
 *
 * @param string $content The original page content.
 * @return string The rebuilt CO₂ page content, or the original content when the page is not targeted.
 */
function nvxContentRestructureCo2Page( string $content ): string {
    if ( ! nvxContentIsCo2Page( $content ) ) {
        return $content;
    }

    $media = '';
    if ( preg_match( '/<(?:figure|div) class="nvx-brand-hero__media"[\s\S]*?<\/(?:figure|div)>/iu', $content, $media_match ) ) {
        $media = $media_match[0];
    }

    $hero  = '<section class="nvx-brand-hero nvx-brand-hero--laser nvx-editorial-hero" aria-labelledby="nvx-co2-h1" aria-label="' . esc_attr__( 'Láser CO₂ NUVANX', 'nuvanx-medical' ) . '">';
    $hero .= '<div class="nvx-brand-hero__inner">';
    $hero .= nvxCo2HeroCopyMarkup();
    $hero .= $media;
    $hero .= '</div></section>';

    $body = nvxCo2EditorialBodyMarkup();

    if ( preg_match( '/(<div class="nvx-brand-page[^"]*"[^>]*>)/iu', $content, $wrap ) ) {
        return $wrap[1] . $hero . $body . '</div>';
    }

    return $hero . $body;
}
add_filter( 'the_content', 'nvxContentRestructureCo2Page', 19 );
