<?php
/**
 * Global rendered-document and front-end runtime governance.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Document rewrite no longer uses a theme output buffer.
 *
 * SiteGround Optimizer + Complianz + core already own the front-end buffer
 * stack. Inserting nvx_document_governance_normalize_document as another
 * callback produced HTTP 200 + empty body on /soluciones-medicas/ (content sat
 * in an inner buffer while the outer rewrite layer flushed empty). Head
 * contract (title, description, canonical, document marker) is enforced via
 * wp_head / Yoast filters instead of full-document preg rewrites.
 */
function nvx_document_governance_start(): void {
	// Intentionally a no-op kept for call-site compatibility (header history).
}

/**
 * Yoast may skip or duplicate canonical under staging noindex. Theme emits the
 * single public canonical from wp_head; suppress Yoast's copy as the final word
 * on wpseo_canonical (priority PHP_INT_MAX so no peer filter can re-enable it).
 *
 * Staging production-host policy for social tags lives on wpseo_opengraph_url
 * in nvx-staging2-canonical-closure.php — not on this filter.
 *
 * @param string|false $canonical Existing canonical.
 * @return false
 */
function nvx_document_governance_suppress_yoast_canonical( $canonical ) {
	unset( $canonical );
	return false;
}
add_filter( 'wpseo_canonical', 'nvx_document_governance_suppress_yoast_canonical', PHP_INT_MAX );

/**
 * Emit document contract pieces that used to be forced by the full-document
 * rewrite buffer: contract marker + exactly one canonical.
 */
function nvx_document_governance_print_head_contract(): void {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_feed() ) {
		return;
	}

	$canonical = nvx_document_governance_canonical_url();
	echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
	echo '<meta name="nvx-document-contract" content="1" />' . "\n";
}
add_action( 'wp_head', 'nvx_document_governance_print_head_contract', 2 );

/**
 * Enqueue the platform accessibility/runtime layer and prevent eager HubSpot.
 */
function nvx_document_governance_enqueue_assets(): void {
	$uri = get_template_directory_uri();

	wp_enqueue_style(
		'nvx-accessibility-governance',
		$uri . '/assets/css/nvx-accessibility-governance.css',
		array( 'nvx-header', 'nvx-footer' ),
		function_exists( 'nvx_asset_version' )
			? nvx_asset_version( 'assets/css/nvx-accessibility-governance.css' )
			: NVX_THEME_VERSION
	);

	wp_enqueue_script(
		'nvx-runtime-governance',
		$uri . '/assets/js/nvx-runtime-governance.js',
		array( 'nvx-main' ),
		function_exists( 'nvx_asset_version' )
			? nvx_asset_version( 'assets/js/nvx-runtime-governance.js' )
			: NVX_THEME_VERSION,
		true
	);

	// Eager HubSpot strip is owned solely by nvx-integrations.php
	// (wp_enqueue_scripts + script_loader_tag).

	$modal_enabled = function_exists( 'nvx_valoracion_modal_enabled' )
		? nvx_valoracion_modal_enabled()
		: false;
	// Never emit a full hsforms URL in server HTML: consent/optimizer scanners
	// treat any inline mention of that domain as an eager marketing embed and can
	// drop the entire runtime-governance handle from modal-enabled routes.
	$config = array(
		'modalEnabled'     => $modal_enabled,
		'modalId'          => 'nvx-valoracion-modal',
		'mobileNavId'      => 'nvx-mobile-nav',
		'hubspotScriptId'  => 'nvx-hubspot-forms-runtime',
		'hubspotPortalId'  => '',
		'hubspotRegion'    => 'eu1',
		'hubspotPageMount' => true,
	);

	if ( function_exists( 'nvx_valoracion_modal_hubspot_config' ) ) {
		$hubspot = nvx_valoracion_modal_hubspot_config();
		$config['hubspotPortalId'] = isset( $hubspot['portal_id'] ) ? (string) $hubspot['portal_id'] : '';
		$config['hubspotRegion']   = isset( $hubspot['region'] ) ? (string) $hubspot['region'] : 'eu1';
	}

	$encoded = wp_json_encode( $config, JSON_UNESCAPED_SLASHES );
	if ( ! is_string( $encoded ) ) {
		$encoded = '{}';
	}

	wp_add_inline_script(
		'nvx-runtime-governance',
		'window.nvxRuntimeGovernance=' . $encoded . ';',
		'before'
	);
}

add_action( 'wp_enqueue_scripts', 'nvx_document_governance_enqueue_assets', 100 );

/**
 * Whether the current front request is the medical solutions hub.
 *
 * Used only for route-specific template/content decisions — not for skipping
 * the document buffer (all public routes share one rewrite pipeline).
 */
function nvx_document_governance_is_solutions_hub(): bool {
	return ! is_admin()
		&& is_page()
		&& 'soluciones-medicas' === (string) get_post_field( 'post_name', get_queried_object_id() );
}

/**
 * Remove only the individual script element that owns a retired integration.
 */
function nvx_document_governance_remove_retired_scripts( string $html ): string {
	$normalized = preg_replace_callback(
		'/<script\b[^>]*>[\s\S]*?<\/script>/iu',
		static function ( array $match ): string {
			if ( false === stripos( $match[0], 'FacebookSignal' ) ) {
				return $match[0];
			}

			return '';
		},
		$html
	);

	return is_string( $normalized ) ? $normalized : $html;
}

/**
 * Read an HTML attribute from a single tag.
 */
function nvx_document_governance_tag_attribute( string $tag, string $attribute ): string {
	$pattern = '/\b' . preg_quote( $attribute, '/' ) . '\s*=\s*(["\'])(.*?)\1/iu';
	if ( 1 !== preg_match( $pattern, $tag, $match ) ) {
		return '';
	}

	return html_entity_decode( (string) $match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
}

/**
 * Resolve an attachment ID with request and object-cache protection.
 */
function nvx_document_governance_attachment_id( string $clean_src ): int {
	static $resolved = array();

	if ( array_key_exists( $clean_src, $resolved ) ) {
		return $resolved[ $clean_src ];
	}

	$cache_key = 'url_attachment_' . hash( 'sha256', $clean_src );
	$cached    = wp_cache_get( $cache_key, 'nvx-document-governance' );
	if ( false !== $cached ) {
		$resolved[ $clean_src ] = (int) $cached;
		return $resolved[ $clean_src ];
	}

	$attachment_id = attachment_url_to_postid( $clean_src );
	if ( $attachment_id <= 0 ) {
		$original_candidate = (string) preg_replace(
			'/-\d+x\d+(?=\.[a-z0-9]+$)/iu',
			'',
			$clean_src
		);
		if ( $original_candidate !== $clean_src ) {
			$attachment_id = attachment_url_to_postid( $original_candidate );
		}
	}

	$resolved[ $clean_src ] = max( 0, (int) $attachment_id );
	wp_cache_set(
		$cache_key,
		$resolved[ $clean_src ],
		'nvx-document-governance',
		HOUR_IN_SECONDS
	);

	return $resolved[ $clean_src ];
}

/**
 * Read width/height integers from attachment metadata or a size entry.
 *
 * @param array<string,mixed> $source Metadata or size array.
 * @return array{0:int,1:int}
 */
function nvx_document_governance_pair_dimensions( array $source ): array {
	return array(
		isset( $source['width'] ) ? (int) $source['width'] : 0,
		isset( $source['height'] ) ? (int) $source['height'] : 0,
	);
}

/**
 * Resolve dimensions for the exact original or derived attachment filename.
 *
 * @return array{0:int,1:int}
 */
function nvx_document_governance_attachment_dimensions( int $attachment_id, string $clean_src ): array {
	$metadata = wp_get_attachment_metadata( $attachment_id );
	if ( ! is_array( $metadata ) ) {
		return array( 0, 0 );
	}

	$basename = wp_basename( $clean_src );
	$sizes    = isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] )
		? $metadata['sizes']
		: array();
	$source   = null;

	foreach ( $sizes as $size ) {
		if ( is_array( $size ) && isset( $size['file'] ) && $basename === (string) $size['file'] ) {
			$source = $size;
			break;
		}
	}

	if ( null === $source && ! preg_match( '/-\d+x\d+\.[a-z0-9]+$/iu', $basename ) ) {
		$source = $metadata;
	}

	return null === $source
		? array( 0, 0 )
		: nvx_document_governance_pair_dimensions( $source );
}

/**
 * Whether an image src is a same-origin WordPress upload.
 */
function nvx_document_governance_is_same_origin_upload( string $src ): bool {
	if ( '' === $src || false === strpos( $src, '/wp-content/uploads/' ) ) {
		return false;
	}

	$site_host  = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
	$image_host = strtolower( (string) wp_parse_url( $src, PHP_URL_HOST ) );

	return '' === $image_host || $image_host === $site_host;
}

/**
 * Append missing width/height attributes when dimensions are known.
 */
function nvx_document_governance_apply_dimensions(
	string $tag,
	bool $has_width,
	bool $has_height,
	int $width,
	int $height
): string {
	$attributes = '';
	if ( ! $has_width ) {
		$attributes .= ' width="' . $width . '"';
	}
	if ( ! $has_height ) {
		$attributes .= ' height="' . $height . '"';
	}

	$candidate = preg_replace_callback(
		'/\s*\/?>$/u',
		static function ( array $match ) use ( $attributes ): string {
			return $attributes . $match[0];
		},
		$tag,
		1
	);
	return is_string( $candidate ) ? $candidate : $tag;
}

/**
 * Add intrinsic dimensions to one eligible image tag.
 */
function nvx_document_governance_add_dimensions_to_tag( string $tag ): string {
	$has_width  = (bool) preg_match( '/(?:^|\s)width\s*=/iu', $tag );
	$has_height = (bool) preg_match( '/(?:^|\s)height\s*=/iu', $tag );
	$normalized = $tag;

	if ( ! ( $has_width && $has_height ) ) {
		$src = nvx_document_governance_tag_attribute( $tag, 'src' );
		if ( nvx_document_governance_is_same_origin_upload( $src ) ) {
			$clean_src     = (string) strtok( $src, '?#' );
			$attachment_id = nvx_document_governance_attachment_id( $clean_src );
			list( $width, $height ) = nvx_document_governance_attachment_dimensions( $attachment_id, $clean_src );
			if ( $attachment_id > 0 && $width > 0 && $height > 0 ) {
				$normalized = nvx_document_governance_apply_dimensions(
					$tag,
					$has_width,
					$has_height,
					$width,
					$height
				);
			}
		}
	}

	return $normalized;
}

/**
 * Restore intrinsic dimensions for same-origin WordPress media images.
 */
function nvx_document_governance_add_image_dimensions( string $html ): string {
	$normalized = preg_replace_callback(
		'/<img\b[^>]*>/iu',
		static function ( array $match ): string {
			return nvx_document_governance_add_dimensions_to_tag( $match[0] );
		},
		$html
	);

	return is_string( $normalized ) ? $normalized : $html;
}

/**
 * Return the first visible main-content text as a metadata fallback.
 */
function nvx_document_governance_visible_main_text( string $html ): string {
	if ( 1 !== preg_match( '/<main\b[^>]*>([\s\S]*?)<\/main>/iu', $html, $match ) ) {
		return '';
	}

	$text = (string) preg_replace(
		'/<(script|style|noscript|svg|form)\b[^>]*>[\s\S]*?<\/\1>/iu',
		' ',
		$match[1]
	);
	$text = html_entity_decode( wp_strip_all_tags( $text, true ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = (string) preg_replace( '/\s+/u', ' ', trim( $text ) );

	return $text;
}

/**
 * Resolve one non-empty document title for every public response.
 */
function nvx_document_governance_title( string $existing_title = '' ): string {
	$title = function_exists( 'nvx_seo_current_metadata' )
		? nvx_seo_current_metadata( 'title', '' )
		: '';

	if ( '' === trim( $title ) ) {
		$title = trim( wp_strip_all_tags( html_entity_decode( $existing_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
	}
	if ( '' === $title ) {
		$title = trim( wp_get_document_title() );
	}
	if ( '' === $title && is_singular() ) {
		$title = trim( (string) get_the_title( get_queried_object_id() ) );
		if ( '' !== $title ) {
			$title .= ' | NUVANX Madrid';
		}
	}
	if ( '' === $title ) {
		$title = trim( (string) get_bloginfo( 'name' ) );
	}

	return '' !== $title ? $title : 'NUVANX Medicina Estética Láser Madrid';
}

/**
 * Return the Unicode-safe length of normalized text.
 */
function nvx_document_governance_text_length( string $text ): int {
	return function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text );
}

/**
 * Normalize one description candidate.
 */
function nvx_document_governance_description_candidate( string $candidate ): string {
	$candidate = trim( wp_strip_all_tags( $candidate ) );
	return (string) preg_replace( '/\s+/u', ' ', $candidate );
}

/**
 * Resolve one useful meta description from canonical data or visible content.
 */
function nvx_document_governance_description( string $existing_description, string $visible_text ): string {
	$canonical = function_exists( 'nvx_seo_current_metadata' )
		? nvx_seo_current_metadata( 'description', '' )
		: '';
	$excerpt   = is_singular()
		? (string) get_the_excerpt( get_queried_object_id() )
		: '';
	$page_title = is_singular()
		? trim( (string) get_the_title( get_queried_object_id() ) )
		: '';
	$title_description = '' !== $page_title
		? 'Información médica sobre ' . $page_title . ' y valoración individualizada en NUVANX Madrid.'
		: '';

	$candidates = array(
		$canonical,
		$existing_description,
		$excerpt,
		$visible_text,
		$title_description,
		(string) get_bloginfo( 'description' ),
		'Medicina estética láser en Madrid con valoración médica individualizada, criterio clínico y seguimiento en NUVANX.',
	);
	$description = '';

	foreach ( $candidates as $candidate ) {
		$normalized = nvx_document_governance_description_candidate( (string) $candidate );
		if ( nvx_document_governance_text_length( $normalized ) >= 40 ) {
			$description = $normalized;
			break;
		}
	}

	if ( function_exists( 'mb_substr' ) && nvx_document_governance_text_length( $description ) > 160 ) {
		$description = rtrim( mb_substr( $description, 0, 157 ) ) . '…';
	} elseif ( strlen( $description ) > 160 ) {
		$description = rtrim( substr( $description, 0, 157 ) ) . '…';
	}

	return $description;
}

/**
 * Resolve the canonical URL without changing the staging robots policy.
 *
 * Always returns a non-empty absolute URL for public HTML so the rendered
 * document never ships without a canonical link.
 */
function nvx_document_governance_canonical_url(): string {
	$fallback = home_url( '/' );
	$url      = '';

	if ( ! is_404() && function_exists( 'nvx_seo_current_canonical_url' ) ) {
		$url = trim( (string) nvx_seo_current_canonical_url() );
	}

	if ( '' === $url && ! is_404() && ! is_front_page() ) {
		$page_id   = (int) get_queried_object_id();
		$permalink = $page_id > 0 ? get_permalink( $page_id ) : '';
		$url       = is_string( $permalink ) ? trim( $permalink ) : '';
	}

	if ( '' === $url ) {
		$url = $fallback;
	}

	return $url;
}

/**
 * Read the first matching meta content value from the rendered head.
 */
function nvx_document_governance_existing_meta_description( string $head ): string {
	if ( 1 !== preg_match( '/<meta\b(?=[^>]*\bname\s*=\s*(["\'])description\1)[^>]*>/iu', $head, $match ) ) {
		return '';
	}

	return nvx_document_governance_tag_attribute( $match[0], 'content' );
}

/**
 * Enforce exactly one viewport, title, description, canonical and contract marker.
 */
function nvx_document_governance_normalize_head( string $head, string $visible_text ): string {
	$existing_title = '';
	if ( 1 === preg_match( '/<title\b[^>]*>([\s\S]*?)<\/title>/iu', $head, $title_match ) ) {
		$existing_title = (string) $title_match[1];
	}

	$title       = nvx_document_governance_title( $existing_title );
	$description = nvx_document_governance_description(
		nvx_document_governance_existing_meta_description( $head ),
		$visible_text
	);
	$canonical   = nvx_document_governance_canonical_url();

	// Literal removals (empty replacement) avoid backreference interpretation hazards.
	$strip_patterns = array(
		'/<title\b[^>]*>[\s\S]*?<\/title>/iu',
		'/<meta\b(?=[^>]*\bname\s*=\s*(["\'])description\1)[^>]*>/iu',
		'/<link\b(?=[^>]*\brel\s*=\s*(["\'])canonical\1)[^>]*>/iu',
		'/<meta\b(?=[^>]*\bname\s*=\s*(["\'])nvx-document-contract\1)[^>]*>/iu',
		'/<meta\b(?=[^>]*\bname\s*=\s*(["\'])viewport\1)[^>]*>/iu',
	);
	foreach ( $strip_patterns as $pattern ) {
		$stripped = preg_replace_callback(
			$pattern,
			static function (): string {
				return '';
			},
			$head
		);
		$head = is_string( $stripped ) ? $stripped : $head;
	}

	$metadata  = "\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\" />\n";
	$metadata .= '<title>' . esc_html( $title ) . '</title>' . "\n";
	$metadata .= '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
	// Always emit exactly one canonical for public documents.
	$metadata .= '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
	$metadata .= '<meta name="nvx-document-contract" content="1" />' . "\n";

	$charset_pattern = '/<meta\b[^>]*charset\s*=\s*(["\'])?[^\s>"\']+\1?[^>]*>/iu';
	if ( 1 === preg_match( $charset_pattern, $head ) ) {
		$normalized = preg_replace_callback(
			$charset_pattern,
			static function ( array $match ) use ( $metadata ): string {
				// Concatenate rather than using $0/$1 in the replacement string.
				return $match[0] . $metadata;
			},
			$head,
			1
		);
		return is_string( $normalized ) ? $normalized : $metadata . $head;
	}

	return $metadata . $head;
}

/**
 * Final public-document callback.
 *
 * Single pipeline: infrastructure cleanup (integrations) then document contract
 * (title/description/canonical/viewport/images). Nested buffers are forbidden.
 *
 * Never returns an empty string when the buffer already holds a document: a
 * failed rewrite (PCRE, partial handler, OPcache-stale edge) must not yield
 * HTTP 200 with Content-Length 0.
 */
function nvx_document_governance_normalize_document( string $html ): string {
	$original = $html;

	if ( '' === $html || false === stripos( $html, '<html' ) ) {
		return $html;
	}

	// Theme infrastructure normalizer (viewport, retired scripts, GSI strip, FAQ).
	// Defined in nvx-integrations.php; optional so early header load stays safe.
	if ( function_exists( 'nvx_theme_normalize_public_document' ) ) {
		$html = nvx_theme_normalize_public_document( $html );
	} else {
		$html = nvx_document_governance_remove_retired_scripts( $html );
	}

	if ( '' === $html ) {
		return $original;
	}

	$html         = nvx_document_governance_add_image_dimensions( $html );
	$visible_text = nvx_document_governance_visible_main_text( $html );

	$normalized = preg_replace_callback(
		'/<head\b([^>]*)>([\s\S]*?)<\/head>/iu',
		static function ( array $match ) use ( $visible_text ): string {
			return '<head' . $match[1] . '>'
				. nvx_document_governance_normalize_head( $match[2], $visible_text )
				. '</head>';
		},
		$html,
		1
	);

	$result = is_string( $normalized ) ? $normalized : $html;
	if ( '' === trim( $result ) && '' !== trim( $original ) ) {
		return $original;
	}

	return $result;
}
