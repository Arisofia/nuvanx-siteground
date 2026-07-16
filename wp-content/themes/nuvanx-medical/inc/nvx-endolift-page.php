<?php
/**
 * Endolift® facial treatment page — editorial high-authority structure.
 *
 * Wire-frame: Hero → Diagnóstico SMAS → Biofísica 1470 nm → Proceso → FAQ GEO → Action banner.
 * Pattern-based (Endolift markers), not page-ID gated.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current main query is a singular page suitable for rewrite.
 */
function nvx_endolift_is_singular_context(): bool {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	// Prefer real page views; still allow content that carries structural Endolift markers
	// when queried via the main loop (avoids rewriting random posts/excerpts).
	return is_singular( 'page' ) || is_page();
}

/**
 * Detect Endolift facial treatment content before rewrite.
 * Anchors primarily on stable structural markers (aria-label / ids / brand classes).
 */
function nvx_content_is_endolift_page( string $content ): bool {
	if ( false !== strpos( $content, 'nvx-endolift-editorial' ) ) {
		return false;
	}

	if ( ! nvx_endolift_is_singular_context() ) {
		return false;
	}

	// Structural markers first (stable across copy edits).
	if ( preg_match(
		'/aria-label=["\']Endolift facial NUVANX["\']|id=["\']nvx-endolift-h1["\']|class=["\'][^"\']*nvx-endolift-hero/iu',
		$content
	) ) {
		return true;
	}

	// Fallback: known brand-page laser hero + Endolift product framing (not laser hub alone).
	return (bool) preg_match(
		'/nvx-brand-hero--laser[\s\S]{0,1200}Endolift®?[\s\S]{0,400}(papada|mand[ií]bul)/iu',
		$content
	);
}

/**
 * Linear process icons — Champagne Bronce stroke only (1.5px).
 *
 * @param string $name Icon key: assess|anesthesia|procedure|recover.
 */
function nvx_endolift_process_icon( string $name ): string {
	$icons = array(
		'assess'      => '<svg class="nvx-endolift-step__icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="22" cy="22" r="10" stroke="currentColor" stroke-width="1.5"/><path d="M30 30 40 40" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M18 22h8M22 18v8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		'anesthesia'  => '<svg class="nvx-endolift-step__icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M18 8h12v8l4 6v18H14V22l4-6V8Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M18 16h12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		'procedure'   => '<svg class="nvx-endolift-step__icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M10 34 28 8l10 6-18 26H10v-6Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M24 14l10 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		'recover'     => '<svg class="nvx-endolift-step__icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 28c4-10 8-14 12-14s8 4 12 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M16 18c3-2 5-3 8-3s5 1 8 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="24" cy="30" r="3" stroke="currentColor" stroke-width="1.5"/></svg>',
	);

	return $icons[ $name ] ?? $icons['assess'];
}

/**
 * Hero copy: authority + dual CTA (valoración + WhatsApp).
 */
function nvx_endolift_hero_copy_markup(): string {
	$colegiado = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';

	$html  = '<div class="nvx-brand-hero__copy nvx-endolift-hero-copy">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'NUVANX · Medicina estética láser', 'nuvanx-medical' ) . '</p>';
	$html .= '<h1 class="nvx-brand-hero__title" id="nvx-endolift-h1">' . esc_html__( 'Endolift® Facial de Alta Precisión en Madrid', 'nuvanx-medical' ) . '</h1>';
	$html .= '<p class="nvx-brand-hero__lead">' . esc_html__( 'Redefinición del arco mandibular y eliminación de grasa submentoniana sin incisiones ni tiempo de inactividad quirúrgica.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-brand-hero__description">' . esc_html(
		sprintf(
			/* translators: %s: medical license number */
			__( 'Bajo la supervisión del Dr. José Javier Rivera Tejeda (Nº Colegiado ICOMEM %s), empleamos tecnología láser subdérmica de última generación para retraer los tejidos laxos y devolver la definición estructural al perfil facial.', 'nuvanx-medical' ),
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
 * Action banner CTAs: valoración (primary) + sedes (secondary). No videoconsulta.
 */
function nvx_endolift_action_ctas_markup(): string {
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
 * Full editorial body after hero.
 */
function nvx_endolift_editorial_body_markup(): string {
	$html  = '<div class="nvx-endolift-editorial">';

	// B. Diagnóstico — 60/40.
	$html .= '<section class="nvx-endolift-section nvx-endolift-diagnosis" aria-labelledby="nvx-endolift-diagnosis-title">';
	$html .= '<div class="nvx-endolift-section__inner nvx-endolift-diagnosis__grid">';
	$html .= '<div class="nvx-endolift-diagnosis__copy">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'El diagnóstico', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-endolift-diagnosis-title" class="nvx-endolift-heading">' . esc_html__( 'La anatomía del perfil: por qué falla la mandíbula', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body">' . esc_html__( 'La pérdida de definición en el óvalo facial y la aparición de la papada no responden a un único factor biológico. El envejecimiento cutáneo y la gravedad inducen una distensión del Sistema Aponeurótico Muscular Superficial (SMAS), combinada con la redistribución del tejido adiposo submentoniano.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-endolift-body">' . esc_html__( 'Para garantizar el éxito del tratamiento, nuestro equipo médico ejecuta un diagnóstico diferencial obligatorio antes de programar la sesión. Determinamos si el origen de la imperfección es una laxitud cutánea pura (pérdida de colágeno tipo I) o una adiposidad localizada resistente. Esta distinción clínica define la calibración exacta de la energía del láser y la dirección de las microfibras ópticas.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';
	$html .= '<aside class="nvx-endolift-diagnosis__panel" aria-label="' . esc_attr__( 'Criterio de diagnóstico', 'nuvanx-medical' ) . '">';
	$html .= '<p class="nvx-endolift-panel-label">' . esc_html__( 'Diagnóstico diferencial', 'nuvanx-medical' ) . '</p>';
	$html .= '<ul class="nvx-endolift-panel-list">';
	$html .= '<li><strong>' . esc_html__( 'Laxitud / SMAS', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Retracción del tejido conectivo y tensado del contorno mandibular.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Adiposidad submentoniana', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Laserlipólisis selectiva de grasa localizada en la papada.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Combinación', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Protocolo mixto con vectores y energía calibrados en consulta.', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul></aside></div></section>';

	// C. Biofísica.
	$html .= '<section class="nvx-endolift-section nvx-endolift-biophysics" aria-labelledby="nvx-endolift-bio-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'La biofísica', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-endolift-bio-title" class="nvx-endolift-heading">' . esc_html__( 'Retracción térmica y lipólisis simultánea', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'El sistema Endolift® opera mediante la inserción subdérmica de microfibras de silicio ultrafinas (de entre 200 y 300 micras de diámetro), estériles y de un solo uso por paciente, garantizando la máxima bioseguridad del procedimiento.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'El equipo emite energía láser a una longitud de onda de 1470 nm, un rango espectral con un coeficiente de absorción óptimo tanto para el agua intracelular como para los lípidos. La tasa de deposición de energía térmica por unidad de volumen dentro del tejido subdérmico puede modelarse mediante la siguiente relación biofísica:', 'nuvanx-medical' ) . '</p>';

	$html .= '<figure class="nvx-endolift-formula" aria-label="' . esc_attr__( 'Modelo de deposición térmica', 'nuvanx-medical' ) . '">';
	$html .= '<p class="nvx-endolift-formula__eq" role="math"><span class="nvx-endolift-formula__q">Q</span> = <span class="nvx-endolift-formula__mu">μ<sub>a</sub></span> · <span class="nvx-endolift-formula__phi">Φ</span></p>';
	$html .= '<figcaption class="nvx-endolift-formula__cap">' . esc_html__( 'Donde Q representa la generación de calor local, μₐ es el coeficiente de absorción específico del tejido a 1470 nm, y Φ es la fluencia o densidad de flujo del láser transmitida por la microfibra.', 'nuvanx-medical' ) . '</figcaption>';
	$html .= '</figure>';

	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'Al desplazar la microfibra con un vector retrógrado, elevamos de forma controlada la temperatura de la dermis reticular y de los septos fibrosos hipodérmicos en un rango preciso de 60 °C a 80 °C. Este gradiente térmico induce dos efectos biológicos inmediatos y sinérgicos:', 'nuvanx-medical' ) . '</p>';
	$html .= '<div class="nvx-endolift-effects">';
	$html .= '<article class="nvx-endolift-effect"><h3 class="nvx-endolift-effect__title">' . esc_html__( 'Desnaturalización térmica estructural', 'nuvanx-medical' ) . '</h3>';
	$html .= '<p class="nvx-endolift-body">' . esc_html__( 'Rompe los puentes de hidrógeno de las fibras de colágeno viejo, forzando una contracción elástica tridimensional del tejido conectivo (SMAS) que eleva la piel de forma inmediata.', 'nuvanx-medical' ) . '</p></article>';
	$html .= '<article class="nvx-endolift-effect"><h3 class="nvx-endolift-effect__title">' . esc_html__( 'Laserlipólisis selectiva', 'nuvanx-medical' ) . '</h3>';
	$html .= '<p class="nvx-endolift-body">' . esc_html__( 'Destruye la membrana del adipocito en la región de la papada, liberando los ácidos grasos para su posterior eliminación natural por el sistema linfático, sin dañar los vasos sanguíneos periféricos ni la epidermis superficial.', 'nuvanx-medical' ) . '</p></article>';
	$html .= '</div></div></section>';

	// D. Proceso clínico.
	$html .= '<section class="nvx-endolift-section nvx-endolift-process" aria-labelledby="nvx-endolift-process-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'El proceso clínico', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-endolift-process-title" class="nvx-endolift-heading">' . esc_html__( 'Tiempos, anestesia y recuperación', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-endolift-process-grid">';

	$steps = array(
		array(
			'icon'  => 'assess',
			'n'     => '01',
			'title' => __( 'Valoración', 'nuvanx-medical' ),
			'body'  => __( 'Diagnóstico diferencial SMAS/adiposidad, plan de vectores y parámetros de energía del láser.', 'nuvanx-medical' ),
		),
		array(
			'icon'  => 'anesthesia',
			'n'     => '02',
			'title' => __( 'Anestesia local', 'nuvanx-medical' ),
			'body'  => __( 'Infiltración tumescente localizada en puntos de entrada. Calor difuso y leve presión; no dolor agudo.', 'nuvanx-medical' ),
		),
		array(
			'icon'  => 'procedure',
			'n'     => '03',
			'title' => __( 'Procedimiento', 'nuvanx-medical' ),
			'body'  => __( 'Canalización subdérmica con microfibras monouso y emisión a 1470 nm según el mapa clínico.', 'nuvanx-medical' ),
		),
		array(
			'icon'  => 'recover',
			'n'     => '04',
			'title' => __( 'Recuperación', 'nuvanx-medical' ),
			'body'  => __( 'Ambulatorio. Edema y eritema leves habituales 3–7 días; reincorporación habitual en menos de 24 h.', 'nuvanx-medical' ),
		),
	);

	foreach ( $steps as $step ) {
		$html .= '<article class="nvx-endolift-step">';
		$html .= nvx_endolift_process_icon( $step['icon'] );
		$html .= '<span class="nvx-endolift-step__n">' . esc_html( $step['n'] ) . '</span>';
		$html .= '<h3 class="nvx-endolift-step__title">' . esc_html( $step['title'] ) . '</h3>';
		$html .= '<p class="nvx-endolift-body">' . esc_html( $step['body'] ) . '</p>';
		$html .= '</article>';
	}

	$html .= '</div></div></section>';

	// E. FAQ GEO — alta densidad, solo Endolift (sin EXION).
	$html .= '<section class="nvx-endolift-section nvx-endolift-faq" aria-labelledby="nvx-endolift-faq-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Preguntas frecuentes', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-endolift-faq-title" class="nvx-endolift-heading">' . esc_html__( 'Rigor clínico sobre Endolift® Facial', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-faq nvx-endolift-faq-list">';

	$faqs = array(
		array(
			'q' => __( '¿Es doloroso el tratamiento de Endolift® Facial y qué tipo de anestesia se requiere?', 'nuvanx-medical' ),
			'a' => __( 'El procedimiento de Endolift® Facial se cataloga como mínimamente invasivo y es altamente tolerable para la mayoría de los pacientes. Para garantizar el máximo confort clínico durante la canalización de la microfibra óptica subdérmica, nuestro equipo médico realiza una infiltración de anestesia local tumescente localizada en los puntos de entrada. El paciente percibe una sensación de calor difuso y una leve presión en la zona tratada, pero no dolor agudo. Este abordaje ambulatorio evita los riesgos y el postoperatorio complejo de una anestesia general o sedación profunda.', 'nuvanx-medical' ),
		),
		array(
			'q' => __( '¿Cuándo se consolidan los resultados del tensado láser y cuánto dura su efecto en el rostro?', 'nuvanx-medical' ),
			'a' => __( 'El proceso de reestructuración tisular es bifásico. Se observa un efecto tensor mecánico inmediato debido a la contracción de las fibras de colágeno preexistentes durante la sesión. No obstante, el resultado definitivo y de mayor impacto clínico se consolida progresivamente entre el segundo y cuarto mes posterior al tratamiento. Durante este periodo, la cascada inflamatoria controlada activa a los fibroblastos para sintetizar colágeno de tipo I. Los resultados de definición mandibular y reducción de la papada se mantienen estables por un periodo de 18 meses a 3 años, dependiendo de la tasa de envejecimiento biológico del paciente y del mantenimiento dermocosmético indicado.', 'nuvanx-medical' ),
		),
		array(
			'q' => __( '¿Qué cuidados postoperatorios inmediatos exige el Endolift® y qué complicaciones menores pueden aparecer?', 'nuvanx-medical' ),
			'a' => __( 'Al no existir incisiones quirúrgicas ni suturas, no se requiere un periodo de baja laboral o inactividad social, permitiendo al paciente reincorporarse a sus actividades cotidianas en menos de 24 horas. Es fisiológicamente normal experimentar un edema (inflamación) leve o moderado, eritema (enrojecimiento) y una sensación de hipersensibilidad al tacto en la región submentoniana durante los primeros 3 a 7 días. Recomendamos la aplicación de frío local controlado durante las primeras 48 horas, mantener una higiene suave de la piel tratada y evitar el ejercicio físico extenuante o la exposición a fuentes de calor intenso (como saunas o radiación solar directa) durante las dos semanas posteriores para asegurar una evolución óptima del tejido.', 'nuvanx-medical' ),
		),
	);

	foreach ( $faqs as $faq ) {
		$html .= '<details class="nvx-brand-faq-item">';
		$html .= '<summary><span>' . esc_html( $faq['q'] ) . '</span></summary>';
		$html .= '<div class="nvx-brand-faq-content"><p>' . esc_html( $faq['a'] ) . '</p></div>';
		$html .= '</details>';
	}

	$html .= '</div></div></section>';

	// F. Action banner — valoración + sedes (no videoconsulta).
	$html .= '<section class="nvx-endolift-action" aria-label="' . esc_attr__( 'Reservar valoración Endolift', 'nuvanx-medical' ) . '">';
	$html .= '<div class="nvx-endolift-action__inner">';
	$html .= '<div>';
	$html .= '<p class="nvx-endolift-action__kicker">' . esc_html__( 'Valoración médica', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 class="nvx-endolift-action__title">' . esc_html__( '¿Es Endolift® el protocolo adecuado para tu mandíbula y papada?', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-action__text">' . esc_html__( 'Reserva una valoración médica presencial. Confirmamos indicación, expectativas y plan de tratamiento antes de cualquier procedimiento.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';
	$html .= nvx_endolift_action_ctas_markup();
	$html .= '</div></section>';

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

	$hero  = '<section class="nvx-brand-hero nvx-brand-hero--laser nvx-endolift-hero" aria-labelledby="nvx-endolift-h1" aria-label="' . esc_attr__( 'Endolift facial NUVANX', 'nuvanx-medical' ) . '">';
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
