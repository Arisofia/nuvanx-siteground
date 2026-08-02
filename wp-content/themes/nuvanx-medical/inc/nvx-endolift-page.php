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

require_once __DIR__ . '/nvx-page-render-helpers.php';

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
	if ( false !== strpos( $content, 'nvx-endolift-editorial' )
		|| false !== strpos( $content, 'nvx-endolaser-editorial' )
		|| false !== strpos( $content, 'nvx-co2-editorial' )
		|| false !== strpos( $content, 'nvx-equipo-editorial' ) ) {
		return false;
	}

	if ( ! nvx_endolift_is_singular_context() || is_front_page() || is_home() ) {
		return false;
	}

	$path = function_exists( 'nvx_schema_current_path' )
		? nvx_schema_current_path( (int) get_queried_object_id() )
		: '';

	if ( is_string( $path ) && (
		false !== strpos( $path, 'endolaser-corporal' )
		|| false !== strpos( $path, 'laser-co2-fraccionado' )
		|| false !== strpos( $path, 'equipo-medico' )
		|| false !== strpos( $path, 'exion' )
	) ) {
		return false;
	}

	$is_endolift = false;
	if ( is_string( $path ) && false !== strpos( $path, 'endolift-facial' ) ) {
		$is_endolift = true;
	} elseif ( preg_match(
		'/aria-label=["\']Endolift facial NUVANX["\']|id=["\']nvx-endolift-h1["\']|class=["\'][^"\']*nvx-endolift-hero(?![^"\']*nvx-endolaser)(?![^"\']*nvx-co2)(?![^"\']*nvx-equipo)/iu',
		$content
	) ) {
		$is_endolift = true;
	} elseif ( preg_match(
		'/nvx-brand-hero--laser[\s\S]{0,1200}Endolift®?[\s\S]{0,400}(papada|mand[ií]bul)/iu',
		$content
	) ) {
		$is_endolift = true;
	}

	return $is_endolift;
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

	$html  = '<div class="nvx-brand-hero__copy">';
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
			__( 'Valoración por el Dr. José Javier Rivera Tejeda (Nº Col. ICOMEM %s). Indicación, comparación con cirugía, protocolo personalizado y recuperación realista — antes de decidir.', 'nuvanx-medical' ),
			$colegiado
		)
	) . '</p>';

	if ( function_exists( 'nvx_cta_pair_markup' ) ) {
		$html .= nvx_cta_pair_markup( 'nvx-brand-actions' );
	}

	$html .= '<p class="nvx-brand-meta">' . esc_html__( 'Papada · Marcación mandibular · Óvalo facial · Chamberí · Salamanca–Goya', 'nuvanx-medical' ) . '</p>';
	$html .= '</div>';

	return $html;
}


/**
 * Full editorial body after hero.
 */
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
	$html .= nvx_page_brand_section_open_markup( 'nvx-endolift-what', 'nvx-endolift-what-title' );
	$html .= nvx_page_brand_section_heading_markup( esc_html__( 'La técnica', 'nuvanx-medical' ), 'nvx-endolift-what-title', esc_html__( '¿Qué es el Endolift® facial y cómo altera la estructura anatómica?', 'nuvanx-medical' ) );
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html__( 'No es un cosmético tópico ni un calentamiento superficial. Es medicina intervencionista mínimamente invasiva: una microfibra óptica del orden de 200–300 micras se introduce bajo la piel y libera energía láser en el tejido subcutáneo.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html__( 'Esa energía puede combinar, cuando hay indicación, reducción de grasa local en papada y línea mandibular, y retracción del tejido de soporte con estímulo de colágeno nuevo. El efecto es un tensado progresivo — no una resección quirúrgica de piel.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div></section>';

	// B. Indicaciones + diagnóstico diferencial (panel) — no price here.
	$html .= nvx_page_brand_section_open_markup( 'nvx-endolift-diagnosis', 'nvx-endolift-diagnosis-title', 'nvx-endolift-diagnosis__grid' );
	$html .= '<div class="nvx-endolift-diagnosis__copy">';
	$html .= nvx_page_brand_section_heading_markup( esc_html__( 'Indicaciones clínicas', 'nuvanx-medical' ), 'nvx-endolift-diagnosis-title', esc_html__( 'Selección rigurosa del paciente ideal', 'nuvanx-medical' ) );
	$html .= '<p class="nvx-body">' . esc_html__( 'El resultado depende sobre todo de una indicación correcta. Está orientado a flacidez leve–moderada del tercio inferior y cuello, y a grasa submentoniana moderada, cuando se busca remodelación estructural sin los riesgos y la baja de un lifting cérvicofacial.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-body">' . esc_html__( 'Se descarta en ptosis severa con pliegues marcados y exceso cutáneo evidente: la retracción térmica no sustituye a la resección quirúrgica. En ese caso se deriva a cirugía plástica.', 'nuvanx-medical' ) . '</p>';
	$html .= '<p class="nvx-body">' . esc_html__( 'Antes de programar, el diagnóstico diferencial separa laxitud del SMAS, adiposidad localizada o la combinación de ambas —eso calibra energía y vectores de la microfibra.', 'nuvanx-medical' ) . '</p>';
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
	$html .= nvx_page_brand_section_open_markup( 'nvx-endolift-compare', 'nvx-endolift-compare-title' );
	$html .= nvx_page_brand_section_heading_markup( esc_html__( 'Comparativa clínica', 'nuvanx-medical' ), 'nvx-endolift-compare-title', esc_html__( 'Endolift® vs lifting cérvicofacial quirúrgico', 'nuvanx-medical' ) );
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
	$html .= nvx_page_brand_section_open_markup( 'nvx-endolift-biophysics', 'nvx-endolift-bio-title' );
	$html .= nvx_page_brand_section_heading_markup( esc_html__( 'La biofísica', 'nuvanx-medical' ), 'nvx-endolift-bio-title', esc_html__( '1470 nm: deposición térmica controlada', 'nuvanx-medical' ) );
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html__( 'Microfibras monouso de silicio (200–300 micras) y emisión a 1470 nm, con alto coeficiente de absorción en agua y lípidos. La energía se modela como deposición local de calor en el tejido subdérmico:', 'nuvanx-medical' ) . '</p>';

	$html .= '<figure class="nvx-endolift-formula" aria-label="' . esc_attr__( 'Modelo de deposición térmica', 'nuvanx-medical' ) . '">';
	$html .= '<p class="nvx-endolift-formula__eq" role="math"><span class="nvx-endolift-formula__q">Q</span> = <span class="nvx-endolift-formula__mu">μ<sub>a</sub></span> · <span class="nvx-endolift-formula__phi">Φ</span></p>';
	$html .= '<figcaption class="nvx-endolift-formula__cap">' . esc_html__( 'Q: calor local; μₐ: coeficiente de absorción a 1470 nm; Φ: fluencia transmitida por la microfibra.', 'nuvanx-medical' ) . '</figcaption>';
	$html .= '</figure>';

	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html__( 'En rango térmico de ~60–80 °C en dermis reticular y septos, se produce desnaturalización del colágeno (contracción SMAS) y laserlipólisis de adipocitos, sin lesionar la epidermis de forma quirúrgica.', 'nuvanx-medical' ) . '</p>';
	$html .= '</div></section>';

	// E. Proceso clínico (planimetría / tumescente / abanico / 60–90 min — no second FAQ recovery essay).
	$html .= nvx_page_brand_section_open_markup( 'nvx-endolift-process', 'nvx-endolift-process-title' );
	$html .= nvx_page_brand_section_heading_markup( esc_html__( 'El procedimiento en NUVANX', 'nuvanx-medical' ), 'nvx-endolift-process-title', esc_html__( 'Ejecución paso a paso', 'nuvanx-medical' ) );
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html__( 'Duración habitual 60–90 minutos. El paciente sale por su propio pie. Recuperación social y dolor se detallan en la FAQ.', 'nuvanx-medical' ) . '</p>';
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
		$html .= '<p class="nvx-body">' . esc_html( $step['body'] ) . '</p>';
		$html .= '</article>';
	}

	$html .= '</div></div></section>';

	// E-Bis. Postoperatorio Real (SEO Capture for recovery pain/fears)
	$html .= nvx_page_brand_section_open_markup( 'nvx-endolift-postop', 'nvx-endolift-postop-title', '', array( 'id' => 'postoperatorio-endolift' ) );
	$html .= nvx_page_brand_section_heading_markup( esc_html__( 'Recuperación Transparente', 'nuvanx-medical' ), 'nvx-endolift-postop-title', esc_html__( 'Cómo es el postoperatorio real del Endolift® en Madrid (sin clichés)', 'nuvanx-medical' ) );
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html__( 'A diferencia de una cirugía invasiva (como una liposucción tradicional o un lifting), el Endolift® no requiere quirófano ni anestesia general, pero esto no significa que no haya un proceso de recuperación. Esta es la verdad clínica sobre qué esperar día a día:', 'nuvanx-medical' ) . '</p>';
	
	$html .= '<ul class="nvx-endolift-price-includes nvx-endolift-postop-list">';
	$html .= '<li><strong>' . esc_html__( 'Días 1 a 3 (Inflamación):', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'Es normal sentir la zona tratada inflamada, ligeramente acartonada y sensible al tacto. Pueden aparecer pequeños hematomas en los puntos de entrada de la fibra láser. No minimizamos el proceso: el disconfort existe, pero se controla con nuestra pauta analgésica oral estandarizada.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Semana 1 (Recuperación Social):', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'La inflamación inicial cede considerablemente. A nivel social, puedes salir a cenar o retomar reuniones sin que sea evidente que te has sometido a un procedimiento médico, aunque tú seguirás notando la zona en proceso de curación.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Semanas 2 a 4 (Retracción Tisular):', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'El tejido comienza su remodelación interna profunda. Las molestias físicas desaparecen casi por completo y empiezas a notar la piel visiblemente más firme y adherida al plano profundo.', 'nuvanx-medical' ) . '</li>';
	$html .= '<li><strong>' . esc_html__( 'Meses 2 a 3 (Resultado Real):', 'nuvanx-medical' ) . '</strong> ' . esc_html__( 'El pico máximo de neo-colagénesis se alcanza en este punto. El contorno mandibular, la papada o la zona tratada muestran su resultado clínico real.', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul>';
	$html .= '<p class="nvx-body nvx-body--measure"><em>' . esc_html__( 'Antes del procedimiento, se te entrega un protocolo escrito con tu teléfono directo de seguimiento. Agenda tu valoración médica y te explicamos exactamente qué esperar en tu anatomía.', 'nuvanx-medical' ) . '</em></p>';
	$html .= '</div></section>';

	// F. Presupuesto Clínico — Valoración personalizada.
	$html .= nvx_page_brand_section_open_markup( 'nvx-endolift-investment', 'nvx-endolift-price-title', '', array( 'id' => 'inversion-endolift' ) );
	$html .= nvx_page_brand_section_heading_markup( esc_html__( 'Presupuesto médico', 'nuvanx-medical' ), 'nvx-endolift-price-title', esc_html__( 'Valoración y presupuesto Endolift® en NUVANX Madrid', 'nuvanx-medical' ) );
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html__( 'El plan y presupuesto de Endolift® se determinan tras la valoración médica presencial en Chamberí o Salamanca–Goya. Cada tratamiento incluye:', 'nuvanx-medical' ) . '</p>';
	$html .= '<ul class="nvx-endolift-price-includes">';
	$html .= '<li>' . esc_html__( 'Valoración anatómica presencial y diagnóstico diferencial por el equipo médico', 'nuvanx-medical' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Honorarios médicos de la intervención', 'nuvanx-medical' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Fibra óptica láser monouso y material fungible de uso exclusivo', 'nuvanx-medical' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Revisiones clínicas protocolizadas (semanas 4, 8 y seguimiento posterior)', 'nuvanx-medical' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Orientación farmacológica del postoperatorio y teléfono directo de atención', 'nuvanx-medical' ) . '</li>';
	$html .= '</ul>';
	$html .= '<p class="nvx-body nvx-body--measure"><em>' . esc_html__( 'Presupuesto cerrado sin sorpresas tras la consulta de valoración.', 'nuvanx-medical' ) . '</em></p>';
	$html .= '</div></section>';

	// G. FAQ — same Q/A as FAQPage schema (nvx_schema_faq_catalog endolift_facial).
	$html .= nvx_page_brand_section_open_markup( 'nvx-endolift-faq', 'nvx-endolift-faq-title' );
	$html .= nvx_page_brand_section_heading_markup( esc_html__( 'Base de conocimiento', 'nuvanx-medical' ), 'nvx-endolift-faq-title', esc_html__( 'Preguntas clínicas frecuentes', 'nuvanx-medical' ) );
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

	$media = function_exists( 'nvx_page_extract_brand_hero_media' ) ? nvx_page_extract_brand_hero_media( $content ) : '';

	$hero  = '<section class="nvx-brand-hero" aria-labelledby="nvx-endolift-h1" aria-label="' . esc_attr__( 'Endolift facial NUVANX', 'nuvanx-medical' ) . '">';
	$hero .= '<div class="nvx-brand-hero__inner">';
	$hero .= nvx_endolift_hero_copy_markup();
	$hero .= $media;
	$hero .= '</div></section>';

	$body = nvx_endolift_editorial_body_markup();

	if ( function_exists( 'nvx_page_render_brand_wrapper' ) ) {
		return nvx_page_render_brand_wrapper( $content, $hero . $body, 'nvx-brand-page nvx-brand-page--endolift' );
	}

	if ( preg_match( '/(<div class="nvx-brand-page[^"]*"[^>]*>)/iu', $content, $wrap ) ) {
		return $wrap[1] . $hero . $body . '</div>';
	}

	return $hero . $body;
}
add_filter( 'the_content', 'nvx_content_restructure_endolift_page', 19 );
