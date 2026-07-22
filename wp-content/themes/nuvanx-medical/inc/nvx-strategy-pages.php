<?php
/**
 * Strategy-led authority, solutions and investment pages.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Returns the approved strategy page catalog. */
function nvx_strategy_page_catalog(): array {
	return array(
		'solutions' => array(
			'slug'          => 'soluciones-medicas',
			'title'         => 'Soluciones médicas',
			'review_status' => 'approved_for_publication',
		),
		'why_nuvanx' => array(
			'slug'          => 'por-que-nuvanx',
			'title'         => 'Por qué NUVANX',
			'review_status' => 'approved_for_publication',
		),
		'investment' => array(
			'slug'          => 'inversion-medicina-estetica',
			'title'         => 'Inversión en medicina estética',
			'review_status' => 'approved_for_publication',
		),
	);
}

/** Identifies the current page's strategy catalog entry. */
function nvx_strategy_current_page_key(): ?string {
	if ( ! is_page() ) {
		return null;
	}

	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	foreach ( nvx_strategy_page_catalog() as $key => $page ) {
		if ( $page['slug'] === $slug && 'approved_for_publication' === $page['review_status'] ) {
			return $key;
		}
	}
	return null;
}

/** Returns a public URL only when an approved strategy page is published. */
function nvx_strategy_published_url( string $key ): string {
	$catalog = nvx_strategy_page_catalog();
	if ( empty( $catalog[ $key ] ) || 'approved_for_publication' !== $catalog[ $key ]['review_status'] ) {
		return '';
	}

	$page = get_page_by_path( $catalog[ $key ]['slug'] );
	if ( ! $page || 'publish' !== get_post_status( $page ) ) {
		return '';
	}
	return (string) get_permalink( $page );
}

/** Renders one solutions card. */
function nvx_strategy_solution_card( string $title, string $problem, string $limits, string $path, string $protocol = '' ): string {
	$html  = '<article class="nvx-catalog-card">';
	$html .= '<div class="nvx-catalog-card__main">';
	$html .= '<h3 class="nvx-catalog-card__title">' . esc_html( $title ) . '</h3>';
	$html .= '<p class="nvx-catalog-card__body"><strong>' . esc_html__( 'Qué se valora:', 'nuvanx-medical' ) . '</strong> ' . esc_html( $problem ) . '</p>';
	$html .= '<p class="nvx-catalog-card__body"><strong>' . esc_html__( 'Límites:', 'nuvanx-medical' ) . '</strong> ' . esc_html( $limits ) . '</p>';
	if ( '' !== $protocol ) {
		$html .= '<p class="nvx-catalog-card__meta">' . esc_html( $protocol ) . '</p>';
	}
	$html .= '</div>';
	$html .= '<a class="nvx-catalog-card__cta" href="' . esc_url( home_url( $path ) ) . '">' . esc_html__( 'Explorar solución', 'nuvanx-medical' ) . ' <span aria-hidden="true">→</span></a>';
	$html .= '</article>';
	return $html;
}

/** Builds the medical-solutions hub organized by anatomy and diagnosis. */
function nvx_strategy_solutions_markup(): string {
	$html  = '<article class="nvx-brand-readable nvx-strategy-page nvx-shell">';
	$html .= '<header class="nvx-strategy-intro">';
	$html .= '<p class="nvx-eyebrow">NUVANX · Soluciones médicas</p>';
	$html .= '<h1 class="nvx-strategy-title">Soluciones médicas para rostro, piel y contorno corporal.</h1>';
	$html .= '<p class="nvx-brand-lead">Dos personas pueden odiar lo mismo de su papada y necesitar tratamientos totalmente distintos — una tiene grasa, la otra solo piel floja. Por eso no te vamos a enseñar un catálogo de máquinas para que elijas: primero miramos qué tienes tú, y de ahí sale el plan.</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">Solicitar valoración médica</a></p>';
	$html .= '<p class="nvx-brand-microcopy">El diagnóstico determina el plan. No la tendencia ni el catálogo.</p>';
	$html .= '</header>';

	$html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner">';
	$html .= '<h2>Una misma preocupación puede tener causas distintas.</h2>';
	$html .= '<p>Grasa localizada, laxitud, textura, pigmentación, cicatriz, soporte estructural y alteraciones musculares pueden producir signos parecidos. La valoración diferencia el componente predominante antes de proponer una tecnología o una combinación.</p>';
	$html .= '</div></section>';

	$html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner"><h2 class="nvx-brand-title">Rostro y cuello</h2>';
	$html .= '<p>Las preocupaciones del tercio inferior, la mirada y la calidad facial suelen ser mixtas. El diagnóstico separa grasa, laxitud, pérdida de soporte y alteraciones de superficie.</p><div class="nvx-catalog-grid">';
	$html .= nvx_strategy_solution_card(
		'Papada y definición mandibular',
		'A veces es grasa, a veces es que la piel ya no aguanta, y a veces las dos cosas. Se nota igual desde fuera, pero el tratamiento no es el mismo — por eso primero te miramos de cerca.',
		'No solemos recomendarlo si el problema es de hueso (mentón retraído) o si sobra demasiada piel — ahí lo honesto es hablar de cirugía, no de láser.',
		'/papada-definicion-mandibular-madrid/',
		'Protocolo relacionado: Profile Definition™'
	);
	$html .= nvx_strategy_solution_card(
		'Región periocular y mirada',
		'Ojera vascular, pigmentaria o estructural, surco, laxitud y bolsas reales o aparentes.',
		'Las bolsas grasas verdaderas o determinadas alteraciones funcionales requieren valoración quirúrgica u oftalmológica.',
		'/medicina-estetica/'
	);
	$html .= nvx_strategy_solution_card(
		'Firmeza, densidad y luminosidad facial',
		'Calidad dérmica, textura, poros, tono y pérdida de firmeza sin asumir que todos los signos tienen la misma causa.',
		'La modalidad depende del fototipo, la profundidad del problema, los antecedentes y el tiempo de recuperación disponible.',
		'/calidad-piel-firmeza-luminosidad-madrid/',
		'Protocolo relacionado: Skin Architecture™'
	);
	$html .= '</div></div></section>';

	$html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner"><h2 class="nvx-brand-title">Contorno corporal</h2>';
	$html .= '<p>La grasa localizada, la laxitud, la celulitis, las estrías y la pérdida de definición son condiciones distintas que pueden aparecer en una misma zona.</p><div class="nvx-catalog-grid">';
	$html .= nvx_strategy_solution_card(
		'Abdomen y flancos',
		'Grasa subcutánea, laxitud, estrías, estabilidad de peso y posible diástasis o hernia.',
		'La grasa visceral, una diástasis significativa o un exceso importante de piel no se resuelven con un tratamiento estético focal.',
		'/grasa-localizada-abdomen-flancos-madrid/',
		'Protocolo relacionado: NUVANX Contour Architecture™'
	);
	$html .= nvx_strategy_solution_card(
		'Brazos y axila',
		'Grasa localizada, laxitud de la cara posterior y continuidad con axila y torso.',
		'La reserva de piel y la proporción anatómica determinan cuánto puede mejorar el contorno sin cirugía.',
		'/flacidez-grasa-localizada-brazos-madrid/'
	);
	$html .= nvx_strategy_solution_card(
		'Espalda y zona del sujetador',
		'Pliegues por grasa, laxitud, ajuste de la prenda y relación con flancos y brazos.',
		'El plan puede ser focal o combinado, pero cada zona debe tener una indicación documentada.',
		'/grasa-espalda-zona-sujetador-madrid/'
	);
	$html .= nvx_strategy_solution_card(
		'Muslos, región subglútea y rodillas',
		'Laxitud, grasa localizada, celulitis estructural y continuidad entre unidades del tren inferior.',
		'La celulitis, la grasa y la laxitud requieren mecanismos distintos; no se presentan como un único problema.',
		'/flacidez-muslos-internos-subgluteo-madrid/'
	);
	$html .= '</div></div></section>';

	$html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner"><h2 class="nvx-brand-title">Calidad y superficie cutánea</h2>';
	$html .= '<p>Cicatrices, poros, estrías, manchas, rojeces y daño solar responden a mecanismos diferentes. El fototipo y la profundidad de la lesión condicionan la selección de energía.</p><div class="nvx-catalog-grid">';
	$html .= nvx_strategy_solution_card(
		'Cicatrices de acné, poros y textura',
		'Tipo y profundidad de cicatriz, textura, poros, fototipo y riesgo de pigmentación postinflamatoria.',
		'Las cicatrices profundas o complejas pueden requerir secuencias combinadas y varias fases de tratamiento.',
		'/cicatrices-acne-poros-textura-madrid/',
		'Protocolo relacionado: Surface Renewal™'
	);
	$html .= nvx_strategy_solution_card(
		'Manchas, rojeces y fotodaño',
		'Léntigos, eritema, telangiectasias, melasma y pigmentación postinflamatoria bajo diagnóstico diferencial.',
		'Las lesiones pigmentadas sospechosas deben evaluarse antes de aplicar luz o láser; algunas requieren derivación dermatológica.',
		'/manchas-rojeces-fotorejuvenecimiento-ipl-madrid/',
		'Protocolo relacionado: Tone Correction™'
	);
	$html .= '</div></div></section>';

	$html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner"><h2 class="nvx-brand-title">Cambios posgestacionales</h2>';
	$html .= '<p>Después del embarazo pueden coexistir grasa localizada, laxitud, estrías, cicatriz de cesárea, diástasis y cambios de proporción. Cada componente se valora de forma independiente.</p><div class="nvx-catalog-grid">';
	$html .= nvx_strategy_solution_card(
		'Post-Maternity Contour™',
		'Abdomen y contorno posgestacional con evaluación de grasa, piel, cicatriz, estrías y pared abdominal.',
		'La diástasis, la hernia o el exceso importante de piel requieren valoración especializada y pueden tener indicación no estética.',
		'/tratamiento-postparto-abdomen-contorno-corporal-madrid/',
		'Protocolo relacionado: Post-Maternity Contour™'
	);
	$html .= '</div></div></section>';

	$html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner"><h2 class="nvx-brand-title">Medicina estética masculina</h2>';
	$html .= '<p>Las zonas pueden coincidir con las femeninas, pero los objetivos, ángulos, proporciones y prioridades de recuperación requieren una planificación específica.</p><div class="nvx-catalog-grid">';
	$html .= nvx_strategy_solution_card(
		'Contorno facial y corporal masculino',
		'Perfil mandibular, grasa localizada corporal, calidad de piel, poros y cicatrices dentro del patrón anatómico masculino.',
		'La indicación no busca feminizar ni imponer una forma estándar; se define según anatomía y objetivo individual.',
		'/contorno-corporal-masculino-madrid/'
	);
	$html .= '</div></div></section>';

	$html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner">';
	$html .= '<h2>Valoración de procedimientos previos</h2>';
	$html .= '<p>¿Te trataste en otro sitio y no estás segura de si quedó bien, o de qué hacer ahora? Te lo miramos sin compromiso. A veces la respuesta es "espera un poco más", y te lo decimos igual, aunque no salga una venta de ahí.</p>';
	$html .= '<p><a class="nvx-btn nvx-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">Solicitar segunda valoración médica</a></p>';
	$html .= '</div></section></article>';
	return $html;
}

/** Builds the NUVANX medical criteria and patient-care standards page. */
function nvx_strategy_why_nuvanx_markup(): string {
	$valuation_url = esc_url( home_url( '/madrid/valoracion/' ) );
	$team_url      = esc_url( home_url( '/equipo-medico/' ) );
	$investment    = nvx_strategy_published_url( 'investment' );

	$html  = '<article class="nvx-brand-readable nvx-strategy-page nvx-shell">';
	$html .= '<header class="nvx-strategy-intro"><p class="nvx-eyebrow">Criterio médico NUVANX</p>';
	$html .= '<h1 class="nvx-strategy-title">Por qué NUVANX. Sin retórica de marketing.</h1>';
	$html .= '<p class="nvx-brand-lead">Diagnóstico, responsabilidad médica identificada, trazabilidad, privacidad y seguimiento. Estos son los criterios concretos con los que organizamos la atención.</p></header>';

	$html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner"><h2 class="nvx-brand-title">Diagnóstico antes de tecnología</h2><p>Revisamos anatomía, antecedentes, objetivos, contraindicaciones y expectativas. Solo entonces se valora si procede tratar, esperar, derivar o no intervenir.</p></div></section>';
	$html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner"><h2 class="nvx-brand-title">Responsabilidad médica y continuidad asistencial</h2><p>El paciente conoce quién realiza la valoración y quién será responsable del procedimiento. Si interviene otro profesional o se produce un cambio en el plan, se comunica antes de continuar.</p></div></section>';
	$html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner"><h2 class="nvx-brand-title">Claridad antes de decidir</h2><p>Si vienes con una preocupación real, mereces saber qué la está causando — no que te la resuelvan con la primera máquina que haya libre ese día.</p></div></section>';
	$html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner"><h2 class="nvx-brand-title">Seguimiento como parte del plan</h2><p>La indicación incluye cómo contactar con el equipo, qué evolución vigilar, cuándo revisar el caso y qué situaciones requieren una consulta adicional. La recuperación no se presenta como idéntica para todas las personas.</p></div></section>';

	$html .= '<section class="nvx-editorial-section nvx-strategy-checklist"><div class="nvx-editorial-section__inner"><h2 class="nvx-brand-title">Lo que hacemos siempre</h2><ul class="nvx-check-list">';
	$html .= '<li>Exploración médica antes de proponer cualquier tratamiento</li>';
	$html .= '<li>Presupuesto detallado y por escrito antes de iniciar el procedimiento</li>';
	$html .= '<li>Identificación del médico responsable de la valoración y del procedimiento</li>';
	$html .= '<li>Registro de lotes y productos sanitarios cuando corresponde</li>';
	$html .= '<li>Información previa sobre cuidados, recuperación y vías de contacto</li>';
	$html .= '<li>Canal de seguimiento posterior y criterios claros para solicitar revisión</li>';
	$html .= '</ul></div></section>';

	$html .= '<section class="nvx-editorial-section nvx-strategy-checklist nvx-strategy-checklist--no"><div class="nvx-editorial-section__inner"><h2 class="nvx-brand-title">Lo que no hacemos</h2><ul class="nvx-check-list nvx-check-list--no">';
	$html .= '<li>Urgencia artificial de precio o presión para decidir en la consulta</li>';
	$html .= '<li>Financiación utilizada para sustituir la explicación clínica del plan</li>';
	$html .= '<li>Tratamientos sin indicación clínica previa documentada</li>';
	$html .= '<li>Cambios de médico responsable sin informar al paciente</li>';
	$html .= '<li>Indicaciones emitidas desde una conversación exclusivamente comercial</li>';
	$html .= '</ul></div></section>';

	$html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner"><h2 class="nvx-brand-title">Trazabilidad de productos</h2>';
	$html .= '<p>Cuando se utilizan productos sanitarios o inyectables, el producto y su lote se documentan en la historia clínica. La información disponible del fabricante o distribuidor puede consultarse antes de firmar el consentimiento y el presupuesto.</p>';
	$html .= '</div></section>';

	$html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner"><h2 class="nvx-brand-title">Privacidad durante la atención</h2>';
	$html .= '<p>La organización de la consulta busca reducir exposiciones innecesarias y preservar la confidencialidad. La sala de espera y los espacios clínicos se gestionan para mantener una experiencia discreta, dentro de la operativa de cada sede.</p>';
	$html .= '</div></section>';

	$html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner"><h2 class="nvx-brand-title">Por qué importa</h2>';
	$html .= '<p>En medicina estética, el resultado no depende solo de la plataforma. Depende de la indicación, de quién asume la responsabilidad clínica, de la trazabilidad, de la información previa y de cómo se gestiona la evolución posterior.</p>';
	$html .= '</div></section>';

	$html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner"><h2 class="nvx-brand-title">Atención en centros sanitarios autorizados</h2>';
	$html .= '<p>NUVANX atiende en Chamberí (CS20144) y Salamanca–Goya (CS20073), con equipo médico colegiado.</p>';
	$html .= '<p><a class="nvx-button" href="' . $valuation_url . '">Solicitar valoración médica</a> <a class="nvx-brand-inline-link" href="' . $team_url . '">Conocer al equipo médico</a>';
	if ( '' !== $investment ) {
		$html .= ' <a class="nvx-brand-inline-link" href="' . esc_url( $investment ) . '">Consultar inversión orientativa</a>';
	}
	$html .= '</p></div></section></article>';
	return $html;
}

/** Groups available clinical tariffs by treatment category. */
function nvx_strategy_verified_investment_groups(): array {
	if ( ! function_exists( 'nvx_tariff_catalog' ) || ! function_exists( 'nvx_format_price_eur' ) ) {
		return array();
	}

	$catalog = nvx_tariff_catalog();
	$groups  = array();

	$facial_keys = array( 'ojeras', 'papada', 'marcacion_mandibular', 'pomulos', 'cuello' );
	foreach ( $facial_keys as $key ) {
		if ( isset( $catalog['endolift'][ $key ] ) ) {
			$item = $catalog['endolift'][ $key ];
			$groups['endolift_facial'][] = array(
				'label' => $item['label'],
				'price' => nvx_format_price_eur( $item['pvp'] ) . ' €',
			);
		}
	}

	$facial_combo_keys = array( 'papada_cuello', 'marcacion_papada', 'full_face' );
	foreach ( $facial_combo_keys as $key ) {
		if ( isset( $catalog['endolift_combo'][ $key ] ) ) {
			$item = $catalog['endolift_combo'][ $key ];
			$groups['endolift_facial'][] = array(
				'label' => $item['label'] . ' (zona combinada)',
				'price' => nvx_format_price_eur( $item['pvp'] ) . ' €',
			);
		}
	}

	$corporal_keys = array( 'abdomen', 'flancos', 'brazos', 'cartucheras', 'subgluteos', 'muslos_internos', 'subescapular', 'rodillas' );
	foreach ( $corporal_keys as $key ) {
		if ( isset( $catalog['endolift'][ $key ] ) ) {
			$item = $catalog['endolift'][ $key ];
			$groups['endolift_corporal'][] = array(
				'label' => $item['label'],
				'price' => nvx_format_price_eur( $item['pvp'] ) . ' €',
			);
		}
	}

	$corporal_combo_keys = array( 'abdomen_flancos', 'subgluteos_cartucheras', 'muslos_rodilla', 'sujetador_brazos', 'cartucheras_muslos', 'cartucheras_subgluteos_muslos' );
	foreach ( $corporal_combo_keys as $key ) {
		if ( isset( $catalog['endolift_combo'][ $key ] ) ) {
			$item = $catalog['endolift_combo'][ $key ];
			$groups['endolift_corporal'][] = array(
				'label' => $item['label'] . ' (zona combinada)',
				'price' => nvx_format_price_eur( $item['pvp'] ) . ' €',
			);
		}
	}

	foreach ( array( 'facial', 'corporal' ) as $key ) {
		if ( isset( $catalog['laser_co2'][ $key ] ) ) {
			$groups['laser_co2'][] = array(
				'label' => $catalog['laser_co2'][ $key ]['label'],
				'price' => nvx_format_price_eur( $catalog['laser_co2'][ $key ]['pvp'] ) . ' €',
			);
		}
	}
	return $groups;
}

/** Returns a flat tariff list for backward-compatible callers. */
function nvx_strategy_verified_investment_rows(): array {
	$rows = array();
	foreach ( nvx_strategy_verified_investment_groups() as $group ) {
		foreach ( $group as $row ) {
			$rows[] = $row;
		}
	}
	return $rows;
}

/** Renders one price-table section for a group of tariff rows. */
function nvx_strategy_investment_table_section( string $heading, array $rows ): string {
	if ( empty( $rows ) ) {
		return '';
	}

	$html  = '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner">';
	$html .= '<h2>' . esc_html( $heading ) . '</h2>';
	$html .= '<p>Las filas muestran el procedimiento técnico y su PVP con IVA. La indicación, el alcance exacto, los cuidados y el seguimiento se documentan en el presupuesto individual.</p>';
	$html .= '<div class="nvx-editorial-price-table-wrap"><table class="nvx-editorial-price-table">';
	$html .= '<thead><tr><th scope="col">Procedimiento</th><th scope="col">PVP con IVA</th></tr></thead><tbody>';
	foreach ( $rows as $row ) {
		$html .= '<tr><td>' . esc_html( $row['label'] ) . '</td><td>' . esc_html( $row['price'] ) . '</td></tr>';
	}
	$html .= '</tbody></table></div></div></section>';
	return $html;
}

/** Builds the investment page with transparent tariffs and clinical context. */
function nvx_strategy_investment_markup(): string {
	$groups        = nvx_strategy_verified_investment_groups();
	$valuation_url = esc_url( home_url( '/madrid/valoracion/' ) );

	$html  = '<article class="nvx-brand-readable nvx-strategy-page nvx-shell">';
	$html .= '<section class="nvx-brand-hero nvx-brand-hero--laser nvx-editorial-hero"><div class="nvx-brand-hero__inner"><div class="nvx-editorial-hero__copy">';
	$html .= '<p class="nvx-eyebrow">Inversión en medicina estética · NUVANX Madrid</p>';
	$html .= '<h1 class="nvx-heading">El presupuesto forma parte de una decisión informada.</h1>';
	$html .= '<p class="nvx-lead">Publicamos tarifas verificadas porque respetamos tu tiempo. La indicación, el alcance exacto y el importe final se confirman después de la valoración médica presencial.</p>';
	$html .= '</div></div></section>';

	$html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner"><h2 class="nvx-brand-title">Cómo leer estas tarifas</h2>';
	$html .= '<p>Una tarifa orientativa permite anticipar el orden de inversión, pero no sustituye la exploración. Dos personas que consultan por la misma zona pueden necesitar tecnologías, combinaciones y seguimientos diferentes.</p>';
	$html .= '</div></section>';

	if ( ! empty( $groups ) ) {
		$group_labels = array(
			'endolift_facial'   => 'Endolift® facial — zonas y combinaciones',
			'endolift_corporal' => 'Endolift® corporal — zonas y combinaciones',
			'laser_co2'         => 'Láser CO₂ fraccionado',
		);
		foreach ( $group_labels as $key => $label ) {
			if ( ! empty( $groups[ $key ] ) ) {
				$html .= nvx_strategy_investment_table_section( $label, $groups[ $key ] );
			}
		}
	} else {
		$html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner"><p>Las tarifas verificadas se mostrarán cuando estén disponibles en el catálogo clínico vigente.</p></div></section>';
	}

	$html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner"><h2 class="nvx-brand-title">Qué incluye siempre el plan en NUVANX</h2><ul class="nvx-check-list">';
	$html .= '<li>Valoración médica previa y revisión de antecedentes relevantes</li>';
	$html .= '<li>Explicación de la indicación, alternativas, límites y recuperación orientativa</li>';
	$html .= '<li>Protocolo anestésico cuando corresponde al procedimiento</li>';
	$html .= '<li>Presupuesto detallado con justificación clínica documentada antes de iniciar</li>';
	$html .= '<li>Indicaciones de cuidados y canal de seguimiento según el plan</li>';
	$html .= '</ul>';
	$html .= '<p>Otras zonas, procedimientos de medicina estética facial y combinaciones no listadas requieren exploración, indicación y presupuesto individualizado.</p></div></section>';

	$html .= '<section class="nvx-editorial-section nvx-strategy-checklist nvx-strategy-checklist--no"><div class="nvx-editorial-section__inner"><h2 class="nvx-brand-title">Qué no encontrarás aquí</h2><ul class="nvx-check-list nvx-check-list--no">';
	$html .= '<li>Un precio final asignado sin conocer la anatomía ni los antecedentes</li>';
	$html .= '<li>Bonos genéricos presentados como solución para diagnósticos distintos</li>';
	$html .= '<li>Urgencia artificial para reservar durante la consulta</li>';
	$html .= '<li>Una promoción utilizada para cambiar la indicación clínica</li>';
	$html .= '</ul></div></section>';

	$html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner"><h2 class="nvx-brand-title">Sobre los precios en medicina estética en Madrid</h2>';
	$html .= '<p>Las diferencias pueden responder al alcance del diagnóstico, la experiencia del profesional, la tecnología indicada, el material utilizado y el seguimiento incluido. La comparación debe hacerse sobre planes equivalentes y documentados por escrito.</p>';
	$html .= '<p>Lo que te preocupa hoy no va a estar mejor por esperar a una oferta. Por eso no jugamos con eso.</p>';
	$html .= '</div></section>';

	$html .= '<section class="nvx-editorial-section"><div class="nvx-editorial-section__inner"><h2 class="nvx-brand-title">Inicia tu valoración médica</h2>';
	$html .= '<p>La consulta permite confirmar si existe indicación, definir el procedimiento y entregar un presupuesto individualizado.</p>';
	$html .= '<p><a class="nvx-button" href="' . $valuation_url . '">Solicitar valoración médica</a></p></div></section>';
	$html .= '</article>';
	return $html;
}

/** Generates the page markup for a supported strategy route. */
function nvx_strategy_page_markup( string $key ): string {
	if ( 'solutions' === $key ) {
		return nvx_strategy_solutions_markup();
	}
	if ( 'why_nuvanx' === $key ) {
		return nvx_strategy_why_nuvanx_markup();
	}
	if ( 'investment' === $key ) {
		return nvx_strategy_investment_markup();
	}
	return '';
}

/** Replaces the current strategy page content with generated markup. */
function nvx_strategy_page_content_filter( string $content ): string {
	if ( is_admin() || ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}
	$key = nvx_strategy_current_page_key();
	return null === $key ? $content : nvx_strategy_page_markup( $key );
}
add_filter( 'the_content', 'nvx_strategy_page_content_filter', 82 );
