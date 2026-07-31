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
 * Preserve the existing canonical hero media slot when rebuilding a page.
 */
function nvx_page_extract_brand_hero_media( string $content ): string {
	$patterns = array(
		'/<figure class="nvx-brand-hero__media"[\s\S]*?<\/figure>/iu',
		'/<div class="nvx-brand-hero__media"[\s\S]*?<\/div>/iu',
	);

	foreach ( $patterns as $pattern ) {
		if ( preg_match( $pattern, $content, $matches ) ) {
			return $matches[0];
		}
	}

	return '';
}

/**
 * Preserve an existing brand-page opening wrapper or apply a defined fallback.
 */
function nvx_page_render_brand_wrapper(
	string $content,
	string $inner_markup,
	string $fallback_class = ''
): string {
	if ( preg_match( '/(<div class="nvx-brand-page[^"]*"[^>]*>)/iu', $content, $matches ) ) {
		return $matches[1] . $inner_markup . '</div>';
	}

	if ( '' !== $fallback_class ) {
		return '<div class="' . esc_attr( $fallback_class ) . '">' . $inner_markup . '</div>';
	}

	return $inner_markup;
}
