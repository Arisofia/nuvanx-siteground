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
function nvxP0ExionPaths(): array {
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
function nvxP0IsExionPage(): bool {
    if ( is_admin() ) {
        return false;
    }

    if ( is_page( 2906 ) ) {
        return true;
    }

    $page_id = (int) get_queried_object_id();
    if ( function_exists( 'nvxSchemaCurrentPath' ) ) {
        $path = nvxSchemaCurrentPath( $page_id );
    } else {
        $request = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
        $path    = '/' . trim( (string) strtok( $request, '?' ), '/' ) . '/';
    }

    return in_array( $path, nvxP0ExionPaths(), true );
}

/**
 * Public price pattern for EXION visible text.
 */
function nvxP0ExionPricePattern(): string {
    return '/(?<![\p{L}\p{N}])(?:\d{1,3}(?:[.\x{00A0}\x{202F}\s]\d{3})+|\d{1,5})(?:[,.]\d{1,2})?\s*(?:€|EUR)(?![\p{L}\p{N}])/iu';
}

/**
 * Replace explicit EXION prices in a text node.
 */
function nvxP0ReplaceExionPricesInText( string $text ): string {
    $replacement = __( 'Presupuesto tras valoración médica', 'nuvanx-medical' );

    return preg_replace( nvxP0ExionPricePattern(), $replacement, $text ) ?? $text;
}

/**
 * Sanitizes EXION HTML content using DOM parsing.
 */
function _nvxP0SanitizeExionDom( string $content ): string {
    $previous = libxml_use_internal_errors( true );
    $document = new DOMDocument( '1.0', 'UTF-8' );
    $wrapped  = '<!DOCTYPE html><html><body><div id="nvx-p0-exion-root">' . $content . '</div></body></html>';
    if ( ! $document->loadHTML( '<?xml encoding="utf-8" ?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD ) ) {
        libxml_clear_errors();
        libxml_use_internal_errors( $previous );
        return $content;
    }

    $xpath = new DOMXPath( $document );
    $root  = $document->getElementById( 'nvx-p0-exion-root' );
    if ( ! $root ) {
        libxml_clear_errors();
        libxml_use_internal_errors( $previous );
        return $content;
    }

    $details = $xpath->query( './/details[contains(translate(string(.), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "morpheus")]', $root );
    if ( $details ) {
        foreach ( $details as $detail ) {
            $detail->parentNode?->removeChild( $detail );
        }
    }

    $text_nodes = $xpath->query( './/text()[not(ancestor::script or ancestor::style or ancestor::code or ancestor::pre)]', $root );
    if ( $text_nodes ) {
        foreach ( $text_nodes as $text_node ) {
            $text_node->nodeValue = nvxP0ReplaceExionPricesInText( (string) $text_node->nodeValue );
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
 * Sanitize one EXION HTML fragment using DOM text nodes.
 */
function nvxP0SanitizeExionContent( string $content ): string {
    if ( '' === trim( $content ) ) {
        return $content;
    }

    if ( class_exists( 'DOMDocument' ) && class_exists( 'DOMXPath' ) ) {
        return _nvxP0SanitizeExionDom( $content );
    }

    // Fallback for environments without DOM extension
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
    $content = preg_replace( nvxP0ExionPricePattern(), __( 'Presupuesto tras valoración médica', 'nuvanx-medical' ), $content ) ?? $content;
    return strtr( $content, $protected );
}

/**
 * Canonical replacement for the legacy `nvx_apply_production_business_rules`.
 */
function nvxApplyP0BusinessRules( $content ) {
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

    if ( nvxP0IsExionPage() ) {
        $content = nvxP0SanitizeExionContent( $content );
    }

    return $content;
}

remove_filter( 'the_content', 'nvx_apply_production_business_rules', 99 );
add_filter( 'the_content', 'nvxApplyP0BusinessRules', 99 );
