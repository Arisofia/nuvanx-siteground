<?php
defined('ABSPATH')||exit;
get_header();
while(have_posts()):the_post();
$cats=get_the_category();
$cat=!empty($cats)?$cats[0]:null;
?>
<section class="nvx-single-hero" aria-label="Cabecera del artículo">
  <div class="nvx-container nvx-container--text">
    <?php if($cat): ?><p class="nvx-kicker"><a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>"><?php echo esc_html($cat->name); ?></a></p><?php endif; ?>
    <h1 class="nvx-display-section"><?php the_title(); ?></h1>
    <span class="nvx-ruling" aria-hidden="true"></span>
    <div class="nvx-flex nvx-gap-5">
      <time class="nvx-meta" datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date('d \d\e F \d\e Y')); ?></time>
      <span class="nvx-meta"><?php echo esc_html(nvx_reading_time()); ?> de lectura</span>
    </div>
  </div>
</section>
<?php if(has_post_thumbnail()): ?>
<div class="nvx-container" style="margin-block:var(--nvx-sp-6)">
  <div class="nvx-ratio nvx-ratio--16-9"><?php the_post_thumbnail('full',['alt'=>get_the_title(),'loading'=>'eager']); ?></div>
</div>
<?php endif; ?>
<div class="nvx-single-content nvx-section">
  <div class="nvx-container nvx-container--text">
    <div class="entry-content"><?php the_content(); ?></div>
    <nav class="nvx-single-nav" aria-label="Navegación entre artículos" style="margin-top:var(--nvx-sp-8);padding-top:var(--nvx-sp-6);border-top:var(--nvx-border);display:flex;justify-content:space-between;flex-wrap:wrap;gap:var(--nvx-sp-5)">
      <?php $p=get_previous_post(); $n=get_next_post(); ?>
      <?php if($p): ?><a href="<?php echo esc_url(get_permalink($p)); ?>" class="nvx-link" rel="prev">&larr; Artículo anterior</a><?php endif; ?>
      <?php if($n): ?><a href="<?php echo esc_url(get_permalink($n)); ?>" class="nvx-link" rel="next">Siguiente artículo &rarr;</a><?php endif; ?>
    </nav>
  </div>
</div>
<?php endwhile; get_footer();
