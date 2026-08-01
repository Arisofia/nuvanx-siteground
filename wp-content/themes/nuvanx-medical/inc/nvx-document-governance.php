<?php
/**
 * Global rendered-document and front-end runtime governance.
 *
 * This is the final contract for every public theme response. It protects
 * document metadata from integration output corruption, keeps third-party form
 * code demand-loaded, restores intrinsic image dimensions and loads the shared
 * accessibility runtime.
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Start the innermost document buffer before the doctype is emitted.
 *
 * The theme has an older outer normalizer that runs from template_redirect.
 * Running this contract as the innermost buffer guarantees that unsafe legacy
 * integration fragments are removed script-by-script before the outer buffer
 * receives the document.
 */
function nvx_document_governance_start(): void {
	static $started = false;

	if (
		$started
		|| is_admin()
		|| wp_doing_ajax()
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		|| is_feed()
	) {
		return;
	}

	$started = true;
	ob_start( 'nvx_document_governance_normalize_document' );
}

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

	// The valoración form is loaded only after an explicit modal-opening action.
	wp_dequeue_script( 'nvx-hubspot-forms-embed' );
	wp_deregister_script( 'nvx-hubspot-forms-embed' );

	$modal_enabled = function_exists( 'nvx_valoracion_modal_enabled' )
		? nvx_valoracion_modal_enabled()
		: false;
	$config        = array(
		'modalEnabled'     => $modal_enabled,
		'modalId'          => 'nvx-valoracion-modal',
		'mobileNavId'      => 'nvx-mobile-nav',
		'hubspotScriptId'  => 'nvx-hubspot-forms-runtime',
		'hubspotScriptUrl' => '',
	);

	if ( $modal_enabled && function_exists( 'nvx_valoracion_modal_hubspot_config' ) ) {
		$hubspot                    = nvx_valoracion_modal_hubspot_config();
		$config['hubspotScriptUrl'] = isset( $hubspot['script_url'] ) ? (string) $hubspot['script_url'] : '';
	}

	wp_add_inline_script(
		'nvx-runtime-governance',
		'window.nvxRuntimeGovernance=' . wp_json_encode( $config, JSON_UNESCAPED_SLASHES ) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'nvx_document_governance_enqueue_assets', 100 );

/**
 * Remove only the individual script element that owns a retired integration.
 *
 * A whole-document regex must never begin at one script and terminate at a
 * later script. That pattern can erase titles, metadata, styles and consent
 * bootstrap variables located between both elements.
 */
function nvx_document_governance_remove_retired_scripts( string $html ): string {
	$normalized = preg_replace_callback(
		'/<script\b[^>]*>[\s\S]*?<\/script>/iu',
		static function ( array $match ): string {
			return false !== stripos( $match[0], 'FacebookSignal' ) ? '' : $match[0];
		},
		$html
	);

	$html = is_string( $normalized ) ? $normalized : $html;
	$html = (string) preg_replace(
		'/(?:window\.)?FacebookSignal\.[a-zA-Z0-9_$]+\([^;]*\);?/iu',
		'',
		$html
	);

	return $html;
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
 * Restore intrinsic dimensions for same-origin WordPress media images.
 */
function nvx_document_governance_add_image_dimensions( string $html ): string {
	$normalized = preg_replace_callback(
		'/<img\b[^>]*>/iu',
		static function ( array $match ): string {
			$tag = $match[0];
			if ( preg_match( '/\bwidth\s*=/iu', $tag ) && preg_match( '/\bheight\s*=/iu', $tag ) ) {
				return $tag;
			}

			$src = nvx_document_governance_tag_attribute( $tag, 'src' );
			if ( '' === $src || false === strpos( $src, '/wp-content/uploads/' ) ) {
				return $tag;
			}

			$site_host  = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
			$image_host = strtolower( (string) wp_parse_url( $src, PHP_URL_HOST ) );
			if ( '' !== $image_host && $image_host !== $site_host ) {
				return $tag;
			}

			$clean_src     = strtok( $src, '?#' );
			$attachment_id = attachment_url_to_postid( (string) $clean_src );
			if ( $attachment_id <= 0 ) {
				return $tag;
			}

			$metadata = wp_get_attachment_metadata( $attachment_id );
			$width    = isset( $metadata['width'] ) ? (int) $metadata['width'] : 0;
			$height   = isset( $metadata['height'] ) ? (int) $metadata['height'] : 0;
			if ( $width <= 0 || $height <= 0 ) {
				return $tag;
			}

			$attributes = '';
			if ( ! preg_match( '/\bwidth\s*=/iu', $tag ) ) {
				$attributes .= ' width="' . $width . '"';
			}
			if ( ! preg_match( '/\bheight\s*=/iu', $tag ) ) {
				$attributes .= ' height="' . $height . '"';
			}

			return (string) preg_replace( '/\s*\/?>$/u', $attributes . '$0', $tag, 1 );
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

	$text = (string) preg_replace( '/<(?:script|style|noscript|svg|form)\b[^>]*>[\s\S]*?<\/(?:script|style|noscript|svg|form)>/iu', ' ', $match[1] );
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
 * Resolve one useful meta description from canonical data or visible content.
 */
function nvx_document_governance_description( string $existing_description, string $visible_text ): string {
	$description = function_exists( 'nvx_seo_current_metadata' )
		? nvx_seo_current_metadata( 'description', '' )
		: '';

	if ( '' === trim( $description ) ) {
		$description = trim( $existing_description );
	}
	if ( '' === $description && is_singular() ) {
		$description = trim( wp_strip_all_tags( (string) get_the_excerpt( get_queried_object_id() ) ) );
	}
	if ( '' === $description ) {
		$description = trim( $visible_text );
	}
	if ( '' === $description ) {
		$page_title  = is_singular() ? trim( (string) get_the_title( get_queried_object_id() ) ) : '';
		$description = '' !== $page_title
			? 'Información médica sobre ' . $page_title . ' y valoración individualizada en NUVANX Madrid.'
			: trim( (string) get_bloginfo( 'description' ) );
	}
	if ( '' === $description ) {
		$description = 'Medicina estética láser en Madrid con valoración médica individualizada, criterio clínico y seguimiento en NUVANX.';
	}

	$description = (string) preg_replace( '/\s+/u', ' ', $description );
	if ( function_exists( 'mb_substr' ) && mb_strlen( $description ) > 160 ) {
		$description = rtrim( mb_substr( $description, 0, 157 ) ) . '…';
	} elseif ( strlen( $description ) > 160 ) {
		$description = rtrim( substr( $description, 0, 157 ) ) . '…';
	}

	return $description;
}

/**
 * Resolve the canonical URL without changing the staging robots policy.
 */
function nvx_document_governance_canonical_url(): string {
	if ( is_404() ) {
		return '';
	}
	if ( function_exists( 'nvx_seo_current_canonical_url' ) ) {
		return (string) nvx_seo_current_canonical_url();
	}
	if ( is_front_page() ) {
		return home_url( '/' );
	}

	$page_id = (int) get_queried_object_id();
	$url     = $page_id > 0 ? get_permalink( $page_id ) : '';
	return is_string( $url ) && '' !== $url ? $url : home_url( '/' );
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
 * Enforce exactly one title, description, canonical and contract marker.
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

	$head = (string) preg_replace( '/<title\b[^>]*>[\s\S]*?<\/title>/iu', '', $head );
	$head = (string) preg_replace( '/<meta\b(?=[^>]*\bname\s*=\s*(["\'])description\1)[^>]*>/iu', '', $head );
	$head = (string) preg_replace( '/<link\b(?=[^>]*\brel\s*=\s*(["\'])canonical\1)[^>]*>/iu', '', $head );
	$head = (string) preg_replace( '/<meta\b(?=[^>]*\bname\s*=\s*(["\'])nvx-document-contract\1)[^>]*>/iu', '', $head );

	$metadata  = "\n<title>" . esc_html( $title ) . "</title>\n";
	$metadata .= '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
	if ( '' !== $canonical ) {
		$metadata .= '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
	}
	$metadata .= '<meta name="nvx-document-contract" content="1" />' . "\n";

	if ( 1 === preg_match( '/<meta\b[^>]*name\s*=\s*(["\'])viewport\1[^>]*>/iu', $head, $viewport ) ) {
		return str_replace( $viewport[0], $viewport[0] . $metadata, $head );
	}

	return $metadata . $head;
}

/**
 * Final public-document callback.
 */
function nvx_document_governance_normalize_document( string $html ): string {
	if ( '' === $html || false === stripos( $html, '<html' ) ) {
		return $html;
	}

	$html         = nvx_document_governance_remove_retired_scripts( $html );
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

	return is_string( $normalized ) ? $normalized : $html;
}
