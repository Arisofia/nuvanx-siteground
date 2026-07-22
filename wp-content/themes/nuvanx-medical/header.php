<?php
defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<title><?php wp_title('|', true, 'right'); ?></title>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="nvx-header<?php echo is_front_page() ? ' nvx-header--home' : ' nvx-header--interior'; ?>" role="banner" id="nvx-header">
  <div class="nvx-header__inner">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="nvx-logo" aria-label="NUVANX MEDICINA ESTÉTICA LÁSER — Inicio">
      <?php
      $logo_id = get_theme_mod( 'custom_logo' );
      if ( $logo_id ) :
        echo wp_get_attachment_image( $logo_id, 'full', false, array( 'class' => 'nvx-logo__img', 'alt' => 'NUVANX' ) );
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
<dialog id="nvx-mobile-nav" class="nvx-mobile-nav" aria-modal="true" aria-label="Menú móvil" aria-hidden="true">
  <button class="nvx-mobile-nav__close" id="nvx-mobile-close" aria-label="Cerrar menú">&times;</button>
  <?php
  wp_nav_menu(
    array(
      'theme_location' => 'primary',
      'menu_class'     => 'nvx-mobile-nav__list',
      'container'      => false,
    )
  );
  ?>
  <a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>" class="nvx-btn nvx-btn--primary nvx-open-valoracion-modal" id="nvx-mobile-cta" data-nvx-valoracion-modal="1" aria-haspopup="dialog"><?php esc_html_e( 'Solicitar valoración médica', 'nuvanx-medical' ); ?></a>
  <a href="<?php echo esc_url( nvx_cta_whatsapp_url() ); ?>" class="nvx-btn nvx-btn--secondary" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Contactar por WhatsApp', 'nuvanx-medical' ); ?></a>
</dialog>
<main id="nvx-main" class="nvx-main" tabindex="-1">
