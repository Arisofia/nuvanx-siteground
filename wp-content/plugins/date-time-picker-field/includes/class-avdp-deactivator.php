<?php
/**
 * Fired during plugin deactivation.
 *
 * @package Availability_Datepicker
 * @since 1.0.0
 */

class AVDP_Deactivator
{

    /**
     * Deactivate the plugin.
     *
     * @since 1.0.0
     */
    public static function deactivate()
    {
        flush_rewrite_rules();
    }
}
