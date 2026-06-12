<?php

if (function_exists('add_filter')) {
    add_filter('dbdbsmsn_networks', 'dbdbsmsn_remove_built_in_networks');
    add_filter('dbdbsmsn_add_social_media_follow_fields', 'dbdbsmsn_sort_network_options');
}

// Get full list of social networks
if (!function_exists('dbdbsmsn_networks')) {
	function dbdbsmsn_networks() {
        $icons = apply_filters('dbdb_font_icon_data', array()); 
		$networks = array();
		foreach($icons as $id=>$network) {
			$networks['dbdb-'.$id] = $network;
		}
		return apply_filters('dbdbsmsn_networks', $networks);
	}
}

if (!function_exists('dbdbsmsn_remove_built_in_networks')) {
	function dbdbsmsn_remove_built_in_networks($networks) {
		unset($networks['dbdb-facebook']);
		unset($networks['dbdb-twitter']);
		unset($networks['dbdb-googleplus']);
		unset($networks['dbdb-pinterest']);
		unset($networks['dbdb-linkedin']);
		unset($networks['dbdb-tumblr']);
		unset($networks['dbdb-instagram']);
		unset($networks['dbdb-skype']);
		unset($networks['dbdb-flickr']);
		unset($networks['dbdb-myspace']);
		unset($networks['dbdb-dribbble']);
		unset($networks['dbdb-youtube']);
		unset($networks['dbdb-vimeo']);
		unset($networks['dbdb-rss']);
		return $networks;
	}
}

if (!function_exists('dbdbsmsn_sort_network_options')) {
	function dbdbsmsn_sort_network_options($fields) {
		if (isset($fields['social_network']['options']) && is_array($fields['social_network']['options'])) {
			uasort($fields['social_network']['options'], 'dbdbsmsm_sort_networks_alphabetically'); 
		}
		return $fields;
	}
}

if (!function_exists('dbdbsmsm_sort_networks_alphabetically')) {
	function dbdbsmsm_sort_networks_alphabetically($a, $b) {
		// Sort non-network items (e.g. "Select a network") first.
		if (!isset($a['value'])) { return -1; } 
		if (!isset($b['value'])) { return 1; }
		// Sort alphabetically
		return strcasecmp($a['value'], $b['value']);
	}
}

include_once(dirname(__FILE__).'/addDiviIcons.php');
include_once(dirname(__FILE__).'/CustomImageIcon.php');

if (function_exists('add_filter')) {
    add_filter('et_pb_all_fields_unprocessed_et_pb_social_media_follow_network', 'dbdbsmsn_add_social_media_follow_fields');
    add_filter('et_module_shortcode_output', 'dbdbsmsn_replace_network_names_in_shortcode_output', 10, 3);
    add_filter('wp_head', 'dbdbsmsn_set_builder_styles');
    add_filter('et_module_shortcode_output', 'dbdbsmsn_set_frontend_styles', 10, 3);
}

if (!function_exists('dbdbsmsn_set_frontend_styles')) {
	function dbdbsmsn_set_frontend_styles($html, $render_slug, $module) {
		if ($render_slug === 'et_pb_social_media_follow_network' && isset($module->props['social_network'])) {
            $isIconFont = apply_filters('dbdbsmsn_network_is_icon_font', true, $module->props['social_network']);
            if ($isIconFont) {
                do_action('dbdb_font_icons_enqueue_fonts');
                $id = $module->props['social_network'];
                $networks = dbdbsmsn_networks();
                $fontFamily = dbdbsmsn_font_family($id);
                if (isset($networks[$id]['code'])) {
                    ET_Builder_Element::set_style($render_slug, array(
                        'selector'    => '%%order_class%% a.icon:before',
                        'declaration' => 'content: "'.$networks[$id]['code'].'";font-family: "'.esc_attr($fontFamily).'" !important;'
                        )
                    );
                }
            }
		}
		return $html;
	}
}

if (!function_exists('dbdbsmsn_replace_network_names_in_shortcode_output')) {
	function dbdbsmsn_replace_network_names_in_shortcode_output($html, $render_slug, $module) {
        if ($render_slug !== 'et_pb_social_media_follow_network') return $html;
		if (!empty($_GET['et_fb'])) { // Don't process in visual builder
			return $html; 
		}
		if (is_array($html)) { // HTML has been rendered as builder data, so leave it alone
			return $html;
		}
		foreach(dbdbsmsn_networks() as $id=>$network) {
			$name = preg_quote(isset($network['name'])?$network['name']:'');
            if ($name === 'Image Icon') {
                if (isset($module->props['dbdb_icon_title'])) {
                    $name = $module->props['dbdb_icon_title'];
                }
                $slug = preg_quote($id);
                $html = preg_replace('/title="([^"]*)'.$slug.'([^"]*)"/', 'title="'.esc_attr($name).'"', $html); // double quotes
                $html = preg_replace("/title='([^']*)".$slug."([^']*)'/", "title='".esc_attr($name)."'", $html); // single quotes
            } else {
                $slug = preg_quote($id);
                $html = preg_replace('/title="([^"]*)'.$slug.'([^"]*)"/', 'title="\\1'.$name.'\\2"', $html); // double quotes
                $html = preg_replace("/title='([^']*)".$slug."([^']*)'/", "title='\\1".$name."\\2'", $html); // single quotes
            }
		}
		return $html;
	}
}

if (!function_exists('dbdbsmsn_set_builder_styles')) {
	function dbdbsmsn_set_builder_styles() { 
		if (empty($_GET['et_fb'])) { 
			return; 
		}
        do_action('dbdb_font_icons_enqueue_fonts');
        
		?>
		<style>
		<?php foreach(dbdbsmsn_networks() as $id=>$network) { 
            $fontFamily = dbdbsmsn_font_family($id);
        ?>
			.et-social-<?php esc_html_e($id); ?> a.icon:before,
			.et-db #et-boc .et-l .et_pb_social_icon.et-social-<?php esc_html_e($id); ?> a.icon:before /* TB override */
			{
				content: "<?php esc_attr_e($network['code']); ?>";
				font-family: '<?php esc_attr_e($fontFamily); ?>' !important;
			}
		<?php } ?>
		</style>
		<?php
	}
}

if (!function_exists('dbdbsmsn_font_family')) {
    function dbdbsmsn_font_family($id) {
        return apply_filters('dbdb_font_icon_set', 'Socicon', str_replace('dbdb-', '', $id));
    }
}

if (!function_exists('dbdbsmsn_add_social_media_follow_fields')) {
	function dbdbsmsn_add_social_media_follow_fields($fields) {
		if (isset($fields['social_network'])) {
			$select = new dbdbsmsn_SocialNetworksField($fields['social_network']);
			foreach(dbdbsmsn_networks() as $id=>$network) {
				if (!$select->has_option($id)) {
					$select->add_option($id, $network['name'], array('color'=>$network['color']));
				}
			}
			$fields['social_network'] = $select->get_field();
		}
		return apply_filters('dbdbsmsn_add_social_media_follow_fields', $fields);
	}
}

if (!class_exists('dbdbsmsn_SocialNetworksField')) {
	class dbdbsmsn_SocialNetworksField {
		
		private $_field;
		
		function __construct($field) {
			$this->_field = $field;
			if (!isset($this->_field['options']) || !is_array($this->_field['options'])) {
				$this->_field['options'] = array();
			}
		}
		
		function has_option($network_id) {
			return isset($this->_field['options'][$network_id]);
		}
		
		function add_option($network_id, $name, $data) {
			$this->_field['options'][$network_id] = array(
				'value' => $name,
				'data' => $data
			);
			if (isset($this->_field['value_overwrite'])) {
				if (isset($data['color'])) {
					$this->_field['value_overwrite'][$network_id] = $data['color'];
				}
			}
		}
		
		function get_field() {
			return $this->_field;
		}
	}
}
