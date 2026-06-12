<?php

add_filter('dbmo_et_pb_menu_whitelisted_fields', 'dbmo_et_pb_menu_register_title_and_tagline_field');
add_filter('dbmo_et_pb_menu_fields', 'dbmo_et_pb_menu_add_title_and_tagline_field');
add_filter('db_pb_menu_content', 'dbdbMenuModule_add_title_and_tagline_code_to_content', 10, 2);

function dbmo_et_pb_menu_register_title_and_tagline_field($fields) {
	$fields[] = 'db_title';
	$fields[] = 'db_tagline';
	return $fields;
}

function dbmo_et_pb_menu_add_title_and_tagline_field($fields) {
	if (!is_array($fields)) { return $fields; }
	$new_fields = array(
		'db_title' => array(
			'label' => 'Show Site Title',
			'type' => 'yes_no_button',
			'options' => array(
				'off' => esc_html__( 'No', 'et_builder' ),
				'on'  => esc_html__( 'yes', 'et_builder' ),
			),
			'option_category' => 'basic_option',
			'description' => 'Display the site title. This option is not previewable, but will show up on the front-end. '.divibooster_module_options_credit(),
			'default' => 'off',
			'tab_slug'          => 'general',
			'toggle_slug'       => 'elements',
		),
		'db_title_use_link' => array(
			'label' => 'Link Site Title to Homepage',
			'type' => 'yes_no_button',
			'options' => array(
				'off' => esc_html__( 'No', 'et_builder' ),
				'on'  => esc_html__( 'yes', 'et_builder' ),
			),
			'option_category' => 'basic_option',
			'description' => 'Make the site title into a link to the site homepage. '.divibooster_module_options_credit(),
			'default' => 'off',
			'tab_slug'          => 'general',
			'toggle_slug'       => 'elements',
            'show_if' => array(
				'db_title' => 'on',
			)
		),
		'db_tagline' => array(
			'label' => 'Show Site Tagline',
			'type' => 'yes_no_button',
			'options' => array(
				'off' => esc_html__( 'No', 'et_builder' ),
				'on'  => esc_html__( 'yes', 'et_builder' ),
			),
			'option_category' => 'basic_option',
			'description' => 'Display the site tagline. This option is not previewable, but will show up on the front-end. '.divibooster_module_options_credit(),
			'default' => 'off',
			'tab_slug'          => 'general',
			'toggle_slug'       => 'elements',
		),
		'db_tagline_below_title' => array(
			'label' => 'Place Site Tagline Below Title',
			'type' => 'yes_no_button',
			'options' => array(
				'off' => esc_html__( 'No', 'et_builder' ),
				'on'  => esc_html__( 'yes', 'et_builder' ),
			),
			'option_category' => 'basic_option',
			'description' => 'Place the tagline below the title (if shown). '.divibooster_module_options_credit(),
			'default' => 'off',
			'tab_slug'          => 'general',
			'toggle_slug'       => 'elements',
            'show_if' => array(
				'db_tagline' => 'on',
			)
		)
	);
	return $new_fields + $fields;
}

// Process added options
function dbdbMenuModule_add_title_and_tagline_code_to_content($content, $args) {	

    $args = wp_parse_args(
        $args, 
        array(
            'db_title' => 'off',
            'db_title_use_link' => 'off',
            'db_tagline' => 'off',
            'db_tagline_below_title' => 'off'
        )
    );

    $classes = array(
        'db_title_'.$args['db_title'],
        'db_title_use_link_'.$args['db_title_use_link'],
        'db_tagline_'.$args['db_tagline'],
        'db_tagline_below_title_'.$args['db_tagline_below_title']
    );

    $content = divibooster_add_module_classes_to_content($content, $classes);

    $title_div = '';
    if ($args['db_title'] === 'on') {
        $title = esc_html(get_bloginfo('name'));
        if ($args['db_title_use_link'] === 'on') {
            $title = '<a href="'.esc_attr(esc_url(home_url())).'">'.$title.'</a>';
        }
        $title_div = '<div class="db_title">'.$title.'</div>';
    }
    $tagline = ($args['db_tagline'] === 'on')?'<div class="db_tagline">'.esc_html(get_bloginfo('description')).'</div>':'';   

    if ($args['db_title'] === 'on' || $args['db_tagline'] === 'on') {
        $title_and_tagline = sprintf(
            '<div class="db_title_and_tagline">%s%s</div>',
            $title_div,
            $tagline
        );
        $content = str_replace('<div class="et_pb_menu__wrap', $title_and_tagline.'<div class="et_pb_menu__wrap', $content);
    }
	
	return $content;
}

add_action('wp_head', 'dbdbMenuModule_print_styles');

function dbdbMenuModule_print_styles() { ?>
<style>
.db_title, .db_tagline { 
    margin-right: 30px;
    margin-top: 8px;
    line-height: 1em;
}
.db_title_and_tagline {
    display: flex;
}
.db_tagline_below_title_on .db_title_and_tagline {
    flex-direction: column;
}
</style>
    <?php
}


add_filter('et_pb_menu_advanced_fields', 'dbdbMenuModule_add_title_and_tagline_font_options', 10, 3);

function dbdbMenuModule_add_title_and_tagline_font_options($fields, $slug, $main_css_element) {
    if (!is_array($fields)) return $fields;
    if (!isset($fields['fonts']) || !is_array($fields['fonts'])) {
        $fields['fonts'] = array();
    }
    $fields['fonts']['db_title'] = array(
        'css'   => array(
            'main' => "{$main_css_element} .db_title, {$main_css_element} .db_title a",
            'important' => 'all'
        ),
        'label' => esc_html__('Site Title', 'divi-booster'),
        'font_size' => array(
			'default' => '14px',
        ),
    );
    $fields['fonts']['db_tagline'] = array(
        'css'   => array(
            'main' => "{$main_css_element} .db_tagline"
        ),
        'label' => esc_html__('Site Tagline', 'divi-booster')
    );
    return $fields;
}


add_filter('et_pb_menu_custom_css_fields', 'dbdbMenuModule_add_title_and_tagline_custom_css_fields', 10, 3);

function dbdbMenuModule_add_title_and_tagline_custom_css_fields($fields, $slug, $main_css_element) {
    if (!is_array($fields)) return $fields;
    $fields['db_title'] = array(
        'label'    => esc_html__( 'Site Title', 'divi-booster' ),
        'selector' => '%%order_class%% .db_title'
    );
    $fields['db_tagline'] = array(
        'label'    => esc_html__( 'Site Tagline', 'divi-booster' ),
        'selector' => '%%order_class%% .db_tagline'
    );
    return $fields;
}