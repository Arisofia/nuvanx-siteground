<?php
/**
 * Fallback theme index (WordPress requires index.php).
 * Prefer front-page.php / home.php / page.php / single.php when applicable.
 *
 * @package NUVANX_Medical
 */

defined( 'ABSPATH' ) || exit;

get_header();
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
get_footer();

