<?php
defined('ABSPATH')||exit;
get_header();
while(have_posts()):the_post();
?>
<section class="nvx-hero-wrap" aria-label="<?php esc_attr_e('Hero portada','nuvanx-editorial-v2'); ?>">
  <?php the_content(); ?>
</section>
<?php endwhile;
get_footer();
