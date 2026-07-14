<?php
defined('ABSPATH')||exit;
get_header();
$blog_page_id=get_option('page_for_posts');
$blog_thumb=$blog_page_id?get_the_post_thumbnail_url($blog_page_id,'large'):'';
?>
<section class="nvx-journal-hero" aria-label="NUVANX Journal">
  <div class="nvx-journal-hero__content">
    <p class="nvx-journal-hero__super">NUVANX</p>
    <h1 class="nvx-journal-hero__title">Journal</h1>
    <span class="nvx-ruling" aria-hidden="true"></span>
    <p class="nvx-journal-hero__sub">MÉDICO ESTÉTICO</p>
    <p class="nvx-lead" style="margin-top:var(--nvx-sp-4);max-width:340px"><?php esc_html_e('Ciencia, experiencia y enfoque médico para comprender la belleza real, saludable y duradera.','nuvanx-editorial-v2'); ?></p>
  </div>
  <?php if($blog_thumb): ?>
  <div class="nvx-journal-hero__media" aria-hidden="true"><img src="<?php echo esc_url($blog_thumb); ?>" alt="" loading="eager" decoding="async"></div>
  <?php endif; ?>
</section>
<div class="nvx-section nvx-bg-ivory">
  <div class="nvx-container">
    <div class="nvx-journal-layout">
      <section aria-label="Artículos del Journal">
        <?php if(have_posts()):
        $is_first=true;
        while(have_posts()):the_post();
        $cats=get_the_category();
        $cat_name=!empty($cats)?$cats[0]->name:'';
        $thumb=get_the_post_thumbnail_url(null,'medium_large');
        ?>
        <article class="nvx-journal-item" aria-labelledby="art-<?php the_ID(); ?>">
          <?php if($thumb): ?><a href="<?php the_permalink(); ?>" class="nvx-journal-item__image nvx-image-zoom" tabindex="-1" aria-hidden="true"><img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="<?php echo $is_first?'eager':'lazy'; ?>" decoding="async" width="480" height="320"></a><?php endif; ?>
          <div class="nvx-journal-item__body">
            <?php if($cat_name): ?><p class="nvx-journal-item__cat"><?php echo esc_html($cat_name); ?></p><?php endif; ?>
            <h2 class="nvx-journal-item__title" id="art-<?php the_ID(); ?>"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
            <time class="nvx-journal-item__date" datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(strtoupper(get_the_date('j \d\e F \d\e Y'))); ?></time>
            <p class="nvx-journal-item__excerpt"><?php the_excerpt(); ?></p>
            <a href="<?php the_permalink(); ?>" class="nvx-link"><?php esc_html_e('LEER ARTÍCULO','nuvanx-editorial-v2'); ?></a>
          </div>
        </article>
        <?php $is_first=false; endwhile; ?>
        <nav class="nvx-pagination" aria-label="Paginación" style="margin-top:var(--nvx-sp-7)"><?php the_posts_pagination(['mid_size'=>2,'prev_text'=>'&larr; Anterior','next_text'=>'Siguiente &rarr;']); ?></nav>
        <?php else: ?><p class="nvx-lead"><?php esc_html_e('No hay artículos publicados.','nuvanx-editorial-v2'); ?></p><?php endif; ?>
      </section>
      <aside class="nvx-journal-sidebar" aria-label="Sidebar">
        <div class="nvx-sidebar-section">
          <h2 class="nvx-sidebar-section__title">TEMAS</h2>
          <ul style="display:flex;flex-direction:column;gap:var(--nvx-sp-3)">
            <?php foreach(get_categories(['hide_empty'=>true]) as $c): ?>
            <li><a href="<?php echo esc_url(get_category_link($c->term_id)); ?>" class="nvx-meta" style="text-decoration:none;color:var(--nvx-ink);display:flex;align-items:center;gap:var(--nvx-sp-2)"><span style="color:var(--nvx-muted)">/</span><?php echo esc_html($c->name); ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </aside>
    </div>
  </div>
</div>
<?php get_footer();
