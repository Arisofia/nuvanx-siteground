<?php
/**
 * Shared helpers for canonical page rebuild modules.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Devuelve el "dueÃ±o" lÃ³gico de la pÃ¡gina actual.
 *
 * Los mÃ³dulos pueden engancharse al filtro 'nvx_page_owner' para declararse
 * propietarios en funciÃ³n del contexto (is_page(), is_singular(), etc.).
 */
function nvx_get_page_owner() {
	/**
	 * Filtro que permite a los mÃ³dulos declarar la propiedad de la pÃ¡gina.
	 *
	 * Debe devolver un identificador estable de propietario (string) o null.
	 */
	$owner = apply_filters( 'nvx_page_owner', null );

	return $owner;
}

/** Extract a balanced div media slot without truncating nested markup. */
function nvx_page_extract_brand_hero_div( string $content ): string {
	if ( ! preg_match( '/<div class="nvx-brand-hero__media"[^>]*>/iu', $content, $opening, PREG_OFFSET_CAPTURE ) ) {
		return '';
	}

	$start = (int) $opening[0][1];
	$tail  = substr( $content, $start );
	if ( ! preg_match_all( '/<\/?div\b[^>]*>/iu', $tail, $tags, PREG_OFFSET_CAPTURE ) ) {
		return '';
	}

	$depth = 0;
	foreach ( $tags[0] as $tag ) {
		$is_closing = 0 === strpos( $tag[0], '</' );
		$depth     += $is_closing ? -1 : 1;
		if ( 0 === $depth ) {
			$length = (int) $tag[1] + strlen( $tag[0] );
			return substr( $tail, 0, $length );
		}
	}

	return '';
}

/** Preserve the existing canonical hero media slot when rebuilding a page. */
function nvx_page_extract_brand_hero_media( string $content ): string {
	if ( preg_match( '/<figure class="nvx-brand-hero__media"[\s\S]*?<\/figure>/iu', $content, $matches ) ) {
		return $matches[0];
	}

	return nvx_page_extract_brand_hero_div( $content );
}

/** Preserve an existing brand-page opening wrapper or apply a defined fallback. */
function nvx_page_render_brand_wrapper(
	string $content,
	string $inner_markup,
	string $fallback_class = 'nvx-brand-page'
): string {
	if ( preg_match( '/(<div class="nvx-brand-page[^"]*"[^>]*>)/iu', $content, $matches ) ) {
		return $matches[1] . $inner_markup . '</div>';
	}

	if ( '' === trim( $fallback_class ) ) {
		$fallback_class = 'nvx-brand-page';
	}

	return '<div class="' . esc_attr( $fallback_class ) . '">' . $inner_markup . '</div>';
}


/**
 * Open a canonical brand section and its inner shell.
 *
 * Callers keep translated copy in their own source and pass escaped markup.
 *
 * @param array<string,string> $section_attributes Additional safe attributes.
 */
function nvx_page_brand_section_open_markup(
	string $section_class,
	string $labelledby,
	string $inner_extra_class = '',
	array $section_attributes = array()
): string {
	$section_classes = 'nvx-brand-section';
	$section_suffix  = trim( $section_class );
	if ( '' !== $section_suffix ) {
		$section_classes .= ' ' . $section_suffix;
	}

	$inner_classes = 'nvx-shell nvx-brand-section__inner';
	$inner_suffix  = trim( $inner_extra_class );
	if ( '' !== $inner_suffix ) {
		$inner_classes .= ' ' . $inner_suffix;
	}

	$html               = '<section class="' . esc_attr( $section_classes ) . '" aria-labelledby="' . esc_attr( $labelledby ) . '"';
	$allowed_attributes = array( 'id' );
	foreach ( $section_attributes as $attribute => $value ) {
		if ( ! is_string( $attribute ) || ! in_array( $attribute, $allowed_attributes, true ) ) {
			continue;
		}
		$html .= ' ' . $attribute . '="' . esc_attr( $value ) . '"';
	}

	return $html . '><div class="' . esc_attr( $inner_classes ) . '">';
}

/**
 * Render the canonical kicker and H2 pair.
 *
 * The kicker and heading arguments must already be escaped by the caller.
 */
function nvx_page_brand_section_heading_markup(
	string $kicker,
	string $heading_id,
	string $heading
): string {
	return '<p class="nvx-brand-kicker">' . $kicker . '</p>'
		. '<h2 id="' . esc_attr( $heading_id ) . '" class="nvx-brand-title">' . $heading . '</h2>';
}

/**
 * Devuelve si la página actual utiliza el page-shell de NUVANX.
 */
function nvx_has_page_shell(): bool {
	// Si tiene 'nvx_page_owner', asumimos que está gobernado por el shell u otro orquestador que necesita su propio <main>.
	if ( function_exists( 'nvx_get_page_owner' ) && ! empty( nvx_get_page_owner() ) ) {
		return true;
	}

	// Otras comprobaciones de plantillas
	return is_page() || is_single() || is_404();
}

/**
 * Devuelve si la página actual utiliza un wrapper de marca personalizado en lugar del contenedor genérico.
 */
function nvx_has_custom_brand_wrapper(): bool {
	if ( ! function_exists( 'nvx_get_page_owner' ) ) {
		return false;
	}

	$owner = nvx_get_page_owner();
	if ( empty( $owner ) ) {
		return false;
	}

	$custom_brand_owners = array(
		'nvx_equipo_page',
		'nvx_nosotros_page',
		'nvx_laser_hub',
		'nvx_endolift_page',
		'nvx_endolaser_page',
		'nvx_co2_page',
		'nvx_clinics_hub',
		'nvx_aesthetic_medicine_page',
		'nvx_btl_detail_page',
		'nvx_aesthetic_treatment_pages',
		'nvx_signature_phase_page',
		'nvx_signature_hub_page',
		'nvx_valoracion_managed_page',
	);

	return in_array( $owner, $custom_brand_owners, true );
}
