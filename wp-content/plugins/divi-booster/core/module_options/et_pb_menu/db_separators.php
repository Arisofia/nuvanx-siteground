<?php

add_filter('dbmo_et_pb_menu_whitelisted_fields', 'dbmo_et_pb_menu_register_separators_field');
add_filter('dbmo_et_pb_menu_fields', 'dbmo_et_pb_menu_add_separators_field');
add_filter('db_pb_menu_content', 'dbdbMenuModule_add_separators_code_to_content', 10, 2);

function dbmo_et_pb_menu_register_separators_field($fields) {
	$fields[] = 'db_separators';
	return $fields;
}

function dbmo_et_pb_menu_add_separators_field($fields) {
	if (!is_array($fields)) { return $fields; }
	$new_fields = array(
		'db_separators' => array(
			'label' => 'Vertical Separator Bars',
			'type' => 'yes_no_button',
			'options' => array(
				'off' => esc_html__( 'No', 'et_builder' ),
				'on'  => esc_html__( 'yes', 'et_builder' ),
			),
			'option_category' => 'basic_option',
			'description' => 'Show vertical separator bars between the menu links. '.divibooster_module_options_credit(),
			'default' => 'off',
			'tab_slug'          => 'advanced',
			'toggle_slug'       => 'layout',
		)
	);
	return $new_fields + $fields;
}


// Process added options
function dbdbMenuModule_add_separators_code_to_content($content, $args) {	
	
	// Get the class
	$order_class = divibooster_get_order_class_from_content('et_pb_menu', $content);
	
	if (!$order_class) { return $content; }
	
	if (isset($args['db_separators']) && $args['db_separators'] === 'on') {
        if (!empty($args['db_link_spacing']) && $args['db_link_spacing'] !== '22px') {
            $padding = esc_html(intval($args['db_link_spacing'])/2);
        } 
        else {
            $padding = '11';
        }
        $content .= <<<END
<style>
.{$order_class} .et-menu.nav > li + li > a:before {
    position: absolute;
    left:-{$padding}px;
    transform: translateX(-50%);
    content: '|';
    font-size: smaller;
}
</style>
END;
	}
	
	return $content;
}