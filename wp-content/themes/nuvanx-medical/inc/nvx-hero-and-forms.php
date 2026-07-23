<?php
/**
 * Hero media injection, valoración form order/stage (content filters).
 * Kept out of functions.php to satisfy CSS Gate inventory rules.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ensure content heroes that lack media use the featured image when available.
 * Inserts media as a SIBLING after the hero copy (never nested inside copy),
 * so kicker + title can overlay the image. Global — not a per-page patch.
 */
function nvx_ensure_hero_featured_media( string $content ): string {
	if ( is_admin() || ! is_singular() || is_front_page() || ! has_post_thumbnail() ) {
		return $content;
	}

	// Content already owns a media rail inside the hero.
	if ( preg_match( '/nvx-(?:brand-hero|editorial-hero|page-hero|hero)__media/i', $content ) ) {
		return $content;
	}

	// Only inject into known hero containers.
	if ( ! preg_match( '/nvx-(?:brand-hero|editorial-hero|page-hero)|\bclass="[^"]*\bnvx-hero\b/i', $content ) ) {
		return $content;
	}

	$thumb = get_the_post_thumbnail(
		null,
		'full',
		array(
			'class'   => 'nvx-media nvx-media--hero',
			'loading' => 'eager',
			'alt'     => the_title_attribute( array( 'echo' => false ) ),
		)
	);

	if ( ! $thumb ) {
		return $content;
	}

	// Never inject site logo / brand mark as hero photography.
	if ( preg_match( '/logo-nuvanx|nuvanx-web\.webp|\/logo[-_]|nvx-logo|site-logo|custom-logo/iu', $thumb ) ) {
		return $content;
	}

	$figure = '<figure class="nvx-brand-hero__media">' . $thumb . '</figure>';

	// Locate first hero __copy opening tag.
	if ( ! preg_match( '/class="[^"]*nvx-(?:brand-hero|editorial-hero|page-hero|hero)__copy[^"]*"/i', $content, $match, PREG_OFFSET_CAPTURE ) ) {
		// No copy block: place media at the start of the first hero section.
		$updated = nvx_content_preg_replace_keep(
			'/(<section\b[^>]*class="[^"]*nvx-(?:brand-hero|editorial-hero|page-hero|hero)[^"]*"[^>]*>)/i',
			'$1' . $figure,
			$content,
			1
		);
		return $updated;
	}

	$class_pos = (int) $match[0][1];
	$open_pos  = strrpos( substr( $content, 0, $class_pos ), '<div' );
	if ( false === $open_pos ) {
		return $content;
	}

	// Balance nested <div>…</div> so the figure is inserted AFTER the whole copy.
	$len   = strlen( $content );
	$i     = $open_pos;
	$depth = 0;
	$end   = null;

	while ( $i < $len ) {
		if ( ! preg_match( '/<\/?div\b[^>]*>/i', $content, $tag_match, PREG_OFFSET_CAPTURE, $i ) ) {
			break;
		}
		$tag_pos = (int) $tag_match[0][1];
		$tag     = $tag_match[0][0];
		if ( $tag_pos > $i && $depth === 0 && $i !== $open_pos ) {
			break;
		}
		$i = $tag_pos;
		if ( 0 === strncasecmp( $tag, '</div', 5 ) ) {
			--$depth;
			$i += strlen( $tag );
			if ( 0 === $depth ) {
				$end = $i;
				break;
			}
			continue;
		}
		// Opening div (ignore malformed self-closing).
		++$depth;
		$i += strlen( $tag );
	}

	if ( null === $end ) {
		return $content;
	}

	return substr( $content, 0, $end ) . $figure . substr( $content, $end );
}
add_filter( 'the_content', 'nvx_ensure_hero_featured_media', 12 );

/**
 * Extract a balanced HTML element starting at $open_pos (must point at "<tag").
 *
 * @return string|null Full element markup including open/close tags.
 */
function nvx_extract_balanced_element( string $html, int $open_pos, string $tag ): ?string {
	$tag   = strtolower( $tag );
	$len   = strlen( $html );
	$open  = '<' . $tag;
	if ( $open_pos < 0 || $open_pos >= $len || 0 !== strncasecmp( substr( $html, $open_pos, strlen( $open ) ), $open, strlen( $open ) ) ) {
		return null;
	}

	$depth = 0;
	$i     = $open_pos;
	$pattern = '/<\/?' . preg_quote( $tag, '/' ) . '\b[^>]*>/i';

	while ( $i < $len ) {
		if ( ! preg_match( $pattern, $html, $m, PREG_OFFSET_CAPTURE, $i ) ) {
			return null;
		}
		$tag_pos = (int) $m[0][1];
		$el      = $m[0][0];
		$i       = $tag_pos;
		if ( 0 === strncasecmp( $el, '</', 2 ) ) {
			--$depth;
			$i += strlen( $el );
			if ( 0 === $depth ) {
				return substr( $html, $open_pos, $i - $open_pos );
			}
			continue;
		}
		// Self-closing section is rare; treat as open.
		if ( preg_match( '/\/>\s*$/', $el ) ) {
			$i += strlen( $el );
			if ( 0 === $depth ) {
				return substr( $html, $open_pos, $i - $open_pos );
			}
			continue;
		}
		++$depth;
		$i += strlen( $el );
	}

	return null;
}

/**
 * Landing valoración: form is the first content after the hero.
 */
function nvx_theme_is_valoracion_landing(): bool {
	if ( ! is_singular( 'page' ) ) {
		return false;
	}
	if ( 'templates/page-landing-valoracion.php' === (string) get_page_template_slug() ) {
		return true;
	}
	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	return 'valoracion' === $slug;
}

/**
 * Move #nvx-hubspot-form section to sit immediately after the page hero.
 */
function nvx_valoracion_form_first( string $content ): string {
	if ( is_admin() || ! nvx_theme_is_valoracion_landing() ) {
		return $content;
	}

	if ( ! preg_match( '/<section\b[^>]*(?:\bid=["\']nvx-hubspot-form["\']|class=["\'][^"\']*nvx-hubspot-form-section[^"\']*["\'])[^>]*>/i', $content, $match, PREG_OFFSET_CAPTURE ) ) {
		return $content;
	}

	$form_start = (int) $match[0][1];
	$form       = nvx_extract_balanced_element( $content, $form_start, 'section' );
	if ( ! is_string( $form ) || $form === '' ) {
		return $content;
	}

	// Already first body block after hero? Detect adjacency.
	$without = substr( $content, 0, $form_start ) . substr( $content, $form_start + strlen( $form ) );

	if ( ! preg_match( '/<section\b[^>]*class=["\'][^"\']*nvx-(?:hero|page-hero|brand-hero)[^"\']*["\'][^>]*>/i', $without, $hero_match, PREG_OFFSET_CAPTURE ) ) {
		// No hero: put form first inside main page wrapper if present.
		if ( preg_match( '/id=["\']nvx-valoracion-main["\'][^>]*>/i', $without, $wrap, PREG_OFFSET_CAPTURE ) ) {
			$pos = (int) $wrap[0][1] + strlen( $wrap[0][0] );
			return substr( $without, 0, $pos ) . $form . substr( $without, $pos );
		}
		return $form . $without;
	}

	$hero_start = (int) $hero_match[0][1];
	$hero       = nvx_extract_balanced_element( $without, $hero_start, 'section' );
	if ( ! is_string( $hero ) || $hero === '' ) {
		return $content;
	}

	$hero_end = $hero_start + strlen( $hero );
	// Skip optional whitespace / injected media siblings already inside hero.
	return substr( $without, 0, $hero_end ) . $form . substr( $without, $hero_end );
}
add_filter( 'the_content', 'nvx_valoracion_form_first', 14 );

/**
 * Valoración form stage: use featured/header image as section atmosphere.
 */
function nvx_valoracion_form_stage_image_css(): void {
	if ( ! nvx_theme_is_valoracion_landing() ) {
		return;
	}

	$image_url = get_the_post_thumbnail_url( get_queried_object_id(), 'full' );
	if ( ! is_string( $image_url ) || $image_url === '' ) {
		// Fallback to known header media filename when present in media library.
		$image_url = content_url( 'uploads/2026/07/fondo-formulario.webp' );
	}

	$css = sprintf(
		'.nvx-hubspot-form-section,.nvx-form-stage{--nvx-form-stage-image:url("%s");}',
		esc_url_raw( $image_url )
	);

	wp_add_inline_style( 'nvx-layout', $css );
}
add_action( 'wp_enqueue_scripts', 'nvx_valoracion_form_stage_image_css', 30 );

/**
 * Mark the valoración form section for stage styling.
 */
function nvx_valoracion_form_stage_class( string $content ): string {
	if ( is_admin() || ! nvx_theme_is_valoracion_landing() ) {
		return $content;
	}

	$updated = nvx_content_preg_replace_keep(
		'/(<section\b[^>]*\bid=["\']nvx-hubspot-form["\'][^>]*\bclass=["\'])([^"\']*)(["\'])/i',
		'$1$2 nvx-form-stage$3',
		$content,
		1
	);

	if ( is_string( $updated ) && $updated !== $content ) {
		return $updated;
	}

	return nvx_content_preg_replace_keep(
		'/(<section\b[^>]*\bclass=["\'])([^"\']*nvx-hubspot-form-section[^"\']*)(["\'])/i',
		'$1$2 nvx-form-stage$3',
		$content,
		1
	) ?: $content;
}
add_filter( 'the_content', 'nvx_valoracion_form_stage_class', 15 );
