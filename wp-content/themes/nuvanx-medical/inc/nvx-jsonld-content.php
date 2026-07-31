<?php
/**
 * Shared helpers for removing embedded Schema.org JSON-LD from HTML content.
 *
 * Canonical structured data is emitted only via Yoast's @graph
 * (wpseo_schema_graph in nvx-structured-data.php). Duplicate blocks left in
 * post_content (EXILITE, home FAQ dumps, etc.) must be stripped consistently
 * by runtime filters and staging DB cleanup.
 *
 * @package NUVANX_Medical
 */

defined( 'ABSPATH' ) || exit;

/**
 * Regex that matches <script type="application/ld+json">…</script> blocks.
 *
 * Kept in one place so theme filters and staging2 cleanup stay aligned.
 *
 * @return string PCRE pattern with delimiters.
 */
function nvx_jsonld_script_pattern() {
	return '#<script\b[^>]*type\s*=\s*(["\'])application/ld\+json\1[^>]*>([\s\S]*?)</script>#iu';
}

/**
 * Whether a JSON-LD payload looks like Schema.org structured data.
 *
 * Non-schema application/ld+json (future integrations) is left intact.
 *
 * @param string $payload Script body.
 * @return bool
 */
function nvx_jsonld_is_schema_org_payload( $payload ) {
	if ( ! is_string( $payload ) || '' === $payload ) {
		return false;
	}

	return (bool) preg_match( '/schema\.org|@graph\b|"@type"\s*:/i', $payload );
}

/**
 * Strip Schema.org JSON-LD script tags from an HTML string.
 *
 * @param string $html Raw HTML.
 * @return string
 */
function nvx_strip_embedded_jsonld_html( $html ) {
	if ( ! is_string( $html ) || '' === $html || false === stripos( $html, 'ld+json' ) ) {
		return $html;
	}

	$cleaned = preg_replace_callback(
		nvx_jsonld_script_pattern(),
		static function ( $matches ) {
			$body = isset( $matches[2] ) ? $matches[2] : '';
			if ( nvx_jsonld_is_schema_org_payload( $body ) ) {
				return '';
			}
			// Keep non-schema ld+json untouched.
			return $matches[0];
		},
		$html
	);

	return is_string( $cleaned ) ? $cleaned : $html;
}

/**
 * Whether the current request should strip embedded Schema.org JSON-LD from content.
 *
 * Scoped to singular pages and the front page so blog posts, archives, and
 * widgets are not rewritten unless they are page content in the main query.
 *
 * @return bool
 */
function nvx_should_strip_embedded_jsonld() {
	if ( is_admin() || wp_doing_ajax() || ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) ) {
		return false;
	}

	return is_front_page() || is_singular( 'page' );
}

/**
 * Filter callback: strip Schema.org JSON-LD from the_content on pages only.
 *
 * @param string $content Post content HTML.
 * @return string
 */
function nvx_filter_strip_embedded_jsonld( $content ) {
	if ( ! nvx_should_strip_embedded_jsonld() ) {
		return $content;
	}

	return nvx_strip_embedded_jsonld_html( $content );
}
