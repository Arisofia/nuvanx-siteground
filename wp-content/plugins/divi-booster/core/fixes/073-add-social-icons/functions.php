<?php 
if (!defined('ABSPATH')) { exit(); } // No direct access

add_action('wp_head', 'db073_enqueue_script');

function db073_enqueue_script() {
    DBDBDynamicAsset::socialMediaFollowCss()->load();
}