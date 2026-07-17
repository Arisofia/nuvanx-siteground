<?php
/**
 * Blog index — posts page template.
 *
 * Markup lives in template-parts/content/nvx-blog-archive.php.
 *
 * @package nuvanx-medical
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="nvx-main" class="nvx-main nvx-page" role="main">
	<?php get_template_part( 'template-parts/content/nvx-blog-archive' ); ?>
</main>
<?php
get_footer();
