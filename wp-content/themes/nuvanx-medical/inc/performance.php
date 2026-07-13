<?php
defined( 'ABSPATH' ) || exit;

function nvx_remove_emojis(): void {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
}

add_action( 'init', 'nvx_remove_emojis' );
