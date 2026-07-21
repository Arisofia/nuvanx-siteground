<?php
/**
 * Strategy-led authority, investment and protocol-review pages.
 *
 * Public copy stays within the clinical claims register: the authority and
 * investment pages explain the decision process, while working protocol names
 * exist only on staging2 and remain noindex until medical and legal approval.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array<string,array{slug:string,title:string,review_status:string}>
 */
function nvx_strategy_page_catalog(): array {
	return array(
		'why_nuvanx' => array(
			'slug'          => 'por-que-nuvanx',
			'title'         => 'Por qué NUVANX',
			'review_status' => 'approved_for_publication',
		),
		'investment'  => array(
			'slug'          => 'inversion-medicina-estetica',
			'title'         => 'Inversión en medicina estética',
			'review_status' => 'approved_for_publication',
		),
		'liposculpt_air' => array(
			'slug'          => 'liposculpt-air',
			'title'         => 'LipoSculpt-Air™',
			'review_status' => 'pending_medical_legal',
		),
		'v_lift_awake' => array(
			'slug'          => 'v-lift-awake',
			'title'         => 'V-Lift Awake™',
			'review_status' => 'pending_medical_legal',
		),
	);
}

/**
 * Return the catalogue key for the current strategy page.
 */
function nvx_strategy_current_page_key(): ?string {
	if ( ! is_page() ) {
		return null;
	}

	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	foreach ( nvx_strategy_page_catalog() as $key => $page ) {
		if ( $page['slug'] === $slug ) {
			return $key;
		}
	}

	return null;
}

/**
 * Return a public URL only when a non-prototype strategy page is published.
 */
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

/**
 * Pending working-name pages are excluded from robots and sitemaps everywhere.
 *
 * @return int[]
 */
function nvx_strategy_pending_page_ids(): array {
	$ids = array();
	foreach ( nvx_strategy_page_catalog() as $page ) {
		if ( 'approved_for_publication' === $page['review_status'] ) {
			continue;
		}

		$stored = get_page_by_path( $page['slug'] );
		if ( $stored ) {
			$ids[] = (int) $stored->ID;
		}
	}

	return array_values( array_unique( $ids ) );
}

/**
 * Keep prototype names visibly in a review state; they are not a treatment offer.
 */
function nvx_strategy_protocol_review_markup( string $key ): string {
	$catalog = nvx_strategy_page_catalog();
	$page    = $catalog[ $key ] ?? null;
	if ( ! is_array( $page ) ) {
		return '';
	}

	return '<article class="nvx-brand-readable nvx-strategy-page nvx-strategy-page--review nvx-shell">'
		. '<header class="nvx-strategy-intro"><p class="nvx-brand-kicker">NUVANX · revisión clínica y jurídica</p>'
		. '<h1 class="nvx-strategy-title">' . esc_html( $page['title'] ) . '</h1>'
		. '<p class="nvx-brand-lead">Protocolo en evaluación. Esta denominación de trabajo no constituye una técnica ofrecida, una indicación médica ni una promesa de resultado.</p></header>'
		. '<section class="nvx-brand-section"><h2>Antes de cualquier publicación</h2>'
		. '<p>La Dirección Médica debe documentar técnica, indicación, contraindicaciones, seguridad, seguimiento y profesional responsable. La denominación también requiere validación jurídica y registral.</p>'
		. '<p>Hasta entonces, esta página permanece fuera de navegación pública, buscadores y campañas.</p></section>'
		. '</article>';
}

/**
 * Authority page with an explicit diagnostic-first promise.
 */
function nvx_strategy_why_nuvanx_markup(): string {
	$valuation_url = esc_url( home_url( '/madrid/valoracion/' ) );
	$team_url      = esc_url( home_url( '/equipo-medico/' ) );
	$investment    = nvx_strategy_published_url( 'investment' );
	$html  = '<article class="nvx-brand-readable nvx-strategy-page">';
	$html .= '<header class="nvx-strategy-intro"><p class="nvx-brand-kicker">Criterio médico NUVANX · Chamberí · Salamanca–Goya</p>';
	$html .= '<h1 class="nvx-strategy-title">NUVANX no es una clínica de máquinas. Es una clínica de criterio médico.</h1>';
	$html .= '<p class="nvx-brand-lead">Una decisión de medicina estética comienza por la exploración, no por una tendencia, una máquina o una promesa de resultado.</p></header>';

	$html .= '<section class="nvx-brand-section"><h2>El diagnóstico precede a la tecnología</h2><p>Revisamos anatomía, antecedentes, objetivos y contraindicaciones. Solo entonces se valora si procede tratar, esperar, derivar o no intervenir. La indicación la decide la exploración, no el catálogo de tratamientos disponibles.</p></section>';
	$html .= '<section class="nvx-brand-section"><h2>Honorarios transparentes desde el primer día</h2><p>El presupuesto se documenta por escrito antes de cualquier decisión. La claridad de precio no es sinónimo de descuento: es condición de confianza. El importe final y la indicación se confirman siempre después de la valoración médica presencial.</p></section>';
	$html .= '<section class="nvx-brand-section"><h2>Privacidad como diseño, no como promesa</h2><p>Cada consulta ocupa su propio box desde la llegada. No existe sala de espera compartida entre pacientes. La discreción es una condición de diseño en ambas sedes —Chamberí (Almagro) y Salamanca–Goya (Barrio de Salamanca)— no un rasgo opcional.</p></section>';
	$html .= '<section class="nvx-brand-section"><h2>Seguimiento como parte del plan</h2><p>La indicación incluye cómo y cuándo contactar con el equipo, qué evolución vigilar y cuándo revisar el caso. La recuperación no es idéntica para todas las personas.</p></section>';

	$html .= '<section class="nvx-brand-section nvx-strategy-checklist">'
		. '<h2>Lo que hacemos siempre</h2>'
		. '<ul class="nvx-check-list">'
		. '<li>Exploración médica antes de proponer cualquier tratamiento</li>'
		. '<li>Presupuesto cerrado y por escrito antes de iniciar el procedimiento</li>'
		. '<li>El médico que hace la valoración es el mismo que ejecuta el tratamiento</li>'
		. '<li>El código de lote de cada producto queda registrado en el historial clínico</li>'
		. '<li>Box privado desde la llegada: sin sala de espera compartida</li>'
		. '<li>Seguimiento posterior accesible sin necesidad de agenda nueva</li>'
		. '</ul>'
		. '</section>';

	$html .= '<section class="nvx-brand-section nvx-strategy-checklist nvx-strategy-checklist--no">'
		. '<h2>Lo que no hacemos</h2>'
		. '<ul class="nvx-check-list nvx-check-list--no">'
		. '<li>Descuentos de temporada ni urgencia de precio</li>'
		. '<li>Financiación como argumento de venta principal</li>'
		. '<li>Tratamientos sin indicación clínica previa documentada</li>'
		. '<li>Rotación de médicos sin informar al paciente</li>'
		. '<li>Valoraciones «gratuitas» que son visitas comerciales</li>'
		. '</ul>'
		. '</section>';

	$html .= '<section class="nvx-brand-section">'
		. '<h2>Trazabilidad de productos</h2>'
		. '<p>En NUVANX el médico abre cada producto en presencia del paciente. El código de lote queda adherido al historial clínico. Trabajamos exclusivamente con distribuidores oficiales de Allergan y Merz. El certificado de proveedor está disponible antes de firmar cualquier presupuesto.</p>'
		. '<p>Cada historial clínico documenta material, lote y médico responsable.</p>'
		. '</section>';

	$html .= '<section class="nvx-brand-section"><h2>Atención en centros sanitarios autorizados</h2><p>NUVANX atiende en Chamberí (Almagro · CS20144) y Salamanca–Goya (Barrio de Salamanca · CS20073), con equipo médico colegiado en el ICOMEM.</p><p><a class="nvx-button" href="' . $valuation_url . '">Solicitar valoración médica</a> <a class="nvx-brand-inline-link" href="' . $team_url . '">Conocer al equipo médico</a>';
	if ( '' !== $investment ) {
		$html .= ' <a class="nvx-brand-inline-link" href="' . esc_url( $investment ) . '">Consultar inversión orientativa</a>';
	}
	$html .= '</p></section></article>';

	return $html;
}

/**
 * Return only tariffs that the clinical-claims register has approved for use,
 * grouped by category for display.
 *
 * @return array<string,array<int,array{label:string,price:string}>>
 */
function nvx_strategy_verified_investment_groups(): array {
	if ( ! function_exists( 'nvx_tariff_catalog' ) || ! function_exists( 'nvx_format_price_eur' ) ) {
		return array();
	}

	$catalog = nvx_tariff_catalog();
	$groups  = array();

	// Endolift facial (individual zones)
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

	// Endolift facial combos
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

	// Endolift corporal (individual zones)
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

	// Endolift corporal combos
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

	// Láser CO₂
	if ( isset( $catalog['laser_co2']['facial'] ) ) {
		$groups['laser_co2'][] = array(
			'label' => $catalog['laser_co2']['facial']['label'],
			'price' => nvx_format_price_eur( $catalog['laser_co2']['facial']['pvp'] ) . ' €',
		);
	}
	if ( isset( $catalog['laser_co2']['corporal'] ) ) {
		$groups['laser_co2'][] = array(
			'label' => $catalog['laser_co2']['corporal']['label'],
			'price' => nvx_format_price_eur( $catalog['laser_co2']['corporal']['pvp'] ) . ' €',
		);
	}

	return $groups;
}

/**
 * Flat list for legacy callers. Kept for backward compatibility.
 *
 * @return array<int,array{label:string,price:string}>
 */
function nvx_strategy_verified_investment_rows(): array {
	$rows = array();
	foreach ( nvx_strategy_verified_investment_groups() as $group ) {
		foreach ( $group as $row ) {
			$rows[] = $row;
		}
	}
	return $rows;
}

/**
 * Render one price-table section for a group of tariff rows.
 *
 * @param string                          $heading  Section H2 text.
 * @param array<int,array{label:string,price:string}> $rows  Tariff rows.
 * @return string
 */
function nvx_strategy_investment_table_section( string $heading, array $rows ): string {
	if ( empty( $rows ) ) {
		return '';
	}

	$html  = '<section class="nvx-brand-section">';
	$html .= '<h2>' . esc_html( $heading ) . '</h2>';
	$html .= '<div class="nvx-endolift-price-table-wrap"><table class="nvx-endolift-price-table">';
	$html .= '<thead><tr><th scope="col">Procedimiento</th><th scope="col">PVP con IVA</th></tr></thead><tbody>';
	foreach ( $rows as $row ) {
		$html .= '<tr><td>' . esc_html( $row['label'] ) . '</td><td>' . esc_html( $row['price'] ) . '</td></tr>';
	}
	$html .= '</tbody></table></div>';
	$html .= '</section>';

	return $html;
}

/**
 * Investment page: transparent tariffs, grouped by category, with clinical context.
 */
function nvx_strategy_investment_markup(): string {
	$groups        = nvx_strategy_verified_investment_groups();
	$valuation_url = esc_url( home_url( '/madrid/valoracion/' ) );

	$html  = '<article class="nvx-brand-readable nvx-strategy-page nvx-shell">';
	$html .= '<header class="nvx-strategy-intro">'
		. '<p class="nvx-brand-kicker">Tarifas Médicas Transparentes · NUVANX Madrid</p>'
		. '<h1 class="nvx-strategy-title">El presupuesto forma parte de una decisión informada.</h1>'
		. '<p class="nvx-brand-lead">El presupuesto se documenta por escrito antes de cualquier decisión. Publicamos tarifas verificadas porque la opacidad de precio no es sinónimo de exclusividad: es una barrera para quien tiene que tomar una decisión clínica. El importe final y la indicación se confirman siempre después de la valoración médica presencial. Sin sorpresas finales.</p>'
		. '</header>';


	if ( ! empty( $groups ) ) {
		$group_labels = array(
			'endolift_facial'  => 'Endolift® facial — zonas y combinaciones',
			'endolift_corporal' => 'Endolift® corporal — zonas y combinaciones',
			'laser_co2'        => 'Láser CO₂ fraccionado',
		);
		foreach ( $group_labels as $key => $label ) {
			if ( ! empty( $groups[ $key ] ) ) {
				$html .= nvx_strategy_investment_table_section( $label, $groups[ $key ] );
			}
		}
	} else {
		$html .= '<section class="nvx-brand-section"><p>Las tarifas verificadas se mostrarán cuando estén disponibles en el catálogo clínico vigente.</p></section>';
	}

	$html .= '<section class="nvx-brand-section">'
		. '<h2>Qué incluye el precio</h2>'
		. '<p>Las tarifas mostradas corresponden al procedimiento técnico. La valoración médica previa, el protocolo anestésico tópico, la información detallada del proceso y el seguimiento posterior están incluidos en el plan general. El presupuesto final se documenta por escrito tras la exploración.</p>'
		. '<p>Otras zonas, procedimientos de medicina estética facial (neuromodulación, bioestimuladores, rellenos) y combinaciones no listadas aquí requieren exploración, indicación y presupuesto individualizado.</p>'
		. '</section>';

	$html .= '<section class="nvx-brand-section">'
		. '<h2>Sobre los precios en medicina estética en Madrid</h2>'
		. '<p>En Madrid, los precios de los mismos tratamientos varían de forma significativa entre clínicas. La razón habitual no es la tecnología: es el tiempo dedicado al diagnóstico, la experiencia del médico que ejecuta y el protocolo de seguimiento posterior. Un presupuesto muy bajo en un procedimiento invasivo no suele reflejar eficiencia; suele reflejar recortes en alguno de esos factores.</p>'
		. '<p>En NUVANX no usamos descuentos estacionales, precios de captación ni financiaciones como argumento de venta. Si el importe no encaja con tu situación, preferimos decírtelo en la valoración antes que comprometer la indicación o el protocolo.</p>'
		. '</section>';

	$html .= '<section class="nvx-brand-section">'
		. '<p><a class="nvx-button" href="' . $valuation_url . '">Solicitar valoración médica</a></p>'
		. '</section>';

	$html .= '</article>';

	return $html;
}

/**
 * Render the correct body for a strategy route.
 */
function nvx_strategy_page_markup( string $key ): string {
	if ( 'why_nuvanx' === $key ) {
		return nvx_strategy_why_nuvanx_markup();
	}

	if ( 'investment' === $key ) {
		return nvx_strategy_investment_markup();
	}

	return nvx_strategy_protocol_review_markup( $key );
}

/**
 * Use a stable, theme-owned rendering path rather than editable CMS fragments.
 */
function nvx_strategy_page_content_filter( string $content ): string {
	if ( is_admin() || ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}

	$key = nvx_strategy_current_page_key();
	return null === $key ? $content : nvx_strategy_page_markup( $key );
}
add_filter( 'the_content', 'nvx_strategy_page_content_filter', 82 );

/**
 * Create reviewable pages only in staging2. Production requires a deliberate
 * editorial publication step, and the two working-name routes never seed there.
 */
function nvx_strategy_seed_staging2_pages(): void {
	if ( ! function_exists( 'nvx_environment_is_staging2' ) || ! nvx_environment_is_staging2() ) {
		return;
	}

	foreach ( nvx_strategy_page_catalog() as $key => $page ) {
		$stored = get_page_by_path( $page['slug'] );
		if ( $stored ) {
			update_post_meta( (int) $stored->ID, '_nvx_strategy_review_status', $page['review_status'] );
			continue;
		}

		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $page['title'],
				'post_name'    => $page['slug'],
				'post_content' => '<!-- NUVANX_STRATEGY_PAGE:' . $key . ' -->',
			),
			true
		);

		if ( ! is_wp_error( $page_id ) ) {
			update_post_meta( (int) $page_id, '_nvx_strategy_review_status', $page['review_status'] );
		}
	}
}
add_action( 'init', 'nvx_strategy_seed_staging2_pages', 31 );
