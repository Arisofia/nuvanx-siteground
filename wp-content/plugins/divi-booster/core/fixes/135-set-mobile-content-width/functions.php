<?php 
if (!defined('ABSPATH')) { exit(); } // No direct access

function db135_user_css($plugin) { 
	list($name, $option) = $plugin->get_setting_bases(__FILE__); ?>

@media only screen and (max-width: 980px) {
	#top-header > .container,
	#main-header > .container,
	#et_search_outer > .container,
	body.single #main-content > .container,
	body.page:not(.et-tb-has-template) div.et_pb_row.dbdb_default_mobile_width,
	body.page.et-tb-has-template .et-l--post > .et_builder_inner_content > .et_pb_section > .et_pb_row.dbdb_default_mobile_width,
	body.single-project div.et_pb_row.dbdb_default_mobile_width,
    .et_section_specialty.dbdb_default_mobile_width > .et_pb_row {
       width: <?php echo intval(@$option['mobilewidth']); ?>% !important; 
   }
}

<?php 
}
add_action('wp_head.css', 'db135_user_css');


add_filter('dbdb_et_pb_module_shortcode_attributes', 'db135_maybe_add_custom_width_class', 10, 3);
add_filter("et_pb_row_shortcode_output", 'db135_remove_default_row_class_filter');
add_filter("et_pb_section_shortcode_output", 'db135_remove_default_section_class_filter');

function db135_remove_default_row_class_filter($output) {
    remove_filter('et_builder_row_classes', 'db135_add_default_width_class');
    return $output;
}

function db135_remove_default_section_class_filter($output) {
    remove_filter('et_builder_section_classes', 'db135_add_default_width_class');
    return $output;
}

function db135_maybe_add_custom_width_class($props, $attrs, $render_slug) {
    if ($render_slug === 'et_pb_row') {
        $has_custom_mobile_width = (isset($props['width']) && $props['width'] !== '80%') || !empty($props['width_phone']);
        if (!$has_custom_mobile_width) { 
            add_filter('et_builder_row_classes', 'db135_add_default_width_class');
        }
    } 
    elseif ($render_slug === 'et_pb_section' && isset($props['specialty']) && $props['specialty'] === 'on') {
        $has_custom_mobile_width = (isset($props['inner_width']) && $props['inner_width'] !== 'auto') || !empty($props['inner_width_phone']);
        if (!$has_custom_mobile_width) { 
            add_filter('et_builder_section_classes', 'db135_add_default_width_class');
        }
    }
    return $props;
}

function db135_add_default_width_class($classes) {
    if (!is_array($classes)) { return $classes; }
    $classes[] = 'dbdb_default_mobile_width';
    return $classes;
}