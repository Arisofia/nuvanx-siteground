<?php
/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @package Availability_Datepicker
 * @since 1.0.0
 */

class AVDP_i18n
{

    /**
     * Load the plugin text domain for translation.
     *
     * @since 1.0.0
     */
    public function load_plugin_textdomain()
    {
        load_plugin_textdomain(
            'availability-datepicker',
            false,
            dirname(AVDP_PLUGIN_BASENAME) . '/languages/'
        );
    }
}
