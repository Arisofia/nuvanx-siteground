<?php
/**
 * Settings Handler.
 *
 * Handles storage and retrieval of plugin settings via WordPress Options API.
 *
 * @package Availability_Datepicker
 * @since 2.4.0.13
 */

class AVDP_Settings
{
    /**
     * Option name.
     *
     * @var string
     */
    const OPTION_NAME = 'avdp_settings';

    /**
     * Default settings.
     *
     * @var array
     */
    private static $defaults = array(
        'availability_rules' => array(
            'method' => 'fixed',
            'weekly_hours' => array(
                'monday' => array(
                    'enabled' => true,
                    'slots' => array(
                        array('start' => '09:00', 'end' => '17:00')
                    )
                ),
                'tuesday' => array(
                    'enabled' => true,
                    'slots' => array(
                        array('start' => '09:00', 'end' => '17:00')
                    )
                ),
                'wednesday' => array(
                    'enabled' => true,
                    'slots' => array(
                        array('start' => '09:00', 'end' => '17:00')
                    )
                ),
                'thursday' => array(
                    'enabled' => true,
                    'slots' => array(
                        array('start' => '09:00', 'end' => '17:00')
                    )
                ),
                'friday' => array(
                    'enabled' => true,
                    'slots' => array(
                        array('start' => '09:00', 'end' => '17:00')
                    )
                ),
                'saturday' => array(
                    'enabled' => false,
                    'slots' => array()
                ),
                'sunday' => array(
                    'enabled' => false,
                    'slots' => array()
                ),
            ),
            'time_settings' => array(
                'slot_interval' => 30,
                'minimum_notice' => 0,
                'buffer_before' => 0,
                'buffer_after' => 0,
                'min_days' => 1,
                'max_days' => 14,
                'min_duration' => 1,
                'max_duration' => 24,
            ),
            'date_overrides' => array(
                'blocked_dates' => array(),
                'allowed_dates' => array(),
            ),
            'booking_window' => array(
                'from_type' => 'dynamic',
                'from_value' => '0',
                'to_type' => 'dynamic',
                'to_value' => '30',
                'days_from' => 0,
                'days_future' => 30,
            ),
        ),
        'css_selectors' => array(
            'single_field' => '.avdp-datepicker',
            'start_date' => '.avdp-start-date',
            'start_time' => '.avdp-start-time',
            'end_date' => '.avdp-end-date',
            'end_time' => '.avdp-end-time',
            'start_datetime' => '.avdp-start-datetime',
            'end_datetime' => '.avdp-end-datetime',
        ),
        'general_settings' => array(
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'timezone' => '',
            'datepicker_library' => 'xdsoft',
            'datepicker_language' => 'en',
            'datepicker_theme' => 'light',
            'datepicker_display_method' => 'dropdown',
        ),
    );

    /**
     * Get default values for a section (for client-side restore).
     *
     * @param string $section_key Section key.
     * @return array Default values for the section.
     */
    public static function get_default_section($section_key)
    {
        if (!isset(self::$defaults[$section_key])) {
            return array();
        }
        $defaults = self::$defaults[$section_key];
        if ($section_key === 'general_settings' && empty($defaults['timezone'])) {
            $tz = new DateTimeZone(wp_timezone_string());
            $now = new DateTime('now', new DateTimeZone('UTC'));
            $offset = $tz->getOffset($now);
            $hours = $offset >= 0 ? intval($offset / 3600) : -intval(abs($offset) / 3600);
            $minutes = abs(intval($offset % 3600 / 60));
            $defaults['timezone'] = ($offset >= 0 ? '+' : '-') . sprintf('%02d:%02d', abs($hours), $minutes);
            if ($offset === 0) {
                $defaults['timezone'] = 'UTC';
            }
        }
        return $defaults;
    }

    /**
     * Restore a section to its default values.
     *
     * @param string $section_key Section key (e.g., 'availability_rules', 'css_selectors', 'general_settings').
     * @return bool True on success.
     */
    public static function restore_section_defaults($section_key)
    {
        if (!isset(self::$defaults[$section_key])) {
            return false;
        }

        $saved_settings = get_option(self::OPTION_NAME, array());
        if (!is_array($saved_settings)) {
            $saved_settings = array();
        }

        $saved_settings[$section_key] = self::$defaults[$section_key];
        $result = update_option(self::OPTION_NAME, $saved_settings);

        if ($result) {
            self::clear_calendar_cache();
        }

        return $result;
    }

    /**
     * Get all settings.
     *
     * @return array
     */
    public static function get_all()
    {
        $settings = get_option(self::OPTION_NAME, self::$defaults);

        if (!is_array($settings)) {
            $settings = self::$defaults;
        }

        return self::array_merge_recursive_distinct(self::$defaults, $settings);
    }

    /**
     * Recursively merge two arrays, with settings overriding defaults.
     *
     * Unlike wp_parse_args() which does shallow merge, this does deep merge.
     * Empty strings are preserved (not replaced with defaults).
     * Handles corrupted data (null, non-array values) gracefully by using defaults.
     *
     * @param array $defaults Default values
     * @param array $settings Settings to merge (overrides defaults)
     * @return array Merged array
     */
    private static function array_merge_recursive_distinct(array $defaults, array $settings)
    {
        $merged = $defaults;

        foreach ($settings as $key => $value) {
            if (null === $value || (!is_array($value) && isset($merged[$key]) && is_array($merged[$key]))) {
                continue;
            }

            if (isset($merged[$key]) && is_array($merged[$key]) && is_array($value)) {
                if (count($value) === 0) {
                    $merged[$key] = $value;
                } else {
                    $merged[$key] = self::array_merge_recursive_distinct($merged[$key], $value);
                }
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Get a specific section of settings.
     *
     * @param string $section_key Section key (e.g., 'availability_rules').
     * @return array
     */
    public static function get_section($section_key)
    {
        $settings = self::get_all();
        return isset($settings[$section_key]) ? $settings[$section_key] : array();
    }

    /**
     * Update a specific section of settings.
     *
     * @param string $section_key Section key.
     * @param array  $data        New data for the section. Can be partial - will be merged with existing section data.
     * @return bool
     */
    public static function update_section($section_key, $data)
    {
        $saved_settings = get_option(self::OPTION_NAME, array());

        $existing_section = isset($saved_settings[$section_key]) && is_array($saved_settings[$section_key])
            ? $saved_settings[$section_key]
            : array();

        if (is_array($data) && is_array($existing_section)) {
            $updated_section = self::array_merge_recursive_distinct($existing_section, $data);
        } else {
            $updated_section = $data;
        }

        $saved_settings[$section_key] = $updated_section;

        // Clear cache before saving so a failed save doesn't leave stale cache
        self::clear_calendar_cache();

        $existing_for_compare = get_option(self::OPTION_NAME, array());

        $result = update_option(self::OPTION_NAME, $saved_settings);

        // update_option() returns false both on real failure AND when the value is
        // unchanged. Treat no-change as success — it means the data is already correct.
        if ($result === false && $saved_settings === $existing_for_compare) {
            return true;
        }

        return $result;
    }

    /**
     * Update all settings.
     *
     * @param array $settings New settings array.
     * @return bool
     */
    public static function update_all($settings)
    {
        $result = update_option(self::OPTION_NAME, $settings);

        if ($result) {
            self::clear_calendar_cache();
        }

        return $result;
    }

    /**
     * Delete all settings.
     *
     * @return bool
     */
    public static function delete_all()
    {
        $result = delete_option(self::OPTION_NAME);

        if ($result) {
            self::clear_calendar_cache();
        }

        return $result;
    }

    /**
     * Clear calendar configuration cache.
     *
     * Deletes all calendar config transients to ensure fresh config is generated
     * after settings changes. Uses direct database queries since WordPress doesn't
     * provide a function to delete transients by pattern.
     *
     * @since 2.4.0.13
     */
    public static function clear_calendar_cache()
    {
        global $wpdb;

        if (!isset($wpdb) || !is_object($wpdb)) {
            return;
        }

        $transient_pattern = $wpdb->esc_like('_transient_avdp_calendar_config_') . '%';
        $timeout_pattern = $wpdb->esc_like('_transient_timeout_avdp_calendar_config_') . '%';

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $transient_pattern
            )
        );

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $timeout_pattern
            )
        );

        if (function_exists('wp_cache_flush_group')) {
            wp_cache_delete('avdp_calendar_config', 'transients');
        }
    }
}
