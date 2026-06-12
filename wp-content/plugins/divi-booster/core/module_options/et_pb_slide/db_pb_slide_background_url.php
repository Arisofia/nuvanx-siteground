<?php

add_filter('dbdb_et_pb_module_shortcode_attributes', 'dbmo_et_pb_slide_transfer_url_to_link_option', 10, 3);

function dbmo_et_pb_slide_transfer_url_to_link_option($props, $attrs, $render_slug) {
    if ($render_slug === 'et_pb_slide' && is_array($props) && is_array($attrs)) {
        if (empty($props['link_option_url']) && !empty($attrs['db_background_url'])) {
            $props['link_option_url'] = $attrs['db_background_url'];
        }
    }
    return $props;
}