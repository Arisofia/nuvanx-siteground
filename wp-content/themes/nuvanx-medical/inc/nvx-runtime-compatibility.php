<?php
/**
 * Narrow runtime compatibility adapters for incomplete naming migrations.
 *
 * Remove an adapter only after every caller and its static contract use the
 * canonical implementation name. Adapters contain no presentation markup;
 * route-specific presentation contracts are isolated in dedicated modules.
 *
 * @package nuvanx-medical
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nvxCtaPairMarkup' ) ) {
    /**
     * Compatibility adapter for callers migrated to camelCase before the
     * canonical presentation helper was renamed.
     */
    function nvxCtaPairMarkup( string $extraClass = '' ): string {
        if ( ! function_exists( 'nvx_cta_pair_markup' ) ) {
            return '';
        }

        return nvx_cta_pair_markup( $extraClass );
    }
}

if ( ! function_exists( 'nvxHtmlAttrsAddClass' ) ) {
    /**
     * Compatibility adapter for the portrait normalizer migrated to camelCase
     * before the canonical attribute helper was renamed.
     */
    function nvxHtmlAttrsAddClass( string $attrs, string $class_token ): string {
        if ( ! function_exists( 'nvx_html_attrs_add_class' ) ) {
            return $attrs;
        }

        return nvx_html_attrs_add_class( $attrs, $class_token );
    }
}

if ( ! function_exists( 'nvxSchemaCurrentPath' ) ) {
    /**
     * Compatibility adapter for the publication guard migrated to camelCase
     * before the canonical schema path helper was renamed.
     *
     * @param int $page_id Queried page ID when available.
     */
    function nvxSchemaCurrentPath( $page_id = 0 ): string {
        if ( ! function_exists( 'nvx_schema_current_path' ) ) {
            return '';
        }

        return (string) nvx_schema_current_path( $page_id );
    }
}

require_once __DIR__ . '/nvx-equipo-layout-contract.php';
