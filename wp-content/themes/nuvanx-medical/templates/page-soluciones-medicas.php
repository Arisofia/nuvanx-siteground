<?php
/**
 * Template Name: Soluciones médicas
 * Template Post Type: page
 *
 * Dedicated route template so /soluciones-medicas/ does not depend on the_content
 * filter timing (which has produced empty 200 responses on staging2).
 *
 * @package nuvanx-medical
 */

defined( 'ABSPATH' ) || exit;

// header.php already opens <main id="nvx-main">; do not nest a second main.
get_header();
?>
	<article <?php post_class( 'nvx-page nvx-page--solutions' ); ?>>
		<?php
		$template = get_template_directory() . '/template-parts/content/nvx-soluciones-medicas-github.php';
		if ( is_readable( $template ) ) {
			include $template;
		} else {
			echo '<div class="nvx-shell"><h1 class="nvx-heading">' . esc_html__( 'Soluciones médicas', 'nuvanx-medical' ) . '</h1></div>';
		}
		?>
	</article>
<?php
get_footer();
