<?php
/**
 * BTL detail treatment pages: EXION Face / Body / Fractional RF + EMFUSION.
 *
 * Same editorial pattern as IPL EXILITE / CO₂: Hero → Mecanismo → Indicaciones →
 * Comparativa breve → Procedimiento → FAQ → CTA.
 * Does not replace hub /exion-btl/ or comparative blogs (linked as depth reading).
 *
 * Paths:
 *   /exion-face/
 *   /exion-body/
 *   /exion-fractional/
 *   /emfusion/
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/nvx-page-render-helpers.php';

/**
 * Singular page context.
 */
function nvx_btl_detail_is_singular(): bool {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}
	return is_singular( 'page' ) || is_page();
}

/**
 * Registry of BTL detail pages (SEO + clinical copy).
 *
 * @return array<string, array<string, mixed>>
 */
function nvx_btl_detail_registry(): array {
	require_once __DIR__ . '/nvx-catalog-json.php';

	return nvx_catalog_json_resolved(
		'btl-detail-pages.json',
		static function ( string $key ) { return nvx_btl_claim( $key ); },
		array(),
		array(),
		'btl-detail-pages'
	);
}

/**
 * Resolve detail key from current request / content.
 *
 * @return string|null Registry key.
 */
function nvx_btl_detail_current_key( string $content = '' ): ?string {
	if ( ! nvx_btl_detail_is_singular() || is_front_page() || is_home() ) {
		return null;
	}

	// Never hijack posts (blogs share similar titles).
	if ( is_singular( 'post' ) ) {
		return null;
	}

	$path = function_exists( 'nvx_schema_current_path' )
		? nvx_schema_current_path( (int) get_queried_object_id() )
		: '';
	$path = is_string( $path ) ? $path : '';

	foreach ( nvx_btl_detail_registry() as $slug => $cfg ) {
		if ( function_exists( 'nvx_schema_path_matches' ) && nvx_schema_path_matches( $path, $cfg['path'] ) ) {
			return $slug;
		}
		if ( false !== strpos( $content, $cfg['marker'] . '-editorial' ) ) {
			return null; // already rebuilt
		}
		// Accept both canonical ids (nvx-exion-body-h1) and legacy double-prefixed ones.
		if (
			false !== strpos( $content, 'id="' . $cfg['marker'] . '-h1"' )
			|| false !== strpos( $content, "id='{$cfg['marker']}-h1'" )
			|| false !== strpos( $content, 'id="nvx-' . $cfg['marker'] . '-h1"' )
			|| false !== strpos( $content, "id='nvx-{$cfg['marker']}-h1'" )
		) {
			return $slug;
		}
	}

	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	if ( isset( nvx_btl_detail_registry()[ $slug ] ) ) {
		return $slug;
	}

	return null;
}

/**
 * Build full editorial markup for a detail key.
 */
function nvx_btl_detail_page_markup( string $key ): string {
	$reg = nvx_btl_detail_registry();
	if ( empty( $reg[ $key ] ) ) {
		return '';
	}
	$c = $reg[ $key ];
	// Markers are already nvx-* (e.g. nvx-exion-body); do not prefix again.
	$id = $c['marker'];

	// Hero.
	$hero  = '<section class="nvx-brand-hero nvx-brand-hero--btl ' . esc_attr( $c['marker'] ) . '-hero" aria-labelledby="' . esc_attr( $id ) . '-h1" aria-label="' . esc_attr( $c['aria'] ) . '">';
	$hero .= '<div class="nvx-brand-hero__inner">';
	$hero .= '<div class="nvx-brand-hero__copy">';
	$hero .= '<p class="nvx-brand-kicker">' . esc_html( $c['kicker'] ) . '</p>';
	$hero .= '<h1 class="nvx-brand-hero__title" id="' . esc_attr( $id ) . '-h1">' . esc_html( $c['h1'] ) . '</h1>';
	
	// E-E-A-T Medical Authority Byline
	$hero .= '<div class="nvx-medical-byline">';
	$hero .= '<div class="nvx-medical-byline__text">';
	$hero .= '<strong>' . esc_html__( 'Escrito y revisado por Dr. Javier Rivera Tejeda', 'nuvanx-medical' ) . '</strong><br>';
	$hero .= '<span class="nvx-medical-byline__title">' . esc_html__( 'Director médico NUVANX · Fecha de revisión: julio 2026', 'nuvanx-medical' ) . '</span>';
	$hero .= '</div></div>';
	$hero .= '<p class="nvx-brand-hero__lead">' . esc_html( $c['lead'] ) . '</p>';
	if ( function_exists( 'nvx_cta_pair_markup' ) ) {
		$hero .= nvx_cta_pair_markup( $c['marker'] . '-hero-ctas nvx-home-hero-ctas' );
	}
	$hero .= '<p class="nvx-brand-meta">' . esc_html( $c['meta'] ) . '</p>';
	$hero .= '</div></div></section>';

	$body  = '<div class="' . esc_attr( $c['marker'] ) . '-editorial nvx-endolift-editorial nvx-btl-detail-editorial">';

	// Mechanism (same zone-list pattern as Endoláser / CO₂ — no page-exclusive layout).
	$mech_title = trim( (string) ( $c['mechanism']['title'] ?? '' ) );
	if ( '' === $mech_title ) {
		$mech_title = __( 'Mecanismo de acción', 'nuvanx-medical' );
	}
	$body .= nvx_page_brand_section_open_markup( '', $id . '-mech' );
	$body .= nvx_page_brand_section_heading_markup( esc_html__( 'Mecanismo', 'nuvanx-medical' ), $id . '-mech', esc_html( $mech_title ) );
	foreach ( (array) ( $c['mechanism']['body'] ?? array() ) as $p ) {
		$p = is_string( $p ) ? trim( $p ) : '';
		if ( '' === $p ) {
			continue;
		}
		$body .= '<p class="nvx-body nvx-body--measure">' . esc_html( $p ) . '</p>';
	}
	if ( ! empty( $c['mechanism']['items'] ) && is_array( $c['mechanism']['items'] ) ) {
		$body .= '<ul class="nvx-endolaser-zone-list">';
		foreach ( $c['mechanism']['items'] as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$title = trim( (string) ( $item['title'] ?? '' ) );
			$text  = trim( (string) ( $item['body'] ?? '' ) );
			if ( '' === $title && '' === $text ) {
				continue;
			}
			$body .= '<li class="nvx-endolaser-zone">';
			if ( '' !== $title ) {
				$body .= '<h3 class="nvx-endolaser-zone__title">' . esc_html( $title ) . '</h3>';
			}
			if ( '' !== $text ) {
				$body .= '<p class="nvx-body">' . esc_html( $text ) . '</p>';
			}
			$body .= '</li>';
		}
		$body .= '</ul>';
	}
	$body .= '<p class="nvx-body"><a class="nvx-brand-inline-link" href="' . esc_url( $c['hub'] ) . '">' . esc_html__( 'Ver plataforma EXION® BTL (hub)', 'nuvanx-medical' ) . '</a></p>';
	$body .= '</div></section>';

	// Indications.
	$body .= nvx_page_brand_section_open_markup( '', $id . '-ind' );
	$body .= nvx_page_brand_section_heading_markup( esc_html__( 'Indicaciones', 'nuvanx-medical' ), $id . '-ind', esc_html__( 'Cuándo tiene sentido este protocolo', 'nuvanx-medical' ) );
	$body .= '<ul class="nvx-endolaser-zone-list">';
	foreach ( (array) ( $c['indications'] ?? array() ) as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$title = trim( (string) ( $item['title'] ?? '' ) );
		$text  = trim( (string) ( $item['body'] ?? '' ) );
		if ( '' === $title && '' === $text ) {
			continue;
		}
		$body .= '<li class="nvx-endolaser-zone">';
		if ( '' !== $title ) {
			$body .= '<h3 class="nvx-endolaser-zone__title">' . esc_html( $title ) . '</h3>';
		}
		if ( '' !== $text ) {
			$body .= '<p class="nvx-body">' . esc_html( $text ) . '</p>';
		}
		$body .= '</li>';
	}
	$body .= '</ul></div></section>';

	// Compare + blog depth (strategy: internal link to money content).
	$compare_title = trim( (string) ( $c['compare']['title'] ?? '' ) );
	$compare_body  = trim( (string) ( $c['compare']['body'] ?? '' ) );
	$compare_link  = trim( (string) ( $c['compare']['link'] ?? '' ) );
	$compare_label = trim( (string) ( $c['compare']['label'] ?? '' ) );
	$has_compare_link = ( '' !== $compare_link && '' !== $compare_label );
	$has_related      = ! empty( $c['related'] ) && is_array( $c['related'] );
	$has_combo        = ! empty( $c['combo'] );
	if ( '' !== $compare_title || '' !== $compare_body || $has_compare_link || $has_related || $has_combo ) {
		// Prefer aria-labelledby only when the heading (and its id) is rendered.
		if ( '' !== $compare_title ) {
			$body .= '<section class="nvx-brand-section" aria-labelledby="' . esc_attr( $id ) . '-cmp">';
		} else {
			$body .= '<section class="nvx-brand-section" aria-label="' . esc_attr__( 'Criterio diferencial', 'nuvanx-medical' ) . '">';
		}
		$body .= '<div class="nvx-shell nvx-brand-section__inner">';
		$body .= '<p class="nvx-brand-kicker">' . esc_html__( 'Criterio diferencial', 'nuvanx-medical' ) . '</p>';
		if ( '' !== $compare_title ) {
			$body .= '<h2 id="' . esc_attr( $id ) . '-cmp" class="nvx-brand-title">' . esc_html( $compare_title ) . '</h2>';
		}
		if ( '' !== $compare_body ) {
			$body .= '<p class="nvx-body nvx-body--measure">' . esc_html( $compare_body ) . '</p>';
		}
		if ( $has_compare_link || $has_combo ) {
			$body .= '<p class="nvx-body">';
			$parts = array();
			if ( $has_compare_link ) {
				$parts[] = '<a class="nvx-brand-inline-link" href="' . esc_url( $compare_link ) . '">' . esc_html( $compare_label ) . '</a>';
			}
			if ( $has_combo ) {
				$parts[] = '<a class="nvx-brand-inline-link" href="' . esc_url( (string) $c['combo'] ) . '">' . esc_html__( 'Protocolos combinados NUVANX', 'nuvanx-medical' ) . '</a>';
			}
			$body .= implode( ' · ', $parts );
			$body .= '</p>';
		}
		if ( $has_related ) {
			foreach ( $c['related'] as $rel ) {
				if ( ! is_array( $rel ) ) {
					continue;
				}
				$rel_url   = trim( (string) ( $rel['url'] ?? '' ) );
				$rel_label = trim( (string) ( $rel['label'] ?? '' ) );
				if ( '' === $rel_url || '' === $rel_label ) {
					continue;
				}
				$body .= '<p class="nvx-body"><a class="nvx-brand-inline-link" href="' . esc_url( $rel_url ) . '">' . esc_html( $rel_label ) . '</a></p>';
			}
		}
		$body .= '</div></section>';
	}

	// Process (string steps or titled steps — same list chrome).
	$body .= nvx_page_brand_section_open_markup( '', $id . '-proc' );
	$body .= nvx_page_brand_section_heading_markup( esc_html__( 'Proceso médico', 'nuvanx-medical' ), $id . '-proc', esc_html__( 'Procedimiento, sesiones y cuidados', 'nuvanx-medical' ) );
	$body .= '<ol class="nvx-endolaser-zone-list">';
	foreach ( (array) ( $c['process'] ?? array() ) as $step ) {
		if ( is_array( $step ) ) {
			$title = trim( (string) ( $step['title'] ?? '' ) );
			$text  = trim( (string) ( $step['body'] ?? '' ) );
			if ( '' === $title && '' === $text ) {
				continue;
			}
			$body .= '<li class="nvx-endolaser-zone">';
			if ( '' !== $title ) {
				$body .= '<h3 class="nvx-endolaser-zone__title">' . esc_html( $title ) . '</h3>';
			}
			if ( '' !== $text ) {
				$body .= '<p class="nvx-body">' . esc_html( $text ) . '</p>';
			}
			$body .= '</li>';
		} else {
			$text = trim( (string) $step );
			if ( '' === $text ) {
				continue;
			}
			$body .= '<li class="nvx-endolaser-zone"><p class="nvx-body">' . esc_html( $text ) . '</p></li>';
		}
	}
	$body .= '</ol></div></section>';

	// FAQ.
	$body .= nvx_page_brand_section_open_markup( '', $id . '-faq' );
	$body .= nvx_page_brand_section_heading_markup( esc_html__( 'FAQ', 'nuvanx-medical' ), $id . '-faq', esc_html__( 'Preguntas frecuentes', 'nuvanx-medical' ) );
	$body .= '<div class="nvx-faq nvx-brand-faq-accordion">';
	foreach ( (array) ( $c['faqs'] ?? array() ) as $faq ) {
		if ( ! is_array( $faq ) ) {
			continue;
		}
		$q = trim( (string) ( $faq['q'] ?? '' ) );
		$a = trim( (string) ( $faq['a'] ?? '' ) );
		if ( '' === $q && '' === $a ) {
			continue;
		}
		$body .= '<details class="nvx-brand-faq-item">';
		$body .= '<summary>' . esc_html( $q ) . '</summary>';
		$body .= '<div class="nvx-brand-faq-content"><p>' . esc_html( $a ) . '</p></div>';
		$body .= '</details>';
	}
	$body .= '</div></div></section>';

	// Closing valoración CTA: site-wide nvx-cta-banner in footer.php (not page-local).

	$body .= '</div>';

	return $hero . $body;
}

/**
 * Restructure the_content for BTL detail pages.
 */
function nvx_content_restructure_btl_detail_page( string $content ): string {
	$key = nvx_btl_detail_current_key( $content );
	if ( null === $key ) {
		return $content;
	}

	$cfg = nvx_btl_detail_registry()[ $key ];
	if ( false !== strpos( $content, $cfg['marker'] . '-editorial' ) ) {
		return $content;
	}

	// Same media sources as Endolift / Endoláser / CO₂: content slot, then featured image.
	$media = nvx_page_extract_brand_hero_media( $content );
	if ( '' === $media && has_post_thumbnail() ) {
		$thumb = get_the_post_thumbnail(
			null,
			'full',
			array(
				'class'   => 'nvx-media nvx-media--hero wp-post-image',
				'alt'     => the_title_attribute( array( 'echo' => false ) ),
				'loading' => 'eager',
			)
		);
		if ( is_string( $thumb ) && '' !== $thumb ) {
			$media = '<figure class="nvx-brand-hero__media">' . $thumb . '</figure>';
		}
	}

	$built = nvx_btl_detail_page_markup( $key );
	// Inject media into hero if present (after copy, inside __inner).
	if ( '' !== $media && false !== strpos( $built, 'nvx-brand-hero__inner' ) ) {
		$built = preg_replace(
			'/(class="nvx-brand-hero__inner">[\s\S]*?<\/div>)(\s*<\/div>\s*<\/section>)/u',
			'$1' . $media . '$2',
			$built,
			1
		) ?? $built;
	}

	$modifier = 'nvx-brand-page--' . sanitize_html_class( $key );
	if ( preg_match( '/(<div class="nvx-brand-page[^"]*"[^>]*>)/iu', $content, $wrap ) ) {
		$opening = $wrap[1];
		if ( false === strpos( $opening, $modifier ) ) {
			$updated = preg_replace( '/\bclass=(["\'])/u', 'class=$1' . $modifier . ' ', $opening, 1 ) ?? $opening;
			$content = preg_replace( '/<div class="nvx-brand-page[^"]*"[^>]*>/iu', $updated, $content, 1 ) ?? $content;
		}
	}

	return nvx_page_render_brand_wrapper(
		$content,
		$built,
		'nvx-brand-page ' . $modifier
	);
}
add_filter( 'the_content', 'nvx_content_restructure_btl_detail_page', 19 );

/**
 * Yoast title for BTL detail pages.
 *
 * @param string $title Title.
 * @return string
 */
function nvx_filter_btl_detail_title( $title ) {
	$key = nvx_btl_detail_current_key( '' );
	if ( null === $key ) {
		return $title;
	}
	return nvx_btl_detail_registry()[ $key ]['yoast_title'];
}
add_filter( 'wpseo_title', 'nvx_filter_btl_detail_title', 21 );

/**
 * Yoast metadesc for BTL detail pages.
 *
 * @param string $desc Description.
 * @return string
 */
function nvx_filter_btl_detail_metadesc( $desc ) {
	$key = nvx_btl_detail_current_key( '' );
	if ( null === $key ) {
		return $desc;
	}
	return nvx_btl_detail_registry()[ $key ]['yoast_desc'];
}
add_filter( 'wpseo_metadesc', 'nvx_filter_btl_detail_metadesc', 21 );
