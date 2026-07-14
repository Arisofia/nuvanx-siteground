<?php
defined('ABSPATH')||exit;
get_header();
?>
<section class="nvx-section nvx-bg-ivory">
  <div class="nvx-container nvx-container--text" style="text-align:center;padding-block:var(--nvx-sp-12)">
    <p class="nvx-kicker">404</p>
    <h1 class="nvx-display-section"><?php esc_html_e('Página no encontrada','nuvanx-editorial-v2'); ?></h1>
    <span class="nvx-ruling" style="margin-inline:auto"></span>
    <p class="nvx-lead"><?php esc_html_e('La página que buscas no existe o ha sido movida.','nuvanx-editorial-v2'); ?></p>
    <a href="<?php echo esc_url(home_url('/')); ?>" class="nvx-btn nvx-btn--primary" style="margin-top:var(--nvx-sp-7)"><?php esc_html_e('VOLVER AL INICIO','nuvanx-editorial-v2'); ?></a>
  </div>
</section>
<?php get_footer();
