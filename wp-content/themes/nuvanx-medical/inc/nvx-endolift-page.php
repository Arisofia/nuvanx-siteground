<?php
/**
 * Endolift® facial treatment page — editorial high-authority structure.
 *
 * Pattern-based (Endolift hero / body markers), not page-ID gated.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detect Endolift facial treatment index/detail content.
 */
function nvx_content_is_endolift_page( string $content ): bool {
	if ( false !== strpos( $content, 'nvx-endolift-editorial' ) ) {
		return false;
	}

	return (bool) preg_match(
		'/Endolift facial NUVANX|nvx-brand-hero--laser[\s\S]{0,800}Endolift|Endolift® facial para papada/iu',
		$content
	);
}

/**
 * Hero copy block (authority + dual CTA).
 */
function nvx_endolift_hero_copy_markup(): string {
	$colegiado = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';

	$html  = '<div class="nvx-brand-hero__copy nvx-endolift-hero-copy">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'NUVANX · Medicina estética láser', 'nuvanx-medical' ) . '</p>';
	$html .= '<h1 class="nvx-brand-hero__title" id="nvx-endolift-h1">' . esc_html__( 'Endolift® Facial de alta precisión en Madrid', 'nuvanx-medical' ) . '</h1>';
	$html .= '<p class="nvx-brand-hero__lead">' . esc_html__( 'Redefinición del arco mandibular y reducción de grasa submentoniana sin incisiones ni tiempo de inactividad quirúrgica.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-brand-hero__description">' . esc_html(
		sprintf(
			/* translators: %s: medical license number */
			__( 'Bajo la supervisión del Dr. José Javier Rivera Tejeda (Colegiado ICOMEM Nº %s), empleamos tecnología láser subdérmica de última generación para retraer tejidos laxos y devolver definición estructural al perfil facial.', 'nuvanx-medical' ),
			$colegiado
		)
	) . '</p>';

	if ( function_exists( 'nvx_cta_pair_markup' ) ) {
		$html .= nvx_cta_pair_markup( 'nvx-endolift-hero-ctas nvx-home-hero-ctas' );
	}

	$html .= '<p class="nvx-brand-meta">' . esc_html__( 'Chamberí · Salamanca–Goya · Indicación médica personalizada', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';

	return $html;
}

/**
 * Full editorial body after hero.
 */
function nvx_endolift_editorial_body_markup(): string {
	$html  = '<div class="nvx-endolift-editorial">';

	// B. Diagnóstico.
	$html .= '<section class="nvx-endolift-section nvx-endolift-diagnosis" aria-labelledby="nvx-endolift-diagnosis-title">';
	$html .= '<div class="nvx-endolift-section__inner nvx-endolift-diagnosis__grid">';
	$html .= '<div class="nvx-endolift-diagnosis__copy">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'El diagnóstico', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-endolift-diagnosis-title" class="nvx-endolift-heading">' . esc_html__( 'La anatomía del perfil: grasa frente a laxitud', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body">' . esc_html__( 'La pérdida de definición en el óvalo facial y la aparición de la papada no responden a un único factor biológico. El envejecimiento cutáneo y la gravedad inducen una distensión del Sistema Aponeurótico Muscular Superficial (SMAS), combinada con la redistribución del tejido adiposo submentoniano.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-endolift-body">' . esc_html__( 'Para garantizar el éxito del tratamiento, el equipo médico ejecuta un diagnóstico diferencial obligatorio antes de programar la sesión. Determinamos si el origen es laxitud cutánea (pérdida de colágeno tipo I) o adiposidad localizada resistente. Esa distinción define la calibración de la energía láser y la dirección de las microfibras ópticas.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';
	$html .= '<aside class="nvx-endolift-diagnosis__panel" aria-label="' . esc_attr__( 'Criterio de diagnóstico', 'nuvanx-medical' ) . '">';
	$html .= '<p class="nvx-endolift-panel-label">' . esc_html__( 'Diagnóstico diferencial', 'nuvanx-medical' ) . '</p>';
	$html .= '<ul class="nvx-endolift-panel-list">';
	$html .= '<li><strong>' . esc_html__( 'Laxitud / SMAS', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Retracción del tejido conectivo y tensado del contorno.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Adiposidad submentoniana', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Laserlipólisis selectiva de grasa localizada.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Combinación', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Protocolo mixto calibrado en consulta.', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul></aside></div></section>';

	// C. Biofísica.
	$html .= '<section class="nvx-endolift-section nvx-endolift-biophysics" aria-labelledby="nvx-endolift-bio-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'La biofísica', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-endolift-bio-title" class="nvx-endolift-heading">' . esc_html__( 'Retracción térmica y lipólisis a 1470 nm', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'Endolift® inserta microfibras de silicio ultrafinas (200–300 micras), estériles y de un solo uso por paciente. El equipo emite láser a 1470 nm, con alto coeficiente de absorción en agua intracelular y lípidos.', 'nuvanx-medical' ) . '</p>';

	$html .= '<figure class="nvx-endolift-formula" aria-label="' . esc_attr__( 'Modelo de deposición térmica', 'nuvanx-medical' ) . '">';
	$html .= '<p class="nvx-endolift-formula__eq"><span class="nvx-endolift-formula__q">Q</span> = <span class="nvx-endolift-formula__mu">μ<sub>a</sub></span> · <span class="nvx-endolift-formula__phi">Φ</span></p>';
	$html .= '<figcaption class="nvx-endolift-formula__cap">' . esc_html__( 'Q: generación de calor local · μₐ: coeficiente de absorción a 1470 nm · Φ: fluencia transmitida por la microfibra.', 'nuvanx-medical' ) . '</figcaption>';
	$html .= '</figure>';

	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'Al desplazar la microfibra en vector retrógrado, se eleva de forma controlada la temperatura de la dermis reticular y de los septos fibrosos hipodérmicos (aprox. 60–80 °C). Ese gradiente induce dos efectos sinérgicos:', 'nuvanx-medical' ) . '</p>';
	$html .= '<div class="nvx-endolift-effects">';
	$html .= '<article class="nvx-endolift-effect"><h3 class="nvx-endolift-effect__title">' . esc_html__( 'Desnaturalización térmica estructural', 'nuvanx-medical' ) . '</h3>';
	$html .= '<p class="nvx-endolift-body">' . esc_html__( 'Contracción elástica tridimensional del tejido conectivo (SMAS) que eleva y redefine el contorno de forma inmediata.', 'nuvanx-medical' ) . '</p></article>';
	$html .= '<article class="nvx-endolift-effect"><h3 class="nvx-endolift-effect__title">' . esc_html__( 'Laserlipólisis selectiva', 'nuvanx-medical' ) . '</h3>';
	$html .= '<p class="nvx-endolift-body">' . esc_html__( 'Destrucción de la membrana del adipocito en la papada, con eliminación natural de ácidos grasos por vía linfática, preservando epidermis y vasos periféricos.', 'nuvanx-medical' ) . '</p></article>';
	$html .= '</div></div></section>';

	// D. Proceso clínico.
	$html .= '<section class="nvx-endolift-section nvx-endolift-process" aria-labelledby="nvx-endolift-process-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'El proceso clínico', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-endolift-process-title" class="nvx-endolift-heading">' . esc_html__( 'Tiempos, anestesia y recuperación', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-endolift-process-grid">';
	$html .= '<article class="nvx-endolift-step"><span class="nvx-endolift-step__n">01</span><h3 class="nvx-endolift-step__title">' . esc_html__( 'Valoración', 'nuvanx-medical' ) . '</h3><p class="nvx-endolift-body">' . esc_html__( 'Diagnóstico diferencial SMAS/adiposidad, plan de vectores y parámetros de energía.', 'nuvanx-medical' ) . '</p></article>';
	$html .= '<article class="nvx-endolift-step"><span class="nvx-endolift-step__n">02</span><h3 class="nvx-endolift-step__title">' . esc_html__( 'Anestesia local', 'nuvanx-medical' ) . '</h3><p class="nvx-endolift-body">' . esc_html__( 'Infiltración tumescente localizada en puntos de entrada. Sensación de calor y presión, sin dolor agudo.', 'nuvanx-medical' ) . '</p></article>';
	$html .= '<article class="nvx-endolift-step"><span class="nvx-endolift-step__n">03</span><h3 class="nvx-endolift-step__title">' . esc_html__( 'Procedimiento', 'nuvanx-medical' ) . '</h3><p class="nvx-endolift-body">' . esc_html__( 'Canalización subdérmica con microfibras monouso y emisión a 1470 nm según el mapa clínico.', 'nuvanx-medical' ) . '</p></article>';
	$html .= '<article class="nvx-endolift-step"><span class="nvx-endolift-step__n">04</span><h3 class="nvx-endolift-step__title">' . esc_html__( 'Recuperación', 'nuvanx-medical' ) . '</h3><p class="nvx-endolift-body">' . esc_html__( 'Ambulatorio. Edema y eritema leves habituales 3–7 días; reincorporación habitual en menos de 24 h.', 'nuvanx-medical' ) . '</p></article>';
	$html .= '</div></div></section>';

	// E. FAQ GEO.
	$html .= '<section class="nvx-endolift-section nvx-endolift-faq" aria-labelledby="nvx-endolift-faq-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Preguntas frecuentes', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-endolift-faq-title" class="nvx-endolift-heading">' . esc_html__( 'Rigor clínico sobre Endolift® Facial', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-faq nvx-endolift-faq-list">';

	$faqs = array(
		array(
			'q' => __( '¿Es doloroso el tratamiento de Endolift® Facial y qué tipo de anestesia se requiere?', 'nuvanx-medical' ),
			'a' => __( 'El procedimiento es mínimamente invasivo y altamente tolerable. Se realiza infiltración de anestesia local tumescente en los puntos de entrada. El paciente percibe calor difuso y leve presión, no dolor agudo. Este abordaje ambulatorio evita los riesgos de la anestesia general o la sedación profunda.', 'nuvanx-medical' ),
		),
		array(
			'q' => __( '¿Cuándo se consolidan los resultados del tensado láser y cuánto dura su efecto?', 'nuvanx-medical' ),
			'a' => __( 'Hay un efecto tensor mecánico inmediato por contracción del colágeno. El resultado de mayor impacto se consolida entre el segundo y el cuarto mes, cuando los fibroblastos sintetizan colágeno tipo I. La definición mandibular y la reducción de papada suelen mantenerse entre 18 meses y 3 años, según del envejecimiento biológico y del mantenimiento indicado.', 'nuvanx-medical' ),
		),
		array(
			'q' => __( '¿Qué cuidados postoperatorios exige el Endolift® y qué molestias menores pueden aparecer?', 'nuvanx-medical' ),
			'a' => __( 'No hay incisiones ni suturas; la reincorporación a la vida cotidiana suele ser en menos de 24 horas. Es normal un edema leve-moderado, eritema e hipersensibilidad 3–7 días. Se recomienda frío local controlado 48 h, higiene suave, y evitar ejercicio intenso, saunas y sol directo durante dos semanas.', 'nuvanx-medical' ),
		),
	);

	foreach ( $faqs as $faq ) {
		$html .= '<details class="nvx-brand-faq-item">';
		$html .= '<summary><span>' . esc_html( $faq['q'] ) . '</span></summary>';
		$html .= '<div class="nvx-brand-faq-content"><p>' . esc_html( $faq['a'] ) . '</p></div>';
		$html .= '</details>';
	}

	$html .= '</div></div></section>';

	// F. Action banner.
	if ( function_exists( 'nvx_cta_pair_markup' ) ) {
		$html .= '<section class="nvx-endolift-action" aria-label="' . esc_attr__( 'Reservar valoración Endolift', 'nuvanx-medical' ) . '">';
		$html .= '<div class="nvx-endolift-action__inner">';
		$html .= '<div>';
		$html .= '<p class="nvx-endolift-action__kicker">' . esc_html__( 'Valoración médica', 'nuvanx-medical' ) . '</p>';
		$html .= '<h2 class="nvx-endolift-action__title">' . esc_html__( '¿Es Endolift® el protocolo adecuado para tu mandíbula y papada?', 'nuvanx-medical' ) . '</h2>';
		$html .= '<p class="nvx-endolift-action__text">' . esc_html__( 'Reserva una valoración médica gratuita (presencial o por videoconsulta). Confirmamos indicación, expectativas y plan de tratamiento antes de cualquier procedimiento.', 'nuvanx-medical' ) . '</p>';
		$html .= '</div>';
		$html .= nvx_cta_pair_markup( 'nvx-endolift-action__ctas' );
		$html .= '</div></section>';
	}

	$html .= '</div>';

	return $html;
}

/**
 * Rebuild Endolift page: authority hero + diagnosis + biophysics + process + FAQ + CTA.
 */
function nvx_content_restructure_endolift_page( string $content ): string {
	if ( ! nvx_content_is_endolift_page( $content ) ) {
		return $content;
	}

	$media = '';
	if ( preg_match( '/<figure class="nvx-brand-hero__media"[\s\S]*?<\/figure>/iu', $content, $m ) ) {
		$media = $m[0];
	} elseif ( preg_match( '/<div class="nvx-brand-hero__media"[\s\S]*?<\/div>/iu', $content, $m ) ) {
		$media = $m[0];
	}

	$hero  = '<section class="nvx-brand-hero nvx-brand-hero--laser nvx-endolift-hero" aria-labelledby="nvx-endolift-h1" aria-label="Endolift facial NUVANX">';
	$hero .= '<div class="nvx-brand-hero__inner">';
	$hero .= nvx_endolift_hero_copy_markup();
	$hero .= $media;
	$hero .= '</div></section>';

	$body = nvx_endolift_editorial_body_markup();

	if ( preg_match( '/(<div class="nvx-brand-page[^"]*"[^>]*>)/iu', $content, $wrap ) ) {
		return $wrap[1] . $hero . $body . '</div>';
	}

	return $hero . $body;
}
add_filter( 'the_content', 'nvx_content_restructure_endolift_page', 19 );
