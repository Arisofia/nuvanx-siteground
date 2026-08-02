<?php
defined( 'ABSPATH' ) || exit;
// No theme-level document rewrite buffer: SiteGround Optimizer + Complianz own
// the front-end buffer stack. Head contract is emitted via wp_head filters.
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
// Single document title: theme-support title-tag + document-governance normalizer.
// Static analyzers that only read this file do not see the runtime <title>; the
// live document still has exactly one title after governance runs.
?>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="nvx-skip-link screen-reader-text" href="#nvx-main"><?php esc_html_e( 'Saltar al contenido principal', 'nuvanx-medical' ); ?></a>
<header class="nvx-header" role="banner" id="nvx-header">
  <div class="nvx-header__inner">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="nvx-logo" aria-label="NUVANX MEDICINA ESTÉTICA LÁSER — Inicio">
      <?php
      $logo_id = get_theme_mod( 'custom_logo' );
      if ( $logo_id ) :
        echo wp_get_attachment_image( $logo_id, 'medium', false, array( 'class' => 'nvx-logo__img', 'alt' => 'NUVANX', 'width' => '160', 'height' => '154' ) );
      else :
        ?>
      <span class="nvx-logo__wordmark" aria-hidden="true">NUVANX</span>
      <span class="nvx-logo__tagline" aria-hidden="true">MEDICINA ESTÉTICA LÁSER</span>
      <?php endif; ?>
    </a>
    <nav class="nvx-nav" aria-label="Menú principal">
      <?php
      wp_nav_menu(
        array(
          'theme_location' => 'primary',
          'menu_class'     => 'nvx-nav__list',
          'container'      => false,
          'items_wrap'     => '<ul class="%2$s">%3$s</ul>',
          'fallback_cb'    => 'nvx_primary_menu_fallback',
          'add_li_class'   => 'nvx-nav__item',
        )
      );
      ?>
      <a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>" class="nvx-header__cta nvx-btn nvx-btn--primary nvx-open-valoracion-modal" id="nvx-header-cta" data-nvx-valoracion-modal="1" aria-haspopup="dialog"><?php esc_html_e( 'Solicitar valoración médica', 'nuvanx-medical' ); ?></a>
    </nav>
    <button class="nvx-hamburger" id="nvx-hamburger-btn" aria-label="Abrir menú" aria-expanded="false" aria-controls="nvx-mobile-nav">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>
<dialog id="nvx-mobile-nav" class="nvx-mobile-nav" aria-label="Menú móvil" aria-hidden="true" inert>
  <button class="nvx-mobile-nav__close" id="nvx-mobile-close" aria-label="Cerrar menú" type="button">&times;</button>
  <?php wp_nav_menu( array( 'theme_location' => 'primary', 'menu_class' => 'nvx-mobile-nav__list', 'container' => false, 'fallback_cb' => false ) ); ?>
  <a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>" class="nvx-btn nvx-btn--primary nvx-open-valoracion-modal" id="nvx-mobile-cta" data-nvx-valoracion-modal="1" aria-haspopup="dialog"><?php esc_html_e( 'Solicitar valoración médica', 'nuvanx-medical' ); ?></a>
  <a href="https://wa.me/34669319836" class="nvx-btn nvx-btn--secondary" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Contactar por WhatsApp', 'nuvanx-medical' ); ?></a>
</dialog>
<main id="nvx-main" class="nvx-main" tabindex="-1">
