<?php
/**
 * Front page — content owns home markup (including video stage).
 * No legacy wrapper; presentation via global CSS + nvx-brand-home.
 *
 * @package NUVANX_Medical
 */
defined( 'ABSPATH' ) || exit;

ob_start();
?>
<main id="nvx-home" class="nvx-home" role="main" aria-label="<?php echo esc_attr( wp_strip_all_tags( get_the_title() ) ); ?>">

	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>

</main><!-- #nvx-home -->
<?php
$content = ob_get_clean();

set_query_var( 'nvx_shell_content', $content );
set_query_var( 'nvx_shell_skip_header', true );
set_query_var( 'nvx_shell_no_wrapper', true );
get_template_part( 'template-parts/content/nvx-page-shell' );
