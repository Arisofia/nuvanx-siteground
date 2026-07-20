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

	return '<article class="nvx-brand-readable nvx-strategy-page nvx-strategy-page--review">'
		. '<header class="nvx-brand-hero"><p class="nvx-brand-kicker">NUVANX · revisión clínica y jurídica</p>'
		. '<h1 class="nvx-brand-title">' . esc_html( $page['title'] ) . '</h1>'
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
	$html .= '<header class="nvx-brand-hero"><p class="nvx-brand-kicker">Criterio médico NUVANX</p>';
	$html .= '<h1 class="nvx-brand-title">Si no hay indicación clínica, no hay tratamiento.</h1>';
	$html .= '<p class="nvx-brand-lead">Una decisión de medicina estética empieza por la exploración, no por una tecnología, una tendencia o una promesa de resultado.</p></header>';
	$html .= '<section class="nvx-brand-section"><h2>Diagnóstico antes de tecnología</h2><p>Revisamos anatomía, antecedentes, objetivos, contraindicaciones y expectativas. Solo entonces se valora si procede tratar, esperar, derivar o no intervenir.</p></section>';
	$html .= '<section class="nvx-brand-section"><h2>Nuestro compromiso clínico</h2><ul class="nvx-check-list">';
	$html .= '<li>Diagnóstico médico presencial obligatorio antes de prescribir cualquier tratamiento.</li>';
	$html .= '<li>Trazabilidad total: apertura de inyectables en tu presencia y entrega del código de lote.</li>';
	$html .= '<li>Claridad absoluta sobre tiempos reales de recuperación y gestión clínica del dolor.</li>';
	$html .= '<li>Presupuestos transparentes y cerrados desde el primer momento.</li>';
	$html .= '<li>Seguimiento exhaustivo incluido en todos los planes terapéuticos.</li>';
	$html .= '<li>No somos una franquicia: atención médica de autor liderada por el Dr. Rivera Tejeda.</li>';
	$html .= '</ul></section>';
	$html .= '<section class="nvx-brand-section"><h2>Lo que no hacemos</h2><ul class="nvx-cross-list">';
	$html .= '<li>No emitimos presupuestos médicos por teléfono o WhatsApp sin haber evaluado tu anatomía presencialmente.</li>';
	$html .= '<li>No delegamos la indicación ni la recomendación en asesores comerciales. Siempre hablarás con tu médico.</li>';
	$html .= '<li>No participamos en "ofertas flash" o campañas orientadas al consumo compulsivo de medicina.</li>';
	$html .= '<li>No cedemos a modas estéticas si comprometen la naturalidad o salud de tu rostro.</li>';
	$html .= '</ul></section>';
	$html .= '<section class="nvx-brand-section"><h2>Atención en centros sanitarios autorizados</h2><p>NUVANX atiende en Chamberí (CS20144) y Salamanca–Goya (CS20073), con equipo médico colegiado.</p><p><a class="nvx-button" href="' . $valuation_url . '">Solicitar valoración médica</a> <a class="nvx-brand-inline-link" href="' . $team_url . '">Conocer al equipo médico</a>';
	if ( '' !== $investment ) {
		$html .= ' <a class="nvx-brand-inline-link" href="' . esc_url( $investment ) . '">Consultar inversión orientativa</a>';
	}
	$html .= '</p></section></article>';

	return $html;
}

/**
 * Return only tariffs that the clinical-claims register has approved for use.
 *
 * @return array<int,array{label:string,price:string}>
 */
function nvx_strategy_verified_investment_rows(): array {
	if ( ! function_exists( 'nvx_endolift_price_from_eur' ) || ! function_exists( 'nvx_endolift_price_papada_eur' ) || ! function_exists( 'nvx_format_price_eur' ) ) {
		return array();
	}

	return array(
		'Láser Endolift®' => array(
			array(
				'label' => 'Endolift® · tercio inferior (papada y línea mandibular)',
				'price' => nvx_format_price_eur( nvx_endolift_price_papada_eur() ) . ' €',
			),
			array(
				'label' => 'Endolift® · ojeras o código de barras',
				'price' => nvx_format_price_eur( nvx_endolift_price_from_eur() ) . ' €',
			),
			array(
				'label' => 'Endolift® · corporal (abdomen, rodillas, brazos)',
				'price' => 'desde 1.100 €',
			),
		),
		'Láser CO₂ Fraccionado' => array(
			array(
				'label' => 'CO₂ Fraccionado · sesión completa facial',
				'price' => '350 €',
			),
			array(
				'label' => 'CO₂ Fraccionado · zona localizada (ojeras, código barras)',
				'price' => '150 €',
			),
		),
		'Medicina Estética Avanzada' => array(
			array(
				'label' => 'Toxina botulínica · tercio superior completo',
				'price' => '320 €',
			),
			array(
				'label' => 'Armonización facial con Ácido Hialurónico',
				'price' => 'desde 300 € / vial',
			),
			array(
				'label' => 'EXION® BTL · Radiofrecuencia Fraccionada con IA',
				'price' => 'desde 250 € / sesión',
			),
		),
	);
}

/**
 * Investment page: transparent, limited to approved tariffs and no bait pricing.
 */
function nvx_strategy_investment_markup(): string {
	$catalog       = nvx_strategy_verified_investment_rows();
	$valuation_url = esc_url( home_url( '/madrid/valoracion/' ) );
	$html          = '<article class="nvx-brand-readable nvx-strategy-page">';
	$html         .= '<header class="nvx-brand-hero"><p class="nvx-brand-kicker">Información de inversión</p><h1 class="nvx-brand-title">El presupuesto forma parte de una decisión informada.</h1><p class="nvx-brand-lead">Mostramos tarifas médicas verificadas sin letra pequeña. El importe final se documenta por escrito tras la valoración anatómica presencial.</p></header>';
	$html         .= '<section class="nvx-brand-section"><h2>Catálogo orientativo de tratamientos</h2>';

	if ( $catalog ) {
		foreach ( $catalog as $group_name => $rows ) {
			$html .= '<h3 class="nvx-price-group-title">' . esc_html( $group_name ) . '</h3>';
			$html .= '<div class="nvx-table-wrap"><table class="nvx-price-table"><thead><tr><th scope="col">Procedimiento</th><th scope="col">PVP con IVA</th></tr></thead><tbody>';
			foreach ( $rows as $row ) {
				$html .= '<tr><td>' . esc_html( $row['label'] ) . '</td><td>' . esc_html( $row['price'] ) . '</td></tr>';
			}
			$html .= '</tbody></table></div>';
		}
	} else {
		$html .= '<p>Las tarifas verificadas se mostrarán cuando estén disponibles en el catálogo clínico vigente.</p>';
	}

	$html .= '<p>Otras zonas y procedimientos requieren exploración y presupuesto individualizado. En NUVANX los presupuestos incluyen las revisiones necesarias y la garantía de trazabilidad del producto.</p>';
	$html .= '<p><a class="nvx-button" href="' . $valuation_url . '">Solicitar valoración médica</a></p></section></article>';

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
