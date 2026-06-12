<?php // functions.php


// if (!function_exists('dbdb_save_mod_rewrite_rules')) {
//     function dbdb_save_mod_rewrite_rules() {
//         if ( is_multisite() ) {
//             return;
//         }
    
//         // Ensure get_home_path() is declared.
//         require_once ABSPATH . 'wp-admin/includes/file.php';
    
//         $home_path     = get_home_path();
//         $htaccess_file = $home_path . '.htaccess';
    
//         /*
//         * If the file doesn't already exist check for write access to the directory
//         * and whether we have some rules. Else check for write access to the file.
//         */
//         if ( ( ! file_exists( $htaccess_file ) && is_writable( $home_path )  ) || is_writable( $htaccess_file ) ) {
//             if (function_exists('got_mod_rewrite') && got_mod_rewrite()) {
//                 $rules = explode("\n", apply_filters('dbdb_mod_rewrite_rules', ''));
//                 return insert_with_markers( $htaccess_file, 'Divi Booster', $rules );
//             }
//         }
    
//         return false;
//     }
// }


// === Builder detection === //

// Try to detect if in context of a divi builder
// - $builder_type is one of:
// -- any
// -- visual (= frontend or backend), 
// -- classic
// -- frontend (= original visual builder)
// -- backend (= "New Divi Experience" builder)

function db_is_divi_builder($builder_type='any') {
	
	// Either visual builder (frontend or backend)
	if (isset($_GET['et_fb']) && $_GET['et_fb'] && in_array($builder_type, array('any', 'visual'))) {
		return true;
	}
	
	// Backend builder
	if (isset($_GET['et_bfb']) && $_GET['et_bfb'] && in_array($builder_type, array('any', 'backend'))) {
		return true;
	}
	
	return false; // Unable to determine builder use
}

// Get Divi Booster setting 
if (!function_exists('dbdb_option')) {
	function dbdb_option($feature, $setting, $default=false) {
		$option = get_option(BOOSTER_SLUG_OLD, $default);
		$val = $default;
		// Retrieve the setting if it exists
		if (isset($option['fixes'][$feature][$setting])) { 
			$val = $option['fixes'][$feature][$setting];
		}
		$val = apply_filters("divibooster_setting_{$feature}_{$setting}", $val);
		return $val;
	}
}

if (!function_exists('dbdb_enabled')) {
	function dbdb_enabled($feature_slug) {
		$enabled = dbdb_option($feature_slug, 'enabled', false);
		return apply_filters('dbdb_enabled', $enabled, $feature_slug);
	}
}

if (!function_exists('dbdb_is_pagebuilder_used')) {
	function dbdb_is_pagebuilder_used($post_id=0) {
		return (function_exists('et_pb_is_pagebuilder_used') && et_pb_is_pagebuilder_used($post_id));
	}
}

if (!function_exists('dbdb_get_current_post_id')) {
	function dbdb_get_current_post_id() {
		global $post;
		if (isset($post) && is_object($post) && property_exists($post, 'ID')) {
			return $post->ID;
		}
		return false;
	}
}