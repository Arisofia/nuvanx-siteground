<?php
defined( 'ABSPATH' ) || exit;

function nvx_preview_mode_notice(): void {
    if ( current_user_can( 'edit_posts' ) && isset( $_GET['preview'] ) ) {
        echo '<div class="notice notice-info"><p>Preview mode enabled.</p></div>';
    }
}

add_action( 'admin_notices', 'nvx_preview_mode_notice' );
