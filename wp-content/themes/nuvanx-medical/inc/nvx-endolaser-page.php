<?php
/**
 * Endoláser corporal page — laserlipólisis + retracción cutánea.
 *
 * Wire-frame: Hero → Mecanismo dual → Zonas → Exclusión → Planificación → CTA.
 * Does not repeat Endolift facial encyclopedia (formula 1470 / papada focus).
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/nvx-page-render-helpers.php';

/**
 * Singular context for Endoláser rewrite.
 */
function nvx_endolaser_is_singular_context(): bool {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	return is_singular( 'page' ) || is_page();
}

/**
 * Detect Endoláser corporal detail page only (never home / hub / other treatments).
 */
function nvx_content_is_endolaser_page( string $content ): bool {
	// Already rewritten once this request.
	if ( false !== strpos( $content, 'nvx-endolaser-editorial' ) ) {
		return false;
	}

	if ( ! nvx_endolaser_is_singular_context() ) {
		return false;
	}

	// Never hijack front page or non-page views (home mentions Endoláser in protocols).
	if ( is_front_page() || is_home() ) {
		return false;
	}

	// Authoritative: canonical path of the treatment page.
	$path = function_exists( 'nvx_schema_current_path' )
		? nvx_schema_current_path( (int) get_queried_object_id() )
		: '';

	if ( is_string( $path ) && false !== strpos( $path, 'endolaser-corporal' ) ) {
		return true;
	}

	// Structural markers only if CMS already used our classes (not free-text "Endoláser" on other pages).
	return (bool) preg_match(
		'/aria-label=["\']Endoláser corporal NUVANX["\']|id=["\']nvx-endolaser-h1["\']|class=["\'][^"\']*nvx-endolaser-hero/iu',
		$content
	);
}

/**
 * Hero copy.
 */
function nvx_endolaser_hero_copy_markup(): string {
	require_once __DIR__ . '/nvx-catalog-json.php';
	$data = nvx_catalog_json_resolved( 'endolaser-page.json' )['hero'] ?? array();
	
	$html  = '<div class="nvx-brand-hero__copy">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html( $data['kicker'] ?? '' ) . '</p>';
	$html .= '<h1 class="nvx-brand-hero__title" id="nvx-endolaser-h1">' . esc_html( $data['h1'] ?? '' ) . '</h1>';
	
	// E-E-A-T Medical Authority Byline
	$html .= '<div class="nvx-medical-byline">';
	$html .= '<div class="nvx-medical-byline__text">';
	$html .= '<strong>' . esc_html( $data['byline_author'] ?? '' ) . '</strong><br>';
	$html .= '<span class="nvx-medical-byline__title">' . esc_html( $data['byline_title'] ?? '' ) . '</span>';
	$html .= '</div></div>';
	$html .= '<p class="nvx-brand-hero__lead">' . esc_html( $data['lead'] ?? '' ) . '</p>';
	$html .= '<p class="nvx-brand-hero__description">' . esc_html( $data['description'] ?? '' ) . '</p>';

	if ( function_exists( 'nvx_cta_pair_markup' ) ) {
		$html .= nvx_cta_pair_markup();
	}

	$html .= '<p class="nvx-brand-meta">' . esc_html( $data['meta'] ?? '' ) . '</p>';
	$html .= '</div>';

	return $html;
}

/**
 * Editorial body (no facial Endolift encyclopedia, no fixed € inventado).
 */
function nvx_endolaser_editorial_body_markup(): string {
	require_once __DIR__ . '/nvx-catalog-json.php';
	$data = nvx_catalog_json_resolved( 'endolaser-page.json' );
	
	$html  = '<div class="nvx-endolaser-editorial nvx-endolift-editorial">';

	// A. Intro + dual mechanism.
	$html .= nvx_page_brand_section_open_markup( 'nvx-endolaser-mechanism', 'nvx-endolaser-mech-title' );
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['mechanism']['kicker'] ?? '' ), 'nvx-endolaser-mech-title', esc_html( $data['mechanism']['title'] ?? '' ) );
	foreach ( $data['mechanism']['body'] ?? array() as $paragraph ) {
		$html .= '<p class="nvx-body nvx-body--measure">' . esc_html( $paragraph ) . '</p>';
	}
	$html .= '<div class="nvx-endolift-effects">';
	foreach ( $data['mechanism']['effects'] ?? array() as $effect ) {
		$html .= '<article class="nvx-endolift-effect"><h3 class="nvx-endolift-effect__title">' . esc_html( $effect['title'] ?? '' ) . '</h3>';
		$html .= '<p class="nvx-body">' . esc_html( $effect['body'] ?? '' ) . '</p></article>';
	}
	$html .= '</div></div></section>';

	// B. Zonas.
	$html .= nvx_page_brand_section_open_markup( 'nvx-endolaser-zones', 'nvx-endolaser-zones-title' );
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['zones']['kicker'] ?? '' ), 'nvx-endolaser-zones-title', esc_html( $data['zones']['title'] ?? '' ) );
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html( $data['zones']['body'] ?? '' ) . '</p>';
	$html .= '<ul class="nvx-endolaser-zone-list">';
	foreach ( $data['zones']['items'] ?? array() as $zone ) {
		$html .= '<li class="nvx-endolaser-zone">';
		$html .= '<h3 class="nvx-endolaser-zone__title">' . esc_html( $zone['title'] ?? '' ) . '</h3>';
		$html .= '<p class="nvx-body">' . esc_html( $zone['body'] ?? '' ) . '</p>';
		$html .= '</li>';
	}
	$html .= '</ul></div></section>';

	// C. Exclusión.
	$html .= nvx_page_brand_section_open_markup( 'nvx-endolaser-exclusion', 'nvx-endolaser-excl-title', 'nvx-endolift-diagnosis__grid' );
	$html .= '<div class="nvx-endolift-diagnosis__copy">';
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['exclusion']['kicker'] ?? '' ), 'nvx-endolaser-excl-title', esc_html( $data['exclusion']['title'] ?? '' ) );
	foreach ( $data['exclusion']['body'] ?? array() as $paragraph ) {
		$html .= '<p class="nvx-body">' . esc_html( $paragraph ) . '</p>';
	}
	$html .= '</div>';
	$html .= '<aside class="nvx-endolift-diagnosis__panel" aria-label="' . esc_attr__( 'Resumen de candidatura', 'nuvanx-medical' ) . '">';
	$html .= '<p class="nvx-endolift-panel-label">' . esc_html( $data['exclusion']['panel_title'] ?? '' ) . '</p>';
	$html .= '<ul class="nvx-endolift-panel-list">';
	foreach ( $data['exclusion']['panel_items'] ?? array() as $item ) {
		$html .= '<li><strong>' . esc_html( $item['title'] ?? '' ) . '</strong> — ' . esc_html( $item['body'] ?? '' ) . '</li>';
	}
	$html .= '</ul></aside></div></section>';

	// D. Planificación / inversión (no precio fijo inventado).
	$html .= nvx_page_brand_section_open_markup( 'nvx-endolaser-planning', 'nvx-endolaser-plan-title', '', array( 'id' => 'planificacion-endolaser' ) );
	$html .= nvx_page_brand_section_heading_markup( esc_html( $data['planning']['kicker'] ?? '' ), 'nvx-endolaser-plan-title', esc_html( $data['planning']['title'] ?? '' ) );
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html( $data['planning']['body'] ?? '' ) . '</p>';
	$html .= '<ul class="nvx-endolift-price-includes">';
	foreach ( $data['planning']['items'] ?? array() as $item ) {
		$html .= '<li>' . esc_html( $item ) . '</li>';
	}
	$html .= '</ul>';
	$html .= '<p class="nvx-body nvx-body--measure"><em>' . esc_html( $data['planning']['note'] ?? '' ) . '</em></p>';
	$html .= '</div></section>';

	// Closing valoración CTA: site-wide nvx-cta-banner in footer.php (not page-local).

	$html .= '</div>';

	return $html;
}

/**
 * Rebuild Endoláser page content.
 */
function nvx_content_restructure_endolaser_page( string $content ): string {
	if ( ! nvx_content_is_endolaser_page( $content ) ) {
		return $content;
	}

	$media = nvx_page_extract_brand_hero_media( $content );

	$hero  = '<section class="nvx-brand-hero" aria-labelledby="nvx-endolaser-h1" aria-label="' . esc_attr__( 'Endoláser corporal NUVANX', 'nuvanx-medical' ) . '">';
	$hero .= '<div class="nvx-brand-hero__inner">';
	$hero .= nvx_endolaser_hero_copy_markup();
	$hero .= $media;
	$hero .= '</div></section>';

	$body = nvx_endolaser_editorial_body_markup();

	return nvx_page_render_brand_wrapper(
		$content,
		$hero . $body,
		'nvx-brand-page nvx-brand-page--endolaser'
	);

}
add_filter( 'the_content', 'nvx_content_restructure_endolaser_page', 19 );
