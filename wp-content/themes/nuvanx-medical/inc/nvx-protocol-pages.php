<?php
/**
 * Published NUVANX Protocol Signature pages.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Approved protocol pages keyed by protocol identifier. */
function nvx_protocol_pages_catalog(): array {
	return array(
		'couture-sculpt' => array(
			'slug'          => 'remodelacion-corporal-laser-madrid',
			'title'         => 'Remodelación corporal láser diseñada según tu anatomía.',
			'kicker'        => 'PROTOCOLO SIGNATURE NUVANX',
			'lead'          => 'NUVANX Contour Sculpt™ es nuestro sistema médico de diagnóstico y tratamiento por unidades anatómicas. Estudiamos la grasa localizada, la laxitud cutánea y la continuidad del contorno para diseñar un plan proporcionado y orientado a una evolución discreta.',
			'description'   => 'NUVANX Contour Sculpt™ articula esta visión: la tecnología se selecciona después de valorar anatomía, tejido predominante, transiciones entre zonas y límites clínicos.',
			'review_status' => 'approved_for_publication',
		),
		'post-maternity' => array(
			'slug'          => 'tratamiento-postparto-abdomen-contorno-corporal-madrid',
			'title'         => 'Tratamiento Postparto: Abdomen y Contorno Corporal en Madrid',
			'kicker'        => 'PROTOCOLO NUVANX',
			'lead'          => 'Diagnóstico médico del abdomen y el contorno posgestacional para diferenciar grasa subcutánea, laxitud cutánea, estrías, cicatriz y posibles alteraciones de la pared muscular.',
			'description'   => 'Después del embarazo no existe un único abdomen posparto. Cada componente requiere una valoración diferente y, en algunos casos, una derivación a fisioterapia especializada o cirugía.',
			'review_status' => 'approved_for_publication',
		),
	);
}

/** Identifies the configured protocol page for the current request. */
function nvx_protocol_pages_current_key(): ?string {
	if ( ! is_page() ) {
		return null;
	}

	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	foreach ( nvx_protocol_pages_catalog() as $key => $page ) {
		if ( $page['slug'] === $slug && 'approved_for_publication' === $page['review_status'] ) {
			return $key;
		}
	}
	return null;
}

/** Builds the NUVANX Contour Sculpt protocol page. */
function nvx_protocol_pages_contour_sculpt_markup( array $data ): string {
	$html  = '<article class="nvx-brand-readable nvx-protocol-page nvx-shell">';
	$html .= '<header class="nvx-strategy-intro">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html( $data['kicker'] ) . '</p>';
	$html .= '<h1 class="nvx-strategy-title">' . esc_html( $data['title'] ) . '</h1>';
	$html .= '<p class="nvx-brand-lead">' . esc_html( $data['lead'] ) . '</p>';
	$html .= '<p>' . esc_html( $data['description'] ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Solicitar valoración médica privada', 'nuvanx-medical' ) . '</a> <a class="nvx-btn nvx-btn--secondary" href="#zonas-tratamiento">' . esc_html__( 'Explorar zonas de tratamiento', 'nuvanx-medical' ) . '</a></p>';
	$html .= '<p class="nvx-brand-microcopy">' . esc_html__( 'La técnica, las zonas, la evolución y el presupuesto se determinan tras la exploración médica.', 'nuvanx-medical' ) . '</p>';
	$html .= '</header>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'No tratamos zonas aisladas. Diseñamos continuidad.', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'El abdomen no termina en el abdomen. El brazo se relaciona con la axila y el torso. La espalda, la cintura y los flancos forman una misma unidad visual. Por eso estudiamos cada zona valorando integración global, espacio negativo, proporción y asimetrías.', 'nuvanx-medical' ) . '</p>';
	$html .= '<div class="nvx-card-diagnostic-wrap"><h3>' . esc_html__( 'Cartografía Anatómica NUVANX', 'nuvanx-medical' ) . '</h3>';
	$html .= '<p>' . esc_html__( 'Antes de proponer tecnología, el médico analiza distribución de grasa localizada, calidad y capacidad de retracción de la piel, transición entre zonas, asimetrías y límites reales de una intervención médico-estética.', 'nuvanx-medical' ) . '</p></div>';
	$html .= '</section>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'Tres decisiones clínicas: Reducir, Redefinir, Retraer', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'La indicación se define según anatomía, tejido predominante y límites del procedimiento. Estas vías pueden utilizarse por separado o combinarse cuando existe justificación clínica.', 'nuvanx-medical' ) . '</p>';
	$html .= '<ul class="nvx-check-list">';
	$html .= '<li><strong>REDUCIR:</strong> ' . esc_html__( 'cuando predomina grasa localizada susceptible de tratamiento mediante energía térmica.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>REDEFINIR:</strong> ' . esc_html__( 'cuando el objetivo es mejorar la transición y proporción entre zonas adyacentes.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>RETRAER:</strong> ' . esc_html__( 'cuando existe indicación para actuar sobre laxitud y calidad del tejido.', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul></section>';

	$html .= '<section class="nvx-brand-section" id="zonas-tratamiento">';
	$html .= '<h2>' . esc_html__( 'Cartografía Anatómica: Zonas de tratamiento', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Aplicamos el sistema por unidades de contorno y justificamos cada combinación.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><strong>' . esc_html__( 'Abdomen y cintura', 'nuvanx-medical' ) . '</strong><br>' . esc_html__( 'Abdomen superior e inferior, flancos y espalda baja se valoran como una transición continua. Grasa y piel requieren diagnósticos distintos.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><strong>' . esc_html__( 'Torso superior', 'nuvanx-medical' ) . '</strong><br>' . esc_html__( 'Brazos, axila anterior y zona del sujetador se valoran como una unidad de continuidad.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><strong>' . esc_html__( 'Piernas y tren inferior', 'nuvanx-medical' ) . '</strong><br>' . esc_html__( 'Muslos internos y externos, región subglútea y rodillas se estudian según laxitud, grasa localizada y proporción.', 'nuvanx-medical' ) . '</p>';
	$html .= '</section>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'NUVANX Contour Sculpt™: El protocolo y la tecnología', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'NUVANX Contour Sculpt™ se articula a través del diagnóstico. El médico selecciona la modalidad que corresponde a la anatomía y al objetivo clínico, en lugar de depender de una única plataforma.', 'nuvanx-medical' ) . '</p>';
	$html .= '<ul class="nvx-check-list">';
	$html .= '<li><strong>Endoláser Corporal / Endolift®:</strong> ' . esc_html__( 'para abordar grasa localizada y laxitud cuando la exploración médica lo indique.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>EXION® Body:</strong> ' . esc_html__( 'para apoyar firmeza y calidad tisular según indicación y plan médico.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Protocolos combinados:', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'para integrar modalidades distintas cuando existe una justificación clínica documentada.', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul></section>';

	$html .= '<section class="nvx-brand-section nvx-strategy-checklist nvx-strategy-checklist--no">';
	$html .= '<h2>' . esc_html__( 'Cuándo no es el tratamiento adecuado', 'nuvanx-medical' ) . '</h2>';
	$html .= '<ul class="nvx-check-list nvx-check-list--no">';
	$html .= '<li>' . esc_html__( 'Cuando el objetivo es una pérdida general de peso.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Cuando existe un exceso importante de piel que requiere valoración quirúrgica.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Cuando existe sospecha de diástasis o hernia que requiera valoración o derivación específica.', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul></section>';

	$html .= '<section class="nvx-brand-section"><h2>' . esc_html__( 'Tu primera valoración clínica', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'El objetivo es definir una intervención proporcionada y médicamente defendible. Todo comienza con una valoración exhaustiva en Chamberí o Salamanca–Goya.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Solicitar valoración médica privada', 'nuvanx-medical' ) . '</a></p></section>';
	$html .= '</article>';
	return $html;
}

/** Builds the Post-Maternity Contour protocol page. */
function nvx_protocol_pages_post_maternity_markup( array $data ): string {
	$html  = '<article class="nvx-brand-readable nvx-protocol-page nvx-shell">';
	$html .= '<header class="nvx-strategy-intro">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html( $data['kicker'] ) . '</p>';
	$html .= '<h1 class="nvx-strategy-title">' . esc_html( $data['title'] ) . '</h1>';
	$html .= '<p class="nvx-brand-lead">' . esc_html( $data['lead'] ) . '</p>';
	$html .= '<p>' . esc_html( $data['description'] ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Solicitar valoración de viabilidad', 'nuvanx-medical' ) . '</a> <a class="nvx-btn nvx-btn--secondary" href="#alteraciones-posparto">' . esc_html__( 'Ver qué podemos valorar', 'nuvanx-medical' ) . '</a></p>';
	$html .= '</header>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'Por qué un tratamiento posparto genérico no es suficiente', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'El aumento de volumen o la pérdida de definición tras el embarazo no constituyen una sola alteración. Pueden coexistir grasa subcutánea, laxitud, estrías, cicatriz de cesárea, diástasis o exceso de piel. Aplicar una tecnología sin diferenciar el componente predominante puede no responder al problema real.', 'nuvanx-medical' ) . '</p>';
	$html .= '</section>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'El Protocolo NUVANX Post-Maternity Contour™', 'nuvanx-medical' ) . '</h2>';
	$html .= '<ol class="nvx-check-list">';
	$html .= '<li><strong>' . esc_html__( 'Diagnóstico anatómico diferencial:', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'se valora qué proporción corresponde a grasa subcutánea, laxitud cutánea, cicatriz, estrías o alteración muscular.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Selección tecnológica:', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'solo si existe indicación se plantea Endoláser corporal, EXION® Body, Láser CO₂ u otra modalidad disponible.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Presupuesto y planificación:', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'el plan se documenta por escrito e incluye tiempos orientativos, cuidados y seguimiento.', 'nuvanx-medical' ) . '</li>';
	$html .= '</ol></section>';

	$html .= '<section class="nvx-brand-section" id="alteraciones-posparto">';
	$html .= '<h2>' . esc_html__( 'Las alteraciones del posparto: qué podemos tratar y cuándo derivamos', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p><strong>' . esc_html__( 'Grasa subcutánea localizada', 'nuvanx-medical' ) . '</strong><br>' . esc_html__( 'Cuando predomina grasa bajo la piel y existe estabilidad de peso, se valora si una modalidad láser o térmica tiene indicación para esa zona.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><strong>' . esc_html__( 'Laxitud cutánea y calidad de piel', 'nuvanx-medical' ) . '</strong><br>' . esc_html__( 'La capacidad de retracción, el espesor y la calidad del tejido determinan si puede plantearse un protocolo de estimulación o retracción.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><strong>' . esc_html__( 'Estrías y cicatriz de cesárea', 'nuvanx-medical' ) . '</strong><br>' . esc_html__( 'Se valoran madurez, color, relieve, síntomas y fototipo antes de plantear Láser CO₂ u otras modalidades de superficie.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><strong>' . esc_html__( 'Diástasis, hernia o exceso importante de piel', 'nuvanx-medical' ) . '</strong><br>' . esc_html__( 'Cuando el componente principal es muscular, existe sospecha de hernia o el exceso de piel requiere cirugía, la medicina estética no sustituye la valoración especializada y se recomienda derivación.', 'nuvanx-medical' ) . '</p>';
	$html .= '</section>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'La valoración médica: el paso indispensable', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'La consulta revisa antecedentes, momento posparto, lactancia cuando corresponda, estabilidad de peso, pared abdominal, piel, grasa subcutánea, cicatrices y expectativas. Con esa información se explica qué puede tratarse, qué debe esperar y qué requiere derivación.', 'nuvanx-medical' ) . '</p>';
	$html .= '</section>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'Preguntas frecuentes', 'nuvanx-medical' ) . '</h2>';
	$html .= '<h3>' . esc_html__( '¿Cuándo puede realizarse una valoración?', 'nuvanx-medical' ) . '</h3>';
	$html .= '<p>' . esc_html__( 'El momento se individualiza. Habitualmente se espera a que la recuperación inicial haya avanzado, el peso sea estable y los tejidos hayan tenido tiempo de evolucionar. La lactancia, los antecedentes y el procedimiento considerado también influyen.', 'nuvanx-medical' ) . '</p>';
	$html .= '<h3>' . esc_html__( '¿Se puede valorar la cicatriz de cesárea?', 'nuvanx-medical' ) . '</h3>';
	$html .= '<p>' . esc_html__( 'Sí. Se revisan madurez, textura, color, relieve y síntomas. La indicación depende del estado de la cicatriz y de la tecnología disponible.', 'nuvanx-medical' ) . '</p>';
	$html .= '<h3>' . esc_html__( '¿Qué ocurre si hay diástasis?', 'nuvanx-medical' ) . '</h3>';
	$html .= '<p>' . esc_html__( 'Si la exploración sugiere una diástasis significativa o una alteración de la pared abdominal, se recomienda valoración especializada. Un tratamiento estético sobre grasa o piel no corrige el componente muscular.', 'nuvanx-medical' ) . '</p>';
	$html .= '</section>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'TU PRIMERA VALORACIÓN CLÍNICA', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2>' . esc_html__( 'Una consulta médica para determinar la indicación de tu caso.', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Evaluamos el caso en nuestras clínicas autorizadas de Chamberí o Salamanca–Goya y documentamos el plan cuando existe una indicación médico-estética.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Iniciar valoración', 'nuvanx-medical' ) . '</a></p>';
	$html .= '</section></article>';
	return $html;
}

/** Dispatches the markup for one approved protocol page. */
function nvx_protocol_pages_markup( string $key, array $data ): string {
	if ( 'couture-sculpt' === $key ) {
		return nvx_protocol_pages_contour_sculpt_markup( $data );
	}
	if ( 'post-maternity' === $key ) {
		return nvx_protocol_pages_post_maternity_markup( $data );
	}
	return '';
}

/** Replaces the content of a matching approved protocol page. */
function nvx_protocol_pages_content_filter( string $content ): string {
	if ( is_admin() || ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}

	$key = nvx_protocol_pages_current_key();
	if ( null === $key ) {
		return $content;
	}

	$data   = nvx_protocol_pages_catalog()[ $key ];
	$markup = nvx_protocol_pages_markup( $key, $data );
	return '' === $markup ? $content : $markup;
}
add_filter( 'the_content', 'nvx_protocol_pages_content_filter', 21 );
