<?php
/**
 * Journal index — WordPress posts page.
 *
 * @package nuvanx-medical
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="nvx-main nvx-blog-index">
	<?php get_template_part( 'template-parts/content/nvx-blog-archive' ); ?>
</div>
<?php
get_footer();

