<?php

add_filter('dbmo_et_pb_menu_whitelisted_fields', 'dbmo_et_pb_menu_register_link_spacing_field');
add_filter('dbmo_et_pb_menu_fields', 'dbmo_et_pb_menu_add_link_spacing_field');
add_filter('db_pb_menu_content', 'dbdbMenuModule_add_link_spacing_code_to_content', 10, 2);

function dbmo_et_pb_menu_register_link_spacing_field($fields) {
	$fields[] = 'db_link_spacing';
	return $fields;
}

function dbmo_et_pb_menu_add_link_spacing_field($fields) {
	if (!is_array($fields)) { return $fields; }
	$new_fields = array(
		'db_link_spacing' => array(
			'label' => 'Link Spacing',
			'type' => 'range',
			'option_category'  => 'layout',
			'description' => 'Set the space between the menu links. '.divibooster_module_options_credit(),
            'range_settings'   => array(
				'min'  => 0,
				'max'  => 200,
				'step' => 1,
			),
            'default'          => '22px',
			'validate_unit'    => true,
			'fixed_unit'       => 'px',
			'fixed_range'      => true,
            'default_on_front' => '',
			'tab_slug'          => 'advanced',
			'toggle_slug'       => 'layout',
		)
	);
	return $new_fields + $fields;
}


function dbdbMenuModule_add_link_spacing_code_to_content($content, $args) {	
	$order_class = divibooster_get_order_class_from_content('et_pb_menu', $content);
	if (!$order_class) { return $content; }
	if (!empty($args['db_link_spacing']) && $args['db_link_spacing'] !== '22px') {
        $padding = esc_html(intval($args['db_link_spacing'])/2);
        $content .= <<<END
<style>
.{$order_class} .et-menu.nav > li  {
	padding-left: {$padding}px !important;
    padding-right: {$padding}px !important;
}
</style>
END;
	}	
	return $content;
}