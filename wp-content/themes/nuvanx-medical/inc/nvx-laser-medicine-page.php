<?php
/**
 * Medicina Estética Láser hub — high-authority editorial rebuild.
 *
 * Wire-frame: Hero → Enfoque 3 columnas → Catálogo plataformas → FAQ AEO → Action banner.
 * Pattern-based (laser hub markers), not page-ID gated. Does not match Endolift detail pages.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singular page context for laser hub rewrite (avoids excerpts / archives).
 */
function nvx_laser_is_singular_context(): bool {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	return is_singular( 'page' ) || is_page();
}

/**
 * Resolve a public page URL by path, with home_url fallback.
 *
 * @param string $path Relative path without domain (e.g. endolift-facial-papada-mandibula).
 */
function nvx_laser_page_url( string $path ): string {
	$path = trim( $path, '/' );
	$page = get_page_by_path( $path );

	if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
		$url = get_permalink( $page );
		if ( is_string( $url ) && '' !== $url ) {
			return $url;
		}
	}

	return home_url( '/' . $path . '/' );
}

/**
 * Determines whether content represents the Medicina Estética Láser hub page.
 *
 * @param string $content The page content to inspect.
 * @return bool `true` if the content matches the laser hub markers in a suitable singular page context, `false` otherwise.
 */
function nvx_content_is_laser_medicine_page( string $content ): bool {
	if ( false !== strpos( $content, 'nvx-laser-editorial' ) ) {
		return false;
	}

	if ( ! nvx_laser_is_singular_context() ) {
		return false;
	}

	// Exclude treatment detail pages that share laser hero modifiers.
	if ( preg_match(
		'/nvx-editorial-page|nvx-editorial-hero|nvx-endolaser-editorial|nvx-endolaser-hero|nvx-co2-editorial|nvx-co2-hero|aria-label=["\']Endolift facial NUVANX["\']|id=["\']nvx-endolift-h1["\']|id=["\']nvx-endolaser-h1["\']|id=["\']nvx-co2-h1["\']|nvx-brand-page--exion|aria-label=["\']EXION/iu',
		$content
	) ) {
		return false;
	}

	// Stable structural markers only (wrapper class / hub ids / hub aria-label).
	return (bool) preg_match(
		'/class=["\'][^"\']*nvx-brand-page--laser|class=["\'][^"\']*nvx-laser-hero|id=["\']nvx-laser-h1["\']|aria-label=["\']Medicina estética láser NUVANX["\']/iu',
		$content
	);
}

/**
 * Linear premium icons — Champagne Bronce stroke 1.5px, 32×32 box.
 *
 * @param string $name Icon key.
 */
function nvx_laser_icon( string $name ): string {
	$icons = array(
		'spectrum'  => '<svg class="nvx-laser-icon" viewBox="0 0 32 32" width="32" height="32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="16" cy="16" r="4" stroke="currentColor" stroke-width="1.5"/><path d="M16 4v5M16 23v5M4 16h5M23 16h5M7.5 7.5l3.5 3.5M21 21l3.5 3.5M24.5 7.5 21 11M11 21l-3.5 3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		'dose'      => '<svg class="nvx-laser-icon" viewBox="0 0 32 32" width="32" height="32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M6 22 16 6l10 16" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M10 22h12M12 26h8M14 30h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		'nature'    => '<svg class="nvx-laser-icon" viewBox="0 0 32 32" width="32" height="32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M16 28V14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M16 24c-6 0-10-3.5-10-8.5 6 0 10 3.5 10 8.5Z" stroke="currentColor" stroke-width="1.5"/><path d="M16 21c6 0 10-3.5 10-8.5-6 0-10 3.5-10 8.5Z" stroke="currentColor" stroke-width="1.5"/><path d="M11 10c3-3 6-4.5 9-4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		'fiber'     => '<svg class="nvx-laser-icon" viewBox="0 0 32 32" width="32" height="32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M6 24 16 6l4 3-10 18H6v-3Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M14 10l4 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		'rf'        => '<svg class="nvx-laser-icon" viewBox="0 0 32 32" width="32" height="32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M8 22c3-7 5-10 8-10s5 3 8 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M10 14c2-1.5 4-2.5 6-2.5s4 1 6 2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="16" cy="20" r="2" stroke="currentColor" stroke-width="1.5"/></svg>',
		'co2'       => '<svg class="nvx-laser-icon" viewBox="0 0 32 32" width="32" height="32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><rect x="6" y="6" width="20" height="20" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M11 16h10M16 11v10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="11" cy="11" r="1.25" stroke="currentColor" stroke-width="1.5"/><circle cx="21" cy="11" r="1.25" stroke="currentColor" stroke-width="1.5"/><circle cx="11" cy="21" r="1.25" stroke="currentColor" stroke-width="1.5"/><circle cx="21" cy="21" r="1.25" stroke="currentColor" stroke-width="1.5"/></svg>',
	);

	return $icons[ $name ] ?? $icons['spectrum'];
}

/**
 * Hero dual CTA: valoración (primary) + sedes (secondary). No videoconsulta.
 */
function nvx_laser_hero_ctas_markup(): string {
	$valoracion = function_exists( 'nvx_cta_valoracion_url' )
		? nvx_cta_valoracion_url()
		: home_url( '/madrid/valoracion/' );
	$clinicas   = home_url( '/clinicas-de-medicina-estetica-nuvanx/' );

	$html  = '<div class="nvx-cta-pair nvx-laser-hero-ctas">';
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
 * Hero copy block.
 */
function nvx_laser_hero_copy_markup(): string {
	$colegiado = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';

	$html  = '<div class="nvx-brand-hero__copy nvx-laser-hero-copy">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'NUVANX · Tecnología médica de precisión', 'nuvanx-medical' ) . '</p>';
	$html .= '<h1 class="nvx-brand-hero__title" id="nvx-laser-h1">' . esc_html__( 'Medicina Estética Láser Avanzada en Madrid', 'nuvanx-medical' ) . '</h1>';
	$html .= '<p class="nvx-brand-hero__lead">' . esc_html__( 'Plataformas de energía selectiva calibradas con rigor clínico para redefinir el contorno, restaurar la firmeza dermoepidérmica y renovar la textura de la piel sin cirugía.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-brand-hero__description">' . esc_html(
		sprintf(
			/* translators: %s: medical license number */
			__( 'Bajo la dirección médica del Dr. José Javier Rivera Tejeda (Nº Colegiado ICOMEM %s), diseñamos protocolos que combinan la biofísica de la luz y la estimulación celular profunda para lograr resultados estables y elegantes.', 'nuvanx-medical' ),
			$colegiado
		)
	) . '</p>';
	$html .= nvx_laser_hero_ctas_markup();
	$html .= '<p class="nvx-brand-meta">' . esc_html__( 'Chamberí (CS20144) · Salamanca–Goya (CS20073) · Indicación médica personalizada', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';

	return $html;
}

/**
 * Full editorial body.
 */
function nvx_laser_editorial_body_markup(): string {
	$html  = '<div class="nvx-laser-editorial">';

	// B. Enfoque — 3 columnas.
	$html .= '<section class="nvx-laser-section nvx-laser-focus" aria-labelledby="nvx-laser-focus-title">';
	$html .= '<div class="nvx-laser-section__inner">';
	$html .= '<p class="nvx-laser-kicker">' . esc_html__( 'El enfoque', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-laser-focus-title" class="nvx-laser-heading">' . esc_html__( 'La diferencia entre tecnología e indicación médica', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-laser-focus-grid">';

	$pillars = array(
		array(
			'icon'  => 'spectrum',
			'title' => __( '1. Fototermólisis selectiva', 'nuvanx-medical' ),
			'body'  => __( 'No aplicamos calor de forma indiscriminada. Seleccionamos longitudes de onda específicas para interactuar únicamente con los cromóforos diana de la piel (agua, melanina o colágeno), protegiendo el tejido sano circundante y optimizando los tiempos de recuperación.', 'nuvanx-medical' ),
		),
		array(
			'icon'  => 'dose',
			'title' => __( '2. Dosificación personalizada', 'nuvanx-medical' ),
			'body'  => __( 'Huimos de los parámetros automáticos de fábrica. Ajustamos de forma milimétrica la fluencia, el ancho de pulso y la entrega de energía térmica según el grosor dermoepidérmico, el fototipo de piel y la capacidad regenerativa de cada paciente.', 'nuvanx-medical' ),
		),
		array(
			'icon'  => 'nature',
			'title' => __( '3. Resultados sin volumen', 'nuvanx-medical' ),
			'body'  => __( 'Nuestro objetivo no es rellenar o alterar las facciones de manera artificial. Utilizamos la energía física para inducir una respuesta biológica natural: la neocolagénesis y el incremento de ácido hialurónico endógeno.', 'nuvanx-medical' ),
		),
	);

	foreach ( $pillars as $pillar ) {
		$html .= '<article class="nvx-laser-pillar">';
		$html .= nvx_laser_icon( $pillar['icon'] );
		$html .= '<h3 class="nvx-laser-pillar__title">' . esc_html( $pillar['title'] ) . '</h3>';
		$html .= '<p class="nvx-laser-body">' . esc_html( $pillar['body'] ) . '</p>';
		$html .= '</article>';
	}

	$html .= '</div></div></section>';

	// C. Catálogo de plataformas clínicas.
	$html .= '<section class="nvx-laser-section nvx-laser-platforms" aria-labelledby="nvx-laser-platforms-title">';
	$html .= '<div class="nvx-laser-section__inner">';
	$html .= '<p class="nvx-laser-kicker">' . esc_html__( 'Nuestras plataformas clínicas', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-laser-platforms-title" class="nvx-laser-heading">' . esc_html__( 'Tecnologías médicas de precisión', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-laser-platform-list">';

	$platforms = array(
		array(
			'n'       => '01',
			'icon'    => 'fiber',
			'title'   => __( 'Endolift® Facial · Reafirmación y perfilado', 'nuvanx-medical' ),
			'body'    => __( 'Tratamiento de retracción tisular mínimamente invasivo que utiliza una microfibra óptica de silicio de entre 200 y 300 micras introducida directamente en la hipodermis superficial. Emplea una longitud de onda de 1470 nm para calentar de forma selectiva los septos fibrosos del SMAS y la grasa submentoniana, eliminando la flacidez de la papada y definiendo el óvalo facial sin cicatrices.', 'nuvanx-medical' ),
			'goal'    => __( 'Redefinición mandibular y eliminación de adiposidad submentoniana.', 'nuvanx-medical' ),
			'recover' => __( 'Edema leve durante 3–5 días; reincorporación social inmediata.', 'nuvanx-medical' ),
			'url'     => nvx_laser_page_url( 'endolift-facial-papada-mandibula' ),
		),
		array(
			'n'       => '02',
			'icon'    => 'rf',
			'title'   => __( 'EXION® Face · Regeneración endógena facial', 'nuvanx-medical' ),
			'body'    => __( 'RF monopolar + ultrasonido a microtemperaturas controladas para calidad de piel e hidratación. Alternativa a protocolos de daño térmico agresivo cuando el diagnóstico lo indica.', 'nuvanx-medical' ),
			'goal'    => __( 'Redensificación, hidratación y firmeza leve–moderada facial.', 'nuvanx-medical' ),
			'recover' => __( 'Downtime mínimo; eritema transitorio ocasional.', 'nuvanx-medical' ),
			'url'     => nvx_laser_page_url( 'exion-face' ),
		),
		array(
			'n'       => '03',
			'icon'    => 'rf',
			'title'   => __( 'EXION® Body · Grasa localizada y tensado', 'nuvanx-medical' ),
			'body'    => __( 'RF con refrigeración activa para adiposidad localizada y laxitud asociada. Se combina con endoláser cuando el espesor graso lo exige.', 'nuvanx-medical' ),
			'goal'    => __( 'Contorno de flancos, abdomen y zonas de grasa + flacidez.', 'nuvanx-medical' ),
			'recover' => __( 'Habitualmente sin baja social.', 'nuvanx-medical' ),
			'url'     => nvx_laser_page_url( 'exion-body' ),
		),
		array(
			'n'       => '04',
			'icon'    => 'rf',
			'title'   => __( 'EXION® Fractional RF · Textura y cicatrices', 'nuvanx-medical' ),
			'body'    => __( 'RF fraccionada con control de tejido para poro, textura y cicatrices superficiales, con plan de sesiones y downtime explícitos.', 'nuvanx-medical' ),
			'goal'    => __( 'Refinamiento de textura y remodelado dérmico progresivo.', 'nuvanx-medical' ),
			'recover' => __( 'Eritema 12–48 h según energía.', 'nuvanx-medical' ),
			'url'     => nvx_laser_page_url( 'exion-fractional' ),
		),
		array(
			'n'       => '05',
			'icon'    => 'co2',
			'title'   => __( 'Láser CO₂ fraccionado · Textura y cicatrices', 'nuvanx-medical' ),
			'body'    => __( 'Tratamiento fraccionado para cicatrices de acné, poros y textura irregular. La recuperación (eritema y descamación) se planifica según la profundidad del protocolo.', 'nuvanx-medical' ),
			'goal'    => __( 'Mejorar cicatrices de acné, poros y signos de fotoenvejecimiento, cuando hay indicación.', 'nuvanx-medical' ),
			'recover' => __( 'Suele implicar varios días de descamación y protección solar estricta (orientativo 5–7 días según plan).', 'nuvanx-medical' ),
			'url'     => nvx_laser_page_url( 'laser-co2-fraccionado-madrid-textura-cicatrices-poro' ),
		),
	);

	foreach ( $platforms as $platform ) {
		$html .= '<article class="nvx-laser-platform">';
		$html .= '<div class="nvx-laser-platform__main">';
		$html .= '<div class="nvx-laser-platform__head">';
		$html .= nvx_laser_icon( $platform['icon'] );
		$html .= '<p class="nvx-laser-platform__n">' . esc_html( $platform['n'] ) . '</p>';
		$html .= '</div>';
		$html .= '<h3 class="nvx-laser-platform__title">' . esc_html( $platform['title'] ) . '</h3>';
		$html .= '<p class="nvx-laser-body">' . esc_html( $platform['body'] ) . '</p>';
		$html .= '<p class="nvx-laser-platform__link-wrap"><a class="nvx-laser-platform__link" href="' . esc_url( $platform['url'] ) . '">' . esc_html__( 'Ver protocolo clínico', 'nuvanx-medical' ) . '</a></p>';
		$html .= '</div>';
		$html .= '<aside class="nvx-laser-platform__meta" aria-label="' . esc_attr__( 'Indicación y recuperación', 'nuvanx-medical' ) . '">';
		$html .= '<p class="nvx-laser-meta-label">' . esc_html__( 'Objetivo clínico', 'nuvanx-medical' ) . '</p>';
		$html .= '<p class="nvx-laser-body">' . esc_html( $platform['goal'] ) . '</p>';
		$html .= '<p class="nvx-laser-meta-label nvx-laser-meta-label--spaced">' . esc_html__( 'Recuperación', 'nuvanx-medical' ) . '</p>';
		$html .= '<p class="nvx-laser-body">' . esc_html( $platform['recover'] ) . '</p>';
		$html .= '</aside></article>';
	}

	$html .= '</div></div></section>';

	// D. FAQ AEO.
	$html .= '<section class="nvx-laser-section nvx-laser-faq" aria-labelledby="nvx-laser-faq-title">';
	$html .= '<div class="nvx-laser-section__inner">';
	$html .= '<p class="nvx-laser-kicker">' . esc_html__( 'Preguntas clínicas', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-laser-faq-title" class="nvx-laser-heading">' . esc_html__( 'Rigor biológico sobre medicina estética láser', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-faq nvx-laser-faq-list">';

	// FAQ 1 with formula in structured markup (not raw LaTeX).
	$html .= '<details class="nvx-brand-faq-item" open>';
	$html .= '<summary><span>' . esc_html__( '¿Cómo funciona la fototermólisis selectiva y cómo evita el láser dañar la superficie de la piel?', 'nuvanx-medical' ) . '</span></summary>';
	$html .= '<div class="nvx-brand-faq-content">';
	$html .= '<p>' . esc_html__( 'El principio fundamental de la medicina estética láser en NUVANX es la fototermólisis selectiva. Consiste en la entrega de una longitud de onda de luz específica orientada a calentar un cromóforo diana (como la melanina en las manchas o el agua en las células de la dermis) sin dañar los tejidos circundantes. Para lograrlo, el ancho de pulso del láser debe ser estrictamente menor o igual al tiempo de relajación térmica del objetivo de tratamiento. El tiempo de relajación térmica (τᵣ) se define mediante la siguiente relación física:', 'nuvanx-medical' ) . '</p>';
	$html .= '<figure class="nvx-laser-formula" aria-label="' . esc_attr__( 'Tiempo de relajación térmica', 'nuvanx-medical' ) . '">';
	$html .= '<p class="nvx-laser-formula__eq" role="math"><span class="nvx-laser-formula__tau">τ<sub>r</sub></span> = <span class="nvx-laser-formula__frac"><span class="nvx-laser-formula__num">d<sup>2</sup></span><span class="nvx-laser-formula__den">4α</span></span></p>';
	$html .= '<figcaption class="nvx-laser-formula__cap">' . esc_html__( 'Donde d representa el diámetro de la estructura celular objetivo (como un haz de colágeno o un vaso capilar) y α corresponde a la difusividad térmica del tejido. Al programar pulsos de energía extremadamente rápidos por debajo de este límite, el calor se confina en la diana biológica y se disipa antes de propagarse a las capas epidérmicas superficiales, reduciendo el riesgo de quemaduras y optimizando la seguridad del paciente.', 'nuvanx-medical' ) . '</figcaption>';
	$html .= '</figure></div></details>';

	$faqs = array(
		array(
			'q' => __( '¿Cuándo es fisiológicamente visible el resultado de un tratamiento de tensado térmico por radiofrecuencia o láser subdérmico?', 'nuvanx-medical' ),
			'a' => __( 'Aunque se produce un efecto tensor inmediato por la contracción elástica mecánica de las fibras de colágeno existentes debido al calor aplicado, la verdadera remodelación estructural sigue una cascada biológica de cicatrización controlada que requiere tiempo. Durante las primeras 72 horas se produce una fase inflamatoria subclínica que estimula la llegada de factores de crecimiento. A partir de la primera semana y hasta el tercer mes, se inicia la fase proliferativa, donde los fibroblastos sintetizan activamente colágeno tipo III, que posteriormente se consolida en colágeno tipo I (más denso y firme). Los resultados de firmeza, textura e hidratación profunda alcanzan su pico clínico de maduración entre los 90 y 120 días posteriores a la sesión.', 'nuvanx-medical' ),
		),
		array(
			'q' => __( '¿Por qué el diagnóstico médico previo en Chamberí y Goya es indispensable antes de aplicar cualquier tecnología láser?', 'nuvanx-medical' ),
			'a' => __( 'No todas las pieles reaccionan igual ante la entrega de energía térmica. Pacientes con un fototipo de piel alto (pieles oscuras) presentan una mayor concentración de melanina epidérmica, lo que exige el uso de longitudes de onda largas y pulsos prolongados para evitar la hiperpigmentación postinflamatoria. Asimismo, si un paciente presenta una dermis extremadamente adelgazada o grados avanzados de elastosis solar, la capacidad de retracción de los tejidos se reduce, haciendo que tratamientos como el Endolift® tengan una eficacia limitada y sea aconsejable una derivación quirúrgica. En nuestras clínicas de Chamberí y Goya, evaluamos estas variables biológicas para confirmar la idoneidad clínica antes de encender cualquier equipo.', 'nuvanx-medical' ),
		),
	);

	foreach ( $faqs as $faq ) {
		$html .= '<details class="nvx-brand-faq-item">';
		$html .= '<summary><span>' . esc_html( $faq['q'] ) . '</span></summary>';
		$html .= '<div class="nvx-brand-faq-content"><p>' . esc_html( $faq['a'] ) . '</p></div>';
		$html .= '</details>';
	}

	$html .= '</div></div></section>';

	// Closing valoración CTA: site-wide nvx-cta-banner in footer.php (not page-local).

	$html .= '</div>';

	return $html;
}

/**
 * Rebuild Medicina Estética Láser hub page.
 */
function nvx_content_restructure_laser_medicine_page( string $content ): string {
	if ( ! nvx_content_is_laser_medicine_page( $content ) ) {
		return $content;
	}

	$media = '';
	if ( preg_match( '/<figure class="nvx-brand-hero__media"[\s\S]*?<\/figure>/iu', $content, $m ) ) {
		$media = $m[0];
	} elseif ( preg_match( '/<div class="nvx-brand-hero__media"[\s\S]*?<\/div>/iu', $content, $m ) ) {
		$media = $m[0];
	}

	$hero  = '<section class="nvx-brand-hero nvx-brand-hero--laser nvx-laser-hero" aria-labelledby="nvx-laser-h1" aria-label="' . esc_attr__( 'Medicina estética láser NUVANX', 'nuvanx-medical' ) . '">';
	$hero .= '<div class="nvx-brand-hero__inner">';
	$hero .= nvx_laser_hero_copy_markup();
	$hero .= $media;
	$hero .= '</div></section>';

	$body = nvx_laser_editorial_body_markup();

	if ( preg_match( '/(<div class="nvx-brand-page[^"]*"[^>]*>)/iu', $content, $wrap ) ) {
		return $wrap[1] . $hero . $body . '</div>';
	}

	return '<div class="nvx-brand-page nvx-brand-page--laser">' . $hero . $body . '</div>';
}
add_filter( 'the_content', 'nvx_content_restructure_laser_medicine_page', 19 );
