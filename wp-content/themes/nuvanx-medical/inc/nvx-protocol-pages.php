<?php
/**
 * Published NUVANX Protocol Signature pages.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides the catalog of approved NUVANX protocol pages.
 *
 * Incomplete or retired protocols must not be added until their full clinical,
 * editorial and legal publication contract is approved.
 *
 * @return array<string, array<string, string>> Protocol page data keyed by protocol identifier.
 */
function nvx_protocol_pages_catalog(): array {
	return array(
		'couture-sculpt' => array(
			'slug'          => 'remodelacion-corporal-laser-madrid',
			'title'         => 'Remodelación corporal láser diseñada según tu anatomía.',
			'kicker'        => 'PROTOCOLO SIGNATURE NUVANX',
			'lead'          => 'NUVANX Contour Architecture™ es nuestro sistema médico de diagnóstico y tratamiento por unidades anatómicas. Estudiamos la grasa localizada, la laxitud cutánea y la continuidad del contorno para diseñar un plan proporcionado y de absoluta discreción.',
			'description'   => 'Entendemos la medicina estética como un ejercicio de quiet luxury. Un abordaje silencioso, mínimamente invasivo y sin huellas quirúrgicas evidentes, donde la elegancia de la transición es tan importante como el resultado final. Nuestro protocolo comercial estrella, Couture Sculpt™, materializa esta visión.',
			'review_status' => 'approved_for_publication',
		),
	);
}

/**
 * Identifies the configured protocol page for the current request.
 *
 * @return string|null The matching protocol catalog key, or null when the request is not a configured protocol page.
 */
function nvx_protocol_pages_current_key(): ?string {
	if ( ! is_page() ) {
		return null;
	}

	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	foreach ( nvx_protocol_pages_catalog() as $key => $page ) {
		if ( $page['slug'] === $slug ) {
			return $key;
		}
	}

	return null;
}

/**
 * Builds the HTML markup for the Couture Sculpt protocol page.
 *
 * @param array $data Protocol page content, including the kicker, title, lead and description.
 * @return string The generated Couture Sculpt protocol page markup.
 */
function nvx_protocol_pages_couture_sculpt_markup( array $data ): string {
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
	$html .= '<p>' . esc_html__( 'El abdomen no termina en el abdomen. El brazo no puede valorarse sin su relación con la axila y el torso. La espalda, la cintura y los flancos forman una misma unidad visual. Por eso, en NUVANX estudiamos cada zona valorando su integración global, el espacio negativo y la proporción. El objetivo no es cambiar tu cuerpo por otro, sino perfeccionar su arquitectura con la mayor naturalidad.', 'nuvanx-medical' ) . '</p>';
	$html .= '<div class="nvx-card-diagnostic-wrap">';
	$html .= '<h3>' . esc_html__( 'Cartografía Anatómica NUVANX', 'nuvanx-medical' ) . '</h3>';
	$html .= '<p>' . esc_html__( 'Antes de proponer tecnología, el médico analiza la distribución de la grasa localizada, la calidad y capacidad de retracción de la piel, la proporción entre zonas, las asimetrías anatómicas y los límites reales de una intervención médico-estética.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';
	$html .= '</section>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'Tres decisiones clínicas: Reducir, Redefinir, Retraer', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'La elegancia de un buen resultado reside en la indicación precisa. Nuestro protocolo clasifica tu necesidad clínica en tres posibles vías de actuación:', 'nuvanx-medical' ) . '</p>';
	$html .= '<ul class="nvx-check-list">';
	$html .= '<li><strong>REDUCIR:</strong> ' . esc_html__( 'Cuando predomina grasa localizada susceptible de tratamiento mediante energía térmica.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>REDEFINIR:</strong> ' . esc_html__( 'Cuando el objetivo es mejorar la transición y proporción entre zonas adyacentes.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>RETRAER:</strong> ' . esc_html__( 'Cuando existe una indicación clara para actuar sobre la laxitud cutánea y densificar el tejido.', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul>';
	$html .= '</section>';

	$html .= '<section class="nvx-brand-section" id="zonas-tratamiento">';
	$html .= '<h2>' . esc_html__( 'Cartografía Anatómica: Zonas de tratamiento', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Aplicamos nuestro sistema por unidades de contorno, justificando cada combinación.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><strong>' . esc_html__( 'Abdomen y Cintura', 'nuvanx-medical' ) . '</strong><br>' . esc_html__( 'El contorno no termina donde termina el abdomen. Grasa y piel requieren diagnósticos distintos.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><strong>' . esc_html__( 'Torso Superior', 'nuvanx-medical' ) . '</strong><br>' . esc_html__( 'Brazos, axila anterior y zona del sujetador. Buscamos que la manga caiga limpia.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><strong>' . esc_html__( 'Piernas y Tren Inferior', 'nuvanx-medical' ) . '</strong><br>' . esc_html__( 'Estudiamos la continuidad, laxitud y proporción para un afinamiento elegante.', 'nuvanx-medical' ) . '</p>';
	$html .= '</section>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'Couture Sculpt™: El protocolo y la tecnología', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Toda la filosofía de NUVANX Contour Architecture™ se vehicula a través de nuestro protocolo médico Couture Sculpt™. En lugar de depender de una sola máquina, el médico prescribe la tecnología exacta que requiere tu anatomía.', 'nuvanx-medical' ) . '</p>';
	$html .= '<ul class="nvx-check-list">';
	$html .= '<li><strong>Endoláser Corporal / Endolift®:</strong> ' . esc_html__( 'Para una licuefacción térmica de la grasa localizada y retracción profunda.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>Radiofrecuencia Fraccionada (EXION® Body):</strong> ' . esc_html__( 'Para estimular la calidad del tejido y la firmeza dérmica.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Protocolos combinados:', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'Diseñados para potenciar la recuperación tisular cuando existe indicación médica.', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul>';
	$html .= '</section>';

	$html .= '<section class="nvx-brand-section nvx-strategy-checklist nvx-strategy-checklist--no">';
	$html .= '<h2>' . esc_html__( 'Cuándo no es el tratamiento adecuado', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'El verdadero lujo es la honestidad. No aplicaremos el protocolo en los siguientes casos:', 'nuvanx-medical' ) . '</p>';
	$html .= '<ul class="nvx-check-list nvx-check-list--no">';
	$html .= '<li>' . esc_html__( 'Cuando el objetivo del paciente es una pérdida general de peso.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Cuando existe un exceso importante de piel que requiere cirugía mayor.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Si existe una diástasis o hernia abdominal severa no tratada.', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul>';
	$html .= '</section>';

	$html .= '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html__( 'Tu primera valoración clínica', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'El objetivo es mejorar la continuidad de tu contorno con una intervención proporcionada, elegante y médicamente defendible. Todo comienza con una valoración exhaustiva en Chamberí o Goya.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Solicitar valoración médica privada', 'nuvanx-medical' ) . '</a></p>';
	$html .= '</section>';
	$html .= '</article>';

	return $html;
}

/**
 * Replaces the content of a matching approved protocol page.
 *
 * @param string $content The original page content.
 * @return string The generated protocol markup or the original content when the page is not applicable.
 */
function nvx_protocol_pages_content_filter( string $content ): string {
	if ( is_admin() || ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}

	$key = nvx_protocol_pages_current_key();
	if ( ! $key ) {
		return $content;
	}

	$data = nvx_protocol_pages_catalog()[ $key ];
	return nvx_protocol_pages_couture_sculpt_markup( $data );
}
add_filter( 'the_content', 'nvx_protocol_pages_content_filter', 21 );
