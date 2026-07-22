<?php
/**
 * Published NUVANX Signature Protocol pages.
 *
 * Public protocol names describe a clinical decision system. Technologies are
 * possible tools selected only after medical assessment.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Approved Signature Protocol catalogue.
 *
 * Eye Frame™ is deliberately excluded from publication until a separate
 * medical, legal, SEO and visual review is completed.
 *
 * @return array<string,array<string,mixed>>
 */
function nvx_protocol_pages_catalog(): array {
	return array(
		'contour-architecture' => array(
			'slug'          => 'remodelacion-corporal-laser-madrid',
			'seo_title'     => 'Remodelación corporal láser en Madrid | NUVANX Contour Architecture',
			'description'   => 'Valoración médica del contorno corporal por unidades anatómicas para diferenciar grasa localizada, laxitud, calidad cutánea y límites del tratamiento.',
			'kicker'        => 'PROTOCOLO SIGNATURE NUVANX',
			'h1'            => 'Remodelación corporal láser diseñada según tu anatomía.',
			'lead'          => 'NUVANX Contour Architecture™ es nuestro sistema médico de diagnóstico y planificación por unidades anatómicas. Estudia grasa localizada, laxitud, calidad cutánea y continuidad del contorno antes de definir una técnica.',
			'diagnosis'     => 'El abdomen, los flancos, la espalda, los brazos y las piernas no se valoran como piezas aisladas. La exploración diferencia el tejido predominante, las transiciones entre zonas, las asimetrías, los tratamientos previos y las situaciones que requieren otra vía asistencial.',
			'objectives'    => array(
				'Reducir volumen focal cuando predomina grasa subcutánea susceptible de tratamiento.',
				'Redefinir transiciones y proporciones entre zonas contiguas cuando existe una indicación razonable.',
				'Actuar sobre laxitud o calidad cutánea cuando la exploración permite esperar una mejora proporcionada.',
			),
			'modalities'    => array(
				'Endoláser corporal o Endolift® corporal cuando la valoración indica un abordaje intersticial.',
				'EXION® Body para planes orientados a firmeza y calidad tisular, según indicación.',
				'Láser CO₂ fraccionado o EXION® Fractional RF cuando existe un componente de superficie compatible con estas modalidades.',
				'Otras tecnologías autorizadas disponibles en NUVANX cuando aportan valor clínico documentado.',
			),
			'limits'        => array(
				'No es un tratamiento para pérdida general de peso ni para grasa visceral.',
				'El exceso importante de piel puede requerir valoración quirúrgica.',
				'La sospecha de diástasis, hernia o enfermedad activa exige valoración específica o derivación.',
				'No se indica cuando la expectativa es incompatible con una intervención focal y progresiva.',
			),
			'process'       => array(
				'Historia clínica, exploración y cartografía anatómica de las zonas relacionadas.',
				'Clasificación del componente predominante y explicación de alternativas, límites y prioridades.',
				'Plan escrito con unidad anatómica, modalidad seleccionada, cuidados, revisiones e inversión.',
				'Registro fotográfico estandarizado y seguimiento bajo condiciones comparables.',
			),
			'planning_levels' => array(
				'Contour Focus'       => 'Planificación focal de una unidad anatómica claramente delimitada.',
				'Contour Continuity'  => 'Valoración coordinada de dos zonas contiguas, sin convertirlas en un paquete cerrado.',
				'Contour Architecture' => 'Plan integral de una región corporal cuando varias unidades forman una misma continuidad.',
				'Post-Maternity Contour' => 'Plan posgestacional por componentes, con derivación cuando el problema no corresponde a medicina estética.',
				'Male Definition'     => 'Planificación del contorno masculino respetando anatomía, proporciones y objetivos individuales.',
				'Corrective Assessment' => 'Valoración de irregularidades previas únicamente tras exploración y revisión documental.',
			),
			'faqs'          => array(
				array(
					'q' => '¿Se utiliza la misma tecnología en todos los casos?',
					'a' => 'No. La modalidad y las zonas se determinan después de la exploración médica. Un caso puede requerir una sola herramienta, una secuencia combinada o ninguna intervención.',
				),
				array(
					'q' => '¿Cuántas zonas deben tratarse?',
					'a' => 'Solo las que tengan una indicación documentada. La continuidad anatómica se estudia para evitar transiciones incoherentes, no para imponer venta cruzada.',
				),
			),
			'review_status' => 'approved_for_publication',
		),
		'post-maternity' => array(
			'slug'          => 'tratamiento-postparto-abdomen-contorno-corporal-madrid',
			'seo_title'     => 'Tratamiento postparto abdomen Madrid | NUVANX',
			'description'   => 'Valoración médica del abdomen posgestacional para diferenciar grasa localizada, laxitud, estrías, cicatriz y alteraciones de la pared abdominal.',
			'kicker'        => 'NUVANX POST-MATERNITY CONTOUR™',
			'h1'            => 'Después del embarazo, “abdomen” puede significar problemas diferentes.',
			'lead'          => 'La valoración separa grasa subcutánea, laxitud cutánea, estrías, cicatriz de cesárea, diástasis, hernia y exceso de piel antes de plantear cualquier tratamiento.',
			'diagnosis'     => 'La medicina estética puede actuar sobre determinados componentes de grasa, piel, cicatriz o textura. No corrige una alteración muscular significativa ni sustituye una valoración quirúrgica cuando existe exceso importante de piel o hernia.',
			'objectives'    => array(
				'Valorar grasa localizada y continuidad entre abdomen, flancos y espalda.',
				'Estudiar laxitud, estrías, calidad cutánea y estado de una cicatriz de cesárea.',
				'Detectar signos que aconsejan fisioterapia especializada, cirugía u otra derivación.',
			),
			'modalities'    => array(
				'Endoláser corporal o EXION® Body cuando existe una indicación sobre grasa localizada o calidad tisular.',
				'Láser CO₂ fraccionado u otras modalidades de superficie cuando el estado de la piel o la cicatriz lo permite.',
				'Plan conservador, espera clínica o derivación cuando tratar no sea la opción adecuada.',
			),
			'limits'        => array(
				'La diástasis o hernia no se corrigen mediante un tratamiento estético sobre grasa o piel.',
				'El momento de valoración depende de recuperación, lactancia, estabilidad de peso y antecedentes.',
				'El exceso importante de piel puede tener indicación quirúrgica.',
			),
			'process'       => array(
				'Revisión de antecedentes obstétricos, recuperación, lactancia y estabilidad de peso.',
				'Exploración de pared abdominal, piel, grasa subcutánea, cicatrices y zonas contiguas.',
				'Explicación por escrito de lo tratable, lo que debe esperar y lo que requiere derivación.',
			),
			'faqs'          => array(
				array(
					'q' => '¿Cuándo puede realizarse una valoración?',
					'a' => 'El momento se individualiza según recuperación, lactancia, estabilidad de peso, antecedentes y modalidad considerada.',
				),
				array(
					'q' => '¿Se puede valorar una cicatriz de cesárea?',
					'a' => 'Sí. Se revisan madurez, relieve, color, síntomas y fototipo antes de decidir si existe una opción médico-estética o conviene otra valoración.',
				),
			),
			'review_status' => 'approved_for_publication',
		),
		'profile-definition' => array(
			'slug'          => 'papada-definicion-mandibular-madrid',
			'seo_title'     => 'Papada y definición mandibular Madrid | NUVANX',
			'description'   => 'Valoración médica de papada, cuello, mandíbula y mentón para diferenciar grasa, laxitud y soporte estructural antes de indicar tratamiento.',
			'kicker'        => 'NUVANX PROFILE DEFINITION™',
			'h1'            => 'Papada, cuello y mandíbula forman un mismo perfil.',
			'lead'          => 'El diagnóstico diferencia grasa submentoniana, laxitud cervical, posición del mentón, soporte mandibular y otros componentes que pueden producir una pérdida de definición parecida.',
			'diagnosis'     => 'No toda falta de definición es grasa y no toda papada puede resolverse con una única técnica. La exploración determina cuál es el componente predominante y qué expectativas son razonables.',
			'objectives'    => array(
				'Valorar grasa localizada submentoniana y continuidad con el cuello.',
				'Estudiar laxitud, calidad cutánea y soporte del tercio inferior.',
				'Mantener proporciones faciales y evitar cambios que ensanchen o alteren innecesariamente el rostro.',
			),
			'modalities'    => array(
				'Endolift® facial cuando existe una indicación sobre grasa localizada o laxitud.',
				'Medicina inyectable cuando el soporte estructural lo requiere y el balance beneficio-riesgo es favorable.',
				'Derivación cuando el componente óseo, glandular o el exceso de piel no corresponde al alcance médico-estético.',
			),
			'limits'        => array(
				'La laxitud severa o determinadas alteraciones óseas pueden requerir valoración quirúrgica.',
				'Las glándulas prominentes, masas o síntomas cervicales requieren evaluación clínica específica.',
				'No se promete una línea mandibular estándar ni un resultado idéntico entre pacientes.',
			),
			'process'       => array(
				'Exploración estática y dinámica del tercio inferior y el cuello.',
				'Diagnóstico del componente predominante y documentación fotográfica.',
				'Plan por prioridades con alternativas y límites explicados antes de decidir.',
			),
			'faqs'          => array(
				array(
					'q' => '¿Se puede tratar solo con relleno?',
					'a' => 'Depende de la anatomía. Añadir soporte sin valorar grasa, laxitud y proporciones puede no mejorar el perfil y puede resultar desproporcionado.',
				),
			),
			'review_status' => 'approved_for_publication',
		),
		'skin-architecture' => array(
			'slug'          => 'calidad-piel-firmeza-luminosidad-madrid',
			'seo_title'     => 'Calidad, firmeza y luminosidad de la piel Madrid | NUVANX',
			'description'   => 'Plan médico para calidad, firmeza, densidad e hidratación de la piel según fototipo, diagnóstico y profundidad del problema.',
			'kicker'        => 'NUVANX SKIN ARCHITECTURE™',
			'h1'            => 'Calidad de piel: firmeza, densidad e hidratación no son lo mismo.',
			'lead'          => 'La valoración diferencia deshidratación superficial, pérdida de densidad, laxitud, daño solar y alteraciones de textura para seleccionar un plan proporcionado.',
			'diagnosis'     => 'La misma apariencia apagada puede tener causas distintas. El fototipo, el espesor cutáneo, los antecedentes y el tiempo de recuperación disponible condicionan la indicación.',
			'objectives'    => array(
				'Mejorar calidad y densidad cutánea cuando existe una indicación clínica.',
				'Abordar firmeza e hidratación sin modificar deliberadamente los volúmenes faciales.',
				'Integrar fotoprotección y cuidados domiciliarios cuando forman parte del plan.',
			),
			'modalities'    => array(
				'EXION® Face, EMFUSION® u otras modalidades no invasivas según diagnóstico.',
				'Bioestimuladores cuando la indicación, la anatomía y los antecedentes lo permiten.',
				'IPL o tecnología fraccionada cuando existe un componente pigmentario, vascular o de superficie compatible.',
			),
			'limits'        => array(
				'No sustituye un lifting quirúrgico ni corrige por sí solo una pérdida estructural relevante.',
				'Las enfermedades cutáneas activas deben estabilizarse o derivarse antes de tratar.',
				'La respuesta, el número de sesiones y el mantenimiento son individuales.',
			),
			'process'       => array(
				'Valoración de fototipo, calidad cutánea, antecedentes y prioridades.',
				'Selección de modalidad y parámetros conforme a la indicación.',
				'Plan de cuidados, seguimiento y reevaluación antes de ampliar o combinar técnicas.',
			),
			'faqs'          => array(
				array(
					'q' => '¿El protocolo añade volumen?',
					'a' => 'No necesariamente. El objetivo principal es calidad cutánea; cualquier técnica que modifique volumen debe justificarse por separado.',
				),
			),
			'review_status' => 'approved_for_publication',
		),
		'surface-renewal' => array(
			'slug'          => 'cicatrices-acne-poros-textura-madrid',
			'seo_title'     => 'Cicatrices de acné, poros y textura Madrid | NUVANX',
			'description'   => 'Valoración médica de cicatrices, poros y textura para seleccionar láser CO₂, radiofrecuencia fraccionada u otras modalidades según fototipo.',
			'kicker'        => 'NUVANX SURFACE RENEWAL™',
			'h1'            => 'Cicatrices, poros y textura requieren diagnóstico por profundidad.',
			'lead'          => 'Las cicatrices atróficas, los poros visibles y la textura irregular no responden necesariamente a la misma técnica. La exploración clasifica tipo, profundidad y riesgo pigmentario.',
			'diagnosis'     => 'Se valora el patrón de cicatriz, el estado del acné, el fototipo, los tratamientos previos y el tiempo de recuperación disponible antes de proponer resurfacing u otras modalidades.',
			'objectives'    => array(
				'Mejorar gradualmente relieve, bordes y uniformidad de determinadas cicatrices.',
				'Abordar textura y poros cuando existe una indicación compatible.',
				'Reducir el riesgo de pigmentación postinflamatoria mediante selección y preparación adecuadas.',
			),
			'modalities'    => array(
				'Láser CO₂ fraccionado para indicaciones seleccionadas de resurfacing.',
				'EXION® Fractional RF u otras modalidades cuando la profundidad o el fototipo aconsejan una alternativa.',
				'Técnicas complementarias únicamente cuando están disponibles, autorizadas y justificadas.',
			),
			'limits'        => array(
				'Las cicatrices profundas rara vez desaparecen por completo.',
				'El acné inflamatorio activo debe estabilizarse antes de determinados procedimientos.',
				'El fototipo, la medicación y los antecedentes pueden modificar o contraindicar el plan.',
			),
			'process'       => array(
				'Clasificación clínica y registro fotográfico bajo iluminación consistente.',
				'Preparación, selección de modalidad y explicación del periodo de recuperación esperado.',
				'Seguimiento de la respuesta, cuidados y riesgo de pigmentación.',
			),
			'faqs'          => array(
				array(
					'q' => '¿Las cicatrices desaparecerán por completo?',
					'a' => 'No se puede prometer su desaparición. El objetivo es una mejora gradual y clínicamente apreciable dentro de los límites del tejido y la modalidad seleccionada.',
				),
			),
			'review_status' => 'approved_for_publication',
		),
		'tone-correction' => array(
			'slug'          => 'manchas-rojeces-fotorejuvenecimiento-ipl-madrid',
			'seo_title'     => 'Manchas, rojeces y fotodaño Madrid | NUVANX',
			'description'   => 'Diagnóstico de manchas, rojeces y fotodaño con selección de IPL, cuidados o derivación según lesión, fototipo y antecedentes.',
			'kicker'        => 'NUVANX TONE CORRECTION™',
			'h1'            => 'Manchas y rojeces no se tratan sin identificar primero la lesión.',
			'lead'          => 'Léntigos, melasma, pigmentación postinflamatoria, eritema y lesiones vasculares requieren enfoques diferentes. Algunas lesiones deben evaluarse antes por dermatología.',
			'diagnosis'     => 'La valoración revisa tipo de lesión, profundidad, componente vascular, fototipo, exposición solar, medicación y antecedentes para evitar tratamientos inadecuados.',
			'objectives'    => array(
				'Abordar alteraciones pigmentarias o vasculares seleccionadas con parámetros individualizados.',
				'Integrar fotoprotección, cuidados domiciliarios y mantenimiento cuando corresponde.',
				'Derivar lesiones sospechosas o problemas que no deben tratarse con luz o láser.',
			),
			'modalities'    => array(
				'BTL EXILITE™ IPL para indicaciones pigmentarias o vasculares seleccionadas.',
				'EMFUSION®, cuidado domiciliario u otras medidas de soporte cuando forman parte del plan.',
				'Derivación dermatológica cuando el diagnóstico no está claro o existe una lesión sospechosa.',
			),
			'limits'        => array(
				'La piel bronceada, la exposición solar reciente o determinados fármacos pueden obligar a posponer el tratamiento.',
				'El melasma requiere manejo prudente y puede presentar recurrencias.',
				'No se fija un número estándar de sesiones antes del diagnóstico y la respuesta inicial.',
			),
			'process'       => array(
				'Historia clínica, evaluación de la lesión y clasificación del fototipo.',
				'Prueba o selección de parámetros cuando el protocolo lo requiere.',
				'Fotoprotección, seguimiento de respuesta y ajuste del plan.',
			),
			'faqs'          => array(
				array(
					'q' => '¿Puede tratarse cualquier mancha con IPL?',
					'a' => 'No. La indicación depende del tipo de lesión, la profundidad y el fototipo; algunas manchas requieren otra modalidad o derivación.',
				),
			),
			'review_status' => 'approved_for_publication',
		),
	);
}

/** Identify the approved protocol page for the current request. */
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

/** Render one approved protocol page. */
function nvx_protocol_pages_render( array $data ): string {
	$html  = '<article class="nvx-brand-readable nvx-protocol-page nvx-shell">';
	$html .= '<header class="nvx-strategy-intro">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html( $data['kicker'] ) . '</p>';
	$html .= '<h1 class="nvx-strategy-title">' . esc_html( $data['h1'] ) . '</h1>';
	$html .= '<p class="nvx-brand-lead">' . esc_html( $data['lead'] ) . '</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Solicitar valoración médica', 'nuvanx-medical' ) . '</a></p>';
	$html .= '<p class="nvx-brand-microcopy">' . esc_html__( 'La indicación, las modalidades, la evolución y el presupuesto se determinan después de la exploración médica.', 'nuvanx-medical' ) . '</p>';
	$html .= '</header>';

	$html .= '<section class="nvx-brand-section"><h2>' . esc_html__( 'El valor del diagnóstico médico', 'nuvanx-medical' ) . '</h2><p>' . esc_html( $data['diagnosis'] ) . '</p></section>';

	$html .= '<section class="nvx-brand-section"><h2>' . esc_html__( 'Objetivos clínicos que se valoran', 'nuvanx-medical' ) . '</h2><ul class="nvx-brand-list">';
	foreach ( $data['objectives'] as $item ) {
		$html .= '<li>' . esc_html( $item ) . '</li>';
	}
	$html .= '</ul></section>';

	if ( ! empty( $data['planning_levels'] ) ) {
		$html .= '<section class="nvx-brand-section"><h2>' . esc_html__( 'Niveles de planificación, no paquetes cerrados', 'nuvanx-medical' ) . '</h2><div class="nvx-catalog-grid">';
		foreach ( $data['planning_levels'] as $name => $body ) {
			$html .= '<article class="nvx-catalog-card"><div class="nvx-catalog-card__main"><h3 class="nvx-catalog-card__title">' . esc_html( $name ) . '</h3><p class="nvx-catalog-card__body">' . esc_html( $body ) . '</p></div></article>';
		}
		$html .= '</div></section>';
	}

	$html .= '<section class="nvx-brand-section"><h2>' . esc_html__( 'Qué puede formar parte del plan', 'nuvanx-medical' ) . '</h2><ul class="nvx-brand-list">';
	foreach ( $data['modalities'] as $item ) {
		$html .= '<li>' . esc_html( $item ) . '</li>';
	}
	$html .= '</ul><p>' . esc_html__( 'No todas las modalidades se utilizan en todos los pacientes.', 'nuvanx-medical' ) . '</p></section>';

	$html .= '<section class="nvx-brand-section"><h2>' . esc_html__( 'Cuándo no es el tratamiento adecuado', 'nuvanx-medical' ) . '</h2><ul class="nvx-brand-list">';
	foreach ( $data['limits'] as $item ) {
		$html .= '<li>' . esc_html( $item ) . '</li>';
	}
	$html .= '</ul></section>';

	$html .= '<section class="nvx-brand-section"><h2>' . esc_html__( 'Proceso clínico', 'nuvanx-medical' ) . '</h2><ol class="nvx-brand-list">';
	foreach ( $data['process'] as $item ) {
		$html .= '<li>' . esc_html( $item ) . '</li>';
	}
	$html .= '</ol></section>';

	if ( ! empty( $data['faqs'] ) ) {
		$html .= '<section class="nvx-brand-section"><h2>' . esc_html__( 'Preguntas frecuentes', 'nuvanx-medical' ) . '</h2><div class="nvx-faq-accordion">';
		foreach ( $data['faqs'] as $faq ) {
			$html .= '<details class="nvx-faq-item"><summary class="nvx-faq-question">' . esc_html( $faq['q'] ) . '</summary><div class="nvx-faq-answer"><p>' . esc_html( $faq['a'] ) . '</p></div></details>';
		}
		$html .= '</div></section>';
	}

	$html .= '<section class="nvx-brand-section"><h2>' . esc_html__( 'Tu primera valoración clínica', 'nuvanx-medical' ) . '</h2><p>' . esc_html__( 'La consulta determina qué puede tener sentido, qué alternativas existen y en qué situaciones es preferible esperar, derivar o no intervenir.', 'nuvanx-medical' ) . '</p><p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Iniciar valoración médica', 'nuvanx-medical' ) . '</a></p></section>';
	$html .= '</article>';
	return $html;
}

/** Render the matching approved protocol page. */
function nvx_protocol_pages_content_filter( string $content ): string {
	if ( is_admin() || ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}
	$key = nvx_protocol_pages_current_key();
	return null === $key ? $content : nvx_protocol_pages_render( nvx_protocol_pages_catalog()[ $key ] );
}
add_filter( 'the_content', 'nvx_protocol_pages_content_filter', 21 );
