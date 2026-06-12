<?php 

if (!function_exists('et_pb_get_extended_font_icon_symbols')) {
    // Override the built-in Divi function to support filtering
    // Changes: 
    // * Added filter
    // * Changed $full_icons_list_path
    // * Added function exist checks
    function et_pb_get_extended_font_icon_symbols() {
        $cache_key = 'et_pb_get_extended_font_icon_symbols';
        if ( ! et_core_cache_has( $cache_key ) ) {
            $full_icons_list_path = dbdb_divi_font_icons_path();
            if ( file_exists( $full_icons_list_path ) ) {
                // phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Can't use wp_remote_get() for local file
                $icons_data = json_decode( file_get_contents( $full_icons_list_path ), true );
                // phpcs:enable
                if ( JSON_ERROR_NONE === json_last_error() ) {
                    $icons_data = apply_filters('dbdb_get_extended_font_icon_symbols', $icons_data);
                    et_core_cache_set( $cache_key, $icons_data ); 
                    return $icons_data;
                }
            }
            et_wrong( 'Problem with loading the icon data on this path: ' . $full_icons_list_path ); 
        } else {
            return et_core_cache_get( $cache_key );
        }
    }
}

function dbdb_divi_font_icons_path() {
    $plugin_active = (defined('ET_BUILDER_PLUGIN_ACTIVE') && ET_BUILDER_PLUGIN_ACTIVE);
    if ($plugin_active) {
        if (defined('WP_PLUGIN_DIR')) {
            return WP_PLUGIN_DIR.'/divi-builder/includes/builder/feature/icon-manager/full_icons_list.json';
        }
    } else {
        if (function_exists('get_template_directory')) {
            return get_template_directory() . '/includes/builder/feature/icon-manager/full_icons_list.json';
        }
    }
    return false;
}