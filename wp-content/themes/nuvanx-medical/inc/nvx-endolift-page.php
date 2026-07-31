<?php
/**
 * Endolift® facial treatment page — editorial high-authority structure.
 *
 * Wire-frame: Hero → Qué es → Indicaciones → vs cirugía → Biofísica → Proceso → Tarifas → FAQ → CTA.
 * Pattern-based (Endolift markers), not page-ID gated.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current main query is a singular page suitable for rewrite.
 */
function nvx_endolift_is_singular_context(): bool {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	// Prefer real page views; still allow content that carries structural Endolift markers
	// when queried via the main loop (avoids rewriting random posts/excerpts).
	return is_singular( 'page' ) || is_page();
}

/**
 * Detect Endolift facial treatment content before rewrite.
 * Anchors primarily on stable structural markers (aria-label / ids / brand classes).
 */
function nvx_content_is_endolift_page( string $content ): bool {
	// Already rewritten, or Endoláser page that reuses endolift layout classes.
	if ( false !== strpos( $content, 'nvx-endolift-editorial' )
		|| false !== strpos( $content, 'nvx-endolaser-editorial' )
		|| false !== strpos( $content, 'nvx-co2-editorial' )
		|| false !== strpos( $content, 'nvx-equipo-editorial' ) ) {
		return false;
	}

	if ( ! nvx_endolift_is_singular_context() ) {
		return false;
	}

	if ( is_front_page() || is_home() ) {
		return false;
	}

	$path = function_exists( 'nvx_schema_current_path' )
		? nvx_schema_current_path( (int) get_queried_object_id() )
		: '';

	// Other treatment detail URLs must not become Endolift facial.
	if ( is_string( $path ) && (
		false !== strpos( $path, 'endolaser-corporal' )
		|| false !== strpos( $path, 'laser-co2-fraccionado' )
		|| false !== strpos( $path, 'equipo-medico' )
		|| false !== strpos( $path, 'exion' )
	) ) {
		return false;
	}

	if ( is_string( $path ) && false !== strpos( $path, 'endolift-facial' ) ) {
		return true;
	}

	// Structural markers first (stable across copy edits).
	if ( preg_match(
		'/aria-label=["\']Endolift facial NUVANX["\']|id=["\']nvx-endolift-h1["\']|class=["\'][^"\']*nvx-endolift-hero(?![^"\']*nvx-endolaser)(?![^"\']*nvx-co2)(?![^"\']*nvx-equipo)/iu',
		$content
	) ) {
		return true;
	}

	// Fallback: known brand-page laser hero + Endolift product framing (not laser hub alone).
	return (bool) preg_match(
		'/nvx-brand-hero--laser[\s\S]{0,1200}Endolift®?[\s\S]{0,400}(papada|mand[ií]bul)/iu',
		$content
	);
}

/**
 * Linear process icons — Champagne Bronce stroke only (1.5px).
 *
 * @param string $name Icon key: assess|anesthesia|procedure|recover.
 */
function nvx_endolift_process_icon( string $name ): string {
	$icons = array(
		'assess'      => '<svg class="nvx-endolift-step__icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="22" cy="22" r="10" stroke="currentColor" stroke-width="1.5"/><path d="M30 30 40 40" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M18 22h8M22 18v8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		'anesthesia'  => '<svg class="nvx-endolift-step__icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M18 8h12v8l4 6v18H14V22l4-6V8Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M18 16h12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		'procedure'   => '<svg class="nvx-endolift-step__icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M10 34 28 8l10 6-18 26H10v-6Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M24 14l10 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		'recover'     => '<svg class="nvx-endolift-step__icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 28c4-10 8-14 12-14s8 4 12 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M16 18c3-2 5-3 8-3s5 1 8 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="24" cy="30" r="3" stroke="currentColor" stroke-width="1.5"/></svg>',
	);

	return $icons[ $name ] ?? $icons['assess'];
}

/**
 * Hero copy: authority + dual CTA (valoración + WhatsApp).
 */
function nvx_endolift_hero_copy_markup(): string {
	$colegiado   = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';
	$price_label = function_exists( 'nvx_format_price_eur' )
		? nvx_format_price_eur( nvx_endolift_price_from_eur() )
		: number_format_i18n( 798.60, 2 );

	$html  = '<div class="nvx-brand-hero__copy nvx-endolift-hero-copy">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'NUVANX · Medicina estética láser', 'nuvanx-medical' ) . '</p>';
	$html .= '<h1 class="nvx-brand-hero__title" id="nvx-endolift-h1">' . esc_html__( 'Endolift® en Madrid: papada, mandíbula y cuello sin quirófano', 'nuvanx-medical' ) . '</h1>';
	
	// E-E-A-T Medical Authority Byline
	$html .= '<div class="nvx-medical-byline">';
	$html .= '<div class="nvx-medical-byline__text">';
	$html .= '<strong>' . esc_html__( 'Escrito y revisado por Dr. Javier Rivera Tejeda', 'nuvanx-medical' ) . '</strong><br>';
	$html .= '<span class="nvx-medical-byline__title">' . esc_html__( 'Director médico NUVANX · Fecha de última revisión: julio 2026', 'nuvanx-medical' ) . '</span>';
	$html .= '</div></div>';
	$html .= '<p class="nvx-brand-hero__lead">' . esc_html__( 'Tratamiento subdérmico de precisión para tensado tisular y reducción de grasa localizada. Indicación médica y presupuesto cerrado tras la primera valoración en Chamberí o Salamanca.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-brand-hero__description">' . esc_html(
		sprintf(
			/* translators: %s: medical license number */
			__( 'Valoración por el Dr. José Javier Rivera Tejeda (Nº Col. ICOMEM %s). Indicación, comparación con cirugía, tarifas PVP por zona y recuperación realista — antes de decidir.', 'nuvanx-medical' ),
			$colegiado
		)
	) . '</p>';

	if ( function_exists( 'nvx_cta_pair_markup' ) ) {
		$html .= nvx_cta_pair_markup( 'nvx-endolift-hero-ctas nvx-home-hero-ctas' );
	}

	$html .= '<p class="nvx-brand-meta">' . esc_html(
		sprintf(
			/* translators: %s: price from */
			__( 'PVP desde %s € (ojeras) · Papada/mandíbula en tabla · Chamberí · Salamanca–Goya', 'nuvanx-medical' ),
			$price_label
		)
	) . '</p>';
	$html .= '</div>';

	return $html;
}


/**
 * Full editorial body after hero.
 */
/**
 * Public PVP table markup for Endolift facial (+ face combos). Body zones omitted on this page.
 *
 * @return string
 */
function nvx_endolift_price_table_markup(): string {
	if ( ! function_exists( 'nvx_tariff_catalog' ) ) {
		return '';
	}

	$catalog = nvx_tariff_catalog();
	$rows    = array();

	foreach ( $catalog['endolift'] as $row ) {
		if ( 'facial' === $row['group'] ) {
			$rows[] = $row;
		}
	}
	foreach ( $catalog['endolift_combo'] as $row ) {
		if ( 'facial' === $row['group'] ) {
			$rows[] = $row;
		}
	}

	$html  = '<div class="nvx-endolift-price-table-wrap">';
	$html .= '<table class="nvx-endolift-price-table">';
	$html .= '<caption class="nvx-endolift-price-table__cap">' . esc_html__( 'PVP con IVA incluido (21 %). Presupuesto definitivo tras valoración.', 'nuvanx-medical' ) . '</caption>';
	$html .= '<thead><tr>';
	$html .= '<th scope="col">' . esc_html__( 'Tratamiento', 'nuvanx-medical' ) . '</th>';
	$html .= '<th scope="col">' . esc_html__( 'PVP', 'nuvanx-medical' ) . '</th>';
	$html .= '</tr></thead><tbody>';

	foreach ( $rows as $row ) {
		$html .= '<tr>';
		$html .= '<th scope="row">' . esc_html( $row['label'] ) . '</th>';
		$html .= '<td>' . esc_html( nvx_format_price_eur( $row['pvp'] ) ) . '&nbsp;€</td>';
		$html .= '</tr>';
	}

	$html .= '</tbody></table></div>';

	return $html;
}

function nvx_endolift_editorial_body_markup(): string {
	$colegiado    = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';
	$price_from   = function_exists( 'nvx_endolift_price_from_eur' ) ? nvx_endolift_price_from_eur() : 798.60;
	$price_papada = function_exists( 'nvx_endolift_price_papada_eur' ) ? nvx_endolift_price_papada_eur() : 1064.80;
	$price_raw    = function_exists( 'nvx_schema_price_string' ) ? nvx_schema_price_string( $price_from ) : '798.60';
	$price_label  = function_exists( 'nvx_format_price_eur' ) ? nvx_format_price_eur( $price_from ) : number_format_i18n( $price_from, 2 );
	$papada_label = function_exists( 'nvx_format_price_eur' ) ? nvx_format_price_eur( $price_papada ) : number_format_i18n( $price_papada, 2 );
	$review_label = defined( 'NVX_ENDOLIFT_REVIEW_LABEL' ) ? NVX_ENDOLIFT_REVIEW_LABEL : 'julio 2026';
	$equipo_url   = home_url( '/equipo-medico/' );

	$html  = '<div class="nvx-endolift-editorial">';

	// Clinical review byline — E-E-A-T (visible + matches schema reviewedBy).
	$html .= '<p class="nvx-endolift-reviewed">';
	$html .= esc_html(
		sprintf(
			/* translators: 1: medical license number, 2: review month label */
			__( 'Documento clínico redactado y revisado de forma independiente por el Dr. José Javier Rivera Tejeda (Nº Col. ICOMEM %1$s). Última revisión científica: %2$s.', 'nuvanx-medical' ),
			$colegiado,
			$review_label
		)
	);
	$html .= ' <a class="nvx-brand-inline-link" href="' . esc_url( $equipo_url ) . '">' . esc_html__( 'Ver equipo médico', 'nuvanx-medical' ) . '</a>';
	$html .= '</p>';

	// A. Qué es (clinical framing; biophysics section keeps 1470 nm / formula detail).
	$html .= '<section class="nvx-endolift-section nvx-endolift-what" aria-labelledby="nvx-endolift-what-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'La técnica', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-endolift-what-title" class="nvx-endolift-heading">' . esc_html__( '¿Qué es el Endolift® facial y cómo altera la estructura anatómica?', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'No es un cosmético tópico ni un calentamiento superficial. Es medicina intervencionista mínimamente invasiva: una microfibra óptica del orden de 200–300 micras se introduce bajo la piel y libera energía láser en el tejido subcutáneo.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'Esa energía puede combinar, cuando hay indicación, reducción de grasa local en papada y línea mandibular, y retracción del tejido de soporte con estímulo de colágeno nuevo. El efecto es un tensado progresivo — no una resección quirúrgica de piel.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div></section>';

	// B. Indicaciones + diagnóstico diferencial (panel) — no price here.
	$html .= '<section class="nvx-endolift-section nvx-endolift-diagnosis" aria-labelledby="nvx-endolift-diagnosis-title">';
	$html .= '<div class="nvx-endolift-section__inner nvx-endolift-diagnosis__grid">';
	$html .= '<div class="nvx-endolift-diagnosis__copy">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Indicaciones clínicas', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-endolift-diagnosis-title" class="nvx-endolift-heading">' . esc_html__( 'Selección rigurosa del paciente ideal', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body">' . esc_html__( 'El resultado depende sobre todo de una indicación correcta. Está orientado a flacidez leve–moderada del tercio inferior y cuello, y a grasa submentoniana moderada, cuando se busca remodelación estructural sin los riesgos y la baja de un lifting cérvicofacial.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-endolift-body">' . esc_html__( 'Se descarta en ptosis severa con pliegues marcados y exceso cutáneo evidente: la retracción térmica no sustituye a la resección quirúrgica. En ese caso se deriva a cirugía plástica.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-endolift-body">' . esc_html__( 'Antes de programar, el diagnóstico diferencial separa laxitud del SMAS, adiposidad localizada o la combinación de ambas —eso calibra energía y vectores de la microfibra.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';
	$html .= '<aside class="nvx-endolift-diagnosis__panel" aria-label="' . esc_attr__( 'Criterio de diagnóstico', 'nuvanx-medical' ) . '">';
	$html .= '<p class="nvx-endolift-panel-label">' . esc_html__( 'Diagnóstico diferencial', 'nuvanx-medical' ) . '</p>';
	$html .= '<ul class="nvx-endolift-panel-list">';
	$html .= '<li><strong>' . esc_html__( 'Laxitud / SMAS', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Retracción del tejido conectivo y tensado del contorno mandibular.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Adiposidad submentoniana', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Laserlipólisis selectiva de grasa localizada en la papada.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Combinación', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Protocolo mixto con vectores y energía calibrados en consulta.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Exclusión', 'nuvanx-medical' ) . '</strong> — ' . esc_html__( 'Ptosis severa / exceso de piel: derivación quirúrgica.', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul></aside></div></section>';

	// C. Comparativa vs lifting (new — not elsewhere on page).
	$html .= '<section class="nvx-endolift-section nvx-endolift-compare" aria-labelledby="nvx-endolift-compare-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Comparativa clínica', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-endolift-compare-title" class="nvx-endolift-heading">' . esc_html__( 'Endolift® vs lifting cérvicofacial quirúrgico', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-endolift-compare-wrap">';
	$html .= '<table class="nvx-endolift-compare-table">';
	$html .= '<thead><tr>';
	$html .= '<th scope="col">' . esc_html__( 'Parámetro', 'nuvanx-medical' ) . '</th>';
	$html .= '<th scope="col">' . esc_html__( 'Endolift® (láser intersticial)', 'nuvanx-medical' ) . '</th>';
	$html .= '<th scope="col">' . esc_html__( 'Lifting cérvicofacial', 'nuvanx-medical' ) . '</th>';
	$html .= '</tr></thead><tbody>';
	$compare_rows = array(
		array( 'Naturaleza', 'Mínimamente invasiva (microfibra, sin cortes de resección)', 'Invasiva (resección y reposicionamiento tisular)' ),
		array( 'Incisiones', 'Microperforaciones sin sutura de lifting', 'Incisiones periauriculares; cicatriz residual posible' ),
		array( 'Anestesia', 'Local infiltrativa en consulta', 'General o sedación profunda habitual' ),
		array( 'Entorno', 'Ambulatorio en cabina médica', 'Quirófano; a menudo ingreso' ),
		array( 'Baja social', '3–7 días de edema/inflamación moderada', '15–21 días de curación inicial típica' ),
		array( 'Expresión facial', 'Preserva la identidad anatómica natural', 'Riesgo de alteración mecánica de la expresión' ),
		array( 'Evolución del resultado', 'Progresiva; pico de colágeno ~3–6 meses', 'Estructural tras remitir el edema postquirúrgico' ),
	);
	foreach ( $compare_rows as $row ) {
		$html .= '<tr>';
		$html .= '<th scope="row">' . esc_html( $row[0] ) . '</th>';
		$html .= '<td>' . esc_html( $row[1] ) . '</td>';
		$html .= '<td>' . esc_html( $row[2] ) . '</td>';
		$html .= '</tr>';
	}
	$html .= '</tbody></table></div></div></section>';

	// D. Biofísica (detail layer — complements “qué es”, no rewrite of clinical intro).
	$html .= '<section class="nvx-endolift-section nvx-endolift-biophysics" aria-labelledby="nvx-endolift-bio-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'La biofísica', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-endolift-bio-title" class="nvx-endolift-heading">' . esc_html__( '1470 nm: deposición térmica controlada', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'Microfibras monouso de silicio (200–300 micras) y emisión a 1470 nm, con alto coeficiente de absorción en agua y lípidos. La energía se modela como deposición local de calor en el tejido subdérmico:', 'nuvanx-medical' ) . '</p>';

	$html .= '<figure class="nvx-endolift-formula" aria-label="' . esc_attr__( 'Modelo de deposición térmica', 'nuvanx-medical' ) . '">';
	$html .= '<p class="nvx-endolift-formula__eq" role="math"><span class="nvx-endolift-formula__q">Q</span> = <span class="nvx-endolift-formula__mu">μ<sub>a</sub></span> · <span class="nvx-endolift-formula__phi">Φ</span></p>';
	$html .= '<figcaption class="nvx-endolift-formula__cap">' . esc_html__( 'Q: calor local; μₐ: coeficiente de absorción a 1470 nm; Φ: fluencia transmitida por la microfibra.', 'nuvanx-medical' ) . '</figcaption>';
	$html .= '</figure>';

	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'En rango térmico de ~60–80 °C en dermis reticular y septos, se produce desnaturalización del colágeno (contracción SMAS) y laserlipólisis de adipocitos, sin lesionar la epidermis de forma quirúrgica.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div></section>';

	// E. Proceso clínico (planimetría / tumescente / abanico / 60–90 min — no second FAQ recovery essay).
	$html .= '<section class="nvx-endolift-section nvx-endolift-process" aria-labelledby="nvx-endolift-process-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'El procedimiento en NUVANX', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-endolift-process-title" class="nvx-endolift-heading">' . esc_html__( 'Ejecución paso a paso', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'Duración habitual 60–90 minutos. El paciente sale por su propio pie. Recuperación social y dolor se detallan en la FAQ.', 'nuvanx-medical' ) . '</p>';
	$html .= '<div class="nvx-endolift-process-grid">';

	$steps = array(
		array(
			'icon'  => 'assess',
			'n'     => '01',
			'title' => __( 'Planimetría y marcaje', 'nuvanx-medical' ),
			'body'  => __( 'Mapeo de líneas de tensión y compartimentos grasos; definición de vectores y parámetros antes de la fibra.', 'nuvanx-medical' ),
		),
		array(
			'icon'  => 'anesthesia',
			'n'     => '02',
			'title' => __( 'Anestesia local tumescente', 'nuvanx-medical' ),
			'body'  => __( 'Infiltración en puntos de entrada para confort. Sensación de calor y presión, no dolor agudo.', 'nuvanx-medical' ),
		),
		array(
			'icon'  => 'procedure',
			'n'     => '03',
			'title' => __( 'Vectorización láser', 'nuvanx-medical' ),
			'body'  => __( 'Patrón subdérmico en abanico con microfibra monouso a 1470 nm según el mapa clínico.', 'nuvanx-medical' ),
		),
		array(
			'icon'  => 'recover',
			'n'     => '04',
			'title' => __( 'Alta y seguimiento', 'nuvanx-medical' ),
			'body'  => __( 'Ambulatorio. Edema 3–7 días habitual; reincorporación típica en menos de 24 h. Revisiones protocolizadas (p. ej. semanas 4 y 8).', 'nuvanx-medical' ),
		),
	);

	foreach ( $steps as $step ) {
		$html .= '<article class="nvx-endolift-step">';
		$html .= nvx_endolift_process_icon( $step['icon'] );
		$html .= '<span class="nvx-endolift-step__n">' . esc_html( $step['n'] ) . '</span>';
		$html .= '<h3 class="nvx-endolift-step__title">' . esc_html( $step['title'] ) . '</h3>';
		$html .= '<p class="nvx-endolift-body">' . esc_html( $step['body'] ) . '</p>';
		$html .= '</article>';
	}

	$html .= '</div></div></section>';

	// E-Bis. Postoperatorio Real (SEO Capture for recovery pain/fears)
	$html .= '<section class="nvx-endolift-section nvx-endolift-postop" aria-labelledby="nvx-endolift-postop-title" id="postoperatorio-endolift">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Recuperación Transparente', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-endolift-postop-title" class="nvx-endolift-heading">' . esc_html__( 'Cómo es el postoperatorio real del Endolift® en Madrid (sin clichés)', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html__( 'A diferencia de una cirugía invasiva (como una liposucción tradicional o un lifting), el Endolift® no requiere quirófano ni anestesia general, pero esto no significa que no haya un proceso de recuperación. Esta es la verdad clínica sobre qué esperar día a día:', 'nuvanx-medical' ) . '</p>';
	
	$html .= '<ul class="nvx-endolift-price-includes nvx-endolift-postop-list">';
	$html .= '<li><strong>' . esc_html__( 'Días 1 a 3 (Inflamación):', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'Es normal sentir la zona tratada inflamada, ligeramente acartonada y sensible al tacto. Pueden aparecer pequeños hematomas en los puntos de entrada de la fibra láser. No minimizamos el proceso: el disconfort existe, pero se controla con nuestra pauta analgésica oral estandarizada.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Semana 1 (Recuperación Social):', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'La inflamación inicial cede considerablemente. A nivel social, puedes salir a cenar o retomar reuniones sin que sea evidente que te has sometido a un procedimiento médico, aunque tú seguirás notando la zona en proceso de curación.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Semanas 2 a 4 (Retracción Tisular):', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'El tejido comienza su remodelación interna profunda. Las molestias físicas desaparecen casi por completo y empiezas a notar la piel visiblemente más firme y adherida al plano profundo.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Meses 2 a 3 (Resultado Real):', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'El pico máximo de neo-colagénesis se alcanza en este punto. El contorno mandibular, la papada o la zona tratada muestran su resultado clínico real.', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure"><em>' . esc_html__( 'Antes del procedimiento, se te entrega un protocolo escrito con tu teléfono directo de seguimiento. Agenda tu valoración médica y te explicamos exactamente qué esperar en tu anatomía.', 'nuvanx-medical' ) . '</em></p>';
	$html .= '</div></section>';

	// F. Inversión — official PVP table (clinic tariff catalog; not a single outdated price).
	$html .= '<section class="nvx-endolift-section nvx-endolift-investment" aria-labelledby="nvx-endolift-price-title" id="inversion-endolift">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Transparencia de precios', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-endolift-price-title" class="nvx-endolift-heading">' . esc_html__( 'Estructura de precios Endolift® en NUVANX Madrid', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-endolift-price" data-nvx-price-from="' . esc_attr( $price_raw ) . '">';
	$html .= esc_html(
		sprintf(
			/* translators: %s: price in euros */
			__( 'Desde %s €', 'nuvanx-medical' ),
			$price_label
		)
	);
	$html .= '</p>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure">' . esc_html(
		sprintf(
			/* translators: 1: from price, 2: papada price */
			__( 'PVP con IVA incluido según tarifa clínica oficial. Facial desde %1$s € (ojeras). Papada y marcación mandibular: %2$s €. Full Face y combos en la tabla. Presupuesto cerrado tras valoración anatómica presencial.', 'nuvanx-medical' ),
			$price_label,
			$papada_label
		)
	) . '</p>';
	$html .= nvx_endolift_price_table_markup();
	$html .= '<ul class="nvx-endolift-price-includes">';
	$html .= '<li>' . esc_html__( 'Honorarios médicos de la intervención', 'nuvanx-medical' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Fibra óptica láser monouso y material fungible', 'nuvanx-medical' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Revisiones clínicas protocolizadas (semanas 4, 8 y control posterior)', 'nuvanx-medical' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Orientación farmacológica del postoperatorio', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul>';
	$html .= '<p class="nvx-endolift-body nvx-endolift-body--measure"><em>' . esc_html__( 'Áreas reducidas o planes combinados pueden ajustar el importe final tras la evaluación. Zonas corporales se presupuestan por mapa anatómico.', 'nuvanx-medical' ) . '</em></p>';
	$html .= '</div></section>';

	// G. FAQ — same Q/A as FAQPage schema (nvx_schema_faq_catalog endolift_facial).
	$html .= '<section class="nvx-endolift-section nvx-endolift-faq" aria-labelledby="nvx-endolift-faq-title">';
	$html .= '<div class="nvx-endolift-section__inner">';
	$html .= '<p class="nvx-endolift-kicker">' . esc_html__( 'Base de conocimiento', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-endolift-faq-title" class="nvx-endolift-heading">' . esc_html__( 'Preguntas clínicas frecuentes', 'nuvanx-medical' ) . '</h2>';
	$html .= '<div class="nvx-faq nvx-endolift-faq-list">';

	// Shared catalog so HTML and JSON-LD never diverge.
	$faqs = array();
	if ( function_exists( 'nvx_schema_faq_catalog' ) ) {
		$catalog = nvx_schema_faq_catalog();
		if ( ! empty( $catalog['endolift_facial'] ) ) {
			$faqs = $catalog['endolift_facial'];
		}
	}
	if ( empty( $faqs ) ) {
		$faqs = array(
			array(
				'q' => '¿Cuánto cuesta el Endolift® facial en NUVANX Madrid?',
				'a' => 'La tarifa de referencia parte desde ' . $price_label . ' €. El presupuesto definitivo se documenta tras valoración anatómica presencial.',
			),
		);
	}

	foreach ( $faqs as $faq ) {
		$html .= '<details class="nvx-brand-faq-item">';
		$html .= '<summary><span>' . esc_html( $faq['q'] ) . '</span></summary>';
		$html .= '<div class="nvx-brand-faq-content"><p>' . esc_html( $faq['a'] ) . '</p></div>';
		$html .= '</details>';
	}

	$html .= '</div></div></section>';

	// Closing valoración CTA: site-wide nvx-cta-banner in footer.php (not page-local).

	$html .= '</div>';

	return $html;
}

/**
 * Rebuild Endolift page: authority hero + diagnosis + biophysics + process + FAQ + CTA.
 */
function nvx_content_restructure_endolift_page( string $content ): string {
	if ( ! nvx_content_is_endolift_page( $content ) ) {
		return $content;
	}

	$media = '';
	if ( preg_match( '/<figure class="nvx-brand-hero__media"[\s\S]*?<\/figure>/iu', $content, $m ) ) {
		$media = $m[0];
	} elseif ( preg_match( '/<div class="nvx-brand-hero__media"[\s\S]*?<\/div>/iu', $content, $m ) ) {
		$media = $m[0];
	}

	$hero  = '<section class="nvx-brand-hero nvx-brand-hero--laser nvx-endolift-hero" aria-labelledby="nvx-endolift-h1" aria-label="' . esc_attr__( 'Endolift facial NUVANX', 'nuvanx-medical' ) . '">';
	$hero .= '<div class="nvx-brand-hero__inner">';
	$hero .= nvx_endolift_hero_copy_markup();
	$hero .= $media;
	$hero .= '</div></section>';

	$body = nvx_endolift_editorial_body_markup();

	if ( preg_match( '/(<div class="nvx-brand-page[^"]*"[^>]*>)/iu', $content, $wrap ) ) {
		return $wrap[1] . $hero . $body . '</div>';
	}

	return $hero . $body;
}
add_filter( 'the_content', 'nvx_content_restructure_endolift_page', 19 );
