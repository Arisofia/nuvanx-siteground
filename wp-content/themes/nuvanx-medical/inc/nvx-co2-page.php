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
function nvx_co2_is_singular_context(): bool {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	return is_singular( 'page' ) || is_page();
}

/**
 * Detect Láser CO₂ fraccionado detail page only (not home/hub cards).
 */
function nvx_content_is_co2_page( string $content ): bool {
	if ( false !== strpos( $content, 'nvx-co2-editorial' ) ) {
		return false;
	}

	if ( ! nvx_co2_is_singular_context() ) {
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
function nvx_co2_hero_copy_markup(): string {
	$price_facial = function_exists( 'nvx_tariff_catalog' )
		? nvx_format_price_eur( nvx_tariff_catalog()['laser_co2']['facial']['pvp'] )
		: number_format_i18n( 330, 2 );

	$html  = '<div class="nvx-brand-hero__copy nvx-co2-hero-copy">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'NUVANX · Medicina estética láser', 'nuvanx-medical' ) . '</p>';
	$html .= '<h1 class="nvx-brand-hero__title" id="nvx-co2-h1">' . esc_html__( 'Láser CO₂ fraccionado en Madrid: textura, poros y cicatrices de acné', 'nuvanx-medical' ) . '</h1>';
	$html .= '<p class="nvx-brand-hero__lead">' . esc_html__( 'Protocolos de resurfacing fraccionado para mejorar irregularidades de textura y cicatrices, con un plan de recuperación realista (eritema y descamación según profundidad).', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-brand-hero__description">' . esc_html(
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
 * Action CTAs.
 */
function nvx_co2_action_ctas_markup(): string {
	$valoracion = function_exists( 'nvx_cta_valoracion_url' )
		? nvx_cta_valoracion_url()
		: home_url( '/madrid/valoracion/' );
	$clinicas   = home_url( '/clinicas-de-medicina-estetica-nuvanx/' );

	$html  = '<div class="nvx-cta-pair nvx-endolift-action__ctas">';
	$html .= sprintf(
		'<a class="nvx-brand-btn nvx-brand-btn--primary" href="%1$s">%2$s</a>',
		esc_url( $valoracion ),
		esc_html__( 'Reservar valoración médica', 'nuvanx-medical' )
	);
	$html .= sprintf(
		'<a class="nvx-brand-btn nvx-brand-btn--secondary" href="%1$s">%2$s</a>',
		esc_url( $clinicas ),
		esc_html__( 'Ver centros en Madrid', 'nuvanx-medical' )
	);
	$html .= '</div>';

	return $html;
}

/**
 * Editorial body.
 */
function nvx_co2_editorial_body_markup(): string {
	$catalog      = function_exists( 'nvx_tariff_catalog' ) ? nvx_tariff_catalog() : array();
	$price_facial = ! empty( $catalog['laser_co2']['facial']['pvp'] )
		? nvx_format_price_eur( $catalog['laser_co2']['facial']['pvp'] )
		: number_format_i18n( 330, 2 );
	$price_body   = ! empty( $catalog['laser_co2']['corporal']['pvp'] )
		? nvx_format_price_eur( $catalog['laser_co2']['corporal']['pvp'] )
		: number_format_i18n( 450, 2 );

	$html  = '<div class="nvx-co2-editorial nvx-endolift-editorial">';

	// A. Science of fractional ablation.
	$html .= '<section class="nvx-endolift-section nvx-co2-science" aria-labelledby="nvx-co2-science-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Mecanismo', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-co2-science-title" class="nvx-endolift-heading">' . esc_html__( 'La ciencia de la ablación fraccionada', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'El láser de dióxido de carbono (CO₂) fraccionado es un resurfacing ablativo de referencia en dermatología estética para renovar la arquitectura de la piel. No vaporiza toda la superficie de forma continua: emite microhaces que crean columnas térmicas de vaporización rodeadas de tejido sano.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'Ese tejido peri-lesional acelera la curación y estimula una respuesta de neocolagénesis (colágeno tipo I y III). No es un peeling cosmético superficial: es una intervención de alto impacto que exige planificación médica y compromiso con el downtime.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div></section>';

	// B. Indications.
	$html .= '<section class="nvx-endolift-section nvx-co2-indications" aria-labelledby="nvx-co2-ind-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Indicaciones', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-co2-ind-title" class="nvx-endolift-heading">' . esc_html__( 'Indicaciones terapéuticas principales', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'El equipo médico (dirección Dr. Rivera Tejeda) ajusta potencia, profundidad fraccional y densidad de haces según fototipo, objetivo y tolerancia al downtime.', 'nuvanx-medical' ) . '</p>';
	$html .= '<ul class="nvx-endolaser-zone-list">';
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
		$html .= '<li class="nvx-endolaser-zone">';
		$html .= '<h3 class="nvx-endolaser-zone__title">' . esc_html( $ind['title'] ) . '</h3>';
		$html .= '<p class="nvx-endolift-body">' . esc_html( $ind['body'] ) . '</p>';
		$html .= '</li>';
	}
	$html .= '</ul></div></section>';

	// C. Recovery timeline (unique — not on Endolift FAQ).
	$html .= '<section class="nvx-endolift-section nvx-co2-downtime" aria-labelledby="nvx-co2-down-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Downtime médico', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-co2-down-title" class="nvx-endolift-heading">' . esc_html__( 'Cronología real de la recuperación', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'El CO₂ fraccionado exige compromiso con la curación. Los plazos siguientes son orientativos y dependen de la profundidad del protocolo.', 'nuvanx-medical' ) . '</p>';
	$html .= '<ol class="nvx-co2-timeline">';
	$phases = array(
		array(
			'n'     => '01',
			'title' => __( 'Días 1 a 3', 'nuvanx-medical' ),
			'body'  => __( 'Eritema intenso (similar a quemadura solar fuerte), calor profundo y patrón punteado oscuro (columnas tratadas / debris microscópico).', 'nuvanx-medical' ),
		),
		array(
			'n'     => '02',
			'title' => __( 'Días 4 a 7', 'nuvanx-medical' ),
			'body'  => __( 'Descamación activa: la epidermis tratada se desprende y aparece piel nueva, rosada e inmadura. No arrancar escamas.', 'nuvanx-medical' ),
		),
		array(
			'n'     => '03',
			'title' => __( 'Día 7 en adelante', 'nuvanx-medical' ),
			'body'  => __( 'Recuperación visual habitual. La remodelación de colágeno continúa en silencio 4–6 semanas (y meses) mejorando firmeza y textura.', 'nuvanx-medical' ),
		),
	);
	foreach ( $phases as $phase ) {
		$html .= '<li class="nvx-co2-timeline__item">';
		$html .= '<span class="nvx-co2-timeline__n">' . esc_html( $phase['n'] ) . '</span>';
		$html .= '<h3 class="nvx-co2-timeline__title">' . esc_html( $phase['title'] ) . '</h3>';
		$html .= '<p class="nvx-endolift-body">' . esc_html( $phase['body'] ) . '</p>';
		$html .= '</li>';
	}
	$html .= '</ol></div></section>';

	// D. PVP reference (clinic tariff — facial 330 / body 450).
	$html .= '<section class="nvx-endolift-section nvx-co2-pricing" aria-labelledby="nvx-co2-price-title" id="tarifas-co2">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Tarifas públicas', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-co2-price-title" class="nvx-endolift-heading">' . esc_html__( 'PVP sesión Láser CO₂ (IVA incluido)', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-endolift-price-table-wrap">';
	$html .= '<table class="nvx-endolift-price-table">';
	$html .= '<caption class="nvx-endolift-price-table__cap">' . esc_html__( 'Tarifa clínica de referencia. Profundidad y zonas pueden modular el plan y el número de sesiones.', 'nuvanx-medical' ) . '</caption>';
	$html .= '<thead><tr><th scope="col">' . esc_html__( 'Sesión', 'nuvanx-medical' ) . '</th><th scope="col">' . esc_html__( 'PVP', 'nuvanx-medical' ) . '</th></tr></thead><tbody>';
	$html .= '<tr><th scope="row">' . esc_html__( 'Láser CO₂ facial', 'nuvanx-medical' ) . '</th><td>' . esc_html( $price_facial ) . '&nbsp;€</td></tr>';
	$html .= '<tr><th scope="row">' . esc_html__( 'Láser CO₂ corporal', 'nuvanx-medical' ) . '</th><td>' . esc_html( $price_body ) . '&nbsp;€</td></tr>';
	$html .= '</tbody></table></div>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure"><em>' . esc_html__( 'La indicación, el fototipo y el downtime esperable se confirman en valoración presencial antes de cualquier sesión.', 'nuvanx-medical' ) . '</em></p>';
	$html .= '</div></section>';

	// E. CTA.
	$html .= '<section class="nvx-endolift-action" aria-label="' . esc_attr__( 'Reservar valoración CO₂', 'nuvanx-medical' ) . '">';
	$html .= '<div class="nvx-endolift-action__inner">';
	$html .= '<div>';
	$html .= '<p class="nvx-endolift-action__kicker">' . esc_html__( 'Valoración médica', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 class="nvx-endolift-action__title">' . esc_html__( '¿El CO₂ fraccionado es el protocolo adecuado para tu piel?', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-action__text">' . esc_html__( 'Valoramos cicatrices, textura, fototipo y tolerancia al downtime. Solo entonces se define profundidad y plan de sesiones.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';
	$html .= nvx_co2_action_ctas_markup();
	$html .= '</div></section>';

	$html .= '</div>';

	return $html;
}

/**
 * Rebuild CO₂ page.
 */
function nvx_content_restructure_co2_page( string $content ): string {
	if ( ! nvx_content_is_co2_page( $content ) ) {
		return $content;
	}

	$media = '';
	if ( preg_match( '/<figure class="nvx-brand-hero__media"[\s\S]*?<\/figure>/iu', $content, $m ) ) {
		$media = $m[0];
	} elseif ( preg_match( '/<div class="nvx-brand-hero__media"[\s\S]*?<\/div>/iu', $content, $m ) ) {
		$media = $m[0];
	}

	$hero  = '<section class="nvx-brand-hero nvx-brand-hero--laser nvx-endolift-hero nvx-co2-hero" aria-labelledby="nvx-co2-h1" aria-label="' . esc_attr__( 'Láser CO₂ NUVANX', 'nuvanx-medical' ) . '">';
	$hero .= '<div class="nvx-brand-hero__inner">';
	$hero .= nvx_co2_hero_copy_markup();
	$hero .= $media;
	$hero .= '</div></section>';

	$body = nvx_co2_editorial_body_markup();

	if ( preg_match( '/(<div class="nvx-brand-page[^"]*"[^>]*>)/iu', $content, $wrap ) ) {
		return $wrap[1] . $hero . $body . '</div>';
	}

	return $hero . $body;
}
add_filter( 'the_content', 'nvx_content_restructure_co2_page', 19 );
