<?php
/**
 * Canonical Phase 2 anatomical pages, Signature SEO and navigation governance.
 *
 * Phase 1 and Eye Frame remain owned by nvx-protocol-pages.php.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Canonical Phase 2 landing pages. */
function nvx_signature_phase2_catalog(): array {
	return array(
		'abdomen-flancos' => array(
			'slug' => 'grasa-localizada-abdomen-flancos-madrid',
			'title' => 'Grasa localizada en abdomen y flancos en Madrid',
			'lead' => 'Valoramos abdomen, cintura, flancos y espalda baja como una unidad para distinguir grasa subcutánea, laxitud, pared abdominal y continuidad del contorno.',
			'assessment' => array( 'Abdomen superior e inferior.', 'Flancos, cintura y espalda baja.', 'Grasa subcutánea frente a volumen visceral.', 'Laxitud, cicatrices y sospecha de diástasis o hernia.' ),
			'technology' => array( 'Endoláser corporal cuando existe una indicación focal.', 'EXION® Body cuando corresponde actuar sobre calidad tisular.', 'Láser fraccionado cuando el problema principal es superficie o cicatriz.' ),
			'limits' => array( 'No es un tratamiento para pérdida general de peso.', 'La grasa visceral no se trata con una intervención estética focal.', 'Diástasis, hernia o exceso importante de piel pueden requerir derivación.' ),
			'seo_title' => 'Grasa localizada abdomen y flancos Madrid | NUVANX',
			'seo_desc' => 'Valoración de grasa localizada, laxitud y pared abdominal en abdomen y flancos en Madrid dentro de NUVANX Contour Architecture™.',
		),
		'brazos' => array(
			'slug' => 'flacidez-grasa-localizada-brazos-madrid',
			'title' => 'Flacidez y grasa localizada en brazos en Madrid',
			'lead' => 'Estudiamos el brazo completo y su relación con la axila anterior y el torso para separar grasa localizada, laxitud y calidad del tejido.',
			'assessment' => array( 'Distribución de grasa y espesor del tejido.', 'Laxitud de la cara posterior e interna.', 'Continuidad con axila anterior y torso.', 'Asimetrías, cicatrices y reserva cutánea.' ),
			'technology' => array( 'Endoláser corporal cuando predomina grasa localizada susceptible de tratamiento.', 'Endolift corporal o EXION® Body cuando la calidad cutánea tiene indicación.', 'Plan combinado solo cuando cada modalidad responde a un componente distinto.' ),
			'limits' => array( 'La laxitud intensa puede no responder adecuadamente sin cirugía.', 'No se promete un brazo estándar ni una reducción determinada.', 'La indicación depende de anatomía, salud y expectativas compatibles.' ),
			'seo_title' => 'Flacidez y grasa localizada brazos Madrid | NUVANX',
			'seo_desc' => 'Tratamiento de flacidez y grasa localizada en brazos en Madrid con valoración de brazo, axila y torso antes de seleccionar tecnología.',
		),
		'espalda' => array(
			'slug' => 'grasa-espalda-zona-sujetador-madrid',
			'title' => 'Grasa de espalda y zona del sujetador en Madrid',
			'lead' => 'Valoramos espalda superior, zona del sujetador, flancos y brazos como una continuidad anatómica, diferenciando pliegues por grasa, laxitud y ajuste de la prenda.',
			'assessment' => array( 'Espalda superior y zona del sujetador.', 'Relación con brazos, axila y flancos.', 'Grasa localizada frente a laxitud cutánea.', 'Postura, asimetrías y procedimientos previos.' ),
			'technology' => array( 'Endoláser corporal para unidades focales con indicación.', 'Modalidades de calidad cutánea cuando la laxitud es el componente tratable.', 'Plan de continuidad solo si cada zona aporta una mejora clínicamente justificable.' ),
			'limits' => array( 'La prenda y la postura pueden producir pliegues sin indicación médica.', 'No se añaden zonas por venta cruzada.', 'El exceso cutáneo importante puede requerir otra vía.' ),
			'seo_title' => 'Grasa espalda y zona del sujetador Madrid | NUVANX',
			'seo_desc' => 'Valoración de grasa y laxitud en espalda y zona del sujetador en Madrid, considerando continuidad con brazos y flancos.',
		),
		'muslos' => array(
			'slug' => 'flacidez-muslos-internos-subgluteo-madrid',
			'title' => 'Flacidez en muslos internos y región subglútea en Madrid',
			'lead' => 'Diagnosticamos muslo interno, cara externa, región subglútea y transición con rodilla para diferenciar grasa localizada, laxitud y celulitis estructural.',
			'assessment' => array( 'Muslo interno y externo.', 'Región subglútea y transición glúteo-muslo.', 'Laxitud, grasa localizada y celulitis estructural.', 'Continuidad con rodillas y cadera.' ),
			'technology' => array( 'Endoláser corporal cuando predomina grasa localizada focal.', 'EXION® Body cuando existe indicación de calidad cutánea.', 'Derivación o no intervención cuando el problema no corresponde a la oferta médico-estética.' ),
			'limits' => array( 'La celulitis requiere un diagnóstico distinto de la grasa localizada.', 'La laxitud intensa o el exceso cutáneo pueden requerir cirugía.', 'No se promete una forma estándar de los muslos.' ),
			'seo_title' => 'Flacidez muslos internos y subglúteo Madrid | NUVANX',
			'seo_desc' => 'Valoración de flacidez, grasa y continuidad en muslos internos y región subglútea en Madrid dentro de Contour Architecture™.',
		),
		'rodillas' => array(
			'slug' => 'tratamiento-rodillas-grasa-flacidez-madrid',
			'title' => 'Grasa localizada y flacidez en rodillas en Madrid',
			'lead' => 'Valoramos la cara interna y superior de la rodilla dentro de la continuidad del muslo y la pierna, con atención a grasa localizada, laxitud y anatomía funcional.',
			'assessment' => array( 'Distribución focal de grasa alrededor de la rodilla.', 'Laxitud y grosor cutáneo.', 'Continuidad con muslo interno y pierna.', 'Asimetrías, edema y antecedentes vasculares.' ),
			'technology' => array( 'Modalidad láser o térmica solo cuando la anatomía permite una indicación segura.', 'Plan combinado con muslo interno únicamente por continuidad clínica.', 'Derivación cuando el volumen corresponde a edema, articulación u otra causa.' ),
			'limits' => array( 'No se trata dolor articular ni patología vascular.', 'La zona exige una indicación focal y parámetros conservadores.', 'No se promete eliminar todo el volumen visible.' ),
			'seo_title' => 'Grasa localizada y flacidez rodillas Madrid | NUVANX',
			'seo_desc' => 'Valoración de grasa localizada y flacidez en rodillas en Madrid, diferenciando tejido estético de causas articulares, vasculares o edema.',
		),
		'male-contour' => array(
			'slug' => 'contorno-corporal-masculino-madrid',
			'title' => 'Contorno corporal masculino en Madrid',
			'lead' => 'Diseñamos planes para abdomen, cintura, pecho, espalda o perfil masculino según distribución de grasa, calidad cutánea, proporción y objetivos individuales.',
			'assessment' => array( 'Abdomen, cintura y espalda.', 'Pecho y relación con el torso.', 'Mandíbula y perfil cuando la consulta es facial.', 'Grasa localizada, laxitud y objetivos anatómicos.' ),
			'technology' => array( 'Endoláser corporal o Endolift® según zona e indicación.', 'EXION® Body o Face cuando la calidad tisular forma parte del plan.', 'Medicina inyectable o derivación cuando el componente principal no es grasa o laxitud.' ),
			'limits' => array( 'No se ofrece una definición abdominal artificial o garantizada.', 'El tratamiento focal no sustituye pérdida de peso, entrenamiento ni cirugía cuando esta es la vía adecuada.', 'Cada zona se presupuesta solo si tiene indicación documentada.' ),
			'seo_title' => 'Contorno corporal masculino Madrid | NUVANX',
			'seo_desc' => 'Contorno corporal masculino en Madrid para abdomen, cintura, espalda o perfil, con diagnóstico y tecnología seleccionada tras valoración.',
		),
	);
}

function nvx_signature_phase2_current_key(): ?string {
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

function nvx_signature_phase_list( string $title, array $items, string $class = '' ): string {
	$html = '<section class="nvx-brand-section ' . esc_attr( $class ) . '"><h2>' . esc_html( $title ) . '</h2><ul class="nvx-check-list">';
	foreach ( $items as $item ) {
		$html .= '<li>' . esc_html( $item ) . '</li>';
	}
	return $html . '</ul></section>';
}

function nvx_signature_phase2_markup( array $page ): string {
	$html  = '<article class="nvx-brand-readable nvx-protocol-page nvx-shell">';
	$html .= '<header class="nvx-strategy-intro"><p class="nvx-brand-kicker">NUVANX CONTOUR ARCHITECTURE™</p>';
	$html .= '<h1 class="nvx-strategy-title">' . esc_html( $page['title'] ) . '</h1>';
	$html .= '<p class="nvx-brand-lead">' . esc_html( $page['lead'] ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">Solicitar valoración médica privada</a></p>';
	$html .= '<p class="nvx-brand-microcopy">La indicación, la tecnología, el número de sesiones, la recuperación y el presupuesto se confirman después de la exploración médica.</p></header>';
	$html .= nvx_signature_phase_list( 'Qué se valora', $page['assessment'] );
	$html .= '<section class="nvx-brand-section"><h2>Cómo se decide el plan</h2><p>El médico identifica el componente predominante, revisa zonas contiguas y descarta problemas que no deben abordarse con medicina estética. Solo entonces selecciona la modalidad y documenta alternativas, cuidados y seguimiento.</p><p><strong>Protocolo relacionado:</strong> NUVANX Contour Architecture™</p></section>';
	$html .= nvx_signature_phase_list( 'Tecnologías que pueden formar parte del plan', $page['technology'] );
	$html .= nvx_signature_phase_list( 'Límites y cuándo derivamos', $page['limits'], 'nvx-strategy-checklist nvx-strategy-checklist--no' );
	$html .= '<section class="nvx-brand-section"><h2>Tu primera valoración clínica</h2><p>La valoración revisa antecedentes, anatomía, tejido predominante, tratamientos previos y expectativas. Si no existe una indicación proporcionada, explicamos la alternativa, la derivación o la decisión de no intervenir.</p><p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">Iniciar valoración médica</a></p></section></article>';
	return $html;
}

function nvx_signature_phase2_content_filter( string $content ): string {
	if ( is_admin() || ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}
	$key = nvx_signature_phase2_current_key();
	return null === $key ? $content : nvx_signature_phase2_markup( nvx_signature_phase2_catalog()[ $key ] );
}
add_filter( 'the_content', 'nvx_signature_phase2_content_filter', 22 );

function nvx_signature_phase2_prepare_shell(): void {
	if ( null !== nvx_signature_phase2_current_key() ) {
		set_query_var( 'nvx_shell_skip_header', true );
	}
}
add_action( 'wp', 'nvx_signature_phase2_prepare_shell', 5 );

/** Publication-aware canonical Signature hierarchy. Eye Frame is retained. */
function nvx_signature_navigation_blueprint( array $blueprint ): array {
	foreach ( $blueprint as $index => $top ) {
		$label = isset( $top['label'] ) ? (string) $top['label'] : '';
		if ( 'Casos clínicos' === $label ) {
			$blueprint[ $index ]['slugs'] = array( 'casos-de-pacientes', 'casos-clinicos' );
		}
		if ( 'Protocolos Signature' !== $label || empty( $top['children'] ) || ! is_array( $top['children'] ) ) {
			continue;
		}
		foreach ( $top['children'] as $child_index => $child ) {
			$child_label = isset( $child['label'] ) ? (string) $child['label'] : '';
			if ( false !== stripos( $child_label, 'Contour Sculpt' ) || false !== stripos( $child_label, 'Contour Architecture' ) || false !== stripos( $child_label, 'Couture Sculpt' ) ) {
				$blueprint[ $index ]['children'][ $child_index ]['label'] = 'NUVANX Contour Architecture™';
				$blueprint[ $index ]['children'][ $child_index ]['slugs'] = array( 'remodelacion-corporal-laser-madrid' );
				$blueprint[ $index ]['children'][ $child_index ]['children'] = array(
					array( 'label' => 'Abdomen y flancos', 'slugs' => array( 'grasa-localizada-abdomen-flancos-madrid' ) ),
					array( 'label' => 'Brazos y axila', 'slugs' => array( 'flacidez-grasa-localizada-brazos-madrid' ) ),
					array( 'label' => 'Espalda y zona del sujetador', 'slugs' => array( 'grasa-espalda-zona-sujetador-madrid' ) ),
					array( 'label' => 'Muslos y región subglútea', 'slugs' => array( 'flacidez-muslos-internos-subgluteo-madrid' ) ),
					array( 'label' => 'Rodillas', 'slugs' => array( 'tratamiento-rodillas-grasa-flacidez-madrid' ) ),
					array( 'label' => 'Contorno masculino', 'slugs' => array( 'contorno-corporal-masculino-madrid' ) ),
				);
			}
		}
	}
	return $blueprint;
}
add_filter( 'nvx_navigation_primary_blueprint', 'nvx_signature_navigation_blueprint', 30 );

function nvx_signature_normalize_public_names( string $content ): string {
	return str_ireplace( array( 'Couture Sculpt™', 'NUVANX Contour Sculpt™', 'Contour Sculpt™' ), 'NUVANX Contour Architecture™', $content );
}
add_filter( 'the_content', 'nvx_signature_normalize_public_names', 219 );

/** SEO for Phase 1, Eye Frame and Phase 2. */
function nvx_signature_current_metadata(): ?array {
	if ( function_exists( 'nvx_protocol_pages_current_key' ) ) {
		$key = nvx_protocol_pages_current_key();
		if ( null !== $key && function_exists( 'nvx_protocol_pages_catalog' ) ) {
			$page = nvx_protocol_pages_catalog()[ $key ] ?? null;
			if ( is_array( $page ) ) {
				return array( 'slug' => $page['slug'], 'seo_title' => $page['seo_title'] ?? '', 'seo_desc' => $page['description'] ?? '' );
			}
		}
	}
	$key = nvx_signature_phase2_current_key();
	return null === $key ? null : nvx_signature_phase2_catalog()[ $key ];
}

function nvx_signature_seo_title( $title ) {
	$page = nvx_signature_current_metadata();
	return is_array( $page ) && ! empty( $page['seo_title'] ) ? $page['seo_title'] : $title;
}
add_filter( 'wpseo_title', 'nvx_signature_seo_title', 130 );
add_filter( 'pre_get_document_title', 'nvx_signature_seo_title', 130 );
add_filter( 'wpseo_opengraph_title', 'nvx_signature_seo_title', 130 );
add_filter( 'wpseo_twitter_title', 'nvx_signature_seo_title', 130 );

function nvx_signature_seo_description( $description ) {
	$page = nvx_signature_current_metadata();
	return is_array( $page ) && ! empty( $page['seo_desc'] ) ? $page['seo_desc'] : $description;
}
add_filter( 'wpseo_metadesc', 'nvx_signature_seo_description', 130 );
add_filter( 'wpseo_opengraph_desc', 'nvx_signature_seo_description', 130 );
add_filter( 'wpseo_twitter_description', 'nvx_signature_seo_description', 130 );

function nvx_signature_seo_url( $url ) {
	$page = nvx_signature_current_metadata();
	return is_array( $page ) ? home_url( '/' . trim( $page['slug'], '/' ) . '/' ) : $url;
}
add_filter( 'wpseo_canonical', 'nvx_signature_seo_url', 130 );
add_filter( 'wpseo_opengraph_url', 'nvx_signature_seo_url', 130 );

function nvx_signature_seo_robots( $robots ) {
	if ( null === nvx_signature_current_metadata() ) {
		return $robots;
	}
	return function_exists( 'nvx_seo_is_nonproduction_environment' ) && nvx_seo_is_nonproduction_environment() ? 'noindex, nofollow' : 'index, follow';
}
add_filter( 'wpseo_robots', 'nvx_signature_seo_robots', 130 );
