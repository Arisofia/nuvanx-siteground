<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Availability_Datepicker
 * @since 1.0.0
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete all plugin options.
delete_option('avdp_settings');
delete_option('avdp_activation_time');
delete_option('avdp_version');
delete_option('avdp_installed_version');
delete_option('avdp_migration_completed');

// Delete all avdp_calendar_config_* transients.
global $wpdb;
$transient_keys = $wpdb->get_col(
    "SELECT option_name FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_avdp_calendar_config_%'
        OR option_name LIKE '_transient_timeout_avdp_calendar_config_%'"
);
foreach ($transient_keys as $key) {
    // Strip the _transient_ prefix to get the actual transient name.
    $transient_name = preg_replace('/^_transient_(timeout_)?/', '', $key);
    delete_transient($transient_name);
}
