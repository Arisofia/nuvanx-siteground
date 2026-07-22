<?php
/**
 * Governed anatomical solution pages — Phase 2.
 *
 * These pages explain one anatomical concern without prescribing a technology
 * before medical assessment. Routes outside the approved roadmap remain out of
 * this public catalogue.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @return array<string,array<string,mixed>> */
function nvx_anatomical_pages_catalog(): array {
	return array(
		'abdomen-flancos' => array(
			'slug'        => 'grasa-localizada-abdomen-flancos-madrid',
			'seo_title'   => 'Grasa localizada abdomen y flancos Madrid | NUVANX',
			'description' => 'Valoración médica de abdomen y flancos para diferenciar grasa subcutánea, laxitud, pared abdominal y límites del tratamiento focal.',
			'kicker'      => 'SOLUCIONES · CONTORNO CORPORAL',
			'h1'          => 'Abdomen y flancos: grasa, piel y pared abdominal son diagnósticos distintos.',
			'lead'        => 'El contorno no termina donde termina el abdomen. Valoramos abdomen superior e inferior, cintura, flancos y espalda baja como una continuidad anatómica.',
			'diagnosis'   => 'La exploración diferencia grasa subcutánea, grasa visceral, laxitud, cicatrices y posibles alteraciones de la pared abdominal. Cada componente tiene un alcance terapéutico diferente.',
			'objectives'  => array(
				'Valorar grasa localizada susceptible de tratamiento focal.',
				'Estudiar laxitud y calidad cutánea antes de plantear una modalidad térmica o de superficie.',
				'Definir si conviene una unidad focal o una planificación de continuidad abdomen–flancos–espalda baja.',
			),
			'limits'      => array(
				'No trata grasa visceral ni sustituye una estrategia de pérdida general de peso.',
				'La diástasis, hernia o exceso importante de piel requieren valoración específica y pueden precisar derivación.',
				'No se promete una reducción concreta antes de explorar el tejido.',
			),
			'process'     => array(
				'Historia clínica y exploración de abdomen, flancos, piel y pared abdominal.',
				'Cartografía de unidades anatómicas y selección de prioridades.',
				'Plan escrito con alternativas, cuidados, revisiones e inversión.',
			),
		),
		'brazos' => array(
			'slug'        => 'flacidez-grasa-localizada-brazos-madrid',
			'seo_title'   => 'Flacidez y grasa localizada brazos Madrid | NUVANX',
			'description' => 'Valoración médica de brazos y axila para diferenciar grasa localizada, laxitud y calidad cutánea antes de indicar tratamiento.',
			'kicker'      => 'SOLUCIONES · CONTORNO CORPORAL',
			'h1'          => 'El brazo se valora junto con la axila y el torso.',
			'lead'        => 'La apariencia del brazo puede depender de grasa localizada, laxitud, calidad de piel o de la transición con la axila anterior y el torso.',
			'diagnosis'   => 'Se examinan distribución del tejido, reserva cutánea, asimetrías y continuidad con las zonas adyacentes. Una intervención focal solo se plantea cuando puede mantener una transición coherente.',
			'objectives'  => array(
				'Valorar grasa localizada y laxitud en cara interna y posterior del brazo.',
				'Estudiar continuidad con axila anterior y torso superior.',
				'Determinar si el alcance médico-estético es suficiente o existe indicación quirúrgica.',
			),
			'limits'      => array(
				'El exceso importante de piel puede no responder de forma adecuada a tecnologías focales.',
				'No se indica una combinación de zonas sin una justificación anatómica.',
				'La evolución depende del tejido y no puede garantizarse antes de la valoración.',
			),
			'process'     => array(
				'Exploración bilateral de brazos, axilas y torso.',
				'Clasificación del componente predominante y del grado de laxitud.',
				'Plan focal o de continuidad con seguimiento médico definido.',
			),
		),
		'espalda-sujetador' => array(
			'slug'        => 'grasa-espalda-zona-sujetador-madrid',
			'seo_title'   => 'Grasa espalda y zona del sujetador Madrid | NUVANX',
			'description' => 'Valoración médica de espalda, zona del sujetador y flancos para diferenciar grasa, laxitud y efecto de la prenda.',
			'kicker'      => 'SOLUCIONES · CONTORNO CORPORAL',
			'h1'          => 'Espalda, sujetador y flancos forman una misma arquitectura.',
			'lead'        => 'Los pliegues pueden depender de grasa localizada, laxitud, continuidad con los flancos o del ajuste de la prenda. La exploración separa estos componentes.',
			'diagnosis'   => 'Se valora espalda superior e inferior, zona del sujetador, flancos y brazos para evitar tratar una prominencia sin comprender la transición completa.',
			'objectives'  => array(
				'Identificar grasa localizada susceptible de abordaje focal.',
				'Valorar laxitud y calidad cutánea en una zona de pliegues y fricción.',
				'Diseñar una transición proporcionada con flancos y brazos cuando corresponde.',
			),
			'limits'      => array(
				'Una prenda inadecuada puede modificar el aspecto y debe diferenciarse del tejido tratable.',
				'No se promete eliminar todos los pliegues ni modificar una anatomía completa mediante una sola zona.',
				'La indicación depende del espesor, la piel y los antecedentes.',
			),
			'process'     => array(
				'Exploración de pie y revisión de la continuidad posterior y lateral.',
				'Cartografía de zonas tratables y exclusión de prominencias no susceptibles de tratamiento.',
				'Plan documentado con límites y seguimiento.',
			),
		),
		'muslos-subgluteo' => array(
			'slug'        => 'flacidez-muslos-internos-subgluteo-madrid',
			'seo_title'   => 'Flacidez muslos internos y región subglútea Madrid | NUVANX',
			'description' => 'Valoración de muslos internos, externos y región subglútea para diferenciar grasa localizada, laxitud y celulitis estructural.',
			'kicker'      => 'SOLUCIONES · CONTORNO CORPORAL',
			'h1'          => 'No tratamos “piernas”: estudiamos continuidad, laxitud y proporción.',
			'lead'        => 'Muslo interno, cara externa, región subglútea y rodilla pueden compartir una transición visual, pero no necesariamente el mismo diagnóstico.',
			'diagnosis'   => 'La exploración diferencia grasa localizada, laxitud, celulitis estructural, asimetrías y calidad cutánea. Estas condiciones requieren mecanismos distintos.',
			'objectives'  => array(
				'Valorar unidades concretas del muslo y su relación con cadera, glúteo y rodilla.',
				'Diferenciar grasa, laxitud y alteraciones de superficie antes de seleccionar tecnología.',
				'Priorizar una intervención proporcionada sin perseguir una forma corporal estándar.',
			),
			'limits'      => array(
				'La celulitis no equivale a grasa localizada ni responde al mismo abordaje.',
				'El exceso importante de piel puede requerir otra vía terapéutica.',
				'No todas las unidades deben tratarse en el mismo plan.',
			),
			'process'     => array(
				'Exploración de pie y comparación bilateral.',
				'Mapa de grasa, laxitud, superficie y transiciones anatómicas.',
				'Plan por prioridades con seguimiento fotográfico consistente.',
			),
		),
		'rodillas' => array(
			'slug'        => 'tratamiento-rodillas-grasa-flacidez-madrid',
			'seo_title'   => 'Grasa y flacidez en rodillas Madrid | NUVANX',
			'description' => 'Valoración médica de la región de las rodillas para diferenciar grasa localizada, laxitud, edema y continuidad con el muslo.',
			'kicker'      => 'SOLUCIONES · CONTORNO CORPORAL',
			'h1'          => 'La región de la rodilla exige precisión y expectativas proporcionadas.',
			'lead'        => 'Una prominencia alrededor de la rodilla puede depender de grasa localizada, laxitud, edema o de la transición con el muslo interno.',
			'diagnosis'   => 'La zona tiene poco margen anatómico y requiere diferenciar tejido tratable de estructuras normales, edema u otras alteraciones que no corresponden a medicina estética.',
			'objectives'  => array(
				'Valorar grasa localizada y calidad cutánea en la cara interna o superior de la rodilla.',
				'Estudiar continuidad con muslo interno y asimetrías.',
				'Descartar edema, alteraciones vasculares o problemas articulares que requieren otra valoración.',
			),
			'limits'      => array(
				'No se trata dolor articular, edema de causa médica ni alteraciones vasculares.',
				'La mejora posible suele ser focal y debe explicarse con prudencia.',
				'La indicación depende del espesor y de la seguridad de la zona.',
			),
			'process'     => array(
				'Exploración local y revisión de antecedentes vasculares o articulares.',
				'Comparación bilateral y valoración de continuidad con el muslo.',
				'Decisión de tratar, observar o derivar según el diagnóstico.',
			),
		),
		'contorno-masculino' => array(
			'slug'        => 'contorno-corporal-masculino-madrid',
			'seo_title'   => 'Contorno corporal masculino Madrid | NUVANX',
			'description' => 'Valoración del contorno masculino en abdomen, cintura, pecho, espalda o mandíbula según anatomía y objetivos individuales.',
			'kicker'      => 'SOLUCIONES · MEDICINA ESTÉTICA MASCULINA',
			'h1'          => 'El contorno masculino se planifica según anatomía, no según una plantilla.',
			'lead'        => 'Abdomen, cintura, pecho, espalda y mandíbula pueden requerir criterios de proporción y prioridades diferentes. El plan conserva rasgos individuales y evita imponer una forma estándar.',
			'diagnosis'   => 'Se diferencia grasa localizada, laxitud, calidad cutánea, distribución glandular o muscular y situaciones que requieren valoración médica específica.',
			'objectives'  => array(
				'Valorar unidades anatómicas concretas y su continuidad.',
				'Definir si el objetivo corresponde a grasa localizada, piel, soporte o superficie.',
				'Seleccionar un plan discreto y compatible con la anatomía masculina del paciente.',
			),
			'limits'      => array(
				'No sustituye una estrategia de pérdida de peso ni el tratamiento de una enfermedad.',
				'Una alteración mamaria, masa o síntoma requiere evaluación diagnóstica antes de cualquier procedimiento estético.',
				'No se promete definición muscular ni un patrón corporal concreto.',
			),
			'process'     => array(
				'Historia clínica y exploración de las unidades solicitadas y contiguas.',
				'Diagnóstico del componente predominante y explicación de alternativas.',
				'Plan escrito con seguimiento y límites individualizados.',
			),
		),
	);
}

/** Identify the approved anatomical page for the current request. */
function nvx_anatomical_pages_current_key(): ?string {
	if ( ! is_page() ) {
		return null;
	}
	$path = trim( (string) get_page_uri( get_queried_object_id() ), '/' );
	foreach ( nvx_anatomical_pages_catalog() as $key => $page ) {
		if ( $page['slug'] === $path ) {
			return $key;
		}
	}
	return null;
}

/** Render one Phase 2 solution page. */
function nvx_anatomical_pages_render( array $data ): string {
	$html  = '<article class="nvx-brand-readable nvx-anatomical-page nvx-shell">';
	$html .= '<header class="nvx-strategy-intro"><p class="nvx-brand-kicker">' . esc_html( $data['kicker'] ) . '</p><h1 class="nvx-strategy-title">' . esc_html( $data['h1'] ) . '</h1><p class="nvx-brand-lead">' . esc_html( $data['lead'] ) . '</p><p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Solicitar valoración médica', 'nuvanx-medical' ) . '</a></p></header>';
	$html .= '<section class="nvx-brand-section"><h2>' . esc_html__( 'Qué se valora', 'nuvanx-medical' ) . '</h2><p>' . esc_html( $data['diagnosis'] ) . '</p></section>';
	$html .= '<section class="nvx-brand-section"><h2>' . esc_html__( 'Objetivos clínicos posibles', 'nuvanx-medical' ) . '</h2><ul class="nvx-brand-list">';
	foreach ( $data['objectives'] as $item ) {
		$html .= '<li>' . esc_html( $item ) . '</li>';
	}
	$html .= '</ul></section><section class="nvx-brand-section"><h2>' . esc_html__( 'Límites y derivación', 'nuvanx-medical' ) . '</h2><ul class="nvx-brand-list">';
	foreach ( $data['limits'] as $item ) {
		$html .= '<li>' . esc_html( $item ) . '</li>';
	}
	$html .= '</ul></section><section class="nvx-brand-section"><h2>' . esc_html__( 'Proceso de valoración', 'nuvanx-medical' ) . '</h2><ol class="nvx-brand-list">';
	foreach ( $data['process'] as $item ) {
		$html .= '<li>' . esc_html( $item ) . '</li>';
	}
	$html .= '</ol></section><section class="nvx-brand-section"><h2>' . esc_html__( 'La tecnología se decide después', 'nuvanx-medical' ) . '</h2><p>' . esc_html__( 'La exploración determina si puede aportar valor una modalidad láser, radiofrecuencia, tratamiento de superficie, combinación secuencial o ninguna intervención. No todas las tecnologías se utilizan en todos los pacientes.', 'nuvanx-medical' ) . '</p><p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Iniciar valoración médica', 'nuvanx-medical' ) . '</a></p></section></article>';
	return $html;
}

/** Replace content on an approved Phase 2 anatomical page. */
function nvx_anatomical_pages_content_filter( string $content ): string {
	if ( is_admin() || ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}
	$key = nvx_anatomical_pages_current_key();
	return null === $key ? $content : nvx_anatomical_pages_render( nvx_anatomical_pages_catalog()[ $key ] );
}
add_filter( 'the_content', 'nvx_anatomical_pages_content_filter', 22 );
