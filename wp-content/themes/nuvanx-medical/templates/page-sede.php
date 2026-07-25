<?php
/**
 * Template Name: Sede Local
 *
 * @package nuvanx-medical
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once get_template_directory() . '/inc/nvx-github-managed-page-state.php';

$page_id = (int) get_queried_object_id();
$slug    = (string) get_post_field( 'post_name', $page_id );

if ( 'clinicas-de-medicina-estetica-nuvanx' === $slug ) {
    nvxSyncGithubManagedPageState( $page_id, 'clinics-hub' );

    ob_start();
    get_template_part( 'template-parts/content/nvx-clinics-hub-github' );
    $nvx_tactical_content = ob_get_clean();

    set_query_var( 'nvx_shell_content', $nvx_tactical_content );
    set_query_var( 'nvx_shell_skip_header', true );
    set_query_var( 'nvx_shell_no_wrapper', true );
    get_template_part( 'template-parts/content/nvx-page-shell' );
    return;
}

/**
 * Branch pages retain their dynamic WordPress content and structured-data hooks.
 */
ob_start();
?>
<!-- INICIO: Lógica táctica, Loops y extracción de datos -->
<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class( 'nvx-dynamic-content' ); ?>>
        <?php the_content(); ?>
    </article>
<?php endwhile; endif; ?>
<!-- FIN: Lógica táctica -->
<?php
$nvx_tactical_content = ob_get_clean();
set_query_var( 'nvx_shell_content', $nvx_tactical_content );
get_template_part( 'template-parts/content/nvx-page-shell' );
