<?php

if (function_exists('add_filter')) {
    add_filter('dbdb_font_icon_data', 'dbdbsmsn_divi_icons');
    add_filter('dbdb_font_icon_set', 'dbdbsmsn_divi_font_family', 10, 2);
}

function dbdbsmsn_divi_font_family($set, $id) {
    if (in_array($id, array_keys(dbdbsmsn_divi_icons()))) {
        return 'ETModules';
    }
    return $set;
}

if (!function_exists('dbdbsmsn_divi_icons')) {
	function dbdbsmsn_divi_icons($icons=array()) {
        $icons['phone'] = array (
            'name' => 'Phone',
            'code' => '\\e090',
            'color' => '#58a9de',
            'font-family' => 'ETModules'
        ); 
        $icons['podcast'] = array (
            'name' => 'Podcast',
            'code' => '\\e01b',
            'color' => '#58a9de',
            'font-family' => 'ETModules'
        );
        $icons['website'] = array (
            'name' => 'Website',
            'code' => '\\e0e3',
            'color' => '#58a9de',
            'font-family' => 'ETModules'
        );
		return $icons;
	}
}