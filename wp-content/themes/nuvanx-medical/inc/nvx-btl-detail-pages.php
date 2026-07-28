<?php
/**
 * BTL detail treatment pages: EXION Face / Body / Fractional RF + EMFUSION.
 *
 * Same editorial pattern as IPL EXILITE / CO₂: Hero → Mecanismo → Indicaciones →
 * Comparativa breve → Procedimiento → FAQ → CTA.
 * Does not replace hub /exion-btl/ or comparative blogs (linked as depth reading).
 *
 * Clinical copy lives in inc/data/nvx-btl-detail-registry.json. This file hydrates
 * that catalogue and renders/restructures detail pages.
 *
 * Paths:
 *   /exion-face/
 *   /exion-body/
 *   /exion-fractional/
 *   /emfusion/
 *   /btl-exilite-ipl-madrid/
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singular page context.
 */
function nvxBtlDetailIsSingular(): bool {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}
	return is_singular( 'page' ) || is_page();
}

/**
 * Load raw BTL detail specs from the versioned JSON catalogue.
 *
 * @return array<string, array<string, mixed>>
 */
function nvxBtlDetailRegistrySpecs(): array {
	return function_exists( 'nvx_theme_load_json_catalog' )
		? nvx_theme_load_json_catalog( 'nvx-btl-detail-registry.json' )
		: array();
}

/**
 * Resolve claim IDs, home paths, and nested catalogue values.
 *
 * @param mixed $value
 * @return mixed
 */
function nvxBtlDetailResolveValue( $value ) {
	if ( is_array( $value ) ) {
		if ( isset( $value['claim'] ) && is_string( $value['claim'] ) ) {
			return function_exists( 'nvx_btl_claim' ) ? nvx_btl_claim( $value['claim'] ) : '';
		}
		if ( isset( $value['path'] ) && is_string( $value['path'] ) ) {
			return home_url( $value['path'] );
		}

		$resolved = array();
		foreach ( $value as $key => $item ) {
			$resolved[ $key ] = nvxBtlDetailResolveValue( $item );
		}
		return $resolved;
	}

	return $value;
}

/**
 * Hydrate one raw JSON zone into a runtime registry entry.
 *
 * @param array<string, mixed> $spec
 * @return array<string, mixed>
 */
function nvxBtlDetailHydrateEntry( array $spec, string $hub ): array {
	$entry        = nvxBtlDetailResolveValue( $spec );
	$entry['hub'] = $hub;
	if ( ! isset( $entry['combo'] ) || ! is_string( $entry['combo'] ) ) {
		$entry['combo'] = '';
	}
	return $entry;
}

/**
 * Hydrate a named subset of the JSON catalogue.
 *
 * @param string[] $keys
 * @return array<string, array<string, mixed>>
 */
function nvxBtlDetailRegistrySlice( array $keys, string $hub ): array {
	$all    = nvxBtlDetailRegistrySpecs();
	$result = array();
	foreach ( $keys as $key ) {
		if ( ! isset( $all[ $key ] ) || ! is_array( $all[ $key ] ) ) {
			continue;
		}
		$result[ $key ] = nvxBtlDetailHydrateEntry( $all[ $key ], $hub );
	}
	return $result;
}

/**
 * Sub-registry for EXION BTL detail pages.
 *
 * @param string $hub Base hub URL.
 * @return array<string, array<string, mixed>>
 */
function nvxBtlDetailRegistryExion( string $hub ): array {
	return nvxBtlDetailRegistrySlice(
		array( 'exion-face', 'exion-body', 'exion-fractional', 'emfusion' ),
		$hub
	);
}

/**
 * Sub-registry for EXILITE BTL detail pages.
 *
 * @param string $hub Base hub URL.
 * @return array<string, array<string, mixed>>
 */
function nvxBtlDetailRegistryExilite( string $hub ): array {
	return nvxBtlDetailRegistrySlice( array( 'exilite' ), $hub );
}

/**
 * Returns BTL detail page registry.
 *
 * @return array<string, array<string, mixed>>
 */
function nvxBtlDetailRegistry(): array {
	static $registry = null;
	if ( null !== $registry ) {
		return $registry;
	}

	$hub      = home_url( '/exion-btl/' );
	$registry = array_merge(
		nvxBtlDetailRegistryExion( $hub ),
		nvxBtlDetailRegistryExilite( $hub )
	);
	return $registry;
}

/**
 * Snake_case alias used by structured-data / SEO readiness schema builders.
 *
 * Historical call sites (and some CI markers) reference nvx_btl_detail_registry();
 * keep a thin alias so Service/FAQ nodes are not silently dropped.
 *
 * @return array<string, array<string, mixed>>
 */
function nvx_btl_detail_registry(): array {
	return nvxBtlDetailRegistry();
}

/**
 * Check if the content contains standard H1 markers for a BTL detail page configuration.
 *
 * @param string               $content HTML content.
 * @param array<string, mixed> $cfg     Detail configuration array.
 * @return bool True if content contains matching H1 IDs.
 */
function nvxBtlDetailMatchesContent( string $content, array $cfg ): bool {
	$marker = (string) ( $cfg['marker'] ?? '' );
	if ( '' === $marker ) {
		return false;
	}
	return false !== strpos( $content, 'id="' . $marker . '-h1"' )
		|| false !== strpos( $content, "id='{$marker}-h1'" )
		|| false !== strpos( $content, 'id="nvx-' . $marker . '-h1"' )
		|| false !== strpos( $content, "id='nvx-{$marker}-h1'" );
}

/**
 * Resolve detail key from current request / content.
 *
 * @return string|null Registry key.
 */
function nvxBtlDetailCurrentKey( string $content = '' ): ?string {
	if ( ! nvxBtlDetailIsSingular() || is_front_page() || is_home() ) {
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

	foreach ( nvxBtlDetailRegistry() as $slug => $cfg ) {
		if ( function_exists( 'nvx_schema_path_matches' ) && nvx_schema_path_matches( $path, $cfg['path'] ) ) {
			return $slug;
		}
		if ( false !== strpos( $content, $cfg['marker'] . '-editorial' ) ) {
			return null; // already rebuilt
		}
		if ( nvxBtlDetailMatchesContent( $content, $cfg ) ) {
			return $slug;
		}
	}

	$slug = get_post_field( 'post_name', get_queried_object_id() );
	if ( is_string( $slug ) && isset( nvxBtlDetailRegistry()[ $slug ] ) ) {
		return $slug;
	}

	return null;
}

/**
 * Renders a standard editorial list section.
 *
 * @param string $id_base   The base for the section ID.
 * @param string $kicker    The section kicker text.
 * @param string $heading   The main heading for the section.
 * @param array  $items     An array of items to list.
 * @param string $list_type The list tag ('ul' or 'ol').
 * @return string The rendered HTML for the section.
 */
function nvxRenderEditorialListSection( string $id_base, string $kicker, string $heading, array $items, string $list_type = 'ul' ): string {
	$html  = '<section class="nvx-editorial-section" aria-labelledby="' . esc_attr( $id_base ) . '">';
	$html .= '<div class="nvx-editorial-section__inner">';
	$html .= '<p class="nvx-editorial-kicker">' . esc_html( $kicker ) . '</p>';
	$html .= '<h2 id="' . esc_attr( $id_base ) . '" class="nvx-editorial-heading">' . esc_html( $heading ) . '</h2>';
	$html .= '<' . tag_escape( $list_type ) . ' class="nvx-editorial-grid-list">';
	foreach ( $items as $item ) {
		$title = is_array( $item ) ? (string) ( $item['title'] ?? '' ) : '';
		$text  = is_array( $item ) ? (string) ( $item['body'] ?? '' ) : (string) $item;
		$html .= '<li class="nvx-editorial-grid-item">' . ( $title ? '<h3 class="nvx-editorial-grid-item__title">' . esc_html( $title ) . '</h3>' : '' ) . '<p class="nvx-editorial-body">' . esc_html( $text ) . '</p></li>';
	}
	return $html . '</' . tag_escape( $list_type ) . '></div></section>';
}

/** Render mechanism items grid list. */
function nvxBtlDetailRenderMechanismItems( array $items ): string {
	$markup = '<ul class="nvx-editorial-grid-list">';
	foreach ( $items as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$title = trim( (string) ( $item['title'] ?? '' ) );
		$text  = trim( (string) ( $item['body'] ?? '' ) );
		if ( '' === $title && '' === $text ) {
			continue;
		}
		$markup .= '<li class="nvx-editorial-grid-item">';
		if ( '' !== $title ) {
			$markup .= '<h3 class="nvx-editorial-grid-item__title">' . esc_html( $title ) . '</h3>';
		}
		if ( '' !== $text ) {
			$markup .= '<p class="nvx-editorial-body">' . esc_html( $text ) . '</p>';
		}
		$markup .= '</li>';
	}
	$markup .= '</ul>';
	return $markup;
}

/**
 * Renders the mechanism section for a BTL detail page.
 *
 * @param array  $c  Detail configuration array.
 * @param string $id Base section identifier.
 * @return string HTML markup.
 */
function nvxBtlDetailRenderMechanismSection( array $c, string $id ): string {
	$body  = '<section class="nvx-editorial-section" aria-labelledby="' . esc_attr( $id ) . '-mech">';
	$body .= '<div class="nvx-editorial-section__inner">';
	$body .= '<p class="nvx-editorial-kicker">' . esc_html__( 'Mecanismo', 'nuvanx-medical' ) . '</p>';
	$body .= '<h2 id="' . esc_attr( $id ) . '-mech" class="nvx-editorial-heading">' . esc_html( (string) ( $c['mechanism']['title'] ?? '' ) ) . '</h2>';
	foreach ( (array) ( $c['mechanism']['body'] ?? array() ) as $p ) {
		$p = is_string( $p ) ? trim( $p ) : '';
		if ( '' !== $p ) {
			$body .= '<p class="nvx-editorial-body nvx-editorial-body--measure">' . esc_html( $p ) . '</p>';
		}
	}
	if ( ! empty( $c['mechanism']['items'] ) && is_array( $c['mechanism']['items'] ) ) {
		$body .= nvxBtlDetailRenderMechanismItems( $c['mechanism']['items'] );
	}
	$body .= '<p class="nvx-editorial-body"><a class="nvx-brand-inline-link" href="' . esc_url( $c['hub'] ) . '">' . esc_html__( 'Ver plataforma EXION® BTL (hub)', 'nuvanx-medical' ) . '</a></p>';
	$body .= '</div></section>';
	return $body;
}

/**
 * Renders the FAQ section for a BTL detail page.
 *
 * @param array  $faqs Array of FAQ items.
 * @param string $id   Base section identifier.
 * @return string HTML markup.
 */
function nvxBtlDetailRenderFaqSection( array $faqs, string $id ): string {
	$body  = '<section class="nvx-editorial-section" aria-labelledby="' . esc_attr( $id ) . '-faq">';
	$body .= '<div class="nvx-editorial-section__inner">';
	$body .= '<p class="nvx-editorial-kicker">' . esc_html__( 'FAQ', 'nuvanx-medical' ) . '</p>';
	$body .= '<h2 id="' . esc_attr( $id ) . '-faq" class="nvx-editorial-heading">' . esc_html__( 'Preguntas frecuentes', 'nuvanx-medical' ) . '</h2>';
	$body .= '<div class="nvx-faq nvx-brand-faq-accordion">';
	foreach ( $faqs as $faq ) {
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
	return $body;
}

/**
 * Generates the complete editorial HTML markup for a supported BTL detail page.
 *
 * @param string $key The registry key identifying the detail page.
 * @return string The generated page markup, or an empty string when the key is unsupported.
 */
function nvxBtlDetailPageMarkup( string $key ): string {
	$reg = nvxBtlDetailRegistry();
	if ( empty( $reg[ $key ] ) ) {
		return '';
	}
	$c = $reg[ $key ];
	// Markers are already nvx-* (e.g. nvx-exion-body); do not prefix again.
	$id = $c['marker'];

	// Hero.
	$hero  = '<section class="nvx-brand-hero nvx-brand-hero--laser nvx-editorial-hero" aria-labelledby="' . esc_attr( $id ) . '-h1" aria-label="' . esc_attr( $c['aria'] ) . '">';
	$hero .= '<div class="nvx-brand-hero__inner">';
	$hero .= '<div class="nvx-editorial-hero__copy">';
	$hero .= '<p class="nvx-eyebrow">' . esc_html( $c['kicker'] ) . '</p>';
	$hero .= '<h1 class="nvx-heading" id="' . esc_attr( $id ) . '-h1">' . esc_html( $c['h1'] ) . '</h1>';

	// E-E-A-T Medical Authority Byline
	$hero .= '<div class="nvx-medical-byline">';
	$hero .= '<div class="nvx-medical-byline__text">';
	$hero .= '<strong>' . esc_html__( 'Escrito y revisado por Dr. Javier Rivera Tejeda', 'nuvanx-medical' ) . '</strong><br>';
	$hero .= '<span class="nvx-medical-byline__title">' . esc_html__( 'Director médico NUVANX · Fecha de revisión: julio 2026', 'nuvanx-medical' ) . '</span>';
	$hero .= '</div></div>';
	$hero .= '<p class="nvx-lead">' . esc_html( $c['lead'] ) . '</p>';
	if ( function_exists( 'nvx_cta_pair_markup' ) ) {
		$hero .= nvx_cta_pair_markup( $c['marker'] . '-hero-ctas nvx-home-hero-ctas' );
	}
	$hero .= '<p class="nvx-brand-meta">' . esc_html( $c['meta'] ) . '</p>';
	$hero .= '</div></div></section>';

	$body  = '<div class="' . esc_attr( $c['marker'] ) . '-editorial nvx-editorial-page nvx-btl-detail-editorial">';

	// Mechanism.
	$body .= nvxBtlDetailRenderMechanismSection( $c, $id );

	// Indications.
	$body .= nvxRenderEditorialListSection( $id . '-ind', __( 'Indicaciones', 'nuvanx-medical' ), __( 'Cuándo tiene sentido este protocolo', 'nuvanx-medical' ), (array) ( $c['indications'] ?? array() ) );

	// Compare + blog depth.
	$compare_title = trim( (string) ( $c['compare']['title'] ?? '' ) );
	$compare_body  = trim( (string) ( $c['compare']['body'] ?? '' ) );
	$body         .= nvxRenderEditorialListSection( $id . '-cmp', __( 'Criterio diferencial', 'nuvanx-medical' ), $compare_title, array( $compare_body ) );

	// Process.
	$body .= nvxRenderEditorialListSection( $id . '-proc', __( 'Proceso médico', 'nuvanx-medical' ), __( 'Procedimiento, sesiones y cuidados', 'nuvanx-medical' ), (array) ( $c['process'] ?? array() ), 'ol' );

	// FAQ.
	$body .= nvxBtlDetailRenderFaqSection( (array) ( $c['faqs'] ?? array() ), $id );

	$body .= '</div>';

	return $hero . $body;
}

/** Extract hero media markup from content or featured image. */
function nvxBtlDetailExtractHeroMedia( string $content ): string {
	if ( preg_match( '/<figure class="nvx-brand-hero__media"[\s\S]*?<\/figure>/iu', $content, $m ) ) {
		return $m[0];
	}
	if ( preg_match( '/<div class="nvx-brand-hero__media"[\s\S]*?<\/div>/iu', $content, $m ) ) {
		return $m[0];
	}
	if ( ! has_post_thumbnail() ) {
		return '';
	}
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
		return '<figure class="nvx-brand-hero__media">' . $thumb . '</figure>';
	}
	return '';
}

/** Wrap built markup in an nvx-brand-page div, reusing existing wrapper when present. */
function nvxBtlDetailWrapInBrandPage( string $content, string $key, string $built ): string {
	if ( preg_match( '/(<div class="nvx-brand-page[^"]*"[^>]*>)/iu', $content, $wrap ) ) {
		$open = $wrap[1];
		$mod  = 'nvx-brand-page--' . sanitize_html_class( $key );
		if ( false === strpos( $open, $mod ) ) {
			$open = preg_replace( '/\bclass=(["\'])/u', 'class=$1' . $mod . ' ', $open, 1 ) ?? $open;
		}
		return $open . $built . '</div>';
	}
	return '<div class="nvx-brand-page nvx-brand-page--' . esc_attr( $key ) . '">' . $built . '</div>';
}

/**
 * Restructure the_content for BTL detail pages.
 */
function nvxContentRestructureBtlDetailPage( string $content ): string {
	$key = nvxBtlDetailCurrentKey( $content );
	if ( null === $key ) {
		return $content;
	}

	$cfg = nvxBtlDetailRegistry()[ $key ];
	if ( false !== strpos( $content, $cfg['marker'] . '-editorial' ) ) {
		return $content;
	}

	// Same media sources as Endolift / Endoláser / CO₂: content figure, then featured image.
	$media = nvxBtlDetailExtractHeroMedia( $content );

	$built = nvxBtlDetailPageMarkup( $key );
	// Inject media into hero if present (after copy, inside __inner).
	if ( '' !== $media && false !== strpos( $built, 'nvx-brand-hero__inner' ) ) {
		$built = preg_replace(
			'/(class="nvx-brand-hero__inner">[\s\S]*?<\/div>)(\s*<\/div>\s*<\/section>)/u',
			'$1' . $media . '$2',
			$built,
			1
		) ?? $built;
	}

	return nvxBtlDetailWrapInBrandPage( $content, $key, $built );
}
add_filter( 'the_content', 'nvxContentRestructureBtlDetailPage', 19 );

/**
 * Read a Yoast field from the current BTL detail entry.
 *
 * @param string $field    Registry field name.
 * @param mixed  $fallback Original Yoast value.
 * @return mixed
 */
function nvxBtlDetailYoastField( string $field, $fallback ) {
	$key = nvxBtlDetailCurrentKey( '' );
	if ( null === $key ) {
		return $fallback;
	}
	$entry = nvxBtlDetailRegistry()[ $key ] ?? null;
	return is_array( $entry ) && isset( $entry[ $field ] ) ? $entry[ $field ] : $fallback;
}

/**
 * Yoast title for BTL detail pages.
 *
 * @param string $title Title.
 * @return string
 */
function nvxFilterBtlDetailTitle( $title ) {
	return nvxBtlDetailYoastField( 'yoast_title', $title );
}
add_filter( 'wpseo_title', 'nvxFilterBtlDetailTitle', 21 );

/**
 * Yoast metadesc for BTL detail pages.
 *
 * @param string $desc Description.
 * @return string
 */
function nvxFilterBtlDetailMetadesc( $desc ) {
	return nvxBtlDetailYoastField( 'yoast_desc', $desc );
}
add_filter( 'wpseo_metadesc', 'nvxFilterBtlDetailMetadesc', 21 );
