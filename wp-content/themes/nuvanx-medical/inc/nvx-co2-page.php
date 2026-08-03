<?php
/**
 * Láser CO₂ fraccionado page — resurfacing, cicatrices, downtime.
 *
 * Wire-frame: Hero → Ablación fraccionada → Indicaciones → Downtime → Tarifas PVP → CTA.
 * Does not repeat Endolift / Endoláser body or laser hub catalog.
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
 * Builds the CO₂ laser treatment hero copy markup.
 *
 * @return string The escaped hero copy HTML.
 */
function nvx_co2_hero_copy_markup(): string {
	require_once __DIR__ . '/nvx-catalog-json.php';
	$data = nvx_catalog_json_resolved( 'laser-co2-page.json' )['hero'] ?? array();
	$price_facial = function_exists( 'nvx_tariff_catalog' )
		? nvx_format_price_eur( nvx_tariff_catalog()['laser_co2']['facial']['pvp'] )
		: number_format_i18n( 330, 2 );

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
			/* translators: %s: facial session PVP */
			$data['description'] ?? '',
			$price_facial
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
 * Editorial body.
 */
function nvx_co2_editorial_body_markup(): string {
	require_once __DIR__ . '/nvx-catalog-json.php';
	$data = nvx_catalog_json_resolved( 'laser-co2-page.json' );
	
	$catalog      = function_exists( 'nvx_tariff_catalog' ) ? nvx_tariff_catalog() : array();
	$price_facial = ! empty( $catalog['laser_co2']['facial']['pvp'] )
		? nvx_format_price_eur( $catalog['laser_co2']['facial']['pvp'] )
		: number_format_i18n( 330, 2 );
	$price_body   = ! empty( $catalog['laser_co2']['corporal']['pvp'] )
		? nvx_format_price_eur( $catalog['laser_co2']['corporal']['pvp'] )
		: number_format_i18n( 450, 2 );

	$html  = '<div class="nvx-co2-editorial nvx-endolift-editorial">';

	// A. Science of fractional ablation.
	$html .= nvx_page_brand_section_open_markup( 'nvx-co2-science', 'nvx-co2-science-title' );
	$html .= nvx_page_brand_section_heading_markup(
		esc_html( $data['science']['kicker'] ?? '' ),
		'nvx-co2-science-title',
		esc_html( $data['science']['title'] ?? '' )
	);
	foreach ( $data['science']['body'] ?? array() as $paragraph ) {
		$html .= '<p class="nvx-body nvx-body--measure">' . esc_html( $paragraph ) . '</p>';
	}
	$html .= '</div></section>';

	// B. Indications.
	$html .= nvx_page_brand_section_open_markup( 'nvx-co2-indications', 'nvx-co2-ind-title' );
	$html .= nvx_page_brand_section_heading_markup(
		esc_html( $data['indications']['kicker'] ?? '' ),
		'nvx-co2-ind-title',
		esc_html( $data['indications']['title'] ?? '' )
	);
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html( $data['indications']['body'] ?? '' ) . '</p>';
	$html .= '<ul class="nvx-feature-zone-list">';
	foreach ( (array) ( $data['indications']['items'] ?? array() ) as $ind ) {
		$html .= '<li class="nvx-feature-zone">';
		$html .= '<h3 class="nvx-feature-zone__title">' . esc_html( $ind['title'] ?? '' ) . '</h3>';
		$html .= '<p class="nvx-body">' . esc_html( $ind['body'] ?? '' ) . '</p>';
		$html .= '</li>';
	}
	$html .= '</ul></div></section>';

	// C. Recovery timeline (unique — not on Endolift FAQ).
	$html .= nvx_page_brand_section_open_markup( 'nvx-co2-downtime', 'nvx-co2-down-title' );
	$html .= nvx_page_brand_section_heading_markup(
		esc_html( $data['downtime']['kicker'] ?? '' ),
		'nvx-co2-down-title',
		esc_html( $data['downtime']['title'] ?? '' )
	);
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html( $data['downtime']['body'] ?? '' ) . '</p>';
	$html .= '<ol class="nvx-treatment-process__steps">';
	foreach ( $data['downtime']['phases'] ?? array() as $phase ) {
		$html .= '<li class="nvx-treatment-process__step">';
		$html .= '<h3 class="nvx-treatment-process__step-title">' . esc_html( $phase['title'] ?? '' ) . '</h3>';
		$html .= '<p class="nvx-body">' . esc_html( $phase['body'] ?? '' ) . '</p>';
		$html .= '</li>';
	}
	$html .= '</ol></div></section>';

	// D. PVP reference (clinic tariff — facial 330 / body 450).
	$html .= nvx_page_brand_section_open_markup(
		'nvx-co2-pricing',
		'nvx-co2-price-title',
		'',
		array( 'id' => 'tarifas-co2' )
	);
	$html .= nvx_page_brand_section_heading_markup(
		esc_html( $data['pricing']['kicker'] ?? '' ),
		'nvx-co2-price-title',
		esc_html( $data['pricing']['title'] ?? '' )
	);
	$html .= '<div class="nvx-endolift-price-table-wrap">';
	$html .= '<table class="nvx-endolift-price-table">';
	$html .= '<caption class="nvx-endolift-price-table__cap">' . esc_html( $data['pricing']['caption'] ?? '' ) . '</caption>';
	$html .= '<thead><tr><th scope="col">' . esc_html( $data['pricing']['col_session'] ?? '' ) . '</th><th scope="col">' . esc_html( $data['pricing']['col_pvp'] ?? '' ) . '</th></tr></thead><tbody>';
	$html .= '<tr><th scope="row">' . esc_html( $data['pricing']['row_facial'] ?? '' ) . '</th><td>' . esc_html( $price_facial ) . '&nbsp;€</td></tr>';
	$html .= '<tr><th scope="row">' . esc_html( $data['pricing']['row_body'] ?? '' ) . '</th><td>' . esc_html( $price_body ) . '&nbsp;€</td></tr>';
	$html .= '</tbody></table></div>';
	$html .= '<p class="nvx-body nvx-body--measure"><em>' . esc_html( $data['pricing']['note'] ?? '' ) . '</em></p>';
	$html .= '</div></section>';

	// Closing valoración CTA: site-wide nvx-cta-banner in footer.php (not page-local).

	$html .= '</div>';

	return $html;
}

/**
 * Rebuild CO₂ page.
 */
add_filter( 'nvx_page_owner', function( $owner ) {
	if ( ! empty( $owner ) ) return $owner;
	global $post;
	$content = $post ? $post->post_content : '';
	if ( function_exists('nvx_content_is_co2_page') && nvx_content_is_co2_page( $content ) ) {
		return 'nvx_co2_page';
	}
	return $owner;
});

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

	return nvx_page_render_brand_wrapper(
		$content,
		$hero . $body,
		'nvx-brand-page nvx-brand-page--co2'
	);

}
add_filter( 'the_content', 'nvx_content_restructure_co2_page', 19 );
