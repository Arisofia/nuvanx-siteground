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
 * Remove stale local responsive-image candidates whose files are missing.
 *
 * WordPress attachment metadata can outlive generated upload derivatives after
 * a migration or media cleanup. Advertising those stale files in `srcset`
 * makes browsers request predictable 404s even when the primary image exists.
 * External/CDN candidates are left untouched because their filesystem cannot
 * be verified from the WordPress uploads directory.
 *
 * @param array|false $sources       Responsive image candidates keyed by width.
 * @param int[]       $size_array    Requested image dimensions.
 * @param string      $image_src     Primary image URL.
 * @param array       $image_meta    Attachment metadata.
 * @param int         $attachment_id Attachment ID.
 * @return array|false Filtered sources, or false when no valid local candidate remains.
 */
function nvx_filter_missing_local_srcset_sources( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
	unset( $size_array, $image_src, $image_meta, $attachment_id );

	if ( ! is_array( $sources ) || array() === $sources ) {
		return $sources;
	}

	$uploads = wp_upload_dir();
	if ( ! empty( $uploads['error'] ) || empty( $uploads['baseurl'] ) || empty( $uploads['basedir'] ) ) {
		return $sources;
	}

	$base_url  = (string) $uploads['baseurl'];
	$base_host = strtolower( (string) wp_parse_url( $base_url, PHP_URL_HOST ) );
	$base_path = rawurldecode( (string) wp_parse_url( $base_url, PHP_URL_PATH ) );
	$base_path = '/' . trim( $base_path, '/' );
	$base_dir  = untrailingslashit( (string) $uploads['basedir'] );

	if ( '' === $base_host || '/' === $base_path || '' === $base_dir ) {
		return $sources;
	}

	static $file_exists = array();

	foreach ( $sources as $width => $source ) {
		if ( ! is_array( $source ) || empty( $source['url'] ) ) {
			continue;
		}

		$source_url  = (string) $source['url'];
		$source_host = strtolower( (string) wp_parse_url( $source_url, PHP_URL_HOST ) );
		$source_path = rawurldecode( (string) wp_parse_url( $source_url, PHP_URL_PATH ) );

		if ( $source_host !== $base_host || '' === $source_path ) {
			continue;
		}

		if ( $source_path !== $base_path && 0 !== strpos( $source_path, $base_path . '/' ) ) {
			continue;
		}

		$relative_path = ltrim( substr( $source_path, strlen( $base_path ) ), '/' );
		if ( '' === $relative_path || false !== strpos( $relative_path, '..' ) ) {
			continue;
		}

		$local_file = $base_dir . '/' . $relative_path;
		if ( ! array_key_exists( $local_file, $file_exists ) ) {
			$file_exists[ $local_file ] = is_file( $local_file );
		}

		if ( ! $file_exists[ $local_file ] ) {
			unset( $sources[ $width ] );
		}
	}

	return array() === $sources ? false : $sources;
}
add_filter( 'wp_calculate_image_srcset', 'nvx_filter_missing_local_srcset_sources', 20, 5 );
