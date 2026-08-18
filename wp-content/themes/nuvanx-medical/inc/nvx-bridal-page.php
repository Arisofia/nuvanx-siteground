<?php
/**
 * Protocolo de novias — clinic and zone photography.
 *
 * CMS page 3544 (/protocolo-novias-madrid/) has no dedicated template.
 * This module injects theme-hosted WebP figures after the philosophy block.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/nvx-page-render-helpers.php';

/** Whether the current request is the bridal protocol page. */
function nvx_is_bridal_protocol_page( string $content = '' ): bool {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	if ( is_page( 'protocolo-novias-madrid' ) || is_page( 3544 ) ) {
		return true;
	}

	return false !== strpos( $content, 'id="nvx-bridal-h1"' )
		|| false !== strpos( $content, "id='nvx-bridal-h1'" );
}

/**
 * Figure markup for a bridal upload, rewritten to theme WebP srcset.
 */
function nvx_bridal_figure( string $filename, string $alt, string $caption, string $extra_class = '' ): string {
	$src = content_url( 'uploads/2026/08/' . ltrim( $filename, '/' ) );
	$img = function_exists( 'nvx_responsive_img_markup' )
		? nvx_responsive_img_markup(
			$src,
			$alt,
			'class="nvx-media nvx-media--body" loading="lazy" decoding="async"'
		)
		: '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt ) . '" class="nvx-media nvx-media--body" loading="lazy" decoding="async">';

	$class = trim( 'nvx-content-figure ' . $extra_class );

	return '<figure class="' . esc_attr( $class ) . '">'
		. $img
		. '<figcaption>' . esc_html( $caption ) . '</figcaption>'
		. '</figure>';
}

/** Gallery section: clinic box + papada / brazos / espalda. */
function nvx_bridal_gallery_markup(): string {
	$html  = '<section class="nvx-brand-section nvx-bridal-gallery" aria-labelledby="nvx-bridal-gallery-title">';
	$html .= '<div class="nvx-brand-section__inner">';
	$html .= '<p class="nvx-brand-kicker">' . esc_html__( 'Entorno clínico y zonas', 'nuvanx-medical' ) . '</p>';
	$html .= '<h2 id="nvx-bridal-gallery-title" class="nvx-brand-title">' . esc_html__( 'Consulta en box y zonas que pueden valorarse', 'nuvanx-medical' ) . '</h2>';
	$html .= '<p class="nvx-body nvx-body--measure">' . esc_html__( 'La valoración contempla piel, rostro y, cuando exista indicación, contorno corporal. Las imágenes ilustran el espacio de consulta y zonas frecuentes en un plan de novia; no constituyen un resultado garantizado.', 'nuvanx-medical' ) . '</p>';

	$html .= nvx_bridal_figure(
		'Box-Clinica-Novias.png',
		__( 'Box de consulta en clínica NUVANX, con camilla y luz de exploración', 'nuvanx-medical' ),
		__( 'Box de consulta en clínica NUVANX', 'nuvanx-medical' ),
		'nvx-bridal-gallery__feature'
	);

	$html .= '<ul class="nvx-bridal-gallery__zones">';
	$html .= '<li class="nvx-bridal-gallery__item nvx-bridal-gallery__item--wide">';
	$html .= nvx_bridal_figure(
		'Papada-novias.png',
		__( 'Papada y contorno cervical en tres momentos de un plan de novia', 'nuvanx-medical' ),
		__( 'Papada y contorno cervical', 'nuvanx-medical' )
	);
	$html .= '</li>';
	$html .= '<li class="nvx-bridal-gallery__item">';
	$html .= nvx_bridal_figure(
		'Brazos-novias.png',
		__( 'Novia de frente con vestido de manga corta, zona de brazos', 'nuvanx-medical' ),
		__( 'Brazos', 'nuvanx-medical' )
	);
	$html .= '</li>';
	$html .= '<li class="nvx-bridal-gallery__item">';
	$html .= nvx_bridal_figure(
		'Espalda-novias.png',
		__( 'Espalda y escote de un vestido de novia sin tirantes', 'nuvanx-medical' ),
		__( 'Espalda', 'nuvanx-medical' )
	);
	$html .= '</li>';
	$html .= '</ul>';
	$html .= '</div></section>';

	return $html;
}

/**
 * Insert the bridal gallery after the philosophy section, or after the opening hero.
 */
function nvx_bridal_inject_media( string $content ): string {
	if ( '' === $content || ! nvx_is_bridal_protocol_page( $content ) ) {
		return $content;
	}

	if ( false !== strpos( $content, 'nvx-bridal-gallery' ) ) {
		return $content;
	}

	$gallery = nvx_bridal_gallery_markup();
	$anchors = array(
		'nvx-philosophy-title',
		'nvx-bridal-h1',
	);

	foreach ( $anchors as $anchor_id ) {
		$pattern = '/<section\b[^>]*(?:aria-labelledby=["\']' . preg_quote( $anchor_id, '/' ) . '["\']|id=["\']' . preg_quote( $anchor_id, '/' ) . '["\'])/iu';
		if ( ! preg_match( $pattern, $content, $match, PREG_OFFSET_CAPTURE ) ) {
			continue;
		}

		$open = (int) $match[0][1];
		$el   = function_exists( 'nvx_extract_balanced_element' )
			? nvx_extract_balanced_element( $content, $open, 'section' )
			: null;

		if ( ! is_string( $el ) || '' === $el ) {
			continue;
		}

		return substr( $content, 0, $open + strlen( $el ) ) . $gallery . substr( $content, $open + strlen( $el ) );
	}

	return $content . $gallery;
}
add_filter( 'the_content', 'nvx_bridal_inject_media', NVX_HOOK_PRIO_BRIDAL_MEDIA );
