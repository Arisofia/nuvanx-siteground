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
 * Resolve a same-host WordPress uploads URL to its local filesystem path.
 *
 * Returns an empty string for external/CDN URLs or URLs that cannot be mapped
 * safely into the active uploads directory.
 */
function nvx_local_upload_file_from_url( string $url ): string {
	if ( '' === trim( $url ) ) {
		return '';
	}

	$uploads = wp_upload_dir();
	if ( ! empty( $uploads['error'] ) || empty( $uploads['baseurl'] ) || empty( $uploads['basedir'] ) ) {
		return '';
	}

	$base_url  = (string) $uploads['baseurl'];
	$base_host = strtolower( (string) wp_parse_url( $base_url, PHP_URL_HOST ) );
	$base_path = rawurldecode( (string) wp_parse_url( $base_url, PHP_URL_PATH ) );
	$base_path = '/' . trim( $base_path, '/' );
	$base_dir  = untrailingslashit( (string) $uploads['basedir'] );

	$source_host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	$source_path = rawurldecode( (string) wp_parse_url( $url, PHP_URL_PATH ) );

	if ( '' === $base_host || '/' === $base_path || '' === $base_dir || '' === $source_path || $source_host !== $base_host ) {
		return '';
	}

	if ( $source_path !== $base_path && 0 !== strpos( $source_path, $base_path . '/' ) ) {
		return '';
	}

	$relative_path = ltrim( substr( $source_path, strlen( $base_path ) ), '/' );
	if ( '' === $relative_path || false !== strpos( $relative_path, '..' ) ) {
		return '';
	}

	return $base_dir . '/' . $relative_path;
}

/**
 * Determine whether a local uploads URL exists on disk.
 *
 * @return bool|null True/false for local uploads, null for external/unmappable URLs.
 */
function nvx_local_upload_url_exists( string $url ) {
	$local_file = nvx_local_upload_file_from_url( $url );
	if ( '' === $local_file ) {
		return null;
	}

	static $file_exists = array();
	if ( ! array_key_exists( $local_file, $file_exists ) ) {
		$file_exists[ $local_file ] = is_file( $local_file );
	}

	return $file_exists[ $local_file ];
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

	foreach ( $sources as $width => $source ) {
		if ( ! is_array( $source ) || empty( $source['url'] ) ) {
			continue;
		}

		$exists = nvx_local_upload_url_exists( (string) $source['url'] );
		if ( false === $exists ) {
			unset( $sources[ $width ] );
		}
	}

	return array() === $sources ? false : $sources;
}
add_filter( 'wp_calculate_image_srcset', 'nvx_filter_missing_local_srcset_sources', 20, 5 );

/**
 * Remove rendered content images that point to missing local upload files.
 *
 * This is a fail-safe for migrated/editorial content whose HTML still points to
 * media that no longer exists. It never fabricates replacement patient media
 * and does not alter external/CDN images. The surrounding copy remains visible.
 */
function nvx_remove_missing_local_content_images( $content ) {
	if ( ! is_string( $content ) || false === stripos( $content, '<img' ) ) {
		return $content;
	}

	$filtered = preg_replace_callback(
		'/<img\b[^>]*>/iu',
		static function ( $matches ) {
			$tag = isset( $matches[0] ) ? (string) $matches[0] : '';
			if ( '' === $tag ) {
				return $tag;
			}

			if ( ! preg_match( '/\bsrc\s*=\s*(["\'])(.*?)\1/iu', $tag, $src_match ) ) {
				return $tag;
			}

			$src = html_entity_decode( (string) $src_match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			return false === nvx_local_upload_url_exists( $src ) ? '' : $tag;
		},
		$content
	);

	if ( ! is_string( $filtered ) ) {
		return $content;
	}

	// Remove only figures left completely empty after deleting a broken image.
	$filtered = preg_replace( '/<figure\b[^>]*>\s*<\/figure>/iu', '', $filtered ) ?? $filtered;

	return $filtered;
}
add_filter( 'the_content', 'nvx_remove_missing_local_content_images', 20 );

/**
 * Render canonical FAQ accordion section markup.
 *
 * @param array{kicker?:string,title?:string,items?:array<int,array{q:string,a:string}>} $faq FAQ data array.
 * @param string $prefix Section CSS prefix (e.g. 'nvx-co2').
 * @return string Rendered section HTML.
 */
function nvx_render_editorial_faq_markup( array $faq, string $prefix ): string {
	$kicker     = esc_html( $faq['kicker'] ?? '' );
	$title      = esc_html( $faq['title'] ?? '' );
	$title_id   = esc_attr( $prefix . '-faq-title' );
	$sec_class  = esc_attr( $prefix . '-faq' );
	$list_class = esc_attr( $prefix . '-faq-list' );

	$html  = nvx_page_brand_section_open_markup( $sec_class, $title_id );
	$html .= nvx_page_brand_section_heading_markup( $kicker, $title_id, $title );
	$html .= '<div class="nvx-faq ' . $list_class . '">';

	foreach ( $faq['items'] ?? array() as $item ) {
		$q     = esc_html( $item['q'] ?? '' );
		$a     = esc_html( $item['a'] ?? '' );
		$html .= '<details class="nvx-brand-faq-item">';
		$html .= '<summary><span>' . $q . '</span></summary>';
		$html .= '<div class="nvx-brand-faq-content"><p>' . $a . '</p></div>';
		$html .= '</details>';
	}

	$html .= '</div></div></section>';
	return $html;
}

/**
 * Render canonical fact panel sidebar component markup.
 *
 * @param array{panel_title?:string,panel_items?:array<int,array{title:string,body:string}>} $diagnosis Diagnosis data.
 * @param string $aria_label Panel accessible label.
 * @return string Rendered sidebar HTML.
 */
function nvx_render_editorial_fact_panel_markup( array $diagnosis, string $aria_label = 'Criterio de diagnóstico' ): string {
	$title = esc_html( $diagnosis['panel_title'] ?? '' );
	$html  = '<aside class="nvx-fact-panel" aria-label="' . esc_attr( $aria_label ) . '">';
	$html .= '<p class="nvx-fact-panel__label">' . $title . '</p>';
	$html .= '<ul class="nvx-fact-panel__list" role="list">';

	foreach ( $diagnosis['panel_items'] ?? array() as $item ) {
		$t     = esc_html( $item['title'] ?? '' );
		$b     = esc_html( $item['body'] ?? '' );
		$html .= '<li><strong>' . $t . '</strong> — ' . $b . '</li>';
	}

	$html .= '</ul></aside>';
	return $html;
}

/**
 * Render canonical process steps grid section markup.
 *
 * @param array{kicker?:string,title?:string,body?:string,steps?:array<int,array{n?:string,title?:string,body?:string,icon?:string}>} $process Process data.
 * @param string $prefix Section CSS prefix (e.g. 'nvx-co2').
 * @param callable $icon_cb Callback function that takes icon name and returns SVG markup.
 * @return string Rendered section HTML.
 */
function nvx_render_editorial_process_grid_markup( array $process, string $prefix, callable $icon_cb ): string {
	$title_id = esc_attr( $prefix . '-process-title' );
	$sec_cls  = esc_attr( $prefix . '-process' );
	$grid_cls = esc_attr( $prefix . '-process-grid' );

	$html  = nvx_page_brand_section_open_markup( $sec_cls, $title_id );
	$html .= nvx_page_brand_section_heading_markup( esc_html( $process['kicker'] ?? '' ), $title_id, esc_html( $process['title'] ?? '' ) );
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html( $process['body'] ?? '' ) . '</p>';
	$html .= '<div class="' . $grid_cls . '">';

	$step_idx = 0;
	foreach ( $process['steps'] ?? array() as $step ) {
		$sid   = esc_attr( $prefix . '-step-' . $step_idx );
		$icon  = is_callable( $icon_cb ) ? $icon_cb( $step['icon'] ?? 'assess' ) : '';
		$html .= '<article class="' . esc_attr( $prefix . '-step' ) . '" aria-labelledby="' . $sid . '">';
		$html .= $icon;
		$html .= '<span class="' . esc_attr( $prefix . '-step__n' ) . '">' . esc_html( $step['n'] ?? '' ) . '</span>';
		$html .= '<h3 id="' . $sid . '" class="' . esc_attr( $prefix . '-step__title' ) . '">' . esc_html( $step['title'] ?? '' ) . '</h3>';
		$html .= '<p class="nvx-body">' . esc_html( $step['body'] ?? '' ) . '</p>';
		$html .= '</article>';
		++$step_idx;
	}

	$html .= '</div></div></section>';
	return $html;
}

