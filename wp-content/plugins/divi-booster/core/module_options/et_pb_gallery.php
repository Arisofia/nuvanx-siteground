<?php

add_filter('dbmo_et_pb_gallery_whitelisted_fields', 'dbmo_et_pb_gallery_register_fields');
add_filter('dbmo_et_pb_gallery_fields', 'dbmo_et_pb_gallery_add_fields');
add_filter('db_pb_gallery_content', array((new DBDBGallery()), 'db_pb_gallery_filter_content'), 10, 2);

function dbmo_et_pb_gallery_register_fields($fields) {
	$fields[] = 'db_images_per_row';
	$fields[] = 'db_images_per_row_tablet';
	$fields[] = 'db_images_per_row_phone';
	$fields[] = 'db_image_max_width';
	$fields[] = 'db_image_max_width_tablet';
	$fields[] = 'db_image_max_width_phone';
	$fields[] = 'db_image_max_height';
	$fields[] = 'db_image_max_height_tablet';
	$fields[] = 'db_image_max_height_phone';
	$fields[] = 'db_image_row_spacing';
	$fields[] = 'db_image_row_spacing_tablet';
	$fields[] = 'db_image_row_spacing_phone';
	$fields[] = 'db_image_center_titles';
	$fields[] = 'db_image_object_fit';
	return $fields;
}

function dbmo_et_pb_gallery_add_fields($fields) {
	$new_fields = array(); 
	foreach($fields as $k=>$v) {
		$new_fields[$k] = $v;
		if ($k === 'posts_number') { // Add after post number option
		
			// Images per row
			$new_fields['db_images_per_row'] = array(
				'label' => 'Images Per Row',
				'type' => 'text',
				'option_category' => 'layout',
				'description' => 'Define the number of images to show per row. '.divibooster_module_options_credit(),
				'default' => '',
				'mobile_options'  => true,
				'tab_slug'        => 'advanced',
				'toggle_slug'        => 'layout',
                'show_if' => array(
                    'fullwidth' => 'off'
                )
				
			);
			$new_fields['db_images_per_row_tablet'] = array(
				'type' => 'skip',
				'tab_slug' => 'advanced',
				'default'=>'',
			);
			$new_fields['db_images_per_row_phone'] = array(
				'type' => 'skip',
				'tab_slug' => 'advanced',
				'default'=>'',
			);
			
			// Max width
			$new_fields['db_image_max_width'] = array(
				'label' => 'Image Area Width',				
				'type' => 'range',
				'range_settings'  => array(
					'min'  => '1',
					'max'  => '100',
					'step' => '1',
				),
				'option_category' => 'layout',
				'description' => 'Define the width of the area of the box containing the image (as % of available width). '.divibooster_module_options_credit(),
				'default' => '83.5',
				'default_unit' => '%',
				'mobile_options'  => true,
				'tab_slug' => 'advanced',
				'toggle_slug' => 'layout',
                'show_if' => array(
                    'fullwidth' => 'off'
                )
				
			);
			$new_fields['db_image_max_width_tablet'] = array(
				'type' => 'skip',
				'tab_slug' => 'advanced',
				'default'=>'',
			);
			$new_fields['db_image_max_width_phone'] = array(
				'type' => 'skip',
				'tab_slug' => 'advanced',
				'default'=>'',
			);
			
			
			// Max height
			$new_fields['db_image_max_height'] = array(
				'label' => 'Image Area Height',				
				'type' => 'range',
				'range_settings'  => array(
					'min'  => '1',
					'max'  => '1000',
					'step' => '1',
				),
				'option_category' => 'layout',
				'description' => 'Define the height of the area of the box containing the image (as % of box width). '.divibooster_module_options_credit(),
				'default' => '',
				'default_unit' => '%',
				'mobile_options'  => true,
				'tab_slug'        => 'advanced',
				'toggle_slug'        => 'layout',
                'show_if' => array(
                    'fullwidth' => 'off'
                )
				
			);
			$new_fields['db_image_max_height_tablet'] = array(
				'type' => 'skip',
				'tab_slug' => 'advanced',
				'default'=>'',
			);
			$new_fields['db_image_max_height_phone'] = array(
				'type' => 'skip',
				'tab_slug' => 'advanced',
				'default'=>'',
			);
			
			
			// Row spacing
			$new_fields['db_image_row_spacing'] = array(
				'label' => 'Image Row Spacing',				
				'type' => 'range',
				'range_settings'  => array(
					'min'  => '0',
					'max'  => '100',
					'step' => '1',
				),
				'option_category' => 'layout',
				'description' => 'Define the space between rows (as % of content width). '.divibooster_module_options_credit(),
				'default' => '5.5',
				'default_unit' => '%',
				'mobile_options'  => true,
				'tab_slug'        => 'advanced',
				'toggle_slug'        => 'layout',
                'show_if' => array(
                    'fullwidth' => 'off'
                )
				
			);
			$new_fields['db_image_row_spacing_tablet'] = array(
				'type' => 'skip',
				'tab_slug' => 'advanced',
				'default'=>'',
			);
			$new_fields['db_image_row_spacing_phone'] = array(
				'type' => 'skip',
				'tab_slug' => 'advanced',
				'default'=>'',
			);
			
			// Center titles
			$new_fields['db_image_center_titles'] = array(
				'label' => 'Title Alignment',
				'type'            => 'select',
				'option_category' => 'layout',
				'options' => array(
					'left'   => esc_html__( 'Left', 'et_builder' ),
					'center' => esc_html__( 'Center', 'et_builder' ),
					'right'  => esc_html__( 'Right', 'et_builder' ),
				),
				'default'           => 'off',
				'description' => 'Adjust the image title text alignment. '.divibooster_module_options_credit(),
				'default' => '',
				'tab_slug' => 'advanced',
				'toggle_slug'        => 'title'
			);
			
			// Object fit
			$new_fields['db_image_object_fit'] = array(
				'label' => 'Image Scaling',
				'type' => 'select',
				'options'         => array(
					'initial' => esc_html__( 'Fill', 'et_builder' ),
					'cover'   => esc_html__( 'Cover', 'et_builder' ),
					'contain' => esc_html__( 'Fit', 'et_builder' ),
					'none' => esc_html__( 'Actual Size', 'et_builder' ),
				),
				'default'         => 'initial',
				'option_category' => 'layout',
				'description' => 'Choose how the image fills its bounding box. '.divibooster_module_options_credit(),
				'default' => '',
				'tab_slug' => 'advanced',
				'toggle_slug'        => 'layout',
                'show_if' => array(
                    'fullwidth' => 'off'
                )
			);
			
			$new_fields['dbdb_version'] = array(
				'label' => 'Divi Booster Version',
				'type' => 'hidden',
				'default' => ''
			);
			
		}
	}
	return $new_fields;
}

// Add "edited with" booster version attribute
add_filter('dbdb_et_pb_module_shortcode_attributes', 'db_pb_gallery_add_booster_version', 10, 3);

function db_pb_gallery_add_booster_version($props, $attrs, $render_slug) {
	if ($render_slug === 'et_pb_gallery' && is_array($props) && isset($_GET['et_fb']) && $_GET['et_fb']==='1') {
		$props['dbdb_version'] = (defined('BOOSTER_VERSION')?BOOSTER_VERSION:'');
	}
	return $props;
}

