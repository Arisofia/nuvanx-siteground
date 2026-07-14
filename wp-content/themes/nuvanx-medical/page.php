<?php
defined('ABSPATH')||exit;
get_header();
?>
<article id="page-<?php the_ID(); ?>" <?php post_class('nvx-page-article'); ?>>
  <?php while(have_posts()):the_post();the_content();endwhile; ?>
</article>
<?php get_footer();
