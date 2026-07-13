<?php
/**
 * Plugin Name: NUVANX Legal & Footer Fix
 * Description: Redirects old privacy policy and injects JS to fix footer and forms globally.
 * Version: 1.0
 * Author: NUVANX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function nvx_redirect_privacy_policy() {
    $requested_path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
    if ( trailingslashit( $requested_path ) === '/politica-de-privacidad/' ) {
        wp_safe_redirect( home_url( '/politica-privacidad/' ), 301 );
        exit;
    }
}
add_action( 'init', 'nvx_redirect_privacy_policy', 1 );

function nvx_inject_footer_fix() {
    ?>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var footerNav = document.querySelector('.nvx-footer__legal-nav');
        if (footerNav) {
            footerNav.innerHTML = '<a href="/politica-privacidad/">Política de privacidad</a><a href="/aviso-legal/">Aviso legal</a><a href="/politica-de-cookies/">Política de cookies</a>';
        }
        
        var privacyHTML = '<div class="nvx-form-privacy-disclaimer" style="font-size: 12px; margin-top: 15px; color: var(--nvx-text-muted, #666); text-align: left;">Al facilitar tus datos aceptas la <a href="/politica-privacidad/" style="text-decoration: underline; color: inherit;">Política de privacidad</a>.</div>';
        function injectPrivacyToForms() {
            var forms = document.querySelectorAll('form:not(.has-privacy-injected)');
            forms.forEach(function(f) {
                if(f.getAttribute('role') === 'search' || (f.className && f.className.indexOf('search') > -1)) return;
                f.classList.add('has-privacy-injected');
                f.insertAdjacentHTML('beforeend', privacyHTML);
            });
        }
        injectPrivacyToForms();
        var observer = new MutationObserver(function(mutations) {
            var hasNewForm = false;
            for(var i=0; i<mutations.length; i++) {
                if(mutations[i].addedNodes.length) { hasNewForm = true; break; }
            }
            if(hasNewForm) injectPrivacyToForms();
        });
        observer.observe(document.body, { childList: true, subtree: true });
    });
    </script>
    <?php
}
add_action( 'wp_footer', 'nvx_inject_footer_fix', 999 );
