<?php
/**
 * Fired during plugin activation.
 *
 * @package Availability_Datepicker
 * @since 1.0.0
 */

class AVDP_Activator
{

    /**
     * Activate the plugin.
     *
     * @since 1.0.0
     */
    public static function activate()
    {
        if (get_option('avdp_activation_time') === false) {
            add_option('avdp_activation_time', time());
        }

        update_option('avdp_version', AVDP_VERSION);

        self::initialize_default_settings();
    }

    /**
     * Initialize default settings from WordPress system configuration.
     *
     * Detects WordPress date format, time format, and timezone settings
     * and saves them to the plugin's general_settings if settings don't exist yet.
     *
     * @since 2.4.0.13
     */
    private static function initialize_default_settings()
    {
        $existing_settings = get_option('avdp_settings', false);

        $has_legacy_data = get_option('dtpicker', false) !== false
            || get_option('dtpicker_advanced', false) !== false;

        if ($existing_settings === false && !$has_legacy_data) {
            $wp_date_format = get_option('date_format', 'Y-m-d');
            $wp_time_format = get_option('time_format', 'H:i');
            $wp_timezone = wp_timezone_string();

            $date_format = self::map_date_format($wp_date_format);
            $time_format = self::map_time_format($wp_time_format);
            $timezone = self::convert_timezone_to_offset($wp_timezone);

            require_once AVDP_PLUGIN_DIR . 'includes/class-avdp-settings.php';

            $settings = AVDP_Settings::get_all();

            $settings['general_settings']['date_format'] = $date_format;
            $settings['general_settings']['time_format'] = $time_format;
            $settings['general_settings']['timezone'] = $timezone;

            update_option('avdp_settings', $settings);
        }
    }

    /**
     * Map WordPress date format to plugin-supported format.
     *
     * @param string $wp_format WordPress date format.
     * @return string Plugin-compatible date format.
     */
    private static function map_date_format($wp_format)
    {
        if (empty($wp_format) || !is_string($wp_format)) {
            return 'Y-m-d';
        }

        $supported_formats = array(
            'Y-m-d',
            'd-m-Y',
            'm-d-Y',
            'd/m/Y',
            'm/d/Y',
            'Y/m/d',
            'd.m.Y',
            'j F Y',
            'F j, Y'
        );

        if (in_array($wp_format, $supported_formats)) {
            return $wp_format;
        }

        $normalized = trim($wp_format);

        if (preg_match('/^Y[-\/\.]m[-\/\.]d/', $normalized)) {
            return 'Y-m-d';
        }

        if (preg_match('/^[dm][-\/\.][dm][-\/\.]Y/', $normalized)) {
            if (preg_match('/^d[-\/\.]/', $normalized)) {
                return 'd/m/Y';
            } elseif (preg_match('/^m[-\/\.]/', $normalized)) {
                return 'm/d/Y';
            }
        }

        if (preg_match('/[Fj].*Y/', $normalized)) {
            if (preg_match('/^F/', $normalized)) {
                return 'F j, Y';
            } else {
                return 'j F Y';
            }
        }

        return 'Y-m-d';
    }

    /**
     * Map WordPress time format to plugin-supported format.
     *
     * @param string $wp_format WordPress time format.
     * @return string Plugin-compatible time format.
     */
    private static function map_time_format($wp_format)
    {
        if (empty($wp_format) || !is_string($wp_format)) {
            return 'H:i';
        }

        $supported_formats = array(
            'H:i',
            'g:i a',
            'g:i A'
        );

        if (in_array($wp_format, $supported_formats)) {
            return $wp_format;
        }

        $normalized = trim($wp_format);

        // Check 'a' before 'A' to avoid false positives with uppercase AM/PM
        if (strpos($normalized, 'a') !== false && strpos($normalized, 'A') === false) {
            return 'g:i a';
        }

        if (strpos($normalized, 'A') !== false) {
            return 'g:i A';
        }

        if (strpos($normalized, 'H') !== false || strpos($normalized, 'G') !== false) {
            return 'H:i';
        }

        if (strpos($normalized, 'h') !== false && strpos($normalized, 'a') === false && strpos($normalized, 'A') === false) {
            return 'H:i';
        }

        return 'H:i';
    }

    /**
     * Convert WordPress timezone to UTC offset format.
     *
     * @param string $wp_timezone WordPress timezone string.
     * @return string UTC offset string (e.g., '+05:00', 'UTC').
     */
    private static function convert_timezone_to_offset($wp_timezone)
    {
        try {
            $timezone_obj = new DateTimeZone($wp_timezone);
            $now = new DateTime('now', new DateTimeZone('UTC'));
            $offset_seconds = $timezone_obj->getOffset($now);

            $hours = intval($offset_seconds / 3600);
            $minutes = abs(intval($offset_seconds % 3600 / 60));

            if ($offset_seconds == 0) {
                return 'UTC';
            }

            return sprintf('%s%02d:%02d', $offset_seconds >= 0 ? '+' : '-', abs($hours), $minutes);
        } catch (Exception $e) {
            return 'UTC';
        }
    }
}
