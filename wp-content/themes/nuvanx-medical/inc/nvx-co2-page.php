<?php
/**
 * Láser CO₂ fraccionado page — editorial high-authority structure.
 *
 * Wire-frame: Hero → Qué es → Indicaciones → vs peelings → Biofísica → Proceso → Postoperatorio → Tarifas → FAQ → CTA.
 * Pattern-based (CO2 markers), not page-ID gated.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/nvx-page-render-helpers.php';

/**
 * Singular context for CO₂ rewrite.
 */
function nvx_co2_is_singular_context(): bool {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	return is_singular( 'page' ) || is_page();
}

/**
 * Detect Láser CO₂ fraccionado detail page only (not home/hub cards).
 */
function nvx_content_is_co2_page( string $content ): bool {
	if ( false !== strpos( $content, 'nvx-co2-editorial' ) ) {
		return false;
	}

	if ( ! nvx_co2_is_singular_context() ) {
		return false;
	}

	if ( is_front_page() || is_home() ) {
		return false;
	}

	$path = function_exists( 'nvx_schema_current_path' )
		? nvx_schema_current_path( (int) get_queried_object_id() )
		: '';

	if ( is_string( $path ) && false !== strpos( $path, 'laser-co2-fraccionado' ) ) {
		return true;
	}

	return (bool) preg_match(
		'/aria-label=["\']Láser CO₂ NUVANX["\']|id=["\']nvx-co2-h1["\']|class=["\'][^"\']*nvx-co2-hero/iu',
		$content
	);
}

/**
 * Linear process icons — Champagne Bronce stroke only (1.5px).
 *
 * @param string $name Icon key: assess|anesthesia|procedure|recover.
 */
function nvx_co2_process_icon( string $name ): string {
	$icons = array(
		'assess'     => '<svg class="nvx-co2-step__icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="22" cy="22" r="10" stroke="currentColor" stroke-width="1.5"/><path d="M30 30 40 40" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M18 22h8M22 18v8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		'anesthesia' => '<svg class="nvx-co2-step__icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M18 8h12v8l4 6v18H14V22l4-6V8Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M18 16h12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		'procedure'  => '<svg class="nvx-co2-step__icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M10 34 28 8l10 6-18 26H10v-6Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M24 14l10 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		'recover'    => '<svg class="nvx-co2-step__icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 28c4-10 8-14 12-14s8 4 12 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M16 18c3-2 5-3 8-3s5 1 8 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="24" cy="30" r="3" stroke="currentColor" stroke-width="1.5"/></svg>',
	);

	return $icons[ $name ] ?? $icons['assess'];
}

/**
 * Builds the CO₂ laser treatment hero copy markup.
 *
 * @return string The escaped hero copy HTML.
 */
function nvx_co2_hero_copy_markup(): string {
	require_once __DIR__ . '/nvx-catalog-json.php';
	$data = nvx_catalog_json_resolved( 'laser-co2-page.json' )['hero'] ?? array();

	$colegiado = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';

	$html  = '<div class="nvx-brand-hero__copy">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html( $data['kicker'] ?? '' ) . '</p>';
	$html .= '<h1 class="nvx-brand-hero__title" id="nvx-co2-h1">' . esc_html( $data['h1'] ?? '' ) . '</h1>';

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
 * Builds the CO₂ laser treatment editorial body markup.
 *
 * @return string The generated editorial HTML.
 */
function nvx_co2_editorial_body_markup(): string {
	require_once __DIR__ . '/nvx-catalog-json.php';
	$data = nvx_catalog_json_resolved( 'laser-co2-page.json' );

	$colegiado    = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';
	$review_label = defined( 'NVX_CO2_REVIEW_LABEL' ) ? NVX_CO2_REVIEW_LABEL : 'julio 2026';
	$equipo_url   = home_url( '/equipo-medico/' );

	$html = '<div class="nvx-co2-editorial">';

	// Clinical review byline — E-E-A-T
	$html .= '<p class="nvx-co2-reviewed">';
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

	// A. Qué es
	$html .= nvx_page_brand_section_open_markup( 'nvx-co2-what', 'nvx-co2-what-title' );
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['what']['kicker'] ?? '' ), 'nvx-co2-what-title', esc_html( $data['what']['title'] ?? '' ) );
	foreach ( $data['what']['body'] ?? array() as $paragraph ) {
		$html .= '<p class="nvx-body nvx-body--measure">' . esc_html( $paragraph ) . '</p>';
	}
	$html .= '</div></section>';

	// B. Indicaciones + diagnóstico diferencial
	$html .= nvx_page_brand_section_open_markup( 'nvx-co2-diagnosis', 'nvx-co2-diagnosis-title', 'nvx-co2-diagnosis__grid' );
	$html .= '<div class="nvx-co2-diagnosis__copy">';
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['diagnosis']['kicker'] ?? '' ), 'nvx-co2-diagnosis-title', esc_html( $data['diagnosis']['title'] ?? '' ) );
	foreach ( $data['diagnosis']['body'] ?? array() as $paragraph ) {
		$html .= '<p class="nvx-body">' . esc_html( $paragraph ) . '</p>';
	}
	$html .= '</div>';
	$html .= nvx_render_editorial_fact_panel_markup( $data['diagnosis'] ?? array() );
	$html .= '</div></section>';

	// C. Comparativa vs peelings
	$html .= nvx_page_brand_section_open_markup( 'nvx-co2-compare', 'nvx-co2-compare-title' );
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['compare']['kicker'] ?? '' ), 'nvx-co2-compare-title', esc_html( $data['compare']['title'] ?? '' ) );
	$html .= '<div class="nvx-co2-compare-wrap">';
	$html .= '<table class="nvx-co2-compare-table">';
	$html .= '<thead><tr>';
	$html .= '<th scope="col">' . esc_html( $data['compare']['col_param'] ?? '' ) . '</th>';
	$html .= '<th scope="col">' . esc_html( $data['compare']['col_co2'] ?? '' ) . '</th>';
	$html .= '<th scope="col">' . esc_html( $data['compare']['col_peel'] ?? '' ) . '</th>';
	$html .= '</tr></thead><tbody>';
	foreach ( $data['compare']['rows'] ?? array() as $row ) {
		$html .= '<tr>';
		$html .= '<th scope="row">' . esc_html( $row['param'] ?? '' ) . '</th>';
		$html .= '<td>' . esc_html( $row['co2'] ?? '' ) . '</td>';
		$html .= '<td>' . esc_html( $row['peel'] ?? '' ) . '</td>';
		$html .= '</tr>';
	}
	$html .= '</tbody></table></div></div></section>';

	// D. Biofísica
	$html .= nvx_page_brand_section_open_markup( 'nvx-co2-biophysics', 'nvx-co2-bio-title' );
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['biophysics']['kicker'] ?? '' ), 'nvx-co2-bio-title', esc_html( $data['biophysics']['title'] ?? '' ) );
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html( $data['biophysics']['body1'] ?? '' ) . '</p>';
	$html .= '<p class="nvx-body nvx-body--measure"><em>' . esc_html( $data['biophysics']['caption'] ?? '' ) . '</em></p>';
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html( $data['biophysics']['body2'] ?? '' ) . '</p>';
	$html .= '</div></section>';

	// E. Proceso clínico
	$html .= nvx_render_editorial_process_grid_markup( $data['process'] ?? array(), 'nvx-co2', 'nvx_co2_process_icon' );

	// F. Postoperatorio Real
	$html .= nvx_page_brand_section_open_markup( 'nvx-co2-postop', 'nvx-co2-postop-title', '', array( 'id' => 'postoperatorio-co2' ) );
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['postop']['kicker'] ?? '' ), 'nvx-co2-postop-title', esc_html( $data['postop']['title'] ?? '' ) );
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html( $data['postop']['body'] ?? '' ) . '</p>';

	$html .= '<ul class="nvx-co2-postop-list" role="list">';
	foreach ( $data['postop']['items'] ?? array() as $item ) {
		$html .= '<li><strong>' . esc_html( $item['title'] ?? '' ) . '</strong> ' . esc_html( $item['body'] ?? '' ) . '</li>';
	}
	$html .= '</ul>';
	$html .= '<p class="nvx-body nvx-body--measure"><em>' . esc_html( $data['postop']['note'] ?? '' ) . '</em></p>';
	$html .= '</div></section>';

	// G. Presupuesto Clínico
	$html .= nvx_page_brand_section_open_markup( 'nvx-co2-investment', 'nvx-co2-price-title', '', array( 'id' => 'inversion-co2' ) );
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['investment']['kicker'] ?? '' ), 'nvx-co2-price-title', esc_html( $data['investment']['title'] ?? '' ) );
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html( $data['investment']['body'] ?? '' ) . '</p>';
	$html .= '<ul class="nvx-co2-price-includes" role="list">';
	foreach ( $data['investment']['items'] ?? array() as $item ) {
		$html .= '<li>' . esc_html( $item ) . '</li>';
	}
	$html .= '</ul>';
	$html .= '<p class="nvx-body nvx-body--measure"><em>' . esc_html( $data['investment']['note'] ?? '' ) . '</em></p>';
	$html .= '</div></section>';

	// H. FAQ
	$html .= nvx_render_editorial_faq_markup( $data['faq'] ?? array(), 'nvx-co2' );

	$html .= '</div>';

	return $html;
}

/**
 * Rebuild CO₂ page.
 */
add_filter(
	'nvx_page_owner',
	function ( $owner ) {
		if ( ! empty( $owner ) ) {
			return $owner; }
		global $post;
		$content = $post ? $post->post_content : '';
		if ( function_exists( 'nvx_content_is_co2_page' ) && nvx_content_is_co2_page( $content ) ) {
			return 'nvx_co2_page';
		}
		return $owner;
	}
);

/**
 * Rebuilds owned CO₂ page content with its branded hero and editorial layout.
 *
 * @param string $content The existing page content, including any extracted hero media.
 * @return string The branded CO₂ page content, or the original content when the page is not owned by the CO₂ module.
 */
function nvx_content_restructure_co2_page( string $content ): string {
	$owner = function_exists( 'nvx_get_page_owner' ) ? nvx_get_page_owner() : null;
	if ( $owner !== 'nvx_co2_page' ) {
		return $content;
	}

	$media = nvx_page_extract_brand_hero_media( $content );

	$hero  = '<section class="nvx-brand-hero" aria-labelledby="nvx-co2-h1">';
	$hero .= '<div class="nvx-brand-hero__inner">';
	$hero .= nvx_co2_hero_copy_markup();
	$hero .= $media;
	$hero .= '</div></section>';

	$body = nvx_co2_editorial_body_markup();

	// Use standard wrapper like soluciones-medicas for consistent margins
	$standard_wrapper = '<div class="entry-content nvx-page__content nvx-prose">';
	return $standard_wrapper . $hero . $body . '</div>';
}
add_filter( 'the_content', 'nvx_content_restructure_co2_page', NVX_HOOK_PRIO_CO2_MODULE );
