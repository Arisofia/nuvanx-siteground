<?php

add_filter('dbmo_et_pb_accordion_whitelisted_fields', 'dbmo_et_pb_accordion_register_closeable_field');
add_filter('dbmo_et_pb_accordion_fields', 'dbmo_et_pb_accordion_add_closeable_field');
add_filter('db_pb_accordion_content', 'db_pb_accordion_add_closeable_code_to_content', 10, 2);
add_filter('dbdb_et_pb_module_shortcode_attributes', 'dbmo_et_pb_accordion_item_inherit_closed_icon_styles', 10, 3);

function dbmo_et_pb_accordion_item_inherit_closed_icon_styles($props, $attrs, $render_slug) {
    if ($render_slug !== 'et_pb_accordion_item' || !is_array($props)) { return $props; }
    foreach(array('icon_color', 'use_icon_font_size', 'icon_font_size') as $prefix) {
        foreach($props as $k=>$v) {
            if (strpos($k, $prefix) === 0) {
                $props["open_{$k}"] = $v;
            }
        }
    }
    return $props;
}

function dbmo_et_pb_accordion_register_closeable_field($fields) {
	$fields[] = 'db_closeable';
	return $fields;
}

function dbmo_et_pb_accordion_add_closeable_field($fields) {
	$new_fields = array(
		'db_closeable' => array(
			'label' => 'Closeable',
			'type' => 'yes_no_button',
			'options' => array(
				'off' => esc_html__( 'No', 'et_builder' ),
				'on'  => esc_html__( 'yes', 'et_builder' ),
			),
			'option_category' => 'basic_option',
			'description' => 'Choose whether individual accordion toggles can be closed. '.divibooster_module_options_credit(),
			'default' => 'off',
			'tab_slug'          => 'advanced',
			'toggle_slug'       => 'toggle_layout',
		)
	);
	return $new_fields + $fields;
}


// Process added options
function db_pb_accordion_add_closeable_code_to_content($content, $args, $module='et_pb_accordion') {

	// Get the class
	$order_class = divibooster_get_order_class_from_content('et_pb_accordion', $content);
	if (!$order_class) { return $content; }
	
	$js = '';
	$css = '';
	
	$globallyCloseable = dbdb_option('106-make-accordions-closable', 'enabled');
	
	// Set toggles as closeable
	if (!$globallyCloseable && !empty($args['db_closeable'])) {
		
		if ($args['db_closeable'] === 'on') {
			$js .= db_pb_accordion_js_closeable($order_class);
			$css .= db_pb_accordion_css_closeable($order_class);
		}
	}
	
	if (!empty($css)) { $content.="<style>$css</style>"; }
	if (!empty($js)) { $content.="<script>$js</script>"; }
	
	return $content;
}

function db_pb_accordion_js_closeable($order_class) {
	return <<<END
jQuery(function($){
  $('.{$order_class} .et_pb_toggle_title').click(function(){
    var toggle = $(this).closest('.et_pb_toggle');
    if (!toggle.hasClass('et_pb_accordion_toggling')) {
      var accordion = toggle.closest('.et_pb_accordion');
      if (toggle.hasClass('et_pb_toggle_open')) {
        accordion.addClass('et_pb_accordion_toggling');
        toggle.find('.et_pb_toggle_content').slideToggle(700, function() { 
          toggle.toggleClass('et_pb_toggle_open et_pb_toggle_close'); 
        });
      }
      setTimeout(function(){ 
        accordion.removeClass('et_pb_accordion_toggling'); 
      }, 750);
    }
  });
});
END;
}

function db_pb_accordion_css_closeable($order_class) {
	return <<<END
.{$order_class} .et_pb_toggle_open .et_pb_toggle_title:before {
	display: block !important;
	content: "\\e04f";
}
END;
}