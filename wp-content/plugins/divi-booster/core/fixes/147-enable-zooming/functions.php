<?php 
if (!defined('ABSPATH')) { exit(); } // No direct access

add_action('after_setup_theme', 'db147_remove_et_viewport_meta');
add_action('wp_head', 'db147_enable_pinch_zoom');

function db147_remove_et_viewport_meta() {
	remove_action('wp_head', 'et_add_viewport_meta');
}

function db147_enable_pinch_zoom() {
	echo '<meta name="viewport" content="width=device-width, user-scalable=yes, initial-scale=1.0, minimum-scale=0.1, maximum-scale=10.0">';
}