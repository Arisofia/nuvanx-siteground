<?php
/**
 * Equipo Médico route-specific editorial layout contract.
 *
 * @package nuvanx-medical
 */

defined( 'ABSPATH' ) || exit;

/** Whether the current public request is the Equipo Médico page. */
function nvxEquipoLayoutContractApplies(): bool {
    if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        return false;
    }

    return is_page( 'equipo-medico' );
}

/** Load the Equipo layout correction after the terminal regression layer. */
function nvxEquipoLayoutContractAssets(): void {
    if ( ! nvxEquipoLayoutContractApplies() ) {
        return;
    }

    $relative = 'assets/css/nvx-equipo-layout-contract.css';
    wp_enqueue_style(
        'nvx-equipo-layout-contract',
        get_template_directory_uri() . '/' . $relative,
        array( 'nvx-ui-regressions' ),
        function_exists( 'nvx_asset_version' ) ? nvx_asset_version( $relative ) : null
    );
}
add_action( 'wp_enqueue_scripts', 'nvxEquipoLayoutContractAssets', 110 );
