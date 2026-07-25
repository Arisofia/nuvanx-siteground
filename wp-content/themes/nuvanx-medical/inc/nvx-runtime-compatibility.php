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
