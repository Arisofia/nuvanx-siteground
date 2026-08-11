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
		'assess'     => '<svg class="nvx-endolift-step__icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="22" cy="22" r="10" stroke="currentColor" stroke-width="1.5"/><path d="M30 30 40 40" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M18 22h8M22 18v8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		'anesthesia' => '<svg class="nvx-endolift-step__icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M18 8h12v8l4 6v18H14V22l4-6V8Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M18 16h12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		'procedure'  => '<svg class="nvx-endolift-step__icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M10 34 28 8l10 6-18 26H10v-6Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M24 14l10 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		'recover'    => '<svg class="nvx-endolift-step__icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 28c4-10 8-14 12-14s8 4 12 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M16 18c3-2 5-3 8-3s5 1 8 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="24" cy="30" r="3" stroke="currentColor" stroke-width="1.5"/></svg>',
	);

	return $icons[ $name ] ?? $icons['assess'];
}

/**
 * Builds the Endolift hero copy with medical authority details, descriptive content, calls to action, and metadata.
 *
 * @return string The rendered hero copy markup.
 */
function nvx_endolift_hero_copy_markup(): string {
	require_once __DIR__ . '/nvx-catalog-json.php';
	$data = nvx_catalog_json_resolved( 'endolift-page.json' )['hero'] ?? array();

	$colegiado = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';

	$html  = '<div class="nvx-brand-hero__copy">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html( $data['kicker'] ?? '' ) . '</p>';
	$html .= '<h1 class="nvx-brand-hero__title" id="nvx-endolift-h1">' . esc_html( $data['h1'] ?? '' ) . '</h1>';

	// E-E-A-T Medical Authority Byline
	$html .= '<div class="nvx-medical-byline">';
	$html .= '<div class="nvx-medical-byline__text">';
	$html .= '<strong>' . esc_html( $data['byline_author'] ?? '' ) . '</strong><br>';
	$html .= '<span class="nvx-medical-byline__title">' . esc_html( $data['byline_title'] ?? '' ) . '</span>';
	$html .= '</div></div>';
	$html .= '<p class="nvx-brand-hero__lead">' . esc_html( $data['lead'] ?? '' ) . '</p>';
	$html .= '<p class="nvx-brand-hero__description">' . esc_html(
		sprintf(
			/* translators: %s: medical license number */
			$data['description'] ?? '',
			$colegiado
		)
	) . '</p>';

	if ( function_exists( 'nvx_cta_pair_markup' ) ) {
		$html .= nvx_cta_pair_markup( 'nvx-brand-actions' );
	} else {
		$html .= '<div class="nvx-brand-actions"><a class="nvx-brand-btn nvx-brand-btn--primary" href="' . esc_url( home_url( '/madrid/valoracion/' ) ) . '">' . esc_html__( 'Reservar valoración médica', 'nuvanx-medical' ) . '</a></div>';
	}

	$html .= '<p class="nvx-brand-meta">' . esc_html( $data['meta'] ?? '' ) . '</p>';
	$html .= '</div>';

	return $html;
}


/**
 * Builds the Endolift editorial body markup, including clinical information, treatment details, pricing, recovery guidance, and FAQs.
 *
 * @return string The rendered editorial body HTML.
 */
function nvx_endolift_editorial_body_markup(): string {
	require_once __DIR__ . '/nvx-catalog-json.php';
	$data = nvx_catalog_json_resolved( 'endolift-page.json' );

	$colegiado    = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';
	$price_from   = function_exists( 'nvx_endolift_price_from_eur' ) ? nvx_endolift_price_from_eur() : 798.60;
	$price_label  = function_exists( 'nvx_format_price_eur' ) ? nvx_format_price_eur( $price_from ) : number_format_i18n( $price_from, 2 );
	$review_label = defined( 'NVX_ENDOLIFT_REVIEW_LABEL' ) ? NVX_ENDOLIFT_REVIEW_LABEL : 'julio 2026';
	$equipo_url   = home_url( '/equipo-medico/' );

	$html = '<div class="nvx-endolift-editorial">';

	// Clinical review byline — E-E-A-T (visible + matches schema reviewedBy).
	$html .= '<p class="nvx-endolift-reviewed">';
	$html .= esc_html(
		sprintf(
			/* translators: 1: medical license number, 2: review month label */
			$data['review']['text'] ?? '',
			$colegiado,
			$review_label
		)
	);
	$html .= ' <a class="nvx-brand-inline-link" href="' . esc_url( $equipo_url ) . '">' . esc_html( $data['review']['link'] ?? '' ) . '</a>';
	$html .= '</p>';

	// A. Qué es (clinical framing; biophysics section keeps 1470 nm / formula detail).
	$html .= nvx_page_brand_section_open_markup( 'nvx-endolift-what', 'nvx-endolift-what-title' );
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['what']['kicker'] ?? '' ), 'nvx-endolift-what-title', esc_html( $data['what']['title'] ?? '' ) );
	foreach ( $data['what']['body'] ?? array() as $paragraph ) {
		$html .= '<p class="nvx-body nvx-body--measure">' . esc_html( $paragraph ) . '</p>';
	}
	$html .= '</div></section>';

	// B. Indicaciones + diagnóstico diferencial (panel) — no price here.
	$html .= nvx_page_brand_section_open_markup( 'nvx-endolift-diagnosis', 'nvx-endolift-diagnosis-title', 'nvx-endolift-diagnosis__grid' );
	$html .= '<div class="nvx-endolift-diagnosis__copy">';
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['diagnosis']['kicker'] ?? '' ), 'nvx-endolift-diagnosis-title', esc_html( $data['diagnosis']['title'] ?? '' ) );
	foreach ( $data['diagnosis']['body'] ?? array() as $paragraph ) {
		$html .= '<p class="nvx-body">' . esc_html( $paragraph ) . '</p>';
	}
	$html .= '</div>';
	$html .= '<aside class="nvx-fact-panel" aria-label="' . esc_attr__( 'Criterio de diagnóstico', 'nuvanx-medical' ) . '">';
	$html .= '<p class="nvx-fact-panel__label">' . esc_html( $data['diagnosis']['panel_title'] ?? '' ) . '</p>';
	$html .= '<ul class="nvx-fact-panel__list" role="list">';
	foreach ( $data['diagnosis']['panel_items'] ?? array() as $item ) {
		$html .= '<li><strong>' . esc_html( $item['title'] ?? '' ) . '</strong> — ' . esc_html( $item['body'] ?? '' ) . '</li>';
	}
	$html .= '</ul></aside></div></section>';

	// C. Comparativa vs lifting (new — not elsewhere on page).
	$html .= nvx_page_brand_section_open_markup( 'nvx-endolift-compare', 'nvx-endolift-compare-title' );
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['compare']['kicker'] ?? '' ), 'nvx-endolift-compare-title', esc_html( $data['compare']['title'] ?? '' ) );
	$html .= '<div class="nvx-endolift-compare-wrap">';
	$html .= '<table class="nvx-endolift-compare-table">';
	$html .= '<thead><tr>';
	$html .= '<th scope="col">' . esc_html( $data['compare']['col_param'] ?? '' ) . '</th>';
	$html .= '<th scope="col">' . esc_html( $data['compare']['col_endo'] ?? '' ) . '</th>';
	$html .= '<th scope="col">' . esc_html( $data['compare']['col_lift'] ?? '' ) . '</th>';
	$html .= '</tr></thead><tbody>';
	foreach ( $data['compare']['rows'] ?? array() as $row ) {
		$html .= '<tr>';
		$html .= '<th scope="row">' . esc_html( $row['param'] ?? '' ) . '</th>';
		$html .= '<td>' . esc_html( $row['endo'] ?? '' ) . '</td>';
		$html .= '<td>' . esc_html( $row['lift'] ?? '' ) . '</td>';
		$html .= '</tr>';
	}
	$html .= '</tbody></table></div></div></section>';

	// D. Biofísica (detail layer — complements “qué es”, no rewrite of clinical intro).
	$html .= nvx_page_brand_section_open_markup( 'nvx-endolift-biophysics', 'nvx-endolift-bio-title' );
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['biophysics']['kicker'] ?? '' ), 'nvx-endolift-bio-title', esc_html( $data['biophysics']['title'] ?? '' ) );
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html( $data['biophysics']['body1'] ?? '' ) . '</p>';

	$html .= '<figure class="nvx-endolift-formula">';
	$html .= '<p class="nvx-endolift-formula__eq" aria-hidden="true"><span class="nvx-endolift-formula__q">Q</span> = <span class="nvx-endolift-formula__mu">μ<sub>a</sub></span> · <span class="nvx-endolift-formula__phi">Φ</span></p>';
	$html .= '<figcaption class="nvx-endolift-formula__cap">' . esc_html( $data['biophysics']['caption'] ?? '' ) . '</figcaption>';
	$html .= '</figure>';

	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html( $data['biophysics']['body2'] ?? '' ) . '</p>';
	$html .= '</div></section>';

	// E. Proceso clínico (planimetría / tumescente / abanico / 60–90 min — no second FAQ recovery essay).
	$html .= nvx_page_brand_section_open_markup( 'nvx-endolift-process', 'nvx-endolift-process-title' );
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['process']['kicker'] ?? '' ), 'nvx-endolift-process-title', esc_html( $data['process']['title'] ?? '' ) );
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html( $data['process']['body'] ?? '' ) . '</p>';
	$html .= '<div class="nvx-endolift-process-grid">';

	$step_idx = 0;
	foreach ( $data['process']['steps'] ?? array() as $step ) {
		$sid   = 'nvx-endolift-step-' . $step_idx;
		$html .= '<article class="nvx-endolift-step" aria-labelledby="' . esc_attr( $sid ) . '">';
		$html .= nvx_endolift_process_icon( $step['icon'] ?? 'assess' );
		$html .= '<span class="nvx-endolift-step__n">' . esc_html( $step['n'] ?? '' ) . '</span>';
		$html .= '<h3 id="' . esc_attr( $sid ) . '" class="nvx-endolift-step__title">' . esc_html( $step['title'] ?? '' ) . '</h3>';
		$html .= '<p class="nvx-body">' . esc_html( $step['body'] ?? '' ) . '</p>';
		$html .= '</article>';
		++$step_idx;
	}

	$html .= '</div></div></section>';

	// E-Bis. Postoperatorio Real (SEO Capture for recovery pain/fears)
	$html .= nvx_page_brand_section_open_markup( 'nvx-endolift-postop', 'nvx-endolift-postop-title', '', array( 'id' => 'postoperatorio-endolift' ) );
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['postop']['kicker'] ?? '' ), 'nvx-endolift-postop-title', esc_html( $data['postop']['title'] ?? '' ) );
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html( $data['postop']['body'] ?? '' ) . '</p>';

	$html .= '<ul class="nvx-endolift-price-includes nvx-endolift-postop-list" role="list">';
	foreach ( $data['postop']['items'] ?? array() as $item ) {
		$html .= '<li><strong>' . esc_html( $item['title'] ?? '' ) . '</strong> ' . esc_html( $item['body'] ?? '' ) . '</li>';
	}
	$html .= '</ul>';
	$html .= '<p class="nvx-body nvx-body--measure"><em>' . esc_html( $data['postop']['note'] ?? '' ) . '</em></p>';
	$html .= '</div></section>';

	// F. Presupuesto Clínico — Valoración personalizada.
	$html .= nvx_page_brand_section_open_markup( 'nvx-endolift-investment', 'nvx-endolift-price-title', '', array( 'id' => 'inversion-endolift' ) );
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['investment']['kicker'] ?? '' ), 'nvx-endolift-price-title', esc_html( $data['investment']['title'] ?? '' ) );
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html( $data['investment']['body'] ?? '' ) . '</p>';
	$html .= '<ul class="nvx-endolift-price-includes" role="list">';
	foreach ( $data['investment']['items'] ?? array() as $item ) {
		$html .= '<li>' . esc_html( $item ) . '</li>';
	}
	$html .= '</ul>';
	$html .= '<p class="nvx-body nvx-body--measure"><em>' . esc_html( $data['investment']['note'] ?? '' ) . '</em></p>';
	$html .= '</div></section>';

	// G. FAQ — same Q/A as FAQPage schema (nvx_schema_faq_catalog endolift_facial).
	$html .= nvx_page_brand_section_open_markup( 'nvx-endolift-faq', 'nvx-endolift-faq-title' );
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['faq']['kicker'] ?? '' ), 'nvx-endolift-faq-title', esc_html( $data['faq']['title'] ?? '' ) );
	$html .= '<div class="nvx-faq nvx-endolift-faq-list">';

	// Shared catalog so HTML and JSON-LD never diverge.
	$faqs = array();
	if ( function_exists( 'nvx_schema_faq_catalog' ) ) {
		$catalog = nvx_schema_faq_catalog();
		if ( ! empty( $catalog['endolift_facial'] ) ) {
			$faqs = $catalog['endolift_facial'];
		}
	}
	if ( empty( $faqs ) && ! empty( $data['faq']['items'] ) && is_array( $data['faq']['items'] ) ) {
		// Process JSON FAQs to replace hardcoded prices with dynamic tariff constants
		$faqs = array();
		foreach ( $data['faq']['items'] as $faq ) {
			$answer = $faq['a'];
			// Replace hardcoded prices with dynamic tariff values
			if ( function_exists( 'nvx_endolift_price_from_eur' ) && function_exists( 'nvx_endolift_price_papada_eur' ) ) {
				$from   = function_exists( 'nvx_format_price_eur' ) ? nvx_format_price_eur( nvx_endolift_price_from_eur() ) : number_format_i18n( nvx_endolift_price_from_eur(), 2 );
				$papada = function_exists( 'nvx_format_price_eur' ) ? nvx_format_price_eur( nvx_endolift_price_papada_eur() ) : number_format_i18n( nvx_endolift_price_papada_eur(), 2 );
				$answer = str_replace( '798 €', $from . ' €', $answer );
				$answer = str_replace( '1.064,80 €', $papada . ' €', $answer );
			}
			$faqs[] = array(
				'q' => $faq['q'],
				'a' => $answer,
			);
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
add_filter(
	'nvx_page_owner',
	function ( $owner ) {
		if ( ! empty( $owner ) ) {
			return $owner;
		}
		global $post;
		$content = $post ? $post->post_content : '';
		if ( function_exists( 'nvx_content_is_endolift_page' ) && nvx_content_is_endolift_page( $content ) ) {
			return 'nvx_endolift_page';
		}
		return $owner;
	}
);

function nvx_content_restructure_endolift_page( string $content ): string {
	$owner = function_exists( 'nvx_get_page_owner' ) ? nvx_get_page_owner() : null;
	if ( $owner !== 'nvx_endolift_page' ) {
		return $content;
	}

	$media = function_exists( 'nvx_page_extract_brand_hero_media' ) ? nvx_page_extract_brand_hero_media( $content ) : '';

	$hero  = '<section class="nvx-brand-hero" aria-labelledby="nvx-endolift-h1">';
	$hero .= '<div class="nvx-brand-hero__inner">';
	$hero .= nvx_endolift_hero_copy_markup();
	$hero .= $media;
	$hero .= '</div></section>';

	$body = nvx_endolift_editorial_body_markup();

	// Use standard wrapper like soluciones-medicas for consistent margins
	$standard_wrapper = '<div class="entry-content nvx-page__content nvx-prose">';
	return $standard_wrapper . $hero . $body . '</div>';
}
add_filter( 'the_content', 'nvx_content_restructure_endolift_page', 21 );
