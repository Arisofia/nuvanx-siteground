<?php
/**
 * Fallback theme index (WordPress requires index.php).
 * Prefer front-page.php / home.php / page.php / single.php when applicable.
 *
 * @package NUVANX_Medical
 */

defined( 'ABSPATH' ) || exit;

ob_start();
?>
<div class="nvx-main nvx-page">
	<?php if ( have_posts() ) : ?>
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'nvx-page-body nvx-shell' ); ?>>
				<?php the_title( '<h1 class="nvx-heading">', '</h1>' ); ?>
				<div class="nvx-copy entry-content">
					<?php the_content(); ?>
				</div>
			</article>
		<?php endwhile; ?>
	<?php else : ?>
		<p class="nvx-copy"><?php esc_html_e( 'No se encontró contenido.', 'nuvanx-medical' ); ?></p>
	<?php endif; ?>
</div>
<?php
$content = ob_get_clean();

set_query_var( 'nvx_shell_content', $content );
set_query_var( 'nvx_shell_skip_header', true );
set_query_var( 'nvx_shell_no_wrapper', true );
get_template_part( 'template-parts/content/nvx-page-shell' );

