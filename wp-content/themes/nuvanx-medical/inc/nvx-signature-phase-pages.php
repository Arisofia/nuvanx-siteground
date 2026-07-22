<?php
/**
 * Governed Signature Phase 1 enrichment and Phase 2 anatomical pages.
 *
 * Phase 1 remains rendered by nvx-protocol-pages.php. This module adds the
 * diagnostic, limits and valuation sections required by the roadmap, and owns
 * the six Phase 2 pages, their SEO and publication-aware navigation.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Phase 1 pages already rendered by the protocol-page module. */
function nvx_signature_phase1_catalog(): array {
	return array(
		'profile-definition' => array(
			'slug'        => 'papada-definicion-mandibular-madrid',
			'h1'          => 'Papada y mandíbula: a veces es grasa, a veces es piel.',
			'protocol'    => 'NUVANX Profile Definition™',
			'seo_title'   => 'Papada y definición mandibular Madrid | NUVANX',
			'seo_desc'    => 'Valoración médica de papada, cuello y mandíbula en Madrid para diferenciar grasa, laxitud y soporte antes de indicar Endolift® u otra opción.',
			'assessment'  => array(
				'Distribución y espesor de la grasa submentoniana.',
				'Laxitud del cuello y calidad cutánea.',
				'Continuidad entre mentón, mandíbula y cuello.',
				'Proyección del mentón, asimetrías y tratamientos previos.',
			),
			'limits'      => array(
				'El exceso importante de piel puede requerir valoración quirúrgica.',
				'Una alteración principalmente ósea no se corrige con energía sobre grasa o piel.',
				'No se promete una línea mandibular estándar ni un cambio idéntico entre pacientes.',
			),
		),
		'skin-architecture' => array(
			'slug'        => 'calidad-piel-firmeza-luminosidad-madrid',
			'h1'          => 'Tu piel no necesita más cremas, necesita reconstruirse por dentro.',
			'protocol'    => 'NUVANX Skin Architecture™',
			'seo_title'   => 'Calidad y firmeza de la piel Madrid | NUVANX',
			'seo_desc'    => 'Tratamiento médico para calidad, firmeza y luminosidad de la piel en Madrid con tecnología seleccionada tras diagnóstico, fototipo y valoración.',
			'assessment'  => array(
				'Firmeza y densidad dérmica.',
				'Hidratación, luminosidad y uniformidad visual.',
				'Textura, poros y líneas finas.',
				'Fototipo, sensibilidad y procedimientos anteriores.',
			),
			'limits'      => array(
				'No sustituye procedimientos dirigidos a pérdida de volumen o soporte estructural.',
				'El número de sesiones y la evolución dependen del diagnóstico y la respuesta individual.',
				'Las lesiones sospechosas requieren evaluación dermatológica antes de aplicar energía.',
			),
		),
		'surface-renewal' => array(
			'slug'        => 'cicatrices-acne-poros-textura-madrid',
			'h1'          => 'Para mejorar las marcas de acné hay que romper la cicatriz, no solo pelar la piel.',
			'protocol'    => 'NUVANX Surface Renewal™',
			'seo_title'   => 'Cicatrices de acné, poros y textura Madrid | NUVANX',
			'seo_desc'    => 'Tratamiento de cicatrices de acné, poros y textura en Madrid con CO₂ o Fractional RF según morfología, fototipo y valoración médica.',
			'assessment'  => array(
				'Tipo, profundidad y distribución de las cicatrices.',
				'Poros, textura y líneas finas asociadas.',
				'Actividad de acné o inflamación residual.',
				'Fototipo y riesgo de pigmentación postinflamatoria.',
			),
			'limits'      => array(
				'Las cicatrices profundas pueden requerir un plan combinado o derivación.',
				'No se garantiza la eliminación completa de una cicatriz o poro.',
				'El tratamiento se pospone cuando existe infección, inflamación activa o contraindicación.',
			),
		),
		'tone-correction' => array(
			'slug'        => 'manchas-rojeces-fotorejuvenecimiento-ipl-madrid',
			'h1'          => 'Quitar una mancha es fácil; que no vuelva a salir es la parte médica.',
			'protocol'    => 'NUVANX Tone Correction™',
			'seo_title'   => 'Manchas, rojeces y fotodaño Madrid | NUVANX',
			'seo_desc'    => 'Tratamiento de manchas, rojeces y fotodaño en Madrid con IPL seleccionada según diagnóstico, fototipo y valoración médica.',
			'assessment'  => array(
				'Tipo, localización y evolución de la pigmentación.',
				'Componente vascular, eritema o telangiectasias.',
				'Fototipo, exposición solar y medicación fotosensibilizante.',
				'Antecedentes de melasma o pigmentación postinflamatoria.',
			),
			'limits'      => array(
				'No se fija un número estándar de sesiones antes de la valoración.',
				'El melasma y otras alteraciones complejas pueden requerir control prolongado.',
				'No se aplica luz o láser sobre una lesión pigmentada no diagnosticada.',
			),
		),
	);
}

/** Phase 2 anatomical landing pages owned by this module. */
function nvx_signature_phase2_catalog(): array {
	return array(
		'abdomen-flancos' => array(
			'slug'       => 'grasa-localizada-abdomen-flancos-madrid',
			'title'      => 'Grasa localizada en abdomen y flancos en Madrid',
			'lead'       => 'Valoramos abdomen, cintura, flancos y espalda baja como una unidad para distinguir grasa subcutánea, laxitud, pared abdominal y continuidad del contorno.',
			'intro'      => 'El abdomen no termina en la línea frontal. La indicación considera su transición con flancos, cintura y espalda, sin confundir grasa subcutánea con grasa visceral o alteraciones musculares.',
			'assessment' => array(
				'Abdomen superior e inferior.',
				'Flancos, cintura y espalda baja.',
				'Grasa subcutánea frente a volumen visceral.',
				'Laxitud, cicatrices y sospecha de diástasis o hernia.',
			),
			'technology' => array(
				'Endoláser corporal cuando existe una indicación focal.',
				'EXION® Body u otras modalidades cuando corresponde actuar sobre calidad tisular.',
				'Láser fraccionado cuando el problema principal es superficie o cicatriz.',
			),
			'limits' => array(
				'No es un tratamiento para pérdida general de peso.',
				'La grasa visceral no se trata con una intervención estética focal.',
				'Diástasis, hernia o exceso importante de piel pueden requerir derivación.',
			),
			'seo_title' => 'Grasa localizada abdomen y flancos Madrid | NUVANX',
			'seo_desc'  => 'Valoración de grasa localizada, laxitud y pared abdominal en abdomen y flancos en Madrid dentro de NUVANX Contour Architecture™.',
		),
		'brazos' => array(
			'slug'       => 'flacidez-grasa-localizada-brazos-madrid',
			'title'      => 'Flacidez y grasa localizada en brazos en Madrid',
			'lead'       => 'Estudiamos el brazo completo y su relación con la axila anterior y el torso para separar grasa localizada, laxitud y calidad del tejido.',
			'intro'      => 'Tratar solo la cara posterior del brazo puede ignorar la transición con la axila y el torso. El plan se limita a las unidades con indicación documentada.',
			'assessment' => array(
				'Distribución de grasa y espesor del tejido.',
				'Laxitud de la cara posterior e interna.',
				'Continuidad con axila anterior y torso.',
				'Asimetrías, cicatrices y reserva cutánea.',
			),
			'technology' => array(
				'Endoláser corporal cuando predomina grasa localizada susceptible de tratamiento.',
				'Endolift corporal o EXION® Body cuando la calidad cutánea tiene una indicación específica.',
				'Plan combinado solo cuando cada modalidad responde a un componente distinto.',
			),
			'limits' => array(
				'La laxitud intensa puede no responder adecuadamente sin cirugía.',
				'No se promete un brazo estándar ni una reducción determinada.',
				'La indicación depende de anatomía, salud y expectativas compatibles.',
			),
			'seo_title' => 'Flacidez y grasa localizada brazos Madrid | NUVANX',
			'seo_desc'  => 'Tratamiento de flacidez y grasa localizada en brazos en Madrid con valoración de brazo, axila y torso antes de seleccionar tecnología.',
		),
		'espalda' => array(
			'slug'       => 'grasa-espalda-zona-sujetador-madrid',
			'title'      => 'Grasa de espalda y zona del sujetador en Madrid',
			'lead'       => 'Valoramos espalda superior, zona del sujetador, flancos y brazos como una continuidad anatómica, diferenciando pliegues por grasa, laxitud y ajuste de la prenda.',
			'intro'      => 'La espalda cambia según postura, prenda y distribución del tejido. La exploración determina si el problema es focal, si afecta zonas contiguas o si no existe una indicación proporcionada.',
			'assessment' => array(
				'Espalda superior y zona del sujetador.',
				'Relación con brazos, axila y flancos.',
				'Grasa localizada frente a laxitud cutánea.',
				'Postura, asimetrías y procedimientos previos.',
			),
			'technology' => array(
				'Endoláser corporal para unidades focales con indicación.',
				'Modalidades de calidad cutánea cuando la laxitud es el componente tratable.',
				'Plan de continuidad solo si cada zona aporta una mejora clínicamente justificable.',
			),
			'limits' => array(
				'La prenda y la postura pueden producir pliegues sin indicación médica.',
				'No se añaden zonas por venta cruzada.',
				'El exceso cutáneo importante puede requerir otra vía.',
			),
			'seo_title' => 'Grasa espalda y zona del sujetador Madrid | NUVANX',
			'seo_desc'  => 'Valoración de grasa y laxitud en espalda y zona del sujetador en Madrid, considerando continuidad con brazos y flancos.',
		),
		'muslos' => array(
			'slug'       => 'flacidez-muslos-internos-subgluteo-madrid',
			'title'      => 'Flacidez en muslos internos y región subglútea en Madrid',
			'lead'       => 'Diagnosticamos muslo interno, cara externa, región subglútea y transición con rodilla para diferenciar grasa localizada, laxitud y celulitis estructural.',
			'intro'      => 'No tratamos “piernas” como una sola zona. Cada unidad tiene un tejido, un riesgo y un límite distinto, y la celulitis no se presenta como equivalente a grasa o laxitud.',
			'assessment' => array(
				'Muslo interno y externo.',
				'Región subglútea y transición glúteo-muslo.',
				'Laxitud, grasa localizada y celulitis estructural.',
				'Continuidad con rodillas y cadera.',
			),
			'technology' => array(
				'Endoláser corporal cuando predomina grasa localizada focal.',
				'EXION® Body u otra modalidad cuando existe indicación de calidad cutánea.',
				'Derivación o no intervención cuando el problema no corresponde a la oferta médico-estética.',
			),
			'limits' => array(
				'La celulitis requiere un diagnóstico distinto de la grasa localizada.',
				'La laxitud intensa o el exceso cutáneo pueden requerir cirugía.',
				'No se promete una separación o forma estándar de los muslos.',
			),
			'seo_title' => 'Flacidez muslos internos y subglúteo Madrid | NUVANX',
			'seo_desc'  => 'Valoración de flacidez, grasa y continuidad en muslos internos y región subglútea en Madrid dentro de Contour Architecture™.',
		),
		'rodillas' => array(
			'slug'       => 'tratamiento-rodillas-grasa-flacidez-madrid',
			'title'      => 'Grasa localizada y flacidez en rodillas en Madrid',
			'lead'       => 'Valoramos la cara interna y superior de la rodilla dentro de la continuidad del muslo y la pierna, con atención a grasa localizada, laxitud y anatomía funcional.',
			'intro'      => 'La rodilla es una unidad pequeña y móvil. La indicación debe ser conservadora y considerar la transición con muslo interno, piel, articulación y estructuras vasculares.',
			'assessment' => array(
				'Distribución focal de grasa alrededor de la rodilla.',
				'Laxitud y grosor cutáneo.',
				'Continuidad con muslo interno y pierna.',
				'Asimetrías, edema y antecedentes vasculares.',
			),
			'technology' => array(
				'Modalidad láser o térmica solo cuando la anatomía permite una indicación segura.',
				'Plan combinado con muslo interno únicamente por continuidad clínica.',
				'Derivación cuando el volumen corresponde a edema, articulación u otra causa.',
			),
			'limits' => array(
				'No se trata dolor articular ni patología vascular.',
				'La zona exige una indicación focal y parámetros conservadores.',
				'No se promete eliminar todo el volumen visible.',
			),
			'seo_title' => 'Grasa localizada y flacidez rodillas Madrid | NUVANX',
			'seo_desc'  => 'Valoración de grasa localizada y flacidez en rodillas en Madrid, diferenciando tejido estético de causas articulares, vasculares o edema.',
		),
		'male-contour' => array(
			'slug'       => 'contorno-corporal-masculino-madrid',
			'title'      => 'Contorno corporal masculino en Madrid',
			'lead'       => 'Diseñamos planes para abdomen, cintura, pecho, espalda o perfil masculino según distribución de grasa, calidad cutánea, proporción y objetivos individuales.',
			'intro'      => 'La planificación masculina no consiste en trasladar un patrón corporal estándar. Se revisan ángulos, distribución del tejido, actividad física, salud y prioridades de recuperación.',
			'assessment' => array(
				'Abdomen, cintura y espalda.',
				'Pecho y relación con el torso.',
				'Mandíbula y perfil cuando la consulta es facial.',
				'Grasa localizada, laxitud y objetivos anatómicos.',
			),
			'technology' => array(
				'Endoláser corporal o Endolift® según zona e indicación.',
				'EXION® Body o Face cuando la calidad tisular forma parte del plan.',
				'Medicina inyectable o derivación cuando el componente principal no es grasa o laxitud.',
			),
			'limits' => array(
				'No se ofrece una definición abdominal artificial o garantizada.',
				'El tratamiento focal no sustituye pérdida de peso, entrenamiento ni cirugía cuando esta es la vía adecuada.',
				'Cada zona se presupuesta solo si tiene indicación documentada.',
			),
			'seo_title' => 'Contorno corporal masculino Madrid | NUVANX',
			'seo_desc'  => 'Contorno corporal masculino en Madrid para abdomen, cintura, espalda o perfil, con diagnóstico y tecnología seleccionada tras valoración.',
		),
	);
}

/** Resolve the current Phase 2 page. */
function nvx_signature_phase_current_key(): ?string {
	if ( ! is_page() ) {
		return null;
	}
	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	foreach ( nvx_signature_phase2_catalog() as $key => $page ) {
		if ( $page['slug'] === $slug ) {
			return $key;
		}
	}
	return null;
}

/** Return metadata for either governed phase. */
function nvx_signature_all_phase_metadata(): ?array {
	$slug = is_page() ? (string) get_post_field( 'post_name', get_queried_object_id() ) : '';
	foreach ( array_merge( nvx_signature_phase1_catalog(), nvx_signature_phase2_catalog() ) as $page ) {
		if ( $page['slug'] === $slug ) {
			return $page;
		}
	}
	return null;
}

/** Render one list section. */
function nvx_signature_phase_list( string $title, array $items, string $class = '' ): string {
	$html  = '<section class="nvx-brand-section ' . esc_attr( $class ) . '">';
	$html .= '<h2>' . esc_html( $title ) . '</h2><ul class="nvx-check-list">';
	foreach ( $items as $item ) {
		$html .= '<li>' . esc_html( (string) $item ) . '</li>';
	}
	return $html . '</ul></section>';
}

/** Shared decision and valuation closure. */
function nvx_signature_phase_decision_sections( array $page ): string {
	$html  = nvx_signature_phase_list( 'Qué se valora', (array) $page['assessment'] );
	$html .= '<section class="nvx-brand-section"><h2>' . esc_html__( 'Cómo se decide el plan', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'El médico identifica el componente predominante, revisa zonas contiguas y descarta problemas que no deben abordarse con medicina estética. Solo entonces se selecciona una modalidad y se documentan alternativas, cuidados y seguimiento.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><strong>' . esc_html__( 'Protocolo relacionado:', 'nuvanx-medical' ) . '</strong> ' . esc_html( $page['protocol'] ?? 'NUVANX Contour Architecture™' ) . '</p></section>';
	if ( isset( $page['technology'] ) ) {
		$html .= nvx_signature_phase_list( 'Tecnologías que pueden formar parte del plan', (array) $page['technology'] );
	}
	$html .= nvx_signature_phase_list( 'Límites y cuándo derivamos', (array) $page['limits'], 'nvx-strategy-checklist nvx-strategy-checklist--no' );
	$html .= '<section class="nvx-brand-section"><h2>' . esc_html__( 'Tu primera valoración clínica', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'La valoración revisa antecedentes, anatomía, tejido predominante, tratamientos previos, expectativas y disponibilidad para cuidados. Si no existe una indicación proporcionada, se explica la alternativa, la derivación o la decisión de no intervenir.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Iniciar valoración médica', 'nuvanx-medical' ) . '</a> <a class="nvx-brand-inline-link" href="' . esc_url( home_url( '/protocolos-signature/' ) ) . '">' . esc_html__( 'Explorar Protocolos Signature', 'nuvanx-medical' ) . '</a></p></section>';
	return $html;
}

/** Enrich the four Phase 1 pages without replacing their approved renderer or H1. */
function nvx_signature_phase1_enrichment_filter( string $content ): string {
	if ( is_admin() || ! is_main_query() || ! in_the_loop() || ! function_exists( 'nvx_protocol_pages_current_key' ) ) {
		return $content;
	}
	$key     = nvx_protocol_pages_current_key();
	$catalog = nvx_signature_phase1_catalog();
	if ( null === $key || ! isset( $catalog[ $key ] ) ) {
		return $content;
	}
	$sections = nvx_signature_phase_decision_sections( $catalog[ $key ] );
	if ( false !== strripos( $content, '</article>' ) ) {
		return (string) preg_replace( '/<\/article>\s*$/i', $sections . '</article>', $content, 1 );
	}
	return $content . $sections;
}
add_filter( 'the_content', 'nvx_signature_phase1_enrichment_filter', 22 );

/** Build one Phase 2 landing page. */
function nvx_signature_phase2_markup( array $page ): string {
	$page['protocol'] = 'NUVANX Contour Architecture™';
	$html  = '<article class="nvx-brand-readable nvx-protocol-page nvx-signature-phase-page nvx-shell">';
	$html .= '<header class="nvx-strategy-intro"><p class="nvx-brand-kicker">NUVANX CONTOUR ARCHITECTURE™</p>';
	$html .= '<h1 class="nvx-strategy-title">' . esc_html( $page['title'] ) . '</h1>';
	$html .= '<p class="nvx-brand-lead">' . esc_html( $page['lead'] ) . '</p><p>' . esc_html( $page['intro'] ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Solicitar valoración médica privada', 'nuvanx-medical' ) . '</a></p>';
	$html .= '<p class="nvx-brand-microcopy">' . esc_html__( 'La indicación, la tecnología, el número de sesiones, el período de recuperación y el presupuesto se confirman después de la exploración médica.', 'nuvanx-medical' ) . '</p></header>';
	$html .= nvx_signature_phase_decision_sections( $page );
	return $html . '</article>';
}

/** Replace Phase 2 CMS placeholders with governed markup. */
function nvx_signature_phase2_content_filter( string $content ): string {
	if ( is_admin() || ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}
	$key = nvx_signature_phase_current_key();
	if ( null === $key ) {
		return $content;
	}
	$catalog = nvx_signature_phase2_catalog();
	return nvx_signature_phase2_markup( $catalog[ $key ] );
}
add_filter( 'the_content', 'nvx_signature_phase2_content_filter', 22 );

/** Prevent the generic shell from emitting a duplicate H1 on Phase 2. */
function nvx_signature_phase_prepare_shell(): void {
	if ( null !== nvx_signature_phase_current_key() ) {
		set_query_var( 'nvx_shell_skip_header', true );
	}
}
add_action( 'wp', 'nvx_signature_phase_prepare_shell', 5 );

/** Filter out menu items containing "Eye Frame" in their label. */
function nvx_signature_phase_filter_eye_frame( array $child ): bool {
	$child_label = isset( $child['label'] ) ? (string) $child['label'] : '';
	return false === stripos( $child_label, 'Eye Frame' );
}

/** Normalize Contour Architecture menu items with canonical label, slug and children. */
function nvx_signature_phase_normalize_contour( array $child ): array {
	$child_label = isset( $child['label'] ) ? (string) $child['label'] : '';
	if ( false !== stripos( $child_label, 'Contour Sculpt' ) || false !== stripos( $child_label, 'Contour Architecture' ) || false !== stripos( $child_label, 'Couture Sculpt' ) ) {
		$child['label'] = 'NUVANX Contour Architecture™';
		$child['slugs'] = array( 'remodelacion-corporal-laser-madrid' );
		$child['children'] = array(
			array( 'label' => 'Abdomen y flancos', 'slugs' => array( 'grasa-localizada-abdomen-flancos-madrid' ) ),
			array( 'label' => 'Brazos y axila', 'slugs' => array( 'flacidez-grasa-localizada-brazos-madrid' ) ),
			array( 'label' => 'Espalda y zona del sujetador', 'slugs' => array( 'grasa-espalda-zona-sujetador-madrid' ) ),
			array( 'label' => 'Muslos y región subglútea', 'slugs' => array( 'flacidez-muslos-internos-subgluteo-madrid' ) ),
			array( 'label' => 'Rodillas', 'slugs' => array( 'tratamiento-rodillas-grasa-flacidez-madrid' ) ),
			array( 'label' => 'Contorno masculino', 'slugs' => array( 'contorno-corporal-masculino-madrid' ) ),
		);
	}
	return $child;
}

/** Publish-aware navigation and explicit removal of unsupported product names. */
function nvx_signature_phase_navigation_blueprint( array $blueprint ): array {
	foreach ( $blueprint as $top_index => $top ) {
		$label = isset( $top['label'] ) ? (string) $top['label'] : '';
		if ( 'Casos clínicos' === $label ) {
			$blueprint[ $top_index ]['slugs'] = array( 'casos-de-pacientes', 'casos-clinicos' );
		}
		if ( 'Protocolos Signature' !== $label || empty( $top['children'] ) || ! is_array( $top['children'] ) ) {
			continue;
		}
		$children = array_filter( $top['children'], 'nvx_signature_phase_filter_eye_frame' );
		$children = array_map( 'nvx_signature_phase_normalize_contour', $children );
		$blueprint[ $top_index ]['children'] = $children;
	}
	return $blueprint;
}
add_filter( 'nvx_navigation_primary_blueprint', 'nvx_signature_phase_navigation_blueprint', 30 );

/** One canonical public body-protocol name; internal legacy keys remain stable. */
function nvx_signature_phase_normalize_public_names( string $content ): string {
	return str_ireplace(
		array( 'Couture Sculpt™', 'NUVANX Contour Sculpt™', 'Contour Sculpt™' ),
		'NUVANX Contour Architecture™',
		$content
	);
}
add_filter( 'the_content', 'nvx_signature_phase_normalize_public_names', 219 );

/** SEO for both phases. */
function nvx_signature_phase_seo_title( $title ) {
	$page = nvx_signature_all_phase_metadata();
	return is_array( $page ) ? $page['seo_title'] : $title;
}
add_filter( 'wpseo_title', 'nvx_signature_phase_seo_title', 130 );
add_filter( 'pre_get_document_title', 'nvx_signature_phase_seo_title', 130 );
add_filter( 'wpseo_opengraph_title', 'nvx_signature_phase_seo_title', 130 );
add_filter( 'wpseo_twitter_title', 'nvx_signature_phase_seo_title', 130 );

function nvx_signature_phase_seo_description( $description ) {
	$page = nvx_signature_all_phase_metadata();
	return is_array( $page ) ? $page['seo_desc'] : $description;
}
add_filter( 'wpseo_metadesc', 'nvx_signature_phase_seo_description', 130 );
add_filter( 'wpseo_opengraph_desc', 'nvx_signature_phase_seo_description', 130 );
add_filter( 'wpseo_twitter_description', 'nvx_signature_phase_seo_description', 130 );

function nvx_signature_phase_seo_url( $url ) {
	$page = nvx_signature_all_phase_metadata();
	return is_array( $page ) ? home_url( '/' . trim( (string) $page['slug'], '/' ) . '/' ) : $url;
}
add_filter( 'wpseo_canonical', 'nvx_signature_phase_seo_url', 130 );
add_filter( 'wpseo_opengraph_url', 'nvx_signature_phase_seo_url', 130 );

function nvx_signature_phase_seo_robots( $robots ) {
	if ( null === nvx_signature_all_phase_metadata() ) {
		return $robots;
	}
	return function_exists( 'nvx_seo_is_nonproduction_environment' ) && nvx_seo_is_nonproduction_environment()
		? 'noindex, nofollow'
		: 'index, follow';
}
add_filter( 'wpseo_robots', 'nvx_signature_phase_seo_robots', 130 );
