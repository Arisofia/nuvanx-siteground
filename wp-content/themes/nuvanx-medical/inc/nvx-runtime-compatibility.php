<?php
/**
 * Narrow runtime compatibility adapters for incomplete naming migrations.
 *
 * Remove an adapter only after every caller and its static contract use the
 * canonical implementation name. This module must not contain presentation
 * markup or business logic.
 *
 * @package nuvanx-medical
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nvxCtaPairMarkup' ) ) {
    /**
     * Compatibility adapter for callers migrated to camelCase before the
     * canonical presentation helper was renamed.
     */
    function nvxCtaPairMarkup( string $extra_class = '' ): string {
        if ( ! function_exists( 'nvx_cta_pair_markup' ) ) {
            return '';
        }

        return nvx_cta_pair_markup( $extra_class );
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
