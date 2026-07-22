<?php
/**
 * Protocol pages mapped to the universal 13-point structure.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'NVX_CALIDAD_CUTANEA' ) ) {
	define( 'NVX_CALIDAD_CUTANEA', 'CALIDAD CUTÁNEA NUVANX' );
}

/**
 * Approved body protocol catalogue.
 *
 * Eye Frame™ is deliberately absent. Its former route is governed as a draft
 * and redirected until an independent medical, legal, SEO and capacity review.
 *
 * @return array<string,array<string,mixed>>
 */
function nvx_protocol_body_catalog(): array {
	return array(
		'contour-architecture' => array(
			'slug'          => 'remodelacion-corporal-laser-madrid',
			'seo_title'     => 'Remodelación corporal láser en Madrid | NUVANX Contour Architecture',
			'description'   => 'NUVANX Contour Architecture™: remodelación corporal láser por unidades anatómicas para grasa localizada, laxitud y continuidad tras valoración médica.',
			'kicker'        => 'PROTOCOLO SIGNATURE NUVANX',
			'h1'            => 'Remodelación corporal láser diseñada según tu anatomía.',
			'lead'          => 'NUVANX Contour Architecture™: El protocolo y la tecnología. La valoración estudia unidades anatómicas relacionadas antes de seleccionar una modalidad.',
			'diagnosis'     => 'Tres decisiones clínicas: Reducir, Redefinir, Retraer. Cada objetivo se considera por separado según grasa subcutánea, laxitud, calidad cutánea, continuidad del contorno, antecedentes y expectativas proporcionadas.',
			'mechanism'     => 'La tecnología se selecciona después del diagnóstico. Endoláser corporal, EXION® Body y otras modalidades autorizadas pueden formar parte del plan cuando existe una indicación documentada para el tejido y la zona evaluados.',
			'indications'   => array(
				'Cartografía Anatómica: Zonas de tratamiento como abdomen, flancos, espalda, brazos, muslos o rodillas se valoran junto con sus transiciones.',
				'Grasa localizada subcutánea susceptible de un abordaje focal.',
				'Laxitud o alteración de la calidad tisular con una expectativa de mejora razonable.',
				'Pérdida de continuidad entre zonas contiguas cuando cada unidad tiene indicación propia.',
			),
			'precautions'   => array(
				'Cuándo no es el tratamiento adecuado: pérdida general de peso, grasa visceral o una expectativa de transformación corporal global.',
				'El exceso importante de piel puede requerir valoración quirúrgica.',
				'La sospecha de diástasis, hernia, enfermedad activa o alteración vascular exige valoración específica o derivación.',
				'No se añaden zonas por venta cruzada ni se presentan niveles de planificación como paquetes cerrados.',
			),
			'process'       => array(
				'Historia clínica, exploración y registro fotográfico estandarizado.',
				'Cartografía de las unidades anatómicas y clasificación del componente predominante.',
				'Explicación de alternativas, límites, recuperación orientativa y riesgos aplicables.',
				'Plan escrito con modalidad, zonas justificadas, cuidados, revisiones e inversión.',
			),
			'faqs'          => array(
				array(
					'q' => '¿Se utiliza la misma tecnología en todos los casos?',
					'a' => 'No. La modalidad se decide después de la exploración. Un caso puede requerir una herramienta, una secuencia combinada, espera clínica, derivación o ninguna intervención.',
				),
				array(
					'q' => '¿Cuántas zonas deben tratarse?',
					'a' => 'Solo las que tengan una indicación documentada. Estudiar continuidad anatómica no implica convertir zonas contiguas en un paquete comercial.',
				),
			),
			'review_status' => 'approved_for_publication',
		),
		'post-maternity' => array(
			'slug'          => 'tratamiento-postparto-abdomen-contorno-corporal-madrid',
			'seo_title'     => 'Tratamiento postparto abdomen Madrid | NUVANX',
			'description'   => 'Valoración médica del abdomen posgestacional para diferenciar grasa localizada, laxitud, cicatriz y diástasis antes de indicar tratamiento o derivación.',
			'kicker'        => 'NUVANX POST-MATERNITY CONTOUR™',
			'h1'            => 'Tratamiento Postparto: Abdomen y Contorno Corporal en Madrid',
			'lead'          => 'Por qué un tratamiento posparto genérico no es suficiente: después del embarazo pueden coexistir cambios de grasa, piel, cicatriz, estrías y pared abdominal que requieren decisiones distintas.',
			'diagnosis'     => 'La exploración diferencia grasa subcutánea, laxitud, estrías, cicatriz de cesárea, diástasis, hernia y exceso de piel. La medicina estética no sustituye fisioterapia, cirugía ni otra valoración especializada cuando el componente principal queda fuera de su alcance.',
			'mechanism'     => 'El Protocolo NUVANX Post-Maternity Contour™ organiza la valoración por componentes. La modalidad, el momento clínico, los cuidados y las posibles derivaciones se definen de forma individual.',
			'indications'   => array(
				'Las alteraciones del posparto: qué podemos tratar y cuándo derivamos.',
				'Grasa localizada y continuidad entre abdomen, flancos y espalda cuando existe indicación.',
				'Laxitud, estrías, calidad cutánea o cicatriz madura susceptibles de valoración médico-estética.',
			),
			'precautions'   => array(
				'Diástasis significativa, hernia o síntomas de la pared abdominal requieren evaluación específica.',
				'El exceso importante de piel puede tener indicación quirúrgica.',
				'El momento del plan depende de recuperación, lactancia, estabilidad de peso, antecedentes y modalidad considerada.',
			),
			'process'       => array(
				'Revisión de antecedentes obstétricos, recuperación, lactancia y estabilidad de peso.',
				'Exploración de pared abdominal, piel, grasa subcutánea, cicatrices y zonas contiguas.',
				'Explicación por escrito de lo tratable, lo que debe esperar y lo que requiere derivación.',
			),
			'faqs'          => array(
				array(
					'q' => 'Preguntas frecuentes: ¿cuándo puede realizarse una valoración?',
					'a' => 'El momento se individualiza según recuperación, lactancia, estabilidad de peso, antecedentes y modalidad considerada.',
				),
				array(
					'q' => '¿Se puede valorar una cicatriz de cesárea?',
					'a' => 'Sí. Se revisan madurez, relieve, color, síntomas y fototipo antes de decidir si existe una opción médico-estética o conviene otra valoración.',
				),
			),
			'review_status' => 'approved_for_publication',
		),
	);
}

/** Compatibility function for code that expects the complete protocol catalogue. */
function nvx_protocol_pages_catalog(): array {
	return nvx_protocol_body_catalog();
}

/** Identifies the current approved protocol page. */
function nvx_protocol_pages_current_key(): ?string {
	if ( ! is_page() ) {
		return null;
	}

	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	foreach ( nvx_protocol_pages_catalog() as $key => $page ) {
		if ( $page['slug'] === $slug && 'approved_for_publication' === ( $page['review_status'] ?? '' ) ) {
			return $key;
		}
	}

	return null;
}

/** Dispatches the universal 13-point markup for one approved protocol page. */
function nvx_protocol_pages_markup( array $data ): string {
	if ( empty( $data ) || ! function_exists( 'nvx_render_13_point_matrix' ) ) {
		return '';
	}

	return nvx_render_13_point_matrix( $data );
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

	$data   = nvx_protocol_pages_catalog()[ $key ] ?? array();
	$markup = nvx_protocol_pages_markup( $data );
	return '' === $markup ? $content : $markup;
}
add_filter( 'the_content', 'nvx_protocol_pages_content_filter', 21 );

/** Renders the Post-Maternity protocol page markup, including its frequently asked questions. */
function nvx_protocol_pages_post_maternity_markup(): string {
	$data = nvx_protocol_pages_catalog()['post-maternity'] ?? array();
	return nvx_protocol_pages_markup( $data );
}
