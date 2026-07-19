<?php
/**
 * Single Journal article.
 *
 * @package nuvanx-medical
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="nvx-main nvx-blog-single">
	<?php get_template_part( 'template-parts/content/nvx-blog-single' ); ?>
</div>
<?php
get_footer();

