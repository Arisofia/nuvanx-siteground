<?php

add_filter('dbmo_et_pb_filterable_portfolio_whitelisted_fields', 'dbmo_et_pb_portfolio_tab_order_register_fields'); 

function dbmo_et_pb_portfolio_tab_order_register_fields($fields) {
	$fields[] = 'db_tab_order';
	$fields[] = 'db_tab_order_slugs';
	return $fields;
}

add_filter('dbmo_et_pb_filterable_portfolio_fields', 'dbmo_et_pb_portfolio_add_tab_order_fields');

function dbmo_et_pb_portfolio_add_tab_order_fields($fields) {
	$fields['db_tab_order'] = array(
		'label' => 'Tab Order',
		'type' => 'select',
		'option_category' => 'layout',
		'options' => array(
			'default' => esc_html__('Default', 'et_builder'),
            'random' => esc_html__('Random', 'et_builder'),
            'reverse' => esc_html__('Reverse', 'et_builder'),
            'by_slug' => esc_html__('By Slug', 'et_builder')
		),
		'default' => 'default',
		'description' => 'Adjust the order in which filter tabs are displayed. '.divibooster_module_options_credit(),
		'tab_slug' => 'advanced',
		'toggle_slug' => 'layout'
	);
	$fields['db_tab_order_slugs'] = array(
		'label' => 'Tab Order Slugs',
		'type' => 'text',
		'option_category' => 'layout',
		'default' => '',
		'description' => 'Enter a comma-separated list of category slugs. These will be displayed in order, before any other categories. '.divibooster_module_options_credit(),
		'tab_slug' => 'advanced',
		'toggle_slug' => 'layout',
		'show_if' => array(
			'db_tab_order' => 'by_slug',
		)
	);
	return $fields;
}

add_filter('dbdb_filterable_portfolio_tabs_terms', 'dbdb_sort_filterable_portfolio_tabs', 10, 3);

function dbdb_sort_filterable_portfolio_tabs($terms, $props, $atts) {
    if (!is_array($terms)) return $terms;
    if (!isset($atts['db_tab_order'])) return $terms; 
    if ($atts['db_tab_order'] === 'reverse') {
        $terms = array_reverse($terms);
    }
    elseif ($atts['db_tab_order'] === 'random') {    
        shuffle($terms);
    }
    elseif ($atts['db_tab_order'] === 'by_slug') {
        if (empty($atts['db_tab_order_slugs'])) return $terms;
        $slugs = explode(',', $atts['db_tab_order_slugs']);
        $slugs = array_reverse($slugs);
        foreach($slugs as $slug) {
            $slug = trim($slug);
            foreach($terms as $k=>$term) {
                if (isset($term->slug) && $term->slug === $slug) {
                    $terms = array($k=>$term) + $terms;
                    break;
                }
            }
        }
        $terms = array_values($terms);
    }
    return $terms;
}