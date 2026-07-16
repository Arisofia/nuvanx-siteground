<?php
/**
 * Endoláser corporal page — laserlipólisis + retracción cutánea.
 *
 * Wire-frame: Hero → Mecanismo dual → Zonas → Exclusión → Planificación → CTA.
 * Does not repeat Endolift facial encyclopedia (formula 1470 / papada focus).
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singular context for Endoláser rewrite.
 */
function nvx_endolaser_is_singular_context(): bool {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	return is_singular( 'page' ) || is_page();
}

/**
 * Detect Endoláser corporal page content.
 */
function nvx_content_is_endolaser_page( string $content ): bool {
	if ( false !== strpos( $content, 'nvx-endolaser-editorial' ) ) {
		return false;
	}

	if ( ! nvx_endolaser_is_singular_context() ) {
		return false;
	}

	if ( preg_match(
		'/aria-label=["\']Endoláser corporal NUVANX["\']|id=["\']nvx-endolaser-h1["\']|class=["\'][^"\']*nvx-endolaser-hero/iu',
		$content
	) ) {
		return true;
	}

	// Path / markers: endoláser corporal + grasa (not facial Endolift alone).
	$path = function_exists( 'nvx_schema_current_path' )
		? nvx_schema_current_path( (int) get_queried_object_id() )
		: '';

	if ( is_string( $path ) && false !== strpos( $path, 'endolaser-corporal' ) ) {
		return true;
	}

	return (bool) preg_match(
		'/Endol[aá]ser\s+corporal|laserlip[oó]lisis|grasa\s+localizada[\s\S]{0,200}(abdomen|flancos|retracci)/iu',
		$content
	);
}

/**
 * Hero copy.
 */
function nvx_endolaser_hero_copy_markup(): string {
	$html  = '<div class="nvx-brand-hero__copy nvx-endolaser-hero-copy">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'NUVANX · Medicina estética láser', 'nuvanx-medical' ) . '</p>';
	$html .= '<h1 class="nvx-brand-hero__title" id="nvx-endolaser-h1">' . esc_html__( 'Endoláser Corporal en Madrid: Destrucción de Grasa Localizada y Retracción Cutánea Simultánea', 'nuvanx-medical' ) . '</h1>';
	$html .= '<p class="nvx-brand-hero__lead">' . esc_html__( 'Laserlipólisis médica intervencionista para contorno corporal: grasa y flacidez en un mismo acto, sin el downtime de una liposucción tradicional.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-brand-hero__description">' . esc_html__( 'Indicación por zonas tras valoración. No es un tratamiento de obesidad ni de pérdida masiva de peso.', 'nuvanx-medical' ) . '</p>';

	if ( function_exists( 'nvx_cta_pair_markup' ) ) {
		$html .= nvx_cta_pair_markup( 'nvx-endolaser-hero-ctas nvx-home-hero-ctas' );
	}

	$html .= '<p class="nvx-brand-meta">' . esc_html__( 'Chamberí · Salamanca–Goya · Plan por zonas · Valoración presencial', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';

	return $html;
}

/**
 * CTAs — valoración + sedes.
 */
function nvx_endolaser_action_ctas_markup(): string {
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
 * Editorial body (no facial Endolift encyclopedia, no fixed € inventado).
 */
function nvx_endolaser_editorial_body_markup(): string {
	$html  = '<div class="nvx-endolaser-editorial nvx-endolift-editorial">';

	// A. Intro + dual mechanism.
	$html .= '<section class="nvx-endolift-section nvx-endolaser-mechanism" aria-labelledby="nvx-endolaser-mech-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Laserlipólisis corporal', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-endolaser-mech-title" class="nvx-endolift-heading">' . esc_html__( 'Mecanismo de acción dual: licuefacción y neocolagénesis', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'A diferencia de terapias térmicas de superficie o sistemas de frío que no abordan la flacidez, el Endoláser trabaja grasa y calidad de piel en un único acto médico ambulatorio.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'Bajo anestesia local se introduce una fibra láser en el tejido subcutáneo. La energía destruye de forma selectiva las paredes de los adipocitos (lipólisis), facilitando su eliminación natural. En paralelo, el estímulo térmico en dermis profunda favorece la contracción de fibras y un andamiaje de soporte que ayuda a limitar el efecto de “piel vacía” tras reducir volumen.', 'nuvanx-medical' ) . '</p>';
	$html .= '<div class="nvx-endolift-effects">';
	$html .= '<article class="nvx-endolift-effect"><h3 class="nvx-endolift-effect__title">' . esc_html__( 'Lipólisis láser', 'nuvanx-medical' ) . '</h3>';
	$html .= '<p class="nvx-endolift-body">' . esc_html__( 'Destrucción irreversible de membranas de adipocitos en focos localizados planificados por el médico.', 'nuvanx-medical' ) . '</p></article>';
	$html .= '<article class="nvx-endolift-effect"><h3 class="nvx-endolift-effect__title">' . esc_html__( 'Retracción y soporte dérmico', 'nuvanx-medical' ) . '</h3>';
	$html .= '<p class="nvx-endolift-body">' . esc_html__( 'Contracción térmica de fibras y estímulo de remodelación para acompañar la pérdida de volumen con mayor firmeza.', 'nuvanx-medical' ) . '</p></article>';
	$html .= '</div></div></section>';

	// B. Zonas.
	$html .= '<section class="nvx-endolift-section nvx-endolaser-zones" aria-labelledby="nvx-endolaser-zones-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Mapa clínico', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-endolaser-zones-title" class="nvx-endolift-heading">' . esc_html__( 'Zonas anatómicas de alta respuesta', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'Se valora idoneidad en grasa resistente a dieta y ejercicio, con flacidez leve–moderada asociada. La indicación es por focos, no por “adelgazamiento general”.', 'nuvanx-medical' ) . '</p>';
	$html .= '<ul class="nvx-endolaser-zone-list">';
	$zones = array(
		array(
			'title' => __( 'Abdomen y flancos', 'nuvanx-medical' ),
			'body'  => __( 'Cinturón adiposo periumbilical y redefinición de contorno de cintura cuando hay grasa localizada con buena candidatura cutánea.', 'nuvanx-medical' ),
		),
		array(
			'title' => __( 'Cara interna de muslos y rodillas', 'nuvanx-medical' ),
			'body'  => __( 'Volumen local que genera fricción y descolgamiento medial leve–moderado, tras excluir exceso cutáneo severo.', 'nuvanx-medical' ),
		),
		array(
			'title' => __( 'Brazos (cara posterior)', 'nuvanx-medical' ),
			'body'  => __( 'Grasa y flacidez pendular en la cara posterior del brazo (“brazos de murciélago”) en casos seleccionados.', 'nuvanx-medical' ),
		),
		array(
			'title' => __( 'Región submandibular', 'nuvanx-medical' ),
			'body'  => __( 'Papada fibrosa o grasa submentoniana corporal-facial de contorno; si el objetivo es solo facial, puede valorarse Endolift® facial en su página dedicada.', 'nuvanx-medical' ),
		),
	);
	foreach ( $zones as $zone ) {
		$html .= '<li class="nvx-endolaser-zone">';
		$html .= '<h3 class="nvx-endolaser-zone__title">' . esc_html( $zone['title'] ) . '</h3>';
		$html .= '<p class="nvx-endolift-body">' . esc_html( $zone['body'] ) . '</p>';
		$html .= '</li>';
	}
	$html .= '</ul></div></section>';

	// C. Exclusión.
	$html .= '<section class="nvx-endolift-section nvx-endolaser-exclusion" aria-labelledby="nvx-endolaser-excl-title">';
	$html .= '<div class="nvx-endolift-section__inner nvx-endolift-diagnosis__grid">';
	$html .= '<div class="nvx-endolift-diagnosis__copy">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Criterio médico', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-endolaser-excl-title" class="nvx-endolift-heading">' . esc_html__( 'Criterios de exclusión y alternativas', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body">' . esc_html__( 'No está indicado para pérdida masiva de peso ni para tratar obesidad. El paciente óptimo mantiene peso estable y presenta acumulaciones grasas muy localizadas con flacidez leve a moderada.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-endolift-body">' . esc_html__( 'Exceso cutáneo drástico (faldones tras grandes pérdidas de peso) se deriva a evaluación de procedimientos excisionales (p. ej. abdominoplastia). El láser no sustituye a la resección quirúrgica de piel.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';
	$html .= '<aside class="nvx-endolift-diagnosis__panel" aria-label="' . esc_attr__( 'Resumen de candidatura', 'nuvanx-medical' ) . '">';
	$html .= '<p class="nvx-endolift-panel-label">' . esc_html__( 'Candidatura', 'nuvanx-medical' ) . '</p>';
	$html .= '<ul class="nvx-endolift-panel-list">';
	$html .= '<li><strong>' . esc_html__( 'Sí', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Peso estable + grasa focal + flacidez leve–moderada.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'No', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Obesidad / pérdida de peso sistémica como objetivo.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Derivar', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Exceso cutáneo severo → cirugía excisional.', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul></aside></div></section>';

	// D. Planificación / inversión (no precio fijo inventado).
	$html .= '<section class="nvx-endolift-section nvx-endolaser-planning" aria-labelledby="nvx-endolaser-plan-title" id="planificacion-endolaser">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Planificación', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-endolaser-plan-title" class="nvx-endolift-heading">' . esc_html__( 'Inversión y planificación por zonas', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'La superficie anatómica, el número de zonas, el tiempo de procedimiento ambulatorio y el material (incl. anestesia) varían caso a caso. Por eso el presupuesto se personaliza en consulta: no publicamos un “precio único corporal” que no reflejaría el mapa real.', 'nuvanx-medical' ) . '</p>';
	$html .= '<ul class="nvx-endolift-price-includes">';
	$html .= '<li>' . esc_html__( 'Valoración de zonas, flacidez y estabilidad de peso', 'nuvanx-medical' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Estimación de extensión de la cuadrícula láser y duración ambulatoria', 'nuvanx-medical' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Presupuesto documentado antes del procedimiento', 'nuvanx-medical' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Seguimiento clínico según protocolo de la zona tratada', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure"><em>' . esc_html__( 'Para papada y óvalo facial con protocolo facial dedicado, consulta la página de Endolift® facial (tarifas PVP faciales publicadas allí).', 'nuvanx-medical' ) . '</em></p>';
	$html .= '</div></section>';

	// E. CTA.
	$html .= '<section class="nvx-endolift-action" aria-label="' . esc_attr__( 'Reservar valoración Endoláser', 'nuvanx-medical' ) . '">';
	$html .= '<div class="nvx-endolift-action__inner">';
	$html .= '<div>';
	$html .= '<p class="nvx-endolift-action__kicker">' . esc_html__( 'Valoración médica', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 class="nvx-endolift-action__title">' . esc_html__( '¿Hay indicación de Endoláser en tus zonas de grasa localizada?', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-action__text">' . esc_html__( 'Reserva una valoración presencial. Confirmamos candidatura, exclusiones y plan por zonas antes de cualquier procedimiento.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';
	$html .= nvx_endolaser_action_ctas_markup();
	$html .= '</div></section>';

	$html .= '</div>';

	return $html;
}

/**
 * Rebuild Endoláser page content.
 */
function nvx_content_restructure_endolaser_page( string $content ): string {
	if ( ! nvx_content_is_endolaser_page( $content ) ) {
		return $content;
	}

	$media = '';
	if ( preg_match( '/<figure class="nvx-brand-hero__media"[\s\S]*?<\/figure>/iu', $content, $m ) ) {
		$media = $m[0];
	} elseif ( preg_match( '/<div class="nvx-brand-hero__media"[\s\S]*?<\/div>/iu', $content, $m ) ) {
		$media = $m[0];
	}

	$hero  = '<section class="nvx-brand-hero nvx-brand-hero--laser nvx-endolift-hero nvx-endolaser-hero" aria-labelledby="nvx-endolaser-h1" aria-label="' . esc_attr__( 'Endoláser corporal NUVANX', 'nuvanx-medical' ) . '">';
	$hero .= '<div class="nvx-brand-hero__inner">';
	$hero .= nvx_endolaser_hero_copy_markup();
	$hero .= $media;
	$hero .= '</div></section>';

	$body = nvx_endolaser_editorial_body_markup();

	if ( preg_match( '/(<div class="nvx-brand-page[^"]*"[^>]*>)/iu', $content, $wrap ) ) {
		return $wrap[1] . $hero . $body . '</div>';
	}

	return $hero . $body;
}
add_filter( 'the_content', 'nvx_content_restructure_endolaser_page', 19 );
