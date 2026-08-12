<?php
/**
 * Contour Architecture™ treatment page — editorial high-authority structure.
 *
 * Wire-frame: Hero → Qué es → Indicaciones → vs liposucción → Tecnología → Proceso → Postoperatorio → Tarifas → FAQ → CTA.
 * Pattern-based (Contour Architecture markers), not page-ID gated.
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
function nvx_contour_architecture_is_singular_context(): bool {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	return is_singular( 'page' ) || is_page();
}

/**
 * Detect Contour Architecture™ treatment content before rewrite.
 * Anchors primarily on stable structural markers (aria-label / ids / brand classes).
 */
function nvx_content_is_contour_architecture_page( string $content ): bool {
	if ( false !== strpos( $content, 'nvx-contour-architecture-editorial' ) ) {
		return false;
	}

	if ( ! nvx_contour_architecture_is_singular_context() || is_front_page() || is_home() ) {
		return false;
	}

	$path = function_exists( 'nvx_schema_current_path' )
		? nvx_schema_current_path( (int) get_queried_object_id() )
		: '';

	$is_contour = false;
	if ( is_string( $path ) && false !== strpos( $path, 'contour-architecture' ) ) {
		$is_contour = true;
	} elseif ( preg_match(
		'/aria-label=["\']Contour Architecture NUVANX["\']|id=["\']nvx-contour-h1["\']|class=["\'][^"\']*nvx-contour-hero/iu',
		$content
	) ) {
		$is_contour = true;
	} elseif ( preg_match(
		'/nvx-brand-hero[\s\S]{0,1200}Contour Architecture[\s\S]{0,400}(abdomen|flancos|caderas|muslos)/iu',
		$content
	) ) {
		$is_contour = true;
	}

	return $is_contour;
}

/**
 * Linear process icons — Champagne Bronce stroke only (1.5px).
 *
 * @param string $name Icon key: assess|anesthesia|procedure|recover.
 */
function nvx_contour_architecture_process_icon( string $name ): string {
	$icons = array(
		'assess'     => '<svg class="nvx-contour-step__icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="22" cy="22" r="10" stroke="currentColor" stroke-width="1.5"/><path d="M30 30 40 40" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M18 22h8M22 18v8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		'anesthesia' => '<svg class="nvx-contour-step__icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M18 8h12v8l4 6v18H14V22l4-6V8Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M18 16h12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		'procedure'  => '<svg class="nvx-contour-step__icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M10 34 28 8l10 6-18 26H10v-6Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M24 14l10 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		'recover'    => '<svg class="nvx-contour-step__icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 28c4-10 8-14 12-14s8 4 12 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M16 18c3-2 5-3 8-3s5 1 8 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="24" cy="30" r="3" stroke="currentColor" stroke-width="1.5"/></svg>',
	);

	return $icons[ $name ] ?? $icons['assess'];
}

/**
 * Builds the Contour Architecture™ hero copy with medical authority details.
 *
 * @return string The rendered hero copy markup.
 */
function nvx_contour_architecture_hero_copy_markup(): string {
	require_once __DIR__ . '/nvx-catalog-json.php';
	$data = nvx_catalog_json_resolved( 'contour-architecture-page.json' )['hero'] ?? array();

	$colegiado = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';

	$html  = '<div class="nvx-brand-hero__copy">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html( $data['kicker'] ?? '' ) . '</p>';
	$html .= '<h1 class="nvx-brand-hero__title" id="nvx-contour-h1">' . esc_html( $data['h1'] ?? '' ) . '</h1>';

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
 * Builds the Contour Architecture™ editorial body markup.
 *
 * @return string The rendered editorial body HTML.
 */
function nvx_contour_architecture_editorial_body_markup(): string {
	require_once __DIR__ . '/nvx-catalog-json.php';
	$data = nvx_catalog_json_resolved( 'contour-architecture-page.json' );

	$colegiado    = defined( 'NVX_DIRECTOR_COLEGIADO' ) ? NVX_DIRECTOR_COLEGIADO : '282864786';
	$review_label = defined( 'NVX_CONTOUR_REVIEW_LABEL' ) ? NVX_CONTOUR_REVIEW_LABEL : 'julio 2026';
	$equipo_url   = home_url( '/equipo-medico/' );

	$html = '<div class="nvx-contour-architecture-editorial">';

	// Clinical review byline — E-E-A-T
	$html .= '<p class="nvx-contour-reviewed">';
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
	$html .= nvx_page_brand_section_open_markup( 'nvx-contour-what', 'nvx-contour-what-title' );
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['what']['kicker'] ?? '' ), 'nvx-contour-what-title', esc_html( $data['what']['title'] ?? '' ) );
	foreach ( $data['what']['body'] ?? array() as $paragraph ) {
		$html .= '<p class="nvx-body nvx-body--measure">' . esc_html( $paragraph ) . '</p>';
	}
	$html .= '</div></section>';

	// B. Indicaciones + diagnóstico diferencial
	$html .= nvx_page_brand_section_open_markup( 'nvx-contour-diagnosis', 'nvx-contour-diagnosis-title', 'nvx-contour-diagnosis__grid' );
	$html .= '<div class="nvx-contour-diagnosis__copy">';
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['diagnosis']['kicker'] ?? '' ), 'nvx-contour-diagnosis-title', esc_html( $data['diagnosis']['title'] ?? '' ) );
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

	// C. Comparativa vs liposucción
	$html .= nvx_page_brand_section_open_markup( 'nvx-contour-compare', 'nvx-contour-compare-title' );
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['compare']['kicker'] ?? '' ), 'nvx-contour-compare-title', esc_html( $data['compare']['title'] ?? '' ) );
	$html .= '<div class="nvx-contour-compare-wrap">';
	$html .= '<table class="nvx-contour-compare-table">';
	$html .= '<thead><tr>';
	$html .= '<th scope="col">' . esc_html( $data['compare']['col_param'] ?? '' ) . '</th>';
	$html .= '<th scope="col">' . esc_html( $data['compare']['col_contour'] ?? '' ) . '</th>';
	$html .= '<th scope="col">' . esc_html( $data['compare']['col_lipo'] ?? '' ) . '</th>';
	$html .= '</tr></thead><tbody>';
	foreach ( $data['compare']['rows'] ?? array() as $row ) {
		$html .= '<tr>';
		$html .= '<th scope="row">' . esc_html( $row['param'] ?? '' ) . '</th>';
		$html .= '<td>' . esc_html( $row['contour'] ?? '' ) . '</td>';
		$html .= '<td>' . esc_html( $row['lipo'] ?? '' ) . '</td>';
		$html .= '</tr>';
	}
	$html .= '</tbody></table></div></div></section>';

	// D. Tecnología
	$html .= nvx_page_brand_section_open_markup( 'nvx-contour-biophysics', 'nvx-contour-bio-title' );
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['biophysics']['kicker'] ?? '' ), 'nvx-contour-bio-title', esc_html( $data['biophysics']['title'] ?? '' ) );
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html( $data['biophysics']['body1'] ?? '' ) . '</p>';
	$html .= '<p class="nvx-body nvx-body--measure"><em>' . esc_html( $data['biophysics']['caption'] ?? '' ) . '</em></p>';
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html( $data['biophysics']['body2'] ?? '' ) . '</p>';
	$html .= '</div></section>';

	// E. Proceso clínico
	$html .= nvx_page_brand_section_open_markup( 'nvx-contour-process', 'nvx-contour-process-title' );
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['process']['kicker'] ?? '' ), 'nvx-contour-process-title', esc_html( $data['process']['title'] ?? '' ) );
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html( $data['process']['body'] ?? '' ) . '</p>';
	$html .= '<div class="nvx-contour-process-grid">';

	$step_idx = 0;
	foreach ( $data['process']['steps'] ?? array() as $step ) {
		$sid   = 'nvx-contour-step-' . $step_idx;
		$html .= '<article class="nvx-contour-step" aria-labelledby="' . esc_attr( $sid ) . '">';
		$html .= nvx_contour_architecture_process_icon( $step['icon'] ?? 'assess' );
		$html .= '<span class="nvx-contour-step__n">' . esc_html( $step['n'] ?? '' ) . '</span>';
		$html .= '<h3 id="' . esc_attr( $sid ) . '" class="nvx-contour-step__title">' . esc_html( $step['title'] ?? '' ) . '</h3>';
		$html .= '<p class="nvx-body">' . esc_html( $step['body'] ?? '' ) . '</p>';
		$html .= '</article>';
		++$step_idx;
	}

	$html .= '</div></div></section>';

	// F. Postoperatorio Real
	$html .= nvx_page_brand_section_open_markup( 'nvx-contour-postop', 'nvx-contour-postop-title', '', array( 'id' => 'postoperatorio-contour' ) );
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['postop']['kicker'] ?? '' ), 'nvx-contour-postop-title', esc_html( $data['postop']['title'] ?? '' ) );
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html( $data['postop']['body'] ?? '' ) . '</p>';

	$html .= '<ul class="nvx-contour-postop-list" role="list">';
	foreach ( $data['postop']['items'] ?? array() as $item ) {
		$html .= '<li><strong>' . esc_html( $item['title'] ?? '' ) . '</strong> ' . esc_html( $item['body'] ?? '' ) . '</li>';
	}
	$html .= '</ul>';
	$html .= '<p class="nvx-body nvx-body--measure"><em>' . esc_html( $data['postop']['note'] ?? '' ) . '</em></p>';
	$html .= '</div></section>';

	// G. Presupuesto Clínico
	$html .= nvx_page_brand_section_open_markup( 'nvx-contour-investment', 'nvx-contour-price-title', '', array( 'id' => 'inversion-contour' ) );
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['investment']['kicker'] ?? '' ), 'nvx-contour-price-title', esc_html( $data['investment']['title'] ?? '' ) );
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html( $data['investment']['body'] ?? '' ) . '</p>';
	$html .= '<ul class="nvx-contour-price-includes" role="list">';
	foreach ( $data['investment']['items'] ?? array() as $item ) {
		$html .= '<li>' . esc_html( $item ) . '</li>';
	}
	$html .= '</ul>';
	$html .= '<p class="nvx-body nvx-body--measure"><em>' . esc_html( $data['investment']['note'] ?? '' ) . '</em></p>';
	$html .= '</div></section>';

	// H. FAQ
	$html .= nvx_page_brand_section_open_markup( 'nvx-contour-faq', 'nvx-contour-faq-title' );
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['faq']['kicker'] ?? '' ), 'nvx-contour-faq-title', esc_html( $data['faq']['title'] ?? '' ) );
	$html .= '<div class="nvx-faq nvx-contour-faq-list">';

	foreach ( $data['faq']['items'] ?? array() as $faq ) {
		$html .= '<details class="nvx-brand-faq-item">';
		$html .= '<summary><span>' . esc_html( $faq['q'] ) . '</span></summary>';
		$html .= '<div class="nvx-brand-faq-content"><p>' . esc_html( $faq['a'] ) . '</p></div>';
		$html .= '</details>';
	}

	$html .= '</div></div></section>';

	$html .= '</div>';

	return $html;
}

/**
 * Rebuild Contour Architecture™ page: authority hero + diagnosis + technology + process + postop + FAQ + CTA.
 */
add_filter(
	'nvx_page_owner',
	function ( $owner ) {
		if ( ! empty( $owner ) ) {
			return $owner;
		}
		global $post;
		$content = $post ? $post->post_content : '';
		if ( function_exists( 'nvx_content_is_contour_architecture_page' ) && nvx_content_is_contour_architecture_page( $content ) ) {
			return 'nvx_contour_architecture_page';
		}
		return $owner;
	}
);

function nvx_content_restructure_contour_architecture_page( string $content ): string {
	$owner = function_exists( 'nvx_get_page_owner' ) ? nvx_get_page_owner() : null;
	if ( $owner !== 'nvx_contour_architecture_page' ) {
		return $content;
	}

	$media = function_exists( 'nvx_page_extract_brand_hero_media' ) ? nvx_page_extract_brand_hero_media( $content ) : '';

	$hero  = '<section class="nvx-brand-hero" aria-labelledby="nvx-contour-h1">';
	$hero .= '<div class="nvx-brand-hero__inner">';
	$hero .= nvx_contour_architecture_hero_copy_markup();
	$hero .= $media;
	$hero .= '</div></section>';

	$body = nvx_contour_architecture_editorial_body_markup();

	$standard_wrapper = '<div class="entry-content nvx-page__content nvx-prose">';
	return $standard_wrapper . $hero . $body . '</div>';
}
add_filter( 'the_content', 'nvx_content_restructure_contour_architecture_page', 21 );
