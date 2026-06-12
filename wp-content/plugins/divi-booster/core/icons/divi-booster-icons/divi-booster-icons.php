<?php

namespace DiviBooster\DiviBooster\Icons\DiviBoosterIcons;

add_filter('dbdb_font_icon_names', __NAMESPACE__.'\\icon_names');
add_filter('dbdb_font_icon_data', __NAMESPACE__.'\\icon_data');
add_filter('dbdb_font_icon_set', __NAMESPACE__.'\\icon_set', 10, 2);
add_action('dbdb_font_icons_enqueue_fonts', __NAMESPACE__.'\\enqueue_fonts');
add_action('wp_enqueue_scripts', __NAMESPACE__.'\\register_css');

function icon_set($set, $id) {
    $icons = array_keys(icon_data());
    if (in_array($id, $icons)) {
        return 'divi-booster-icons';
    }
    return $set;
}

function icon_names($names) {
    $new_names = wp_list_pluck(icon_data(), 'name');
    return $names + $new_names;
}

function icon_data($icons=array()) {
    return $icons + array(
        'linktree' => array(
            'name' => 'Linktree', 
            'color' => '#39e09b',
            'code' => '\e900'
        ),
        'eventbrite' => array(
            'name' => 'Eventbrite', 
            'color' => '#eb572c',
            'code' => '\e901'
        )
    );
}

function register_css() { 
    wp_register_style('dbdb-icons-divi-booster-icons', plugin_dir_url(__FILE__).'icomoon/style.css', array(), BOOSTER_VERSION);
}

function enqueue_fonts() {  
    wp_enqueue_style('dbdb-icons-divi-booster-icons'); 
}