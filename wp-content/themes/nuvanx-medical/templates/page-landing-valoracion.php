<?php
/**
 * Template Name: Landing Valoración
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_template_directory() . '/inc/nvx-github-managed-page-state.php';

$page_id = (int) get_queried_object_id();
if ( $page_id < 1 ) {
	$page_id = (int) get_the_ID();
}
if ( $page_id < 1 ) {
	$page = get_page_by_path( 'madrid/valoracion', OBJECT, 'page' );
	if ( ! $page instanceof WP_Post ) {
		$page = get_page_by_path( 'valoracion', OBJECT, 'page' );
	}
	$page_id = $page instanceof WP_Post ? (int) $page->ID : 0;
}
nvxSyncGithubManagedPageState( $page_id, 'valoracion' );

ob_start();
get_template_part( 'template-parts/content/nvx-valoracion-github' );
$nvx_valoracion_content = ob_get_clean();

set_query_var( 'nvx_shell_content', $nvx_valoracion_content );
set_query_var( 'nvx_shell_skip_header', true );
set_query_var( 'nvx_shell_no_wrapper', true );
get_template_part( 'template-parts/content/nvx-page-shell' );
