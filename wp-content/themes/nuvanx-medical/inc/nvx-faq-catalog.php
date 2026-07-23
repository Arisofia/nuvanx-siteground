<?php
/**
 * NUVANX · FAQ Catalog — Single source of truth
 *
 * Returns the canonical FAQ array used by:
 *   1. The visible HTML FAQ block (frontend)
 *   2. The Yoast FAQPage JSON-LD schema node
 *
 * Usage:
 *   $faqs = nvx_get_faq_catalog();
 *
 * To add or edit an FAQ entry, edit this file only.
 * Do NOT maintain separate copies in the Yoast SEO settings
 * or hardcode FAQ HTML elsewhere.
 *
 * @package nuvanx-medical
 * @version 2.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns the FAQ catalog as an array of ['q' => string, 'a' => string].
 *
 * @return array<int, array{q: string, a: string}>
 */
function nvx_get_faq_catalog(): array {
	return [
		[
			'q' => '¿Cómo se solicita una valoración médica en NUVANX?',
			'a' => 'Puedes solicitar una valoración médica para revisar tu caso, confirmar si existe indicación y conocer las alternativas antes de decidir un tratamiento.',
		],
		[
			'q' => '¿Cuánto cuesta el Endolift® facial en NUVANX?',
			'a' => 'El presupuesto depende de la zona, la indicación y el plan médico. Se documenta por escrito tras la valoración anatómica presencial.',
		],
		[
			'q' => '¿Cuánto duran los resultados del Endolift®?',
			'a' => 'La evolución depende de la indicación, la zona tratada, la calidad de piel y los hábitos. En consulta se explican los límites y el seguimiento esperable para tu caso; no existe una duración universal.',
		],
		[
			'q' => '¿Cuántas sesiones necesita el láser CO₂ fraccionado?',
			'a' => 'El número de sesiones depende de la indicación, el fototipo, la profundidad y la respuesta clínica. El plan y el presupuesto se definen después de la valoración.',
		],
		[
			'q' => '¿Cómo se elige la tecnología en NUVANX?',
			'a' => 'La tecnología y sus parámetros se eligen tras la exploración, según la zona, el objetivo, los antecedentes y la indicación médica. No se recomienda una plataforma por tendencia o por una promesa comercial.',
		],
		[
			'q' => '¿Qué ocurre si se valora una plataforma como EXION® BTL?',
			'a' => 'La disponibilidad, el aplicador y la indicación se confirman en consulta. El equipo explica qué alternativa puede tener sentido para el caso y los cuidados asociados antes de que decidas.',
		],
		[
			'q' => '¿Cómo sé qué tratamiento necesito?',
			'a' => 'Solo el médico puede determinarlo tras exploración. En NUVANX el diagnóstico evalúa piel, historial y objetivos antes de indicar Endolift®, CO₂, EXION®, IPL o una combinación. No se decide por teléfono ni por formulario.',
		],
		[
			'q' => '¿Implican los tratamientos tiempo de recuperación?',
			'a' => 'Depende del protocolo, la zona, los parámetros y la respuesta individual. Antes de decidir se explican los cuidados, los posibles efectos y el período de recuperación esperable.',
		],
		[
			'q' => '¿Cuál es la diferencia entre NUVANX y una clínica de estética convencional?',
			'a' => 'NUVANX son centros sanitarios autorizados (CS20144 y CS20073) con equipo médico colegiado. Los tratamientos requieren indicación médica previa: si no está indicado para tu caso, no se realiza.',
		],
		[
			'q' => '¿Dónde están las clínicas NUVANX en Madrid?',
			'a' => 'Dos sedes: Chamberí (C/ Fernández de la Hoz 4, CS20144) y Salamanca–Goya (C/ Fernán González 26, CS20073). Puedes elegir sede al reservar la valoración.',
		],
		[
			'q' => '¿Quién forma el equipo médico de NUVANX?',
			'a' => 'Dirección médica del Dr. José Javier Rivera Tejeda (ICOMEM 282864786), con Dra. Ivon Yamileth Rivera Deras (well-aging / geriatría preventiva, FEA Hospital La Paz) y Dr. Fabio Augusto Quiñónez Bareiro (PhD, geriatría y paciente complejo), además del resto del equipo clínico.',
		],
	];
}

/**
 * Renders the FAQ section using the canonical FAQ catalog.
 */
function nvx_render_faq_block(): void {
	$faqs = nvx_get_faq_catalog();
	if ( empty( $faqs ) ) {
		return;
	}
	echo '<section class="nvx-faq" aria-labelledby="nvx-faq-heading">';
	echo '<h2 id="nvx-faq-heading">' . esc_html__( 'Preguntas frecuentes', 'nuvanx-medical' ) . '</h2>';
	echo '<p class="nvx-faq__intro">' . esc_html__( 'Información clara antes de decidir', 'nuvanx-medical' ) . '</p>';
	echo '<dl class="nvx-faq__list">';
	foreach ( $faqs as $item ) {
		echo '<div class="nvx-faq__item">';
		echo '<dt class="nvx-faq__question">' . esc_html( $item['q'] ) . '</dt>';
		echo '<dd class="nvx-faq__answer">' . esc_html( $item['a'] ) . '</dd>';
		echo '</div>';
	}
	echo '</dl>';
	echo '</section>';
}

/**
 * Builds the FAQPage JSON-LD schema for the site's FAQ catalog.
 *
 * @return array<string, mixed> The FAQPage schema with its canonical identifier and questions.
 */
function nvx_get_faqpage_schema(): array {
	$faqs        = nvx_get_faq_catalog();
	$main_entity = [];
	foreach ( $faqs as $item ) {
		$main_entity[] = [
			'@type'          => 'Question',
			'name'           => $item['q'],
			'acceptedAnswer' => [
				'@type' => 'Answer',
				'text'  => $item['a'],
			],
		];
	}
	return [
		'@type'      => 'FAQPage',
		'@id'        => home_url( '/#faqpage' ),
		'mainEntity' => $main_entity,
	];
}

/**
 * Inject FAQPage node into Yoast SEO graph on the front page.
 */
function nvx_inject_faqpage_schema_graph( array $data ): array {
	if ( is_front_page() ) {
		$data[] = nvx_get_faqpage_schema();
	}
	return $data;
}
# La homepage FAQ y sus FAQs en Schema ya se gobiernan vía nvx_home_faq_v2_schema_graph.
# add_filter( 'wpseo_schema_graph', 'nvx_inject_faqpage_schema_graph' );
