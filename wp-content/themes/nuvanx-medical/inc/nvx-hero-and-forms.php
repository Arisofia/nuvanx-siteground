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
 * Featured-image figure markup for hero injection, or empty when unusable.
 */
function nvx_hero_featured_media_figure(): string {
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
		return '';
	}

	// Never inject site logo / brand mark as hero photography.
	if ( preg_match( '/logo-nuvanx|nuvanx-web\.webp|\/logo[-_]|nvx-logo|site-logo|custom-logo/iu', $thumb ) ) {
		return '';
	}

	return '<figure class="nvx-brand-hero__media">' . $thumb . '</figure>';
}

/**
 * Find the end offset of a balanced <div> opened at $open_pos.
 *
 * @return int|null Offset after the matching closing tag.
 */
function nvx_hero_find_balanced_div_end( string $content, int $open_pos ): ?int {
	$len   = strlen( $content );
	$i     = $open_pos;
	$depth = 0;

	while ( $i < $len ) {
		if ( ! preg_match( '/<\/?div\b[^>]*>/i', $content, $tag_match, PREG_OFFSET_CAPTURE, $i ) ) {
			break;
		}
		$tag_pos = (int) $tag_match[0][1];
		$tag     = $tag_match[0][0];
		if ( $tag_pos > $i && 0 === $depth && $i !== $open_pos ) {
			break;
		}
		$i = $tag_pos;
		if ( 0 === strncasecmp( $tag, '</div', 5 ) ) {
			--$depth;
			$i += strlen( $tag );
			if ( 0 === $depth ) {
				return $i;
			}
			continue;
		}
		// Opening div (ignore malformed self-closing).
		++$depth;
		$i += strlen( $tag );
	}

	return null;
}

/**
 * Insert a hero media figure after the first hero __copy block, or at section start.
 */
function nvx_hero_insert_media_figure( string $content, string $figure ): string {
	$updated = $content;

	// Locate first hero __copy opening tag.
	if ( ! preg_match( '/class="[^"]*nvx-(?:brand-hero|editorial-hero|page-hero|hero)__copy[^"]*"/i', $content, $match, PREG_OFFSET_CAPTURE ) ) {
		// No copy block: place media at the start of the first hero section.
		$candidate = preg_replace(
			'/(<section\b[^>]*class="[^"]*nvx-(?:brand-hero|editorial-hero|page-hero|hero)[^"]*"[^>]*>)/i',
			'$1' . $figure,
			$content,
			1
		);
		$updated   = is_string( $candidate ) ? $candidate : $content;
	} else {
		$class_pos = (int) $match[0][1];
		$open_pos  = strrpos( substr( $content, 0, $class_pos ), '<div' );
		// Balance nested <div>…</div> so the figure is inserted AFTER the whole copy.
		$end = false !== $open_pos ? nvx_hero_find_balanced_div_end( $content, $open_pos ) : null;
		if ( null !== $end ) {
			$updated = substr( $content, 0, $end ) . $figure . substr( $content, $end );
		}
	}

	return $updated;
}

/**
 * Remove duplicate hero sections from Pattern A templates.
 * These templates have custom heroes in PHP and also call the_content()
 * which may contain a hero from the CMS. This filter removes the CMS hero
 * to prevent duplication.
 */
function nvx_remove_duplicate_hero_from_content( string $content ): string {
	if ( is_admin() || ! is_singular() ) {
		return $content;
	}

	// Only apply to Pattern A templates
	$template = get_page_template_slug();
	if ( $template && '' !== $template ) {
		if ( in_array( $template, array( 'page-landing-valoracion.php', 'page-sede.php', 'page-soluciones-medicas.php' ), true ) ) {
			// Remove ALL hero sections from the_content for these templates
			// Pattern A templates have their own heroes in PHP
			// Try multiple regex patterns to match different HTML structures
			$content = preg_replace( '/<section[^>]*class="[^"]*nvx-brand-hero[^"]*"[^>]*>.*?<\/section>/is', '', $content );
			$content = preg_replace( '/<section[^>]*class="[^"]*nvx-page-hero[^"]*"[^>]*>.*?<\/section>/is', '', $content );
			$content = preg_replace( '/<section[^>]*class="[^"]*nvx-editorial-hero[^"]*"[^>]*>.*?<\/section>/is', '', $content );
		}
	}

	// Also apply to clinic hub pages that use nvx_clinics_hub_render_managed
	$current_url = $_SERVER['REQUEST_URI'] ?? '';
	if ( strpos( $current_url, '/clinicas-de-medicina-estetica-nuvanx/' ) !== false ||
	     strpos( $current_url, '/medicina-estetica-chamberi/' ) !== false ||
	     strpos( $current_url, '/medicina-estetica-goya-barrio-salamanca/' ) !== false ) {
		$content = preg_replace( '/<section[^>]*class="[^"]*nvx-brand-hero[^"]*"[^>]*>.*?<\/section>/is', '', $content );
		$content = preg_replace( '/<section[^>]*class="[^"]*nvx-page-hero[^"]*"[^>]*>.*?<\/section>/is', '', $content );
		$content = preg_replace( '/<section[^>]*class="[^"]*nvx-editorial-hero[^"]*"[^>]*>.*?<\/section>/is', '', $content );
	}

	// Also apply to /madrid/valoracion/ via URL check
	if ( strpos( $current_url, '/madrid/valoracion/' ) !== false ) {
		$content = preg_replace( '/<section[^>]*class="[^"]*nvx-brand-hero[^"]*"[^>]*>.*?<\/section>/is', '', $content );
		$content = preg_replace( '/<section[^>]*class="[^"]*nvx-page-hero[^"]*"[^>]*>.*?<\/section>/is', '', $content );
		$content = preg_replace( '/<section[^>]*class="[^"]*nvx-editorial-hero[^"]*"[^>]*>.*?<\/section>/is', '', $content );
	}

	return $content;
}
add_filter( 'the_content', 'nvx_remove_duplicate_hero_from_content', 1 );

/**
 * Ensure content heroes that lack media use the featured image when available.
 * Inserts media as a SIBLING after the hero copy (never nested inside copy),
 * so kicker + title can overlay the image. Global — not a per-page patch.
 */
function nvx_ensure_hero_featured_media( string $content ): string {
	if ( is_admin() || ! is_singular() || is_front_page() || ! has_post_thumbnail() ) {
		return $content;
	}

	// Skip for Pattern A templates that have custom heroes in PHP
	// These templates inject their own hero and also call the_content()
	$template = get_page_template_slug();
	if ( $template && '' !== $template ) {
		if ( in_array( $template, array( 'page-landing-valoracion.php', 'page-sede.php', 'page-soluciones-medicas.php' ), true ) ) {
			return $content;
		}
	}

	// Skip for pages using nvx-clinics-hub (custom hero injection)
	// Detect by checking if content has clinic-hub-specific markers
	if ( function_exists( 'nvx_clinics_hub_page_markup' ) && has_shortcode( $content, 'nvx_clinics_hub' ) ) {
		return $content;
	}

	// Skip if global flag is set (nvx-page-shell with hero media)
	global $nvx_page_shell_has_hero;
	if ( isset( $nvx_page_shell_has_hero ) && $nvx_page_shell_has_hero ) {
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

	$figure = nvx_hero_featured_media_figure();
	if ( '' === $figure ) {
		return $content;
	}

	return nvx_hero_insert_media_figure( $content, $figure );
}
add_filter( 'the_content', 'nvx_ensure_hero_featured_media', NVX_HOOK_PRIO_HERO_MEDIA );

/**
 * Extract a balanced HTML element starting at $open_pos (must point at "<tag").
 *
 * @return string|null Full element markup including open/close tags.
 */
function nvx_extract_balanced_element( string $html, int $open_pos, string $tag ): ?string {
	$tag  = strtolower( $tag );
	$len  = strlen( $html );
	$open = '<' . $tag;
	if ( $open_pos < 0 || $open_pos >= $len || 0 !== strncasecmp( substr( $html, $open_pos, strlen( $open ) ), $open, strlen( $open ) ) ) {
		return null;
	}

	$depth   = 0;
	$i       = $open_pos;
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
	if ( ! is_page() ) {
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
add_filter( 'the_content', 'nvx_valoracion_form_first', NVX_HOOK_PRIO_VALORACION_FORM_FIRST );

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

	$updated = preg_replace(
		'/(<section\b[^>]*\bid=["\']nvx-hubspot-form["\'][^>]*\bclass=["\'])([^"\']*)(["\'])/i',
		'$1$2 nvx-form-stage$3',
		$content,
		1,
		$count
	);

	if ( is_string( $updated ) && $count > 0 ) {
		return $updated;
	}

	$replaced = preg_replace(
		'/(<section\b[^>]*\bclass=["\'])([^"\']*nvx-hubspot-form-section[^"\']*)(["\'])/i',
		'$1$2 nvx-form-stage$3',
		$content,
		1
	);
	return $replaced ? $replaced : $content;
}
add_filter( 'the_content', 'nvx_valoracion_form_stage_class', NVX_HOOK_PRIO_VALORACION_FORM_CLASS );

/*
═══════════════════════════════════════════════════════════
	Valoración HubSpot mount (absorbed from retired MU plugin)
	Single canonical lazy frame; no eager hsforms <script>.
	function_exists guards allow one-release coexistence until MU is removed.
	═══════════════════════════════════════════════════════════ */

if ( ! defined( 'NVX_VALORACION_HS_FRAME_PORTAL_ID' ) ) {
	define( 'NVX_VALORACION_HS_FRAME_PORTAL_ID', '147416356' );
}
if ( ! defined( 'NVX_VALORACION_HS_FRAME_FORM_ID' ) ) {
	define( 'NVX_VALORACION_HS_FRAME_FORM_ID', '5042522a-0bc5-4381-ac3e-5aee8649b69c' );
}
if ( ! defined( 'NVX_VALORACION_HS_FRAME_REGION' ) ) {
	define( 'NVX_VALORACION_HS_FRAME_REGION', 'eu1' );
}

/**
 * Whether the current request is the canonical valoración form route.
 */
if ( ! function_exists( 'nvx_valoracion_native_hubspot_is_target_page' ) ) {
	function nvx_valoracion_native_hubspot_is_target_page(): bool {
		if ( function_exists( 'nvx_is_valoracion_page_request' ) && nvx_is_valoracion_page_request() ) {
			return true;
		}
		return is_page( 2636 ) || is_page( 'valoracion' );
	}
}

/**
 * Lazy HubSpot mount markup (demand-loaded by nvx-runtime-governance.js).
 */
if ( ! function_exists( 'nvx_valoracion_native_hubspot_mount_markup' ) ) {
	function nvx_valoracion_native_hubspot_mount_markup(): string {
		$portal_id   = esc_attr( (string) NVX_VALORACION_HS_FRAME_PORTAL_ID );
		$form_id     = esc_attr( (string) NVX_VALORACION_HS_FRAME_FORM_ID );
		$region      = esc_attr( (string) NVX_VALORACION_HS_FRAME_REGION );
		$privacy_url = esc_url( home_url( '/politica-privacidad/' ) );

		return '<div class="hs-form-frame" data-region="' . $region . '" data-form-id="' . $form_id . '" data-portal-id="' . $portal_id . '" data-nvx-hubspot-lazy="1"></div>'
			. '<p class="nvx-copy nvx-hubspot-privacy">Al facilitar tus datos aceptas la <a class="nvx-text-link" href="' . $privacy_url . '">Política de privacidad</a>.</p>';
	}
}

/**
 * Balanced <div> range starting at $open_offset.
 *
 * @return array{start:int,length:int}|null
 */
if ( ! function_exists( 'nvx_valoracion_balanced_div_range' ) ) {
	function nvx_valoracion_balanced_div_range( string $html, int $open_offset ): ?array {
		if ( $open_offset < 0 || ! preg_match( '/\G<div\b[^>]*>/i', $html, $opening, 0, $open_offset ) ) {
			return null;
		}
		if ( ! preg_match_all( '/<div\b[^>]*>|<\/div\s*>/i', $html, $tokens, PREG_OFFSET_CAPTURE, $open_offset ) ) {
			return null;
		}

		$depth = 0;
		foreach ( $tokens[0] as $token ) {
			$markup = (string) $token[0];
			$offset = (int) $token[1];
			$depth += 0 === stripos( $markup, '</div' ) ? -1 : 1;
			if ( 0 === $depth ) {
				return array(
					'start'  => $open_offset,
					'length' => $offset + strlen( $markup ) - $open_offset,
				);
			}
		}
		return null;
	}
}

/**
 * Remove balanced divs that carry a given class token.
 */
if ( ! function_exists( 'nvx_valoracion_remove_divs_by_class' ) ) {
	function nvx_valoracion_remove_divs_by_class( string $html, string $class_token ): string {
		$pattern = '/<div\b(?=[^>]*\bclass=["\'][^"\']*\b'
			. preg_quote( $class_token, '/' )
			. '\b[^"\']*["\'])[^>]*>/i';

		if ( ! preg_match_all( $pattern, $html, $matches, PREG_OFFSET_CAPTURE ) ) {
			return $html;
		}

		$ranges = array();
		foreach ( $matches[0] as $match ) {
			$range = nvx_valoracion_balanced_div_range( $html, (int) $match[1] );
			if ( is_array( $range ) ) {
				$ranges[] = $range;
			}
		}
		usort(
			$ranges,
			static function ( array $a, array $b ): int {
				return $b['start'] <=> $a['start'];
			}
		);
		foreach ( $ranges as $range ) {
			$html = substr_replace( $html, '', $range['start'], $range['length'] );
		}
		return $html;
	}
}

/**
 * Keep a single canonical HubSpot mount on the valoración page output.
 */
if ( ! function_exists( 'nvx_valoracion_native_hubspot_enforce_single_mount' ) ) {
	function nvx_valoracion_native_hubspot_enforce_single_mount( string $html ): string {
		$mount_pattern = '/<div\b[^>]*\bid=["\']nvx-hubspot-native-form["\'][^>]*>/i';
		if ( ! preg_match_all( $mount_pattern, $html, $mounts, PREG_OFFSET_CAPTURE ) || empty( $mounts[0] ) ) {
			return $html;
		}

		$ranges = array();
		foreach ( $mounts[0] as $mount ) {
			$range = nvx_valoracion_balanced_div_range( $html, (int) $mount[1] );
			if ( is_array( $range ) ) {
				$range['opening'] = (string) $mount[0];
				$ranges[]         = $range;
			}
		}
		if ( empty( $ranges ) ) {
			return $html;
		}

		usort(
			$ranges,
			static function ( array $a, array $b ): int {
				return $a['start'] <=> $b['start'];
			}
		);
		$first_offset  = (int) $ranges[0]['start'];
		$first_opening = (string) $ranges[0]['opening'];
		$marker        = '<!-- NVX_VALORACION_CANONICAL_MOUNT -->';

		$descending = $ranges;
		usort(
			$descending,
			static function ( array $a, array $b ): int {
				return $b['start'] <=> $a['start'];
			}
		);
		foreach ( $descending as $range ) {
			$html = substr_replace( $html, '', (int) $range['start'], (int) $range['length'] );
		}
		$html = substr( $html, 0, $first_offset ) . $marker . substr( $html, $first_offset );

		$stripped = preg_replace( '#<script\b[^>]*\bsrc=["\'][^"\']*hsforms\.net/[^"\']*["\'][^>]*>\s*</script>#iu', '', $html );
		$html     = is_string( $stripped ) ? $stripped : $html;
		$stripped = preg_replace( '#<iframe\b[^>]*(?:hsforms|hubspot)[^>]*>[\s\S]*?</iframe>#iu', '', $html );
		$html     = is_string( $stripped ) ? $stripped : $html;
		$html     = nvx_valoracion_remove_divs_by_class( $html, 'hs-form-frame' );
		$html     = nvx_valoracion_remove_divs_by_class( $html, 'hbspt-form' );

		$canonical = $first_opening . nvx_valoracion_native_hubspot_mount_markup() . '</div>';
		return str_replace( $marker, $canonical, $html );
	}
}

add_action(
	'template_redirect',
	static function (): void {
		if ( nvx_valoracion_native_hubspot_is_target_page() ) {
			ob_start( 'nvx_valoracion_native_hubspot_enforce_single_mount' );
		}
	}
);

// If MU still present this request, still ensure theme does not double-register the buffer.
// (MU owns the callback until disk retirement completes.)
