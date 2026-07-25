<?php
/**
 * Template Name: Landing Valoración
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

ob_start();
get_template_part( 'template-parts/content/nvx-valoracion-github' );
$nvx_valoracion_content = ob_get_clean();

set_query_var( 'nvx_shell_content', $nvx_valoracion_content );
set_query_var( 'nvx_shell_skip_header', true );
set_query_var( 'nvx_shell_no_wrapper', true );
get_template_part( 'template-parts/content/nvx-page-shell' );
