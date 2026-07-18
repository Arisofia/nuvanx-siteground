<?php
/**
 * Single Journal article.
 *
 * @package nuvanx-medical
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="nvx-main" class="nvx-main nvx-blog-single" role="main">
	<?php get_template_part( 'template-parts/content/nvx-blog-single' ); ?>
</main>
<?php
get_footer();
