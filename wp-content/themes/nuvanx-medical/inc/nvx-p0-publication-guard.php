<?php
/**
 * Canonical P0 publication safeguards.
 *
 * Replaces the legacy all-in-one runtime filter with scoped legal, team and
 * EXION rules. Contacto and Valoración are governed by their template/MU-plugin.
 *
 * @package NUVANX_Medical
 */

defined( 'ABSPATH' ) || exit;

/**
 * Canonical EXION routes.
 *
 * @return string[]
 */
function nvx_p0_exion_paths(): array {
	return array(
		'/exion-btl/',
		'/exion-face/',
		'/exion-body/',
		'/exion-fractional/',
	);
}

/**
 * Whether the current public request belongs to the EXION family.
 */
function nvx_p0_is_exion_page(): bool {
	if ( is_admin() ) {
		return false;
	}

	if ( is_page( 2906 ) ) {
		return true;
	}

	$page_id = (int) get_queried_object_id();
	if ( function_exists( 'nvx_schema_current_path' ) ) {
		$path = nvx_schema_current_path( $page_id );
	} else {
		$request = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
		$path    = '/' . trim( (string) strtok( $request, '?' ), '/' ) . '/';
	}

	return in_array( $path, nvx_p0_exion_paths(), true );
}

/**
 * Public price pattern for EXION visible text.
 */
function nvx_p0_exion_price_pattern(): string {
	return '/(?<![\p{L}\p{N}])(?:\d{1,3}(?:[.\x{00A0}\x{202F}\s]\d{3})+|\d{1,5})(?:[,.]\d{1,2})?\s*(?:€|EUR)(?![\p{L}\p{N}])/iu';
}

/**
 * Replace explicit EXION prices in a text node.
 */
function nvx_p0_replace_exion_prices_in_text( string $text ): string {
	$replacement = __( 'Presupuesto tras valoración médica', 'nuvanx-medical' );

	return preg_replace( nvx_p0_exion_price_pattern(), $replacement, $text ) ?? $text;
}

/**
 * Sanitize one EXION HTML fragment using DOM text nodes.
 */
function nvx_p0_sanitize_exion_content( string $content ): string {
	if ( '' === trim( $content ) ) {
		return $content;
	}

	if ( ! class_exists( 'DOMDocument' ) || ! class_exists( 'DOMXPath' ) ) {
		$protected = array();
		$content   = preg_replace_callback(
			'#<(script|style|code|pre)\b[^>]*>[\s\S]*?</\1>#iu',
			static function ( array $matches ) use ( &$protected ): string {
				$key               = '___NVX_PROTECTED_' . count( $protected ) . '___';
				$protected[ $key ] = $matches[0];
				return $key;
			},
			$content
		) ?? $content;
		$content = preg_replace( '/<details\b[^>]*>[\s\S]*?Morpheus[\s\S]*?<\/details>/iu', '', $content ) ?? $content;
		$content = preg_replace( nvx_p0_exion_price_pattern(), __( 'Presupuesto tras valoración médica', 'nuvanx-medical' ), $content ) ?? $content;
		return strtr( $content, $protected );
	}

	$previous = libxml_use_internal_errors( true );
	$document = new DOMDocument( '1.0', 'UTF-8' );
	$wrapped  = '<!DOCTYPE html><html><body><div id="nvx-p0-exion-root">' . $content . '</div></body></html>';
	$loaded   = $document->loadHTML( '<?xml encoding="utf-8" ?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

	if ( ! $loaded ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
		return $content;
	}

	$xpath = new DOMXPath( $document );
	$root  = $document->getElementById( 'nvx-p0-exion-root' );

	if ( ! $root instanceof DOMElement ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
		return $content;
	}

	$uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$lowercase = 'abcdefghijklmnopqrstuvwxyz';
	$details   = $xpath->query(
		'.//details[contains(translate(string(.), "' . $uppercase . '", "' . $lowercase . '"), "morpheus")]',
		$root
	);

	if ( $details instanceof DOMNodeList ) {
		$remove = array();
		foreach ( $details as $detail ) {
			$remove[] = $detail;
		}
		foreach ( $remove as $detail ) {
			if ( $detail->parentNode ) {
				$detail->parentNode->removeChild( $detail );
			}
		}
	}

	$text_nodes = $xpath->query( './/text()', $root );

	if ( $text_nodes instanceof DOMNodeList ) {
		foreach ( $text_nodes as $text_node ) {
			$skip   = false;
			$parent = $text_node->parentNode;
			while ( $parent instanceof DOMElement && $parent !== $root ) {
				if ( in_array( strtolower( $parent->tagName ), array( 'script', 'style', 'code', 'pre' ), true ) ) {
					$skip = true;
					break;
				}
				$parent = $parent->parentNode;
			}
			if ( $skip ) {
				continue;
			}

			$value = (string) $text_node->nodeValue;
			if ( preg_match( nvx_p0_exion_price_pattern(), $value ) ) {
				$text_node->nodeValue = nvx_p0_replace_exion_prices_in_text( $value );
			}
		}
	}

	$rebuilt = '';
	foreach ( $root->childNodes as $child ) {
		$rebuilt .= $document->saveHTML( $child );
	}

	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	return $rebuilt;
}

/**
 * Canonical replacement for the legacy `nvx_apply_production_business_rules`.
 */
function nvx_apply_p0_business_rules( $content ) {
	if ( is_admin() || ! is_string( $content ) || '' === trim( $content ) ) {
		return $content;
	}

	$page_id = (int) get_queried_object_id();

	if ( in_array( $page_id, array( 3, 20 ), true ) ) {
		$content = preg_replace( '/<div\b[^>]*\bnvx-legal-placeholder\b[^>]*>[\s\S]*?<\/div>/iu', '', $content ) ?? $content;
		if (
			false === strpos( $content, 'El artículo 13 del RGPD' )
			&& function_exists( 'nvx_legal_framework_note_markup' )
		) {
			$content .= nvx_legal_framework_note_markup();
		}
	}

	if ( 1575 === $page_id && function_exists( 'nvx_enrich_cristina_marquez_profile' ) ) {
		$content = nvx_enrich_cristina_marquez_profile( $content );
	}

	if ( nvx_p0_is_exion_page() ) {
		$content = nvx_p0_sanitize_exion_content( $content );
	}

	return $content;
}

remove_filter( 'the_content', 'nvx_apply_production_business_rules', 99 );
add_filter( 'the_content', 'nvx_apply_p0_business_rules', 99 );
