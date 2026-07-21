<?php
/**
 * Strategy-led authority and investment pages.
 *
 * Public copy stays within the clinical claims register and only approved
 * strategy pages are represented in the runtime catalog.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the approved strategy page catalog.
 *
 * @return array<string,array{slug:string,title:string,review_status:string}>
 */
function nvx_strategy_page_catalog(): array {
	return array(
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

/**
 * Identifies the current page's strategy catalog entry.
 *
 * @return string|null The matching strategy key, or null when the current request is not a cataloged page.
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
 * Returns a public URL only when an approved strategy page is published.
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
 * Builds the NUVANX medical criteria and patient-care standards page markup.
 *
 * @return string The rendered HTML for the NUVANX strategy page.
 */
function nvx_strategy_why_nuvanx_markup(): string {
	$valuation_url = esc_url( home_url( '/madrid/valoracion/' ) );
	$team_url      = esc_url( home_url( '/equipo-medico/' ) );
	$investment    = nvx_strategy_published_url( 'investment' );

	$html  = '<article class="nvx-brand-readable nvx-strategy-page">';
	$html .= '<header class="nvx-strategy-intro"><p class="nvx-brand-kicker">Criterio médico NUVANX</p>';
	$html .= '<h1 class="nvx-strategy-title">El diagnóstico precede a la indicación.</h1>';
	$html .= '<p class="nvx-brand-lead">Madrid. Medicina estética láser y well-aging. Un único criterio médico desde la primera valoración hasta el alta.</p></header>';

	$html .= '<section class="nvx-brand-section"><h2>Diagnóstico antes de tecnología</h2><p>Revisamos anatomía, antecedentes, objetivos, contraindicaciones y expectativas. Solo entonces se valora si procede tratar, esperar, derivar o no intervenir.</p></section>';
	$html .= '<section class="nvx-brand-section"><h2>Claridad antes de decidir</h2><p>El plan explica la alternativa propuesta, sus límites, cuidados, posibles efectos y presupuesto. La decisión se toma con información comprensible y con tiempo para resolver dudas.</p></section>';
	$html .= '<section class="nvx-brand-section"><h2>Seguimiento como parte del plan</h2><p>La indicación incluye cómo y cuándo contactar con el equipo, qué evolución vigilar y cuándo revisar el caso. La recuperación no se presenta como idéntica para todas las personas.</p></section>';

	$html .= '<section class="nvx-brand-section nvx-strategy-checklist">'
		. '<h2>Lo que hacemos siempre</h2>'
		. '<ul class="nvx-check-list">'
		. '<li>Exploración médica antes de proponer cualquier tratamiento</li>'
		. '<li>Presupuesto cerrado y por escrito antes de iniciar el procedimiento</li>'
		. '<li>El médico que hace la valoración es el mismo que ejecuta el tratamiento</li>'
		. '<li>El código de lote de cada producto queda registrado en el historial clínico</li>'
		. '<li>Sala de espera individual: cada paciente ocupa su propio espacio</li>'
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
		. '<li>Valoraciones "gratuitas" que son visitas comerciales</li>'
		. '</ul>'
		. '</section>';

	$html .= '<section class="nvx-brand-section">'
		. '<h2>Trazabilidad de productos</h2>'
		. '<p>En NUVANX el médico abre cada producto en presencia del paciente. El código de lote queda adherido al historial clínico. Trabajamos exclusivamente con distribuidores oficiales de las marcas que empleamos. El certificado de proveedor está disponible antes de firmar cualquier presupuesto.</p>'
		. '<p>Este procedimiento no es habitual en el sector. Lo describimos porque creemos que debería serlo.</p>'
		. '</section>';

	$html .= '<section class="nvx-brand-section"><h2>Atención en centros sanitarios autorizados</h2><p>NUVANX atiende en Chamberí (CS20144) y Salamanca–Goya (CS20073), con equipo médico colegiado.</p><p><a class="nvx-button" href="' . $valuation_url . '">Solicitar valoración médica</a> <a class="nvx-brand-inline-link" href="' . $team_url . '">Conocer al equipo médico</a>';
	if ( '' !== $investment ) {
		$html .= ' <a class="nvx-brand-inline-link" href="' . esc_url( $investment ) . '">Consultar inversión orientativa</a>';
	}
	$html .= '</p></section></article>';

	return $html;
}

/**
 * Groups available clinical tariffs by treatment category.
 *
 * @return array<string, array<int, array{label: string, price: string}>>
 */
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
 * Returns a flat tariff list for backward-compatible callers.
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
 * Renders one price-table section for a group of tariff rows.
 *
 * @param string                                         $heading Section H2 text.
 * @param array<int,array{label:string,price:string}> $rows    Tariff rows.
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
 * Builds the investment page with transparent tariffs and clinical context.
 */
function nvx_strategy_investment_markup(): string {
	$groups        = nvx_strategy_verified_investment_groups();
	$valuation_url = esc_url( home_url( '/madrid/valoracion/' ) );

	$html  = '<article class="nvx-brand-readable nvx-strategy-page nvx-shell">';
	$html .= '<header class="nvx-strategy-intro">'
		. '<p class="nvx-brand-kicker">Inversión en medicina estética · NUVANX Madrid</p>'
		. '<h1 class="nvx-strategy-title">El presupuesto forma parte de una decisión informada.</h1>'
		. '<p class="nvx-brand-lead">Publicamos tarifas verificadas porque la opacidad de precio no es sinónimo de exclusividad: es una barrera para quien tiene que tomar una decisión clínica. El importe final y la indicación se confirman siempre después de la valoración médica presencial.</p>'
		. '</header>';

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
 * Generates the page markup for a supported strategy route.
 *
 * @param string $key The strategy route key.
 * @return string The strategy page markup, or an empty string for an unsupported key.
 */
function nvx_strategy_page_markup( string $key ): string {
	if ( 'why_nuvanx' === $key ) {
		return nvx_strategy_why_nuvanx_markup();
	}
	if ( 'investment' === $key ) {
		return nvx_strategy_investment_markup();
	}
	return '';
}

/**
 * Replaces the content of the current strategy page with its generated markup.
 *
 * @param string $content The original page content.
 * @return string The generated strategy markup or the original content when the request is not eligible.
 */
function nvx_strategy_page_content_filter( string $content ): string {
	if ( is_admin() || ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}

	$key = nvx_strategy_current_page_key();
	return null === $key ? $content : nvx_strategy_page_markup( $key );
}
add_filter( 'the_content', 'nvx_strategy_page_content_filter', 82 );
