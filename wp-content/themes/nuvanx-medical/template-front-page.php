<?php
/**
 * Template Name: Front Page (Canonical)
 *
 * Home page template for NUVANX.
 * Renders the WordPress page content via the_content() without any
 * editorial template-parts bypass.
 *
 * Reconciliation note (2026-07-14 → 2026-07-20):
 *   - Removed legacy template-parts/editorial/home.php include
 *   - Removed hardcoded copy that duplicated WP page ID 9
 *   - H1 and aria-label now come from the page title / block content
 *   - FAQ sourced exclusively from nvx-faq-catalog.php
 *   - video hero params: autoplay muted playsinline preload="metadata"
 *
 * @package nuvanx-medical
 * @version 2.1.0
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="nvx-home" class="nvx-home" role="main" aria-label="<?php echo esc_attr( wp_strip_all_tags( get_the_title() ) ); ?>">

	<?php
	if ( have_posts() ) :
		while ( have_posts() ) :
			the_post();
			the_content();
		endwhile;
	endif;
	?>

</main><!-- #nvx-home -->

<?php get_footer(); ?>
