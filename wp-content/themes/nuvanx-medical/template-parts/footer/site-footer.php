<footer id="colophon" class="nvx-site-footer">
    <div class="nvx-container nvx-footer-container">
        <div class="nvx-footer-brand">
            <h3 class="nvx-footer-title">NUVANX</h3>
            <p>Medicina estética láser en Madrid con criterio médico.</p>
        </div>
        
        <div class="nvx-footer-clinics">
            <div class="nvx-footer-clinic">
                <h4>Chamberí</h4>
                <p>C/ de Fernández de la Hoz, 4 Bajo Derecha<br>28010 Madrid</p>
                <p>Tel: <a href="tel:+34669319836">669 319 836</a></p>
                <p class="nvx-registro">Registro sanitario: CS20144</p>
            </div>
            <div class="nvx-footer-clinic">
                <h4>Goya / Salamanca</h4>
                <p>C/ de Fernán González, 26<br>28009 Madrid</p>
                <p>Tel: <a href="tel:+34647505107">647 505 107</a></p>
                <p class="nvx-registro">Registro sanitario: CS20073</p>
            </div>
        </div>
        
        <?php
        $hide_footer_cta = false;
        if ( is_singular() ) {
            $post_content = get_post_field( 'post_content', get_the_ID() );
            if ( strpos( $post_content, 'nvx-brand-section--cta' ) !== false || strpos( $post_content, 'nvx-contact-cta' ) !== false ) {
                $hide_footer_cta = true;
            }
        }
        if ( ! $hide_footer_cta ) :
        ?>
        <div class="nvx-footer-cta">
            <a href="<?php echo esc_url( home_url( '/madrid/valoracion/' ) ); ?>" class="nvx-btn nvx-btn-secondary nvx-footer-btn">Solicitar consulta médica personalizada</a>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="nvx-footer-bottom">
        <div class="nvx-container">
            <div class="nvx-footer-legal">
                <?php
                wp_nav_menu( array(
                    'theme_location' => 'legal',
                    'fallback_cb'    => false,
                    'depth'          => 1,
                ) );
                ?>
            </div>
            <div class="nvx-footer-copy">
                &copy; <?php echo date('Y'); ?> NUVANX. Todos los derechos reservados.
            </div>
        </div>
    </div>
</footer><!-- #colophon -->