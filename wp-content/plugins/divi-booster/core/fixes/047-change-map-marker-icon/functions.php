<?php 
if (!defined('ABSPATH')) { exit(); } // No direct access
/*
add_filter('dbdb_mod_rewrite_rules', 'dbdb_register_map_marker_rewrite_rules', 10, 2);

function dbdb_register_map_marker_rewrite_rules($rules) {
    if (!dbdb_enabled('047-change-map-marker-icon')) return $rules;
    $url = dbdb_option('047-change-map-marker-icon', 'url', '');
    if (empty($url)) return $rules;
    $rules .= "<IfModule mod_rewrite.c>\n";
    $rules .= "RewriteEngine On\n";
    $rules .= "RewriteRule ^wp-content/themes/Divi/images/marker.png$ ".esc_html($url)." [L]\n";
    $rules .= "RewriteRule ^wp-content/themes/Divi/includes/builder/images/marker.png$ ".esc_html($url)." [L]\n";
    $rules .= "</IfModule>";
    return $rules;
}
*/