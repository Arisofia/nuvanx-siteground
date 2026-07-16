<?php
defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="nvx-sr-only" href="#nvx-main"><?php esc_html_e( 'Saltar al contenido', 'nuvanx-medical' ); ?></a>
<header class="nvx-header<?php echo is_front_page()?' nvx-header--home':' nvx-header--interior'; ?>" role="banner" id="nvx-header">
  <div class="nvx-header__inner">
    <a href="<?php echo esc_url(home_url('/')); ?>" class="nvx-logo" aria-label="NUVANX — Inicio">
      <?php
      $logo_id = get_theme_mod('custom_logo');
      if($logo_id): echo wp_get_attachment_image($logo_id,'full',false,['class'=>'nvx-logo__img','alt'=>'NUVANX']);
      else: ?>
      <span class="nvx-logo__wordmark" aria-hidden="true">NUVANX</span>
      <span class="nvx-logo__tagline" aria-hidden="true">MEDICINA ESTÉTICA LÁSER</span>
      <?php endif; ?>
    </a>
    <nav class="nvx-nav" role="navigation" aria-label="Menú principal">
      <?php wp_nav_menu(['theme_location'=>'primary','menu_class'=>'nvx-nav__list','container'=>false,'items_wrap'=>'<ul class="%2$s" role="menubar">%3$s</ul>','fallback_cb'=>'nvx_primary_menu_fallback','add_li_class'=>'nvx-nav__item']); ?>
      <a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>" class="nvx-btn nvx-btn--primary" id="nvx-header-cta"><?php esc_html_e( 'Reservar valoración gratuita', 'nuvanx-medical' ); ?></a>
    </nav>
    <button class="nvx-hamburger" id="nvx-hamburger-btn" aria-label="Abrir menú" aria-expanded="false" aria-controls="nvx-mobile-nav">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>
<div id="nvx-mobile-nav" class="nvx-mobile-nav" role="dialog" aria-modal="true" aria-hidden="true">
  <button class="nvx-mobile-nav__close" id="nvx-mobile-close" aria-label="Cerrar menú">&times;</button>
  <?php wp_nav_menu(['theme_location'=>'primary','menu_class'=>'nvx-mobile-nav__list','container'=>false,'fallback_cb'=>false]); ?>
  <a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>" class="nvx-btn nvx-btn--primary"><?php esc_html_e( 'Reservar valoración gratuita', 'nuvanx-medical' ); ?></a>
  <a href="https://wa.me/34669319836" class="nvx-btn nvx-btn--secondary" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Contactar por WhatsApp', 'nuvanx-medical' ); ?></a>
</div>
<main id="nvx-main" class="nvx-main" tabindex="-1">
