<header id="masthead" class="nvx-site-header">
    <div class="nvx-container nvx-header-container">
        <div class="nvx-site-branding">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/logo.svg" alt="<?php bloginfo( 'name' ); ?>" class="nvx-logo" width="160" height="48" decoding="async" onerror="this.onerror=null; this.src=''; this.alt='NUVANX';">
            </a>
        </div><!-- .site-branding -->

        <nav id="site-navigation" class="nvx-main-navigation">
            <button class="nvx-menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                <span class="nvx-menu-icon"></span>
                <span class="screen-reader-text"><?php esc_html_e( 'Primary Menu', 'nuvanx-medical' ); ?></span>
            </button>
            <?php
            wp_nav_menu( array(
                'theme_location' => 'primary',
                'menu_id'        => 'primary-menu',
                'container_class'=> 'nvx-menu-wrapper',
                'fallback_cb'    => false,
            ) );
            ?>
        </nav><!-- #site-navigation -->
        
        <div class="nvx-header-cta">
            <a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>" class="nvx-btn nvx-btn-primary">Consulta médica</a>
        </div>
    </div>
</header><!-- #masthead -->