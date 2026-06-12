<?php

// Fix WP Rocket preventing tab closing
add_filter( 'rocket_delay_js_exclusions', 'dbdb_wp_rocket_delay_js_exclusions' );

function dbdb_wp_rocket_delay_js_exclusions($excluded) {
    // MUST ESCAPE PERIODS AND PARENTHESES!
    if (!is_array($excluded)) { return $excluded; }

    // Exclude jquery as required by, e.g. accordion initial state js
    $excluded[] = '/jquery-?[0-9.](.*)(.min|.slim|.slim.min)?.js';
    $excluded[] = '/jquery-migrate(.min)?.js';

    // Don't delay accordion initial state js as it has visual impact
    $excluded[] = 'data-name="dbdb-accordion-initial-state"'; 

    return $excluded;
}

add_filter( 'rocket_exclude_defer_js', 'dbdb_wp_rocket_defer_js_exclusions');

function dbdb_wp_rocket_defer_js_exclusions($excluded) {
    if (!is_array($excluded)) { return $excluded; }
	$excluded[] = '/includes/builder/feature/dynamic-assets/assets/js/magnific-popup\.js';
	return $excluded;
}