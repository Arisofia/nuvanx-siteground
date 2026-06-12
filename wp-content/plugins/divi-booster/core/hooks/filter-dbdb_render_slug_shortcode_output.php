<?php // Like Divi's {$render_slug}_shortcode_output filter, but also passes the props and module instance

add_filter('et_module_shortcode_output', 'dbdb_module_shortcode_output', 10, 3);

if (!function_exists('dbdb_module_shortcode_output')) {
    function dbdb_module_shortcode_output($content, $render_slug, $module) {
		if (is_array($content)) { 
			return $content; // HTML has been rendered as builder data array, so leave it alone
		}
        $props = isset($module->props)?$module->props:array();
        return apply_filters("dbdb_{$render_slug}_shortcode_output", $content, $props, $render_slug, $module);
    }
}