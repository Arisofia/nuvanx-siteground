<?php

if (function_exists('add_filter')) {
    add_filter('dbdbsmsn_networks', 'dbdbsmsn_add_custom_image_icon');
    add_filter('dbdbsmsn_add_social_media_follow_fields', 'dbdbsmsn_add_image_icon_fields');
    add_filter('et_module_shortcode_output', 'dbdbsmsn_set_image_icon_frontend_styles', 10, 3);
    add_filter('dbdbsmsn_network_is_icon_font', 'dbdbsmsn_return_false_if_image_icon', 10, 2);
}

if (!function_exists('dbdbsmsn_return_false_if_image_icon')) {
    function dbdbsmsn_return_false_if_image_icon($isIconFont, $networkId) {
        if ($networkId === 'dbdb-custom-image') return false;
        return $isIconFont;
    }
}

if (!function_exists('dbdbsmsn_set_image_icon_frontend_styles')) {
	function dbdbsmsn_set_image_icon_frontend_styles($html, $render_slug, $module) {
		if ($render_slug === 'et_pb_social_media_follow_network' && isset($module->props['social_network'])) {
            if ($module->props['social_network'] === 'dbdb-custom-image') {
                // [dbdb_image__hover_enabled] => on|hover [dbdb_image__hover] => http://localhost/dev/nightly/20220104/divi-booster-KOb/wordpress/wp-content/uploads/2022/01/200x200-forest.png )
                if (!empty($module->props['dbdb_image'])) {
                    ET_Builder_Element::set_style(
                        $render_slug, 
                        array(
                            'selector'    => '%%order_class%%.et-social-dbdb-custom-image a',
                            'declaration' => "background-image: url('".esc_html($module->props['dbdb_image'])."');background-size: contain;"
                        )
                    );
                    ET_Builder_Element::set_style(
                        $render_slug, 
                        array(
                            'selector'    => '%%order_class%%.et-social-dbdb-custom-image a:before',
                            'declaration' => 'content:"a"; visibility:hidden;' // need content so icon positioned same as font icons
                        )
                    );
                    if (isset($module->props['dbdb_image__hover_enabled']) && $module->props['dbdb_image__hover_enabled'] === 'on|hover') {
                        if (!empty($module->props['dbdb_image__hover'])) {
                            ET_Builder_Element::set_style(
                                $render_slug, 
                                array(
                                    'selector'    => '%%order_class%%.et-social-dbdb-custom-image a:hover',
                                    'declaration' => "background-image: url('".esc_html($module->props['dbdb_image__hover'])."');"
                                )
                            );
                        }
                    }
                }
            } 
		}
		return $html;
	}
}


if (!function_exists('dbdbsmsn_add_custom_image_icon')) {
	function dbdbsmsn_add_custom_image_icon($networks) {
        $networks['dbdb-custom-image'] = array (
            'name' => 'Image Icon',
            'code' => '\\e005',
            'color' => '#58a9de',
            'font-family' => 'ETModules'
        );
		return $networks;
	}
}

function dbdbsmsn_add_image_icon_fields($fields) {
	$fields['dbdb_image'] = array(
        'label'              => esc_html__( 'Image', 'et_builder' ),
        'type'               => 'upload',
        'option_category'    => 'basic_option',
        'upload_button_text' => esc_html__( 'Upload an image', 'et_builder' ),
        'choose_text'        => esc_attr__( 'Choose an Image', 'et_builder' ),
        'update_text'        => esc_attr__( 'Set As Image', 'et_builder' ),
        'description'        => esc_html__( 'Upload an image for your social media network icon.', 'et_builder' ),
        'toggle_slug'        => 'main_content',
        'dynamic_content'    => 'image',
        'mobile_options'     => true,
        'hover'              => 'tabs',
        'show_if' => array(
            'social_network' => 'dbdb-custom-image'
        )
    );
    $fields['dbdb_icon_title'] = array(
        'label'           => esc_html__( 'Icon Title', 'et_builder' ),
        'type'            => 'text',
        'option_category' => 'basic_option',
        'description'     => esc_html__( 'This defines the HTML title of the icon, used in the tooltip.', 'et_builder' ),
        'toggle_slug'     => 'main_content',
        'dynamic_content' => 'text',
        'show_if' => array(
            'social_network' => 'dbdb-custom-image'
        )
    );
    return $fields;
}
