<?php
/**
 * Canonical template for /soluciones-medicas/.
 *
 * Visible content, layout and assets are owned by GitHub. WordPress retains
 * only the page record required for routing and metadata.
 *
 * @package nuvanx-medical
 */

defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/inc/nvx-github-managed-page-state.php';

$page_id = (int) get_queried_object_id();
if ( $page_id < 1 ) {
    $page = get_page_by_path( 'soluciones-medicas', OBJECT, 'page' );
    $page_id = $page instanceof WP_Post ? (int) $page->ID : 0;
}
nvxSyncGithubManagedPageState( $page_id, 'solutions' );

$solutions_css = 'assets/css/nvx-soluciones-medicas.css';
wp_enqueue_style(
    'nvx-soluciones-medicas',
    get_template_directory_uri() . '/' . $solutions_css,
    array(),
    nvx_asset_version( $solutions_css )
);

ob_start();
get_template_part( 'template-parts/content/nvx-soluciones-medicas-github' );
$solutions_content = ob_get_clean();

set_query_var( 'nvx_shell_content', $solutions_content );
set_query_var( 'nvx_shell_skip_header', true );
set_query_var( 'nvx_shell_no_wrapper', true );
get_template_part( 'template-parts/content/nvx-page-shell' );
